@echo off
REM ============================================================
REM  Seekitar WhatsApp Gateway — mulai sebagai daemon background
REM  (Windows). Memakai PM2 agar auto-restart & ikut boot.
REM
REM  Prasyarat: Node.js 18+ terpasang.
REM  Sekali saja:   npm install -g pm2
REM ============================================================
setlocal
cd /d "%~dp0"

if not exist node_modules (
  echo [1/3] npm install...
  call npm install --no-audit --no-fund || goto :error
)

echo [2/3] Menyiapkan token dari BAILEYS_TOKEN di .env Laravel...
REM Setel BAILEYS_TOKEN di sini (sama dengan .env Laravel) ATAU edit
REM ecosystem.config.js. Contoh: set BAILEYS_TOKEN=seekitar-gateway-2026
if "%BAILEYS_TOKEN%"=="" (
  echo      BAILEYS_TOKEN kosong — buka file ini dan isi, atau set di ecosystem.config.js
)

echo [3/3] Menjalankan gateway via PM2...
call npx pm2 start ecosystem.config.js || goto :error
call npx pm2 save
echo.
echo Selesai. Cek: npx pm2 status  |  Log: npx pm2 logs seekitar-wa
goto :eof

:error
echo.
echo GAGAL. Pastikan Node.js & PM2 terpasang.
exit /b 1
