/* ============================================================
   لایه دیتابیس — سازگار با هاست اشتراکی لینوکس (cPanel)
   - موتور اصلی: MariaDB / MySQL (همان دیتابیس cPanel شما)
   - در صورت نبود تنظیمات MariaDB، به‌صورت خودکار روی SQLite
     (فقط برای توسعه محلی) می‌افتد.
   - مقادیر اتصال را از فایل config.env (یا متغیرهای محیطی) بخوانید.
   ============================================================ */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

// ---- بارگذاری config.env (بدون وابستگی خارجی) ----
function loadEnvFile() {
  const envPath = path.join(__dirname, 'config.env');
  if (!fs.existsSync(envPath)) return;
  for (const line of fs.readFileSync(envPath, 'utf8').split(/\r?\n/)) {
    const m = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/);
    if (m && process.env[m[1]] === undefined) {
      let v = m[2].replace(/^["']|["']$/g, '');
      process.env[m[1]] = v;
    }
  }
}
loadEnvFile();

const cfg = {
  host: process.env.DB_HOST || 'localhost',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || '',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || ''
};

const useMySQL = Boolean(cfg.user && cfg.database);

let pool = null;      // mariadb pool
let sqlite = null;    // better-sqlite3 (fallback توسعه محلی)
let driverName = '';

async function initMySQL() {
  const mariadb = require('mariadb');
  pool = mariadb.createPool({
    host: cfg.host,
    port: cfg.port,
    user: cfg.user,
    password: cfg.password,
    database: cfg.database,
    connectionLimit: 5,
    charset: 'utf8mb4',          // فارسی کامل؛ روی سرور cp1252 هم auto-convert می‌شود
    collation: 'utf8mb4_unicode_ci'
  });
  driverName = 'mysql';

  await pool.query(`CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    price BIGINT NOT NULL DEFAULT 0,
    old_price BIGINT NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    short_desc TEXT,
    description LONGTEXT,
    image VARCHAR(500) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`);

  await pool.query(`CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL DEFAULT '',
    subject VARCHAR(255) NOT NULL DEFAULT '',
    message TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`);

  await pool.query(`CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(100) NOT NULL PRIMARY KEY,
    sval LONGTEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`);

  await seedIfEmpty();
  await ensureAdmin();
}

function initSQLite() {
  const Database = require('better-sqlite3');
  sqlite = new Database(path.join(__dirname, 'data.sqlite'));
  sqlite.pragma('journal_mode = WAL');
  driverName = 'sqlite';

  sqlite.exec(`
  CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, category TEXT DEFAULT '',
    price INTEGER DEFAULT 0, old_price INTEGER DEFAULT 0, stock INTEGER DEFAULT 0,
    short_desc TEXT DEFAULT '', description TEXT DEFAULT '', image TEXT DEFAULT '',
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now','localtime'))
  );
  CREATE TABLE IF NOT EXISTS contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, phone TEXT NOT NULL, email TEXT DEFAULT '',
    subject TEXT DEFAULT '', message TEXT DEFAULT '', status TEXT DEFAULT 'new',
    created_at TEXT DEFAULT (datetime('now','localtime'))
  );
  CREATE TABLE IF NOT EXISTS settings ( key TEXT PRIMARY KEY, value TEXT );`);

  seedIfEmptySync();
  ensureAdminSync();
}

/* ---------------- unified async helpers ---------------- */
async function all(sql, params = []) {
  if (driverName === 'mysql') { const rows = await pool.query(sql, params); return Array.isArray(rows) ? rows : []; }
  return sqlite.prepare(sql).all(...params);
}
async function get(sql, params = []) {
  if (driverName === 'mysql') {
    const conn = await pool.getConnection();
    try {
      const rows = await conn.query(sql, params);
      return Array.isArray(rows) ? (rows[0] || null) : (rows || null);
    } finally { conn.release(); }
  }
  return sqlite.prepare(sql).get(...params) || null;
}
async function run(sql, params = []) {
  if (driverName === 'mysql') { const r = await pool.query(sql, params); return { lastInsertRowid: Number(r.insertId || 0) }; }
  const r = sqlite.prepare(sql).run(...params);
  return { lastInsertRowid: Number(r.lastInsertRowid) };
}

/* ---------------- seed demo products ---------------- */
const DEMO = [
  ['تعمیر لپ تاپ ایسوس (X550)', 'لپ تاپ', 850000, 1200000, 0,
    'تشخیص رایگان، گارانتی ۳ ماهه تعمیرات',
    'تعمیر تخصصی مادربرد، تعویض ال‌سی‌دی، کیبورد و هارد لپ‌تاپ ایسوس مدل X550 با قطعات اصلی و گارانتی تعمیرات.', ''],
  ['فروش لپ تاپ استوک Lenovo ThinkPad T480', 'لپ تاپ استوک', 18500000, 21000000, 3,
    'Core i5 نسل ۸ / ۸GB RAM / 256GB SSD',
    'لپ‌تاپ استوک وارداتی لنوو ThinkPad T480، تمیز و بدون خط و خش، با گارانتی ۶ ماهه سخت‌افزار.', ''],
  ['تعمیر موبایل آیفون 11 (تعویض تاچ و ال‌سی‌دی)', 'موبایل', 2200000, 2800000, 0,
    'ال‌سی‌دی درجه یک، گارانتی ۶ ماهه',
    'تعویض اورجینال تاچ و ال‌سی‌دی آیفون 11 با ضمانت سلامت پیکسل و تاچ، انجام در کمتر از ۲ ساعت.', ''],
  ['شارژر اصلی سامسونگ 25W Type-C', 'لوازم جانبی', 650000, 800000, 12,
    'اصلی، دارای گارانتی ۶ ماهه',
    'آداپتور شارژ سریع ۲۵ وات سامسونگ با کابل Type-C، اصل و دارای مهلت گارانتی.', '']
];
const INSERT_PRODUCT = `INSERT INTO products (name, category, price, old_price, stock, short_desc, description, image, is_active) VALUES (?,?,?,?,?,?,?,?,1)`;

async function seedIfEmpty() {
  const row = await get('SELECT COUNT(*) c FROM products');
  if (Number(row.c) > 0) return;
  for (const d of DEMO) await run(INSERT_PRODUCT, d);
  await run("INSERT INTO settings (skey,sval) VALUES ('site_name','تعمیرامیر') ON DUPLICATE KEY UPDATE sval='تعمیرامیر'");
}
function seedIfEmptySync() {
  const c = sqlite.prepare('SELECT COUNT(*) c FROM products').get().c;
  if (c > 0) return;
  const ins = sqlite.prepare(INSERT_PRODUCT);
  for (const d of DEMO) ins.run(...d);
  sqlite.prepare("INSERT OR REPLACE INTO settings (key,value) VALUES ('site_name','تعمیرامیر')").run();
}

/* ---------------- admin account ---------------- */
function hashPassword(pw, salt) { return crypto.scryptSync(pw, salt, 32).toString('hex'); }

async function ensureAdmin() {
  const row = await get("SELECT sval v FROM settings WHERE skey='admin'");
  if (row) return;
  const salt = crypto.randomBytes(16).toString('hex');
  const hash = hashPassword('admin123', salt);
  await run("INSERT INTO settings (skey,sval) VALUES ('admin',?)",
    [JSON.stringify({ username: 'admin', salt, hash })]);
  console.log('[DB] Admin created -> username: admin  password: admin123');
}
function ensureAdminSync() {
  const row = sqlite.prepare("SELECT * FROM settings WHERE key='admin'").get();
  if (row) return;
  const salt = crypto.randomBytes(16).toString('hex');
  const hash = hashPassword('admin123', salt);
  sqlite.prepare("INSERT INTO settings (key,value) VALUES ('admin',?)")
    .run(JSON.stringify({ username: 'admin', salt, hash }));
  console.log('[DB] Admin created -> username: admin  password: admin123');
}

/* ---------------- public API ---------------- */
const api = {
  get driver() { return driverName; },
  all, get, run,

  async verifyAdmin(username, password) {
    const row = await get(driverName === 'mysql'
      ? "SELECT sval v FROM settings WHERE skey='admin'"
      : "SELECT value v FROM settings WHERE key='admin'");
    if (!row) return false;
    const a = JSON.parse(row.v);
    if (a.username !== username) return false;
    try {
      return crypto.timingSafeEqual(Buffer.from(a.hash, 'hex'), Buffer.from(hashPassword(password, a.salt), 'hex'));
    } catch { return false; }
  },

  async changeAdminPassword(password) {
    const row = await get(driverName === 'mysql'
      ? "SELECT sval v FROM settings WHERE skey='admin'"
      : "SELECT value v FROM settings WHERE key='admin'");
    if (!row) return;
    const a = JSON.parse(row.v);
    a.salt = crypto.randomBytes(16).toString('hex');
    a.hash = hashPassword(password, a.salt);
    await run(driverName === 'mysql'
      ? "UPDATE settings SET sval=? WHERE skey='admin'"
      : "UPDATE settings SET value=? WHERE key='admin'", [JSON.stringify(a)]);
  },

  async init() {
    if (useMySQL) {
      try {
        await initMySQL();
        console.log(`[DB] Connected to MariaDB/MySQL -> ${cfg.user}@${cfg.host}/${cfg.database}`);
        return;
      } catch (e) {
        console.error('[DB] MariaDB connection failed:', e.message);
        console.error('[DB] Falling back to SQLite (development only).');
      }
    } else {
      console.warn('[DB] No DB credentials found in config.env — using SQLite (development only).');
    }
    initSQLite();
  }
};

module.exports = api;
