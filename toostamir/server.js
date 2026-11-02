const express = require('express');
const session = require('express-session');
const methodOverride = require('method-override');
const path = require('path');
const db = require('./db');

const app = express();
// پورت Node.js روی هاست‌های cPanel معمولاً 3000 است (از متغیر محیطی PORT)
const PORT = process.env.PORT || 3000;

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.set('trust proxy', 1); // برای HTTPS پشت Proxy cpPanel / LiteSpeed
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(methodOverride('_method'));
app.use('/public', express.static(path.join(__dirname, 'public')));
app.use(session({
  secret: process.env.SESSION_SECRET || 'toostamir-secret-change-me',
  resave: false,
  saveUninitialized: true,
  cookie: { httpOnly: true, sameSite: 'lax' }
}));

// expose current path to views (for admin sidebar active state)
app.use((req, res, next) => { res.locals.path = req.path; next(); });

// price helper (تومان) + Persian digits
function toman(n) {
  return Number(n || 0).toLocaleString('fa-IR');
}
app.locals.toman = toman;
app.locals.year = new Date().getFullYear();
app.locals.dbDriver = () => db.driver;

function requireAdmin(req, res, next) {
  if (req.session && req.session.admin) return next();
  res.redirect('/admin/login');
}

// ===================== PUBLIC SITE =====================

app.get('/', async (req, res, next) => {
  try {
    const products = await db.all('SELECT * FROM products WHERE is_active=1 ORDER BY id DESC LIMIT 8');
    res.render('index', { products });
  } catch (e) { next(e); }
});

app.get('/products', async (req, res, next) => {
  try {
    const q = (req.query.q || '').trim();
    const cat = (req.query.cat || '').trim();
    let rows = await db.all('SELECT * FROM products WHERE is_active=1 ORDER BY id DESC');
    if (q) rows = rows.filter(p => ((p.name || '') + (p.short_desc || '') + (p.description || '')).includes(q));
    if (cat) rows = rows.filter(p => p.category === cat);
    const cats = await db.all("SELECT DISTINCT category FROM products WHERE is_active=1 AND category<>''");
    res.render('products', { products: rows, categories: cats.map(r => r.category), q, cat });
  } catch (e) { next(e); }
});

app.get('/product/:id', async (req, res, next) => {
  try {
    const p = await db.get('SELECT * FROM products WHERE id=? AND is_active=1', [req.params.id]);
    if (!p) return res.status(404).render('404');
    res.render('product', { p });
  } catch (e) { next(e); }
});

app.get('/services', (req, res) => res.render('services'));
app.get('/warranty', (req, res) => res.render('warranty'));

// ---- contact form ----
app.get('/contact', (req, res) => res.render('contact', { sent: false, error: '', subject: req.query.subject || '' }));

app.post('/contact', async (req, res, next) => {
  try {
    const { name, phone, email, subject, message } = req.body;
    if (!name || !phone) {
      return res.render('contact', { sent: false, error: 'نام و شماره تماس الزامی است.', subject: subject || '' });
    }
    await db.run('INSERT INTO contact_messages (name, phone, email, subject, message) VALUES (?,?,?,?,?)',
      [name, phone, email || '', subject || '', message || '']);
    res.render('contact', { sent: true, error: '' });
  } catch (e) { next(e); }
});

// ===================== ADMIN PANEL =====================

app.get('/admin/login', (req, res) => res.render('admin/login', { error: '' }));

app.post('/admin/login', async (req, res) => {
  const { username, password } = req.body;
  if (await db.verifyAdmin(username, password)) {
    req.session.admin = username;
    return res.redirect('/admin');
  }
  res.render('admin/login', { error: 'نام کاربری یا رمز عبور اشتباه است.' });
});

app.get('/admin/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/'));
});

app.use('/admin', requireAdmin);

app.get('/admin', async (req, res, next) => {
  try {
    const stats = {
      products: Number((await db.get('SELECT COUNT(*) c FROM products')).c),
      active: Number((await db.get('SELECT COUNT(*) c FROM products WHERE is_active=1')).c),
      messages: Number((await db.get("SELECT COUNT(*) c FROM contact_messages WHERE status='new'")).c),
      lowStock: Number((await db.get('SELECT COUNT(*) c FROM products WHERE is_active=1 AND stock<=2')).c)
    };
    const recent = await db.all('SELECT * FROM products ORDER BY id DESC LIMIT 5');
    res.render('admin/dashboard', { stats, recent });
  } catch (e) { next(e); }
});

