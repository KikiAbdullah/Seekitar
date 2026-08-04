/**
 * PM2 — jalankan gateway sebagai daemon background (auto-restart).
 *
 *   npm i -g pm2
 *   pm2 start ecosystem.config.js
 *   pm2 save                 # agar ikut restart saat reboot
 *   pm2 logs seekitar-wa     # lihat log
 *
 * Env: isi BAILEYS_TOKEN & REDIS_URL di bawah (atau via `pm2 env`).
 */
module.exports = {
  apps: [
    {
      name: 'seekitar-wa',
      script: 'index.js',
      cwd: __dirname,
      instances: 1,               // WA session = state dalam proses; jangan cluster
      exec_mode: 'fork',
      autorestart: true,
      max_restarts: 20,
      restart_delay: 3000,
      env: {
        NODE_ENV: 'production',
        HOST: '127.0.0.1',
        PORT: 3001,
        // ⚠️ WAJIB diisi: token sama dengan BAILEYS_TOKEN di .env Laravel.
        BAILEYS_TOKEN: '',
        // Jalur cepat socket: URL Redis, mis. redis://127.0.0.1:6379.
        // Biarkan kosong untuk memakai HTTP saja.
        REDIS_URL: '',
        WA_CHANNEL_SEND: 'seekitar:wa:send',
        WA_CHANNEL_RESULT: 'seekitar:wa:result',
        LOG_LEVEL: 'warn',
      },
    },
  ],
};
