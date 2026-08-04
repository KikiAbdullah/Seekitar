'use strict';

/**
 * Seekitar WhatsApp Gateway — Baileys sidecar service.
 *
 * Menjalankan sesi WhatsApp Web (Baileys) dan mengekspos HTTP API kecil yang
 * dipakai backend Laravel (`App\Services\WhatsApp\BaileysGateway`) untuk:
 *
 *   GET  /status   -> online/offline + nomor tersambung
 *   GET  /qr       -> QR code (data URL PNG, DI-CACHE) untuk scan dari panel admin
 *   POST /logout   -> putuskan sesi & hapus kredensial (minta scan ulang)
 *   POST /reset    -> hapus sesi & langsung minta QR BARU (perbaiki QR macet)
 *   POST /send     -> kirim pesan teks (dipakai pengiriman OTP)
 *
 * OPTIMASI KECEPATAN (versi ini):
 *   1. Versi Baileys DI-PIN, tanpa fetchLatestBaileysVersion() — memanggil
 *      GitHub API tiap (re)connect memperlambat & bisa gagal; socket dibuat
 *      seketika sehingga QR muncul jauh lebih cepat.
 *   2. QR di-generate data URL SEKALI (cache), bukan per polling.
 *   3. Auto-heal sesi: bila sesi tersimpan terus putus (3x) tanpa pernah
 *      open/QR, folder sesi di-reset otomatis agar pairing fresh (QR keluar).
 *   4. Reconnect cepat (default 3s) dengan timer tunggal (tidak menumpuk).
 *   5. Kirim OTP via Redis pub/sub (jalur socket persisten) bila REDIS_URL
 *      diset — tanpa HTTP handshake per pesan.
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
} = require('@whiskeysockets/baileys');

// ───────────────────────── konfigurasi ─────────────────────────
const PORT = Number(process.env.PORT || 3001);
const HOST = process.env.HOST || '127.0.0.1';
const SESSION_DIR = process.env.SESSION_DIR || path.join(__dirname, 'session');
const LOG_LEVEL = process.env.LOG_LEVEL || 'silent'; // pino: silent|error|warn|info|debug
const BAILEYS_TOKEN = process.env.BAILEYS_TOKEN || '';
// QR Baileys ~20s; TTL sedikit lebih besar dari itu.
const QR_TTL_MS = Number(process.env.QR_TTL_MS || 22000);
// Reconnect cepat — 3s (sebelumnya 5s).
const RECONNECT_DELAY_MS = Number(process.env.RECONNECT_DELAY_MS || 3000);
// Batas waktu kirim pesan (sendMessage bisa menggantung saat koneksi mati).
const SEND_TIMEOUT_MS = Number(process.env.SEND_TIMEOUT_MS || 8000);
// Berapa kali koneksi tertutup (tanpa QR/open) sebelum sesi di-reset otomatis.
const AUTO_RESET_AFTER = Number(process.env.AUTO_RESET_AFTER || 3);

/**
 * Versi Baileys yang DI-PIN (terbukti bekerja). TIDAK memanggil
 * fetchLatestBaileysVersion() agar startup seketika & tanpa ketergantungan
 * jaringan ke GitHub — ini kunci QR muncul cepat.
 */
const BAILEYS_VERSION = [2, 3000, 1043857760];

// ── Redis pub/sub (jalur cepat socket) ─────────────────────────────────────
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
  lastQr: null,          // string QR mentah dari Baileys
  lastQrAt: null,
  lastQrDataUrl: null,   // CACHE data URL PNG — di-generate sekali saja
  loggedOut: false,
  reconnectTimer: null,
  connecting: false,
  failedAttempts: 0,     // hitungan koneksi tertutup berturut-turut
  autoResetDone: false,  // cegah reset berulang dalam satu siklus
  redisSub: null,
};

const logger = pino({ level: LOG_LEVEL });

// ───────────────────────── socket Baileys ─────────────────────────
function hasStoredSession() {
  try {
    return fs.readdirSync(SESSION_DIR).some((f) => f.startsWith('creds'));
  } catch {
    return false;
  }
}