// ---- products CRUD ----
app.get('/admin/products', async (req, res, next) => {
  try {
    const products = await db.all('SELECT * FROM products ORDER BY id DESC');
    res.render('admin/products', { products });
  } catch (e) { next(e); }
});

app.get('/admin/products/new', (req, res) => {
  res.render('admin/product_form', { p: null, error: '' });
});

app.post('/admin/products', async (req, res, next) => {
  try {
    const b = req.body;
    if (!b.name || !b.name.trim()) {
      return res.render('admin/product_form', { p: b, error: 'نام محصول الزامی است.' });
    }
    await db.run(`INSERT INTO products (name, category, price, old_price, stock, short_desc, description, image, is_active)
      VALUES (?,?,?,?,?,?,?,?,?)`,
      [b.name.trim(), b.category || '', +b.price || 0, +b.old_price || 0, +b.stock || 0,
        b.short_desc || '', b.description || '', b.image || '', b.is_active ? 1 : 0]);
    res.redirect('/admin/products');
  } catch (e) { next(e); }
});

app.get('/admin/products/:id/edit', async (req, res, next) => {
  try {
    const p = await db.get('SELECT * FROM products WHERE id=?', [req.params.id]);
    if (!p) return res.redirect('/admin/products');
    res.render('admin/product_form', { p, error: '' });
  } catch (e) { next(e); }
});

app.put('/admin/products/:id', async (req, res, next) => {
  try {
    const b = req.body;
    await db.run(`UPDATE products SET name=?, category=?, price=?, old_price=?, stock=?, short_desc=?, description=?, image=?, is_active=? WHERE id=?`,
      [(b.name || '').trim(), b.category || '', +b.price || 0, +b.old_price || 0, +b.stock || 0,
        b.short_desc || '', b.description || '', b.image || '', b.is_active ? 1 : 0, req.params.id]);
    res.redirect('/admin/products');
  } catch (e) { next(e); }
});

app.delete('/admin/products/:id', async (req, res, next) => {
  try {
    await db.run('DELETE FROM products WHERE id=?', [req.params.id]);
    res.redirect('/admin/products');
  } catch (e) { next(e); }
});

app.patch('/admin/products/:id/toggle', async (req, res, next) => {
  try {
    await db.run('UPDATE products SET is_active = 1 - is_active WHERE id=?', [req.params.id]);
    res.redirect('/admin/products');
  } catch (e) { next(e); }
});

// ---- contact messages ----
app.get('/admin/messages', async (req, res, next) => {
  try {
    const messages = await db.all('SELECT * FROM contact_messages ORDER BY id DESC');
    res.render('admin/messages', { messages });
  } catch (e) { next(e); }
});

app.patch('/admin/messages/:id', async (req, res, next) => {
  try {
    await db.run('UPDATE contact_messages SET status=? WHERE id=?', [req.body.status, req.params.id]);
    res.redirect('/admin/messages');
  } catch (e) { next(e); }
});

app.delete('/admin/messages/:id', async (req, res, next) => {
  try {
    await db.run('DELETE FROM contact_messages WHERE id=?', [req.params.id]);
    res.redirect('/admin/messages');
  } catch (e) { next(e); }
});

// ---- settings / change password ----
app.get('/admin/settings', (req, res) => res.render('admin/settings', { msg: '' }));

app.post('/admin/settings/password', async (req, res, next) => {
  try {
    const { new_password } = req.body;
    if (!new_password || new_password.length < 6) {
      return res.render('admin/settings', { msg: 'رمز جدید باید حداقل ۶ کاراکتر باشد.' });
    }
    await db.changeAdminPassword(new_password);
    res.render('admin/settings', { msg: 'رمز عبور با موفقیت تغییر کرد.' });
  } catch (e) { next(e); }
});

// ---- error handlers ----
app.use((req, res) => res.status(404).render('404'));
app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).send('خطای سرور: ' + err.message);
});

// اتصال به دیتابیس (MariaDB طبق config.env، در غیر این صورت SQLite محلی)
db.init().then(() => {
  app.listen(PORT, () => console.log(`Toostamir site running at http://localhost:${PORT} (driver: ${db.driver})`));
}).catch(err => {
  console.error('Fatal DB init error:', err);
  process.exit(1);
});
