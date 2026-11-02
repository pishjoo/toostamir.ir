const express = require('express');
const session = require('express-session');
const methodOverride = require('method-override');
const path = require('path');
const { db, verifyAdmin, changeAdminPassword } = require('./db');

const app = express();
const PORT = process.env.PORT || 3000;

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(methodOverride('_method'));
app.use('/public', express.static(path.join(__dirname, 'public')));
app.use(session({
  secret: process.env.SESSION_SECRET || 'toostamir-secret-change-me',
  resave: false,
  saveUninitialized: true
}));

// expose current path to views (for admin sidebar active state)
app.use((req, res, next) => { res.locals.path = req.path; next(); });

// price helper (تومان) + Persian digits
function toman(n) {
  return Number(n || 0).toLocaleString('fa-IR');
}
app.locals.toman = toman;
app.locals.year = new Date().getFullYear();
app.locals.db = db;

function requireAdmin(req, res, next) {
  if (req.session && req.session.admin) return next();
  res.redirect('/admin/login');
}

// ===================== PUBLIC SITE =====================

app.get('/', (req, res) => {
  const products = db.prepare('SELECT * FROM products WHERE is_active=1 ORDER BY id DESC LIMIT 8').all();
  res.render('index', { products });
});

app.get('/products', (req, res) => {
  const q = (req.query.q || '').trim();
  const cat = (req.query.cat || '').trim();
  let rows = db.prepare('SELECT * FROM products WHERE is_active=1 ORDER BY id DESC').all();
  if (q) rows = rows.filter(p => (p.name + p.short_desc + p.description).includes(q));
  if (cat) rows = rows.filter(p => p.category === cat);
  const categories = db.prepare("SELECT DISTINCT category FROM products WHERE is_active=1 AND category<>''").all().map(r => r.category);
  res.render('products', { products: rows, categories, q, cat });
});

app.get('/product/:id', (req, res) => {
  const p = db.prepare('SELECT * FROM products WHERE id=? AND is_active=1').get(req.params.id);
  if (!p) return res.status(404).render('404');
  res.render('product', { p });
});

app.get('/services', (req, res) => res.render('services'));
app.get('/warranty', (req, res) => res.render('warranty'));

// ---- contact form ----
app.get('/contact', (req, res) => res.render('contact', { sent: false, error: '', subject: req.query.subject || '' }));

app.post('/contact', (req, res) => {
  const { name, phone, email, subject, message } = req.body;
  if (!name || !phone) {
    return res.render('contact', { sent: false, error: 'نام و شماره تماس الزامی است.', subject: subject || '' });
  }
  db.prepare('INSERT INTO contact_messages (name, phone, email, subject, message) VALUES (?,?,?,?,?)')
    .run(name, phone, email || '', subject || '', message || '');
  res.render('contact', { sent: true, error: '' });
});

// ===================== ADMIN PANEL =====================

app.get('/admin/login', (req, res) => res.render('admin/login', { error: '' }));

app.post('/admin/login', (req, res) => {
  const { username, password } = req.body;
  if (verifyAdmin(username, password)) {
    req.session.admin = username;
    return res.redirect('/admin');
  }
  res.render('admin/login', { error: 'نام کاربری یا رمز عبور اشتباه است.' });
});

app.get('/admin/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/'));
});

app.use('/admin', requireAdmin);

app.get('/admin', (req, res) => {
  const stats = {
    products: db.prepare('SELECT COUNT(*) c FROM products').get().c,
    active: db.prepare('SELECT COUNT(*) c FROM products WHERE is_active=1').get().c,
    messages: db.prepare("SELECT COUNT(*) c FROM contact_messages WHERE status='new'").get().c,
    lowStock: db.prepare('SELECT COUNT(*) c FROM products WHERE is_active=1 AND stock<=2').get().c
  };
  const recent = db.prepare('SELECT * FROM products ORDER BY id DESC LIMIT 5').all();
  res.render('admin/dashboard', { stats, recent });
});

// ---- products CRUD ----
app.get('/admin/products', (req, res) => {
  const products = db.prepare('SELECT * FROM products ORDER BY id DESC').all();
  res.render('admin/products', { products });
});

app.get('/admin/products/new', (req, res) => {
  res.render('admin/product_form', { p: null, error: '' });
});

app.post('/admin/products', (req, res) => {
  const b = req.body;
  if (!b.name || !b.name.trim()) {
    return res.render('admin/product_form', { p: b, error: 'نام محصول الزامی است.' });
  }
  db.prepare(`INSERT INTO products (name, category, price, old_price, stock, short_desc, description, image, is_active)
    VALUES (?,?,?,?,?,?,?,?,?)`)
    .run(b.name.trim(), b.category || '', +b.price || 0, +b.old_price || 0, +b.stock || 0,
      b.short_desc || '', b.description || '', b.image || '', b.is_active ? 1 : 0);
  res.redirect('/admin/products');
});

app.get('/admin/products/:id/edit', (req, res) => {
  const p = db.prepare('SELECT * FROM products WHERE id=?').get(req.params.id);
  if (!p) return res.redirect('/admin/products');
  res.render('admin/product_form', { p, error: '' });
});

app.put('/admin/products/:id', (req, res) => {
  const b = req.body;
  db.prepare(`UPDATE products SET name=?, category=?, price=?, old_price=?, stock=?, short_desc=?, description=?, image=?, is_active=? WHERE id=?`)
    .run((b.name || '').trim(), b.category || '', +b.price || 0, +b.old_price || 0, +b.stock || 0,
      b.short_desc || '', b.description || '', b.image || '', b.is_active ? 1 : 0, req.params.id);
  res.redirect('/admin/products');
});

app.delete('/admin/products/:id', (req, res) => {
  db.prepare('DELETE FROM products WHERE id=?').run(req.params.id);
  res.redirect('/admin/products');
});

app.patch('/admin/products/:id/toggle', (req, res) => {
  db.prepare('UPDATE products SET is_active = 1 - is_active WHERE id=?').run(req.params.id);
  res.redirect('/admin/products');
});

// ---- contact messages ----
app.get('/admin/messages', (req, res) => {
  const messages = db.prepare('SELECT * FROM contact_messages ORDER BY id DESC').all();
  res.render('admin/messages', { messages });
});

app.patch('/admin/messages/:id', (req, res) => {
  db.prepare('UPDATE contact_messages SET status=? WHERE id=?').run(req.body.status, req.params.id);
  res.redirect('/admin/messages');
});

app.delete('/admin/messages/:id', (req, res) => {
  db.prepare('DELETE FROM contact_messages WHERE id=?').run(req.params.id);
  res.redirect('/admin/messages');
});

// ---- settings / change password ----
app.get('/admin/settings', (req, res) => res.render('admin/settings', { msg: '' }));

app.post('/admin/settings/password', (req, res) => {
  const { new_password } = req.body;
  if (!new_password || new_password.length < 6) {
    return res.render('admin/settings', { msg: 'رمز جدید باید حداقل ۶ کاراکتر باشد.' });
  }
  changeAdminPassword(new_password);
  res.render('admin/settings', { msg: 'رمز عبور با موفقیت تغییر کرد.' });
});

// ---- 404 ----
app.use((req, res) => res.status(404).render('404'));

app.listen(PORT, () => console.log(`Toostamir site running at http://localhost:${PORT}`));