async function startSocket() {
  if (state.connecting) {
    // eslint-disable-next-line no-console
    console.log('[WA] ⏳ startSocket dipanggil saat connecting — dilewati.');
    return;
  }
  state.connecting = true;
  state.loggedOut = false;

  try {
    fs.mkdirSync(SESSION_DIR, { recursive: true });

    // eslint-disable-next-line no-console
    console.log(`[WA] 🔌 Memulai sesi Baileys (versi ${BAILEYS_VERSION.join('.')}) — folder: ${SESSION_DIR}`);
    const { state: authState, saveCreds } = await useMultiFileAuthState(SESSION_DIR);

    const sock = makeWASocket({
      version: BAILEYS_VERSION,
      auth: authState,
      logger,
      printQRInTerminal: false,
      browser: ['Seekitar Gateway', 'Chrome', '1.0.0'],
      markOnlineOnConnect: false,
    });

    state.socket = sock;
    // eslint-disable-next-line no-console
    console.log('[WA] ✅ Socket Baileys dibuat — menunggu koneksi/QR…');

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        state.lastQr = qr;
        state.lastQrAt = Date.now();
        state.lastQrDataUrl = null;   // invalidate cache — regenerate
        state.failedAttempts = 0;
        state.autoResetDone = false;
        // Generate data URL DI BACKGROUND agar polling UI pertama langsung
        // dapat QR (tanpa menunggu generate di request).
        QRCode.toDataURL(qr, { width: 320, margin: 1 })
          .then((url) => { if (state.lastQr === qr) state.lastQrDataUrl = url; })
          .catch(() => {});
        // eslint-disable-next-line no-console
        console.log(`[WA] 🟢 QR BARU tersedia (${new Date().toLocaleTimeString()}) — scan dari panel admin.`);
      }

      if (connection === 'open') {
        state.online = true;
        state.lastQr = null;
        state.lastQrAt = null;
        state.lastQrDataUrl = null;
        state.failedAttempts = 0;
        state.autoResetDone = false;
        state.lastConnectedAt = new Date().toISOString();
        const jid = sock.user?.id || '';
        state.phone = jid.split(':')[0] || null;
        logger.info({ phone: state.phone }, 'whatsapp connected');
        // eslint-disable-next-line no-console
        console.log(`[WA] ✅ TERSAMBUNG — nomor: ${state.phone || '?'} (${new Date().toLocaleTimeString()})`);
      }

      if (connection === 'close') {
        state.online = false;
        // QR lama tidak relevan lagi — bersihkan agar UI tidak menampilkan QR basi.
        state.lastQr = null;
        state.lastQrAt = null;
        state.lastQrDataUrl = null;

        const code = lastDisconnect?.error?.output?.statusCode;
        const isLoggedOut = code === DisconnectReason.loggedOut;

        if (isLoggedOut) {
          state.loggedOut = true;
          state.phone = null;
          logger.warn('logged out — scan QR ulang');
          // eslint-disable-next-line no-console
          console.log('[WA] ⚠️ Sesi di-LOGOUT — scan QR ulang.');
          scheduleReconnect();
          return;
        }

        // Auto-heal: sesi tersimpan yang terus putus (tanpa pernah QR/open)
        // kemungkinan korup — hapus sekali agar pairing fresh & QR keluar.
        state.failedAttempts++;
        // eslint-disable-next-line no-console
        console.log(`[WA] ⚠️ Koneksi tertutup (kode ${code ?? '?'}) — percobaan ke-${state.failedAttempts}${state.connecting ? '' : ''}.`);

        if (state.failedAttempts >= AUTO_RESET_AFTER && hasStoredSession() && !state.autoResetDone) {
          state.autoResetDone = true;
          // eslint-disable-next-line no-console
          console.log('[WA] 🧹 Sesi tersimpan bermasalah — reset folder sesi, QR baru akan muncul…');
          try { fs.rmSync(SESSION_DIR, { recursive: true, force: true }); } catch (_) {}
          state.phone = null;
        }

        scheduleReconnect();
      }
    });

    sock.ev.on('messages.upsert', () => {
      // Pesan masuk tidak diproses gateway ini (hanya kirim OTP).
    });
  } catch (err) {
    logger.error({ err }, 'startSocket failed');
    // eslint-disable-next-line no-console
    console.error('[WA] ❌ startSocket gagal:', err.message);
    scheduleReconnect();
  } finally {
    state.connecting = false;
  }
}

