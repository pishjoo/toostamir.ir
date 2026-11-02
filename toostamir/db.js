const Database = require('better-sqlite3');
const path = require('path');
const crypto = require('crypto');

const db = new Database(path.join(__dirname, 'data.sqlite'));
db.pragma('journal_mode = WAL');

db.exec(`
CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  category TEXT DEFAULT '',
  price INTEGER DEFAULT 0,
  old_price INTEGER DEFAULT 0,
  stock INTEGER DEFAULT 0,
  short_desc TEXT DEFAULT '',
  description TEXT DEFAULT '',
  image TEXT DEFAULT '',
  is_active INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS contact_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  phone TEXT NOT NULL,
  email TEXT DEFAULT '',
  subject TEXT DEFAULT '',
  message TEXT DEFAULT '',
  status TEXT DEFAULT 'new',
  created_at TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);
`);

// ---- seed demo products if empty ----
const count = db.prepare('SELECT COUNT(*) c FROM products').get().c;
if (count === 0) {
  const ins = db.prepare(`INSERT INTO products (name, category, price, old_price, stock, short_desc, description, image, is_active)
    VALUES (?,?,?,?,?,?,?,?,1)`);
  ins.run('تعمیر لپ تاپ ایسوس (X550)', 'لپ تاپ', 850000, 1200000, 0,
    'تشخیص رایگان، گارانتی ۳ ماهه تعمیرات',
    'تعمیر تخصصی مادربرد، تعویض ال‌سی‌دی، کیبورد و هارد لپ‌تاپ ایسوس مدل X550 با قطعات اصلی و گارانتی تعمیرات.', '');
  ins.run('فروش لپ تاپ استوک Lenovo ThinkPad T480', 'لپ تاپ استوک', 18500000, 21000000, 3,
    'Core i5 نسل ۸ / ۸GB RAM / 256GB SSD',
    'لپ‌تاپ استوک وارداتی لنوو ThinkPad T480، تمیز و بدون خط و خش، با گارانتی ۶ ماهه سخت‌افزار.', '');
  ins.run('تعمیر موبایل آیفون 11 (تعویض تاچ و ال‌سی‌دی)', 'موبایل', 2200000, 2800000, 0,
    'ال‌سی‌دی درجه یک، گارانتی ۶ ماهه',
    'تعویض اورجینال تاچ و ال‌سی‌دی آیفون 11 با ضمانت سلامت پیکسل و تاچ، انجام در کمتر از ۲ ساعت.', '');
  ins.run('شارژر اصلی سامسونگ 25W Type-C', 'لوازم جانبی', 650000, 800000, 12,
    'اصلی، دارای گارانتی ۶ ماهه',
    'آداپتور شارژ سریع ۲۵ وات سامسونگ با کابل Type-C، اصل و دارای مهلت گارانتی.', '');
  db.prepare("INSERT OR REPLACE INTO settings (key,value) VALUES ('site_name','تعمیرامیر')").run();
}

// ---- default admin account (change password after first login!) ----
function hashPassword(pw, salt) {
  return crypto.scryptSync(pw, salt, 32).toString('hex');
}
let admin = db.prepare("SELECT * FROM settings WHERE key='admin'").get();
if (!admin) {
  const salt = crypto.randomBytes(16).toString('hex');
  const hash = hashPassword('admin123', salt);
  db.prepare("INSERT INTO settings (key,value) VALUES ('admin',?)")
    .run(JSON.stringify({ username: 'admin', salt, hash }));
  console.log('[DB] Admin created -> username: admin  password: admin123');
}

module.exports = {
  db,
  verifyAdmin(username, password) {
    const row = db.prepare("SELECT value FROM settings WHERE key='admin'").get();
    if (!row) return false;
    const a = JSON.parse(row.value);
    if (a.username !== username) return false;
    return crypto.timingSafeEqual(Buffer.from(a.hash, 'hex'), Buffer.from(hashPassword(password, a.salt), 'hex'));
  },
  changeAdminPassword(password) {
    const row = db.prepare("SELECT value FROM settings WHERE key='admin'").get();
    const a = JSON.parse(row.value);
    a.salt = crypto.randomBytes(16).toString('hex');
    a.hash = hashPassword(password, a.salt);
    db.prepare("UPDATE settings SET value=? WHERE key='admin'").run(JSON.stringify(a));
  }
};
