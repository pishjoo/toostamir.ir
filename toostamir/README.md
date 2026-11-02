# تعمیرامیر — toostamir.ir

سایت فروشگاهی/خدماتی فارسی (RTL) با پنل مدیریت محصولات، فرم تماس/استعلام و صفحه گارانتی تعمیرات.
- استعلام: تلفنی + فرم تماس (پیام‌ها در پنل => «پیام‌های تماس» قابل مشاهده است)
- تصاویر محصولات: Placeholder (با وارد کردن آدرس تصویر در پنل جایگزین می‌شود)
- زرین‌پال: هنوز متصل نیست (در حال انجام) — پیام آن در سایت نمایش داده شده است
- اینستاگرام: @toostamir

## تکنولوژی
Node.js + Express + EJS + MariaDB/MySQL (درایور `mariadb`).
برای توسعه محلی، اگر تنظیمات دیتابیس نباشد خودکار روی SQLite می‌افتد.

## اجرای محلی
```bash
npm install
cp config.env.example config.env   # یا حذف کنید تا SQLite استفاده شود
npm start                          # http://localhost:3000
```
ورود پنل `/admin` : `admin` / `admin123` (از تنظیمات تغییرش دهید).

---

## راه‌اندازی روی هاست اشتراکی لینوکس (cPanel)

مشخصات سرور شما: MariaDB 11.x ، charset پیش‌فرض cp1252.
کد به‌صورت خودکار اتصال را روی **utf8mb4** تنظیم می‌کند تا متن فارسی سالم بماند.

### ۱) ساخت دیتابیس در cPanel
1. cPanel → **MySQL® Databases**
2. ساخت دیتابیس (مثلاً `USER_toostamir`)
3. ساخت یوزر دیتابیس (مثلاً `USER_admin`) با رمز قوی
4. Add User To Database → تیک **ALL PRIVILEGES**

### ۲) آپلود کد
- فایل‌های این پوشه را (بدون `node_modules`) به مثلاً `~/toostamir_app` آپلود کنید (File Manager یا Git).
- در File Manager فایل `config.env.example` را به `config.env` تغییر نام دهید و مقادیر DB_NAME/DB_USER/DB_PASS را با اطلاعات مرحله ۱ پر کنید.

### ۳) نصب پکیج‌ها (مهم)
در Terminal cPanel:
```bash
cd ~/toostamir_app
npm install --omit=dev --no-optional
```
> **حتماً فلگ `--no-optional` را بگذارید:** پکیج اختیاری `better-sqlite3` فقط برای توسعه محلی است و روی هاست اشتراکی (که دسترسی apt/yum ندارد) بیلد آن ناموفق می‌ماند؛ با این فلگ npm آن را نادیده می‌گیرد و نصب موفق و سریع انجام می‌شود. سایت روی همان MariaDB کار می‌کند.

### ۴) ساخت اپ Node.js
- cPanel → **Setup Node.js App** (Application Manager)
- Create Application:
  - Node.js version: 18 یا بالاتر
  - Application root: `toostamir_app`
  - Application URL: `toostamir.ir` (یا subdomain)
  - Entry point: `server.js`
- پس از ساخت، در Terminal همان بخش:
```bash
source ~/nodevenv/toostamir_app/18/bin/activate
```

- روی **Restart App** کلیک کنید. لاگ باید شامل `[DB] Connected to MariaDB/MySQL` باشد.

### ۵) دامنه
اگر Application URL را روی `toostamir.ir` ست کرده باشید، cPanel خودش پروکسی را تنظیم می‌کند.
در غیر این صورت فایل `.htaccess` موجود (پروکسی به پورت 3000 + هدایت HTTPS) را داخل `public_html` کپی کنید.

### ۶) بعد از اولین ورود
- در `/admin/settings` رمز پیش‌فرض (`admin123`) را عوض کنید.
- یک `SESSION_SECRET` تصادفی در `config.env` بگذارید.

## ساختار جداول (MariaDB)
- `products` — محصولات (نام، دسته، قیمت، قیمت قبلی، موجودی، توضیحات، تصویر، فعال/غیرفعال)
- `contact_messages` — پیام‌های فرم تماس/استعلام
- `settings` — تنظیمات (نام سایت، حساب ادمین با هش scrypt)

جدول‌ها خودکار در اولین اجرا ساخته و با ۴ محصول نمونه (تصویر Placeholder) پر می‌شوند.

## زرین‌پال (به‌زودی)
پس از دریافت مرچنت‌کد، می‌توان درگاه را به مسیر checkout اضافه کرد؛ فعلاً سفارش/استعلام از طریق فرم تماس و تلفن هماهنگ می‌شود.
