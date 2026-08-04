'use strict';

/**
 * Seekitar WhatsApp Gateway — Baileys sidecar service.
 *
 * Menjalankan sesi WhatsApp Web (Baileys) dan mengekspos HTTP API kecil yang
 * dipakai backend Laravel (`App\Services\WhatsApp\BaileysGateway`) untuk:
 *
 *   GET  /status   -> online/offline + nomor tersambung
 *   GET  /qr       -> QR code (data URL PNG) untuk scan dari panel admin
 *   POST /logout   -> putuskan sesi & hapus kredensial (minta scan ulang)
 *   POST /send     -> kirim pesan teks (dipakai pengiriman OTP)
 *
 * ⚠️ BAILEYS BUKAN API RESMI WHATSAPP. Pemakaian menyalahi Ketentuan Layanan
 *    WhatsApp dan akun/nomor bisa diblokir. Gunakan hanya untuk nomor gateway
 *    khusus OTP, dan pertimbangkan provider resmi (Kirim WA/Twilio) untuk
 *    produksi berskala — lihat Server_Implementation_Guide.md §15.
 *
 * Keamanan:
 *   - Bind ke 127.0.0.1 secara default (jangan expose ke jaringan).
 *   - Set BAILEYS_TOKEN; Laravel mengirimnya sebagai `Authorization: Bearer`.
 *   - Folder `session/` berisi kredensial WhatsApp — JANGAN di-commit.
 */

const fs = require('node:fs');
const path = require('node:path');
const express = require('express');
const pino = require('pino');
const QRCode = require('qrcode');
const Redis = require('ioredis');

const {
  default: makeWASocket,
  useMultiFileAuthState,
  DisconnectReason,
  fetchLatestBaileysVersion,
} = require('@whiskeysockets/baileys');

// ───────────────────────── konfigurasi ─────────────────────────
const PORT = Number(process.env.PORT || 3001);
const HOST = process.env.HOST || '127.0.0.1';
const SESSION_DIR = process.env.SESSION_DIR || path.join(__dirname, 'session');
const LOG_LEVEL = process.env.LOG_LEVEL || 'silent'; // pino: silent|error|warn|info|debug
const BAILEYS_TOKEN = process.env.BAILEYS_TOKEN || '';
const QR_TTL_MS = Number(process.env.QR_TTL_MS || 45000); // QR Baileys ~20s; beri ruang
const RECONNECT_DELAY_MS = Number(process.env.RECONNECT_DELAY_MS || 5000);
// Batas waktu kirim pesan. sendMessage bisa menggantung bila koneksi
// WhatsApp mati diam-diam (state.online belum sempat false) — tanpa ini,
// request /api/send menggantung sampai timeout di sisi pemanggil.
const SEND_TIMEOUT_MS = Number(process.env.SEND_TIMEOUT_MS || 8000);

// ── Redis pub/sub (jalur cepat socket) ─────────────────────────────────────
// Bila REDIS_URL diisi, gateway men-subscribe channel WA_CHANNEL_SEND dan
// mengirim setiap pesan lewat Baileys; hasilnya di-publish ke
// WA_CHANNEL_RESULT. Laravel memakai jalur ini (BAILEYS_REDIS_URL) untuk
// OTP — koneksi socket persisten, tanpa HTTP handshake per pesan.
const REDIS_URL = process.env.REDIS_URL || '';
const WA_CHANNEL_SEND = process.env.WA_CHANNEL_SEND || 'seekitar:wa:send';
const WA_CHANNEL_RESULT = process.env.WA_CHANNEL_RESULT || 'seekitar:wa:result';

const WS_OPEN = 1; // WebSocket.OPEN

// ───────────────────────── state sesi ─────────────────────────
const state = {
  socket: null,
  online: false,
  phone: null,
  lastConnectedAt: null,
  lastQr: null,
  lastQrAt: null,
  loggedOut: false,
  reconnectTimer: null,
  connecting: false,
};

const logger = pino({ level: LOG_LEVEL });

