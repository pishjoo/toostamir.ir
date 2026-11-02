/* ============================================================
   پیکربندی PM2 برای «App Node.js» در cPanel (بخس Application Manager)
   اگر از طریق cPanel > Setup Node.js App اپ می‌سازید، این فایل اختیاری است؛
   فقط ENTRY POINT را روی server.js تنظیم کنید.
   ============================================================ */
module.exports = {
  apps: [{
    name: 'toostamir',
    script: 'server.js',
    cwd: __dirname,
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '300M',
    env: { NODE_ENV: 'production' }
  }]
};