function scheduleReconnect() {
  if (state.reconnectTimer) clearTimeout(state.reconnectTimer);
  state.reconnectTimer = setTimeout(() => {
    // eslint-disable-next-line no-console
    console.log('[WA] 🔄 Mencoba sambung ulang…');
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
 * `ws.readyState === 1` di sini SELALU false, yang membuat status di-reset
 * ke offline terus-menerus padahal koneksi nyata terbuka.
 */
function isSocketOpen() {
  if (!state.online || !state.socket || !state.socket.ws) return false;
  const ws = state.socket.ws;
  if (typeof ws.isOpen === 'boolean') return ws.isOpen; // Baileys 6.7.x
  return ws.readyState === WS_OPEN;                     // WebSocket mentah
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

  // eslint-disable-next-line no-console
  console.log(`[WA] 📤 Kirim pesan → ${phone} (${new Date().toLocaleTimeString()})`);

  const send = state.socket.sendMessage(toJid(phone), { text });
  const timeout = new Promise((_, reject) => {
    setTimeout(() => {
      reject(Object.assign(
        new Error('Waktu kirim habis — koneksi WhatsApp tidak merespons.'),
        { status: 504 }
      ));
    }, SEND_TIMEOUT_MS);
  });

  try {
    await Promise.race([send, timeout]);
    // eslint-disable-next-line no-console
    console.log(`[WA] ✅ Pesan terkirim → ${phone}`);
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error(`[WA] ❌ Gagal kirim → ${phone}: ${err.message}`);
    throw err;
  }
}

/**
 * Konservatif: hanya turunkan status bila WebSocket JELAS tertutup.
 * ws undefined / bentuk tak dikenal → biarkan event connection.update
 * yang menentukan (tidak memicu reset online palsu).
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

app.get('/api/status', (req, res) => {
  syncConnectionState();
  const qrFresh = state.lastQr && state.lastQrAt && (Date.now() - state.lastQrAt) < QR_TTL_MS;
  // eslint-disable-next-line no-console
  console.log(`[WA] 📊 /api/status → online=${state.online} phone=${state.phone || '-'} qr=${qrFresh ? 'ADA' : '-'} connecting=${state.connecting}`);
  res.json({
    success: true,
    data: {
      online: state.online,
      phone: state.phone,
      last_connected_at: state.lastConnectedAt,
      qr_available: !!qrFresh,
      logged_out: state.loggedOut,
      connecting: state.connecting,
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
    return res.json({
      success: true,
      data: {
        qr: null,
        online: false,
        connecting: state.connecting,
        message: state.connecting
          ? 'Menghubungkan ke server WhatsApp…'
          : 'QR belum tersedia — tunggu sebentar…',
      },
    });
  }

  try {
    // Data URL DI-CACHE — tidak di-generate ulang per polling.
    if (!state.lastQrDataUrl) {
      state.lastQrDataUrl = await QRCode.toDataURL(state.lastQr, { width: 320, margin: 1 });
    }
    res.json({
      success: true,
      data: {
        qr: state.lastQrDataUrl,
        online: false,
        connecting: false,
        expires_in: Math.max(0, Math.round((QR_TTL_MS - (Date.now() - state.lastQrAt)) / 1000)),
      },
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

app.post('/api/logout', async (req, res) => {
  // eslint-disable-next-line no-console
  console.log(`[WA] 🔌 /api/logout dipanggil (${new Date().toLocaleTimeString()})`);
  try {
    if (state.socket) {
      try { await state.socket.logout(); } catch (_) { /* abaikan */ }
    }
    fs.rmSync(SESSION_DIR, { recursive: true, force: true });

    state.online = false;
    state.phone = null;
    state.lastConnectedAt = null;
    state.lastQr = null;
    state.lastQrAt = null;
    state.lastQrDataUrl = null;
    state.failedAttempts = 0;
    state.autoResetDone = false;
    state.loggedOut = true;
    if (state.reconnectTimer) clearTimeout(state.reconnectTimer);

    setTimeout(() => startSocket(), 300);

    res.json({ success: true, message: 'Sesi WhatsApp dicabut. Scan QR untuk menyambung ulang.' });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/** Reset sesi — untuk kasus QR macet / sesi korup: langsung QR baru. */
app.post('/api/reset', async (req, res) => {
  // eslint-disable-next-line no-console
  console.log(`[WA] 🧹 /api/reset dipanggil (${new Date().toLocaleTimeString()})`);
  try {
    if (state.socket) {
      try { state.socket.end?.(); } catch (_) {}
      try { await state.socket.logout(); } catch (_) {}
    }
    fs.rmSync(SESSION_DIR, { recursive: true, force: true });

    state.online = false;
    state.phone = null;
    state.lastConnectedAt = null;
    state.lastQr = null;
    state.lastQrAt = null;
    state.lastQrDataUrl = null;
    state.failedAttempts = 0;
    state.autoResetDone = false;
    state.loggedOut = true;
    if (state.reconnectTimer) clearTimeout(state.reconnectTimer);

    // Restart segera — QR fresh muncul dalam hitungan detik.
    setTimeout(() => startSocket(), 200);

    res.json({ success: true, message: 'Sesi di-reset. QR baru akan segera muncul — scan ulang.' });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

app.post('/api/send', async (req, res) => {
  const { to, text } = req.body || {};
  // eslint-disable-next-line no-console
  console.log(`[WA] 📥 POST /api/send → ${to || '(kosong)'} (${new Date().toLocaleTimeString()})`);
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
function startRedisSubscriber() {
  if (!REDIS_URL) {
    // eslint-disable-next-line no-console
    console.log('[WA] 🟠 REDIS_URL kosong — jalur cepat socket NONAKTIF; pakai HTTP biasa.');
    return;
  }

  // eslint-disable-next-line no-console
  console.log(`[WA] 🔵 Menghubungkan ke Redis: ${REDIS_URL.replace(/:[^:@/]+@/, ':*****@')}`);

  const sub = new Redis(REDIS_URL, { maxRetriesPerRequest: null });

  sub.on('error', (err) => {
    logger.error({ err }, 'redis subscriber error');
    // eslint-disable-next-line no-console
    console.error('[WA] ❌ Redis subscriber error:', err.message);
  });
  sub.on('connect', () => {
    logger.info('redis subscriber connected');
    // eslint-disable-next-line no-console
    console.log('[WA] 🔵 Redis subscriber CONNECTED.');
  });
  sub.on('ready', () => {
    sub.subscribe(WA_CHANNEL_SEND, (err) => {
      if (err) {
        logger.error({ err }, 'redis subscribe gagal');
        // eslint-disable-next-line no-console
        console.error(`[WA] ❌ Gagal subscribe ${WA_CHANNEL_SEND}:`, err.message);
      } else {
        logger.info({ channel: WA_CHANNEL_SEND }, 'redis subscribed');
        // eslint-disable-next-line no-console
        console.log(`[WA] 🔵 Redis SUBSCRIBED ke channel "${WA_CHANNEL_SEND}" — jalur cepat siap.`);
      }
    });
  });

  sub.on('message', async (channel, message) => {
    if (channel !== WA_CHANNEL_SEND) return;

    // eslint-disable-next-line no-console
    console.log(`[WA] 📨 Pesan dari Redis channel "${channel}" diterima (${new Date().toLocaleTimeString()}).`);

    let payload;
    try { payload = JSON.parse(message); } catch (_) { return; }
    const { id = null, to, text } = payload;
    if (!to || !text) return;

    try {
      await sendText(to, text);
      sub.publish(WA_CHANNEL_RESULT, JSON.stringify({ id, ok: true, to }));
      // eslint-disable-next-line no-console
      console.log(`[WA] ✅ Hasil kirim via Redis → ${to} (ok=true).`);
    } catch (err) {
      sub.publish(WA_CHANNEL_RESULT, JSON.stringify({ id, ok: false, to, error: err.message }));
      // eslint-disable-next-line no-console
      console.error(`[WA] ❌ Hasil kirim via Redis → ${to} (ok=false): ${err.message}`);
    }
  });

  state.redisSub = sub; // hindari di-GC
}

// ───────────────────────── startup ─────────────────────────
app.listen(PORT, HOST, () => {
  logger.info({ port: PORT, host: HOST }, 'seekitar whatsapp gateway listening');
  // eslint-disable-next-line no-console
  console.log('==========================================================');
  console.log(`[WA] 🚀 Seekitar WhatsApp Gateway dimulai (PID ${process.pid})`);
  console.log(`[WA] 🌐 HTTP : http://${HOST}:${PORT}   (healthz: /healthz)`);
  console.log(`[WA] 🔑 Token: ${BAILEYS_TOKEN ? 'TERSET (auth aktif)' : 'KOSONG (tanpa auth — hanya dev!)'}`);
  console.log(`[WA] 🗂️  Folder sesi: ${SESSION_DIR}${hasStoredSession() ? ' (ADA sesi tersimpan — akan auto-connect)' : ' (kosong — butuh scan QR)'}`);
  console.log(`[WA] 📡 Baileys: ${require('@whiskeysockets/baileys/package.json').version} (dipin ${BAILEYS_VERSION.join('.')}, tanpa fetch GitHub)`);
  console.log(`[WA] ⚡ Reconnect ${RECONNECT_DELAY_MS / 1000}s · QR TTL ${QR_TTL_MS / 1000}s · Auto-reset sesi setelah ${AUTO_RESET_AFTER}x putus`);
  console.log('==========================================================');
  startSocket();
  startRedisSubscriber();
});