// ───────────────────────── socket Baileys ─────────────────────────
async function startSocket() {
  if (state.connecting) return;
  state.connecting = true;
  state.loggedOut = false;

  try {
    fs.mkdirSync(SESSION_DIR, { recursive: true });

    const { state: authState, saveCreds } = await useMultiFileAuthState(SESSION_DIR);
    const { version } = await fetchLatestBaileysVersion();

    const sock = makeWASocket({
      version,
      auth: authState,
      logger,
      printQRInTerminal: false,
      // Tandai sebagai klien non-Baileys umum; tetap sah untuk gateway.
      browser: ['Seekitar Gateway', 'Chrome', '1.0.0'],
      markOnlineOnConnect: false,
    });

    state.socket = sock;

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        state.lastQr = qr;
        state.lastQrAt = Date.now();
      }

      if (connection === 'open') {
        state.online = true;
        state.lastQr = null;
        state.lastQrAt = null;
        state.lastConnectedAt = new Date().toISOString();
        const jid = sock.user?.id || '';
        state.phone = jid.split(':')[0] || null;
        logger.info({ phone: state.phone }, 'whatsapp connected');
        // eslint-disable-next-line no-console
        console.log(`[Seekitar WhatsApp Gateway] ✅ TERSAMBUNG — nomor: ${state.phone || '?'}`);
      }

      if (connection === 'close') {
        state.online = false;
        const code = lastDisconnect?.error?.output?.statusCode;
        const isLoggedOut = code === DisconnectReason.loggedOut;

        if (isLoggedOut) {
          state.loggedOut = true;
          state.phone = null;
          logger.warn('logged out — scan QR ulang');
          // eslint-disable-next-line no-console
          console.log('[Seekitar WhatsApp Gateway] ⚠️ Sesi di-logout — scan QR ulang.');
        } else {
          logger.warn({ code }, 'connection closed — will reconnect');
          // eslint-disable-next-line no-console
          console.log(`[Seekitar WhatsApp Gateway] ⚠️ Koneksi tertutup (${code ?? '?'}) — coba sambung ulang…`);
          scheduleReconnect();
        }
      }
    });

    sock.ev.on('messages.upsert', () => {
      // Pesan masuk tidak diproses gateway ini (hanya kirim OTP).
    });
  } catch (err) {
    logger.error({ err }, 'startSocket failed');
    scheduleReconnect();
  } finally {
    state.connecting = false;
  }
}

function scheduleReconnect() {
  if (state.reconnectTimer) clearTimeout(state.reconnectTimer);
  state.reconnectTimer = setTimeout(() => {
    startSocket();
  }, RECONNECT_DELAY_MS);
}
// ───────────────────────── helper kirim ─────────────────────────
/**
 * Koneksi dianggap hidup hanya bila flag online DAN WebSocket benar-benar
 * OPEN.
 *
 * ⚠️ Di Baileys 6.7.x, `socket.ws` adalah instance WebSocketClient yang
 * TIDAK punya `readyState` — ia punya getter `isOpen` (boolean). Memakai
 * `ws.readyState === 1` di sini SELALU false (undefined === 1), yang
 * membuat status di-reset ke offline terus-menerus padahal koneksi nyata
 * terbuka. Deteksi di bawah menangani keduanya.
 */
function isSocketOpen() {
  if (!state.online || !state.socket || !state.socket.ws) return false;
  const ws = state.socket.ws;
  // Baileys 6.7.x: WebSocketClient.isOpen (boolean getter).
  if (typeof ws.isOpen === 'boolean') return ws.isOpen;
  // Bentuk lain / versi lama: WebSocket mentah dengan readyState.
  return ws.readyState === 1;
}

function ensureConnected() {
  if (!isSocketOpen()) {
    const err = new Error('WhatsApp belum tersambung. Scan QR di panel admin dulu.');
    err.status = 409;
    throw err;
  }
}

function toJid(phone) {
  const digits = String(phone).replace(/\D/g, '');
  if (digits.length < 8) throw new Error('Nomor tidak valid');
  return `${digits}@s.whatsapp.net`;
}

async function sendText(phone, text) {
  ensureConnected();

  // Promise.race: kalau sendMessage tidak selesai dalam SEND_TIMEOUT_MS,
  // balas 504 agar pemanggil tidak menunggu sampai timeout-nya sendiri.
  const send = state.socket.sendMessage(toJid(phone), { text });
  const timeout = new Promise((_, reject) => {
    setTimeout(() => {
      reject(Object.assign(
        new Error('Waktu kirim habis — koneksi WhatsApp tidak merespons.'),
        { status: 504 }
      ));
    }, SEND_TIMEOUT_MS);
  });

  await Promise.race([send, timeout]);
}

// ───────────────────────── HTTP server ─────────────────────────
const app = express();
app.use(express.json({ limit: '256kb' }));

function requireAuth(req, res, next) {
  if (!BAILEYS_TOKEN) return next(); // token kosong = terbuka (dev saja)
  const header = req.headers.authorization || '';
  if (header === `Bearer ${BAILEYS_TOKEN}`) return next();
  return res.status(401).json({ success: false, message: 'unauthorized' });
}

app.use('/api', requireAuth);

/**
 * Turunkan state.online bila WebSocket ternyata sudah TIDAK terbuka —
 * dipanggil sebelum status/QR dilaporkan supaya UI tidak menampilkan
 * "Online" padahal koneksi sudah mati diam-diam.
 *
 * Konservatif: kalau `ws` tidak bisa diperiksa (undefined / bentuk tak
 * dikenal), JANGAN menurunkan status — biarkan event connection.update
 * yang menentukan. (Deteksi agresif di versi sebelumnya memakai
 * `ws.readyState` yang selalu undefined di Baileys 6.7.x → online di-reset
 * terus → web selalu offline meski sudah scan.)
 */
function isWsDefinitelyClosed() {
  if (!state.online || !state.socket || !state.socket.ws) return false;
  const ws = state.socket.ws;
  if (typeof ws.isOpen === 'boolean') return !ws.isOpen;
  return ws.readyState !== undefined && ws.readyState !== 1 && ws.readyState !== 0;
}

function syncConnectionState() {
  if (isWsDefinitelyClosed()) {
    state.online = false;
    scheduleReconnect();
  }
}

app.get('/api/status', (req, res) => {
  syncConnectionState();
  const qrFresh = state.lastQr && state.lastQrAt && (Date.now() - state.lastQrAt) < QR_TTL_MS;
  res.json({
    success: true,
    data: {
      online: state.online,
      phone: state.phone,
      last_connected_at: state.lastConnectedAt,
      qr_available: !!qrFresh,
      logged_out: state.loggedOut,
    },
  });
});

app.get('/api/qr', async (req, res) => {
  syncConnectionState();
  if (state.online) {
    return res.json({ success: true, data: { qr: null, online: true, message: 'Sudah tersambung.' } });
  }

  const fresh = state.lastQr && state.lastQrAt && (Date.now() - state.lastQrAt) < QR_TTL_MS;
  if (!fresh) {
    return res.json({ success: true, data: { qr: null, online: false, message: 'QR belum tersedia — tunggu sebentar.' } });
  }

  try {
    const dataUrl = await QRCode.toDataURL(state.lastQr, { width: 320, margin: 1 });
    res.json({
      success: true,
      data: { qr: dataUrl, online: false, expires_in: Math.max(0, Math.round((QR_TTL_MS - (Date.now() - state.lastQrAt)) / 1000)) },
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

app.post('/api/logout', async (req, res) => {
  try {
    if (state.socket) {
      try { await state.socket.logout(); } catch (_) { /* abaikan */ }
    }
    // Hapus kredensial agar scan ulang bersih.
    fs.rmSync(SESSION_DIR, { recursive: true, force: true });

    state.online = false;
    state.phone = null;
    state.lastConnectedAt = null;
    state.lastQr = null;
    state.lastQrAt = null;
    state.loggedOut = true;
    if (state.reconnectTimer) clearTimeout(state.reconnectTimer);

    // Mulai ulang sesi kosong -> Baileys segera mengeluarkan QR baru.
    setTimeout(() => startSocket(), 500);

    res.json({ success: true, message: 'Sesi WhatsApp dicabut. Scan QR untuk menyambung ulang.' });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

app.post('/api/send', async (req, res) => {
  const { to, text } = req.body || {};
  if (!to || !text) {
    return res.status(422).json({ success: false, message: 'Parameter `to` dan `text` wajib.' });
  }
  try {
    await sendText(to, text);
    res.json({ success: true, message: 'Pesan terkirim.' });
  } catch (err) {
    res.status(err.status || 500).json({ success: false, message: err.message });
  }
});

app.get('/healthz', (req, res) => res.json({ success: true, data: { online: state.online } }));

// ───────────────────────── Redis subscriber (jalur cepat) ─────────────────────────
// Laravel (BaileysGateway) mem-publish pesan ke channel ini saat
// BAILEYS_REDIS_URL diset — socket persisten, tanpa HTTP handshake per pesan.
function startRedisSubscriber() {
  if (!REDIS_URL) {
    logger.info('REDIS_URL kosong — jalur cepat socket nonaktif; pakai HTTP.');
    return;
  }

  const sub = new Redis(REDIS_URL, { maxRetriesPerRequest: null });

  sub.on('error', (err) => logger.error({ err }, 'redis subscriber error'));
  sub.on('connect', () => logger.info('redis subscriber connected'));
  sub.on('ready', () => {
    sub.subscribe(WA_CHANNEL_SEND, (err) => {
      if (err) logger.error({ err }, 'redis subscribe gagal');
      else logger.info({ channel: WA_CHANNEL_SEND }, 'redis subscribed');
    });
  });

  sub.on('message', async (channel, message) => {
    if (channel !== WA_CHANNEL_SEND) return;

    let payload;
    try { payload = JSON.parse(message); } catch (_) { return; }
    const { id = null, to, text } = payload;
    if (!to || !text) return;

    try {
      await sendText(to, text);
      sub.publish(WA_CHANNEL_RESULT, JSON.stringify({ id, ok: true, to }));
    } catch (err) {
      sub.publish(WA_CHANNEL_RESULT, JSON.stringify({ id, ok: false, to, error: err.message }));
    }
  });

  state.redisSub = sub; // hindari di-GC
}

app.listen(PORT, HOST, () => {
  logger.info({ port: PORT, host: HOST }, 'seekitar whatsapp gateway listening');
  // eslint-disable-next-line no-console
  console.log(`[Seekitar WhatsApp Gateway] http://${HOST}:${PORT}  (healthz: /healthz)`);
  startSocket();
  startRedisSubscriber();
});
