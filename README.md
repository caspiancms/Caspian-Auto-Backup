# 🛡️ Caspian Auto Backup & Recovery System | CaspianCMS

An enterprise-grade OpenCart extension for automated backups and instant disaster recovery. Features AES-256 database encryption, a "Nuke and Pave" restoration process to completely wipe hacker backdoors, and domain-locked security.

یک افزونه سازمانی برای اپن‌کارت جهت بک‌آپ‌گیری خودکار و بازیابی فوری در شرایط فاجعه‌بار (Disaster Recovery). دارای رمزنگاری دیتابیس (AES-256)، سیستم بازگردانی نابودی کامل (Nuke and Pave) برای پاکسازی کامل درهای پشتی هکرها، و امنیت لایسنس‌گذاری روی دامنه.

---

## 🇬🇧 English

### 📝 Description

This extension automatically backs up your entire OpenCart website (files + database) and keeps the last 5 backups. If your site is hacked, you upload the latest backup package, run `restore.php`, and the system completely destroys all current infected files and database tables, then rebuilds your store from the clean backup in seconds.

### ✨ Key Features

- **Automated Cron Job Backups** — a secure PHP-CLI command is generated for your host's Cron Job to take daily automatic backups.
- **Smart Rotation Policy** — automatically keeps only the latest 5 backups and deletes older ones to save server space.
- **AES-256 Database Encryption** — the database is encrypted into `database.enc`; hackers cannot import it directly via phpMyAdmin.
- **Disaster Recovery (Nuke & Pave)** — `restore.php` deletes all current files (including hidden hacker backdoors) and drops all database tables, then extracts the clean backup.
- **Domain-Locked Recovery** — the recovery package is locked to the original domain. If a hacker steals the backup and tries to restore it elsewhere, they need a license key.
- **Dynamic URL & Folder Rebuild** — automatically updates `config_url` in the database and recreates missing essential folders (`logs`, `session`, `cache`) during recovery to prevent OpenCart errors.
- **Real Domain Detection** — licenses are generated from the actual browser URL, ensuring unique licenses for subfolders or subdomains.

### 📂 Folder Structure

```
upload/
├── admin/
│   ├── controller/extension/module/
│   │   └── caspian_backup.php        # Admin Controller (UI & Manual Backup)
│   ├── language/
│   │   ├── en-gb/extension/module/
│   │   │   └── caspian_backup.php    # English Language
│   │   └── fa-ir/extension/module/
│   │       └── caspian_backup.php    # Persian Language
│   └── view/template/extension/module/
│       └── caspian_backup.twig       # Admin Dashboard UI
└── system/
    ├── caspian_cron.php              # Secure Cron Job Executable
    └── library/
        └── caspian_backup_engine.php # Core Engine (Backup & Restore Logic)
```

### 🚀 Installation

1. Upload the contents of the `upload` folder to your OpenCart root directory.
2. Go to **Admin Panel > Extensions > Extensions > Modules**.
3. Install **"Caspian Backup & Recovery | CaspianCMS"**.
4. Click **Edit** to access the dashboard.

### 🛠️ How to Use

- **Automatic Backup**: Copy the "Cron Job Command" from the dashboard and paste it into your hosting control panel's Cron Jobs section (set to run once a day).
- **Manual Backup**: Click the "Create Manual Backup" button.
- **Recovering a Hacked Site**:
  1. Download the latest backup ZIP file.
  2. Upload it to the infected host's root folder.
  3. Extract it (this places `restore.php`, `backup_files.zip`, and `database.enc` in the root).
  4. Open your browser and go to `your-domain.com/restore.php`.
  5. Click "Start Recovery." Your site will be fully restored and cleaned in seconds.

---

## 🇮🇷 فارسی

### 📝 توضیحات

این افزونه به‌صورت خودکار از کل سایت اپن‌کارت (فایل‌ها + دیتابیس) بک‌آپ می‌گیرد و ۵ بک‌آپ آخر را نگه می‌دارد. اگر سایت شما هک شد، کافی‌ست آخرین پکیج بک‌آپ را آپلود کرده و فایل `restore.php` را اجرا کنید. سیستم تمام فایل‌ها و دیتابیس آلوده فعلی را کاملاً نابود کرده و سایت را از روی نسخه سالم در عرض چند ثانیه بازسازی می‌کند.

### ✨ ویژگی‌های کلیدی

- **بک‌آپ‌گیری خودکار (Cron Job)**: یک دستور امن PHP-CLI برای تنظیم در بخش Cron Job هاست شما تولید می‌شود تا بک‌آپ‌گیری به‌صورت روزانه و خودکار انجام شود.
- **مدیریت هوشمند فضای هاست (Rotation)**: سیستم به‌صورت خودکار فقط ۵ بک‌آپ آخر را نگه داشته و بک‌آپ‌های قدیمی‌تر را پاک می‌کند.
- **رمزنگاری دیتابیس (AES-256)**: دیتابیس به `database.enc` تبدیل می‌شود؛ هکرها نمی‌توانند آن را مستقیماً در phpMyAdmin ایمپورت کنند.
- **بازیابی فاجعه (Nuke and Pave)**: اسکریپت `restore.php` تمام فایل‌های فعلی (از جمله درهای پشتی مخفی هکر) را کاملاً پاک کرده و جداول دیتابیس آلوده را Drop می‌کند، سپس فایل‌های سالم را جایگزین می‌کند.
- **قفل دامنه در بازیابی**: پکیج بازیابی روی دامنه اصلی قفل می‌شود. اگر هکر بک‌آپ را بدزدد و بخواهد روی دامنه خودش بازیابی کند، سیستم از او کد لایسنس می‌خواهد.
- **بازسازی هوشمند آدرس‌ها و پوشه‌ها**: در زمان بازیابی، `config_url` در دیتابیس به مسیر فعلی آپدیت شده و پوشه‌های ضروری (`logs`، `session`، `cache`) ساخته می‌شوند تا اپن‌کارت بدون خطا بالا بیاید.
- **تشخیص دامنه واقعی**: کدهای لایسنس بر اساس آدرس واقعی مرورگر ساخته می‌شوند تا برای پوشه‌ها یا ساب‌دامین‌های مختلف، کد یکسان تولید نشود.

### 📂 ساختار پوشه‌ها

```
upload/
├── admin/
│   ├── controller/extension/module/
│   │   └── caspian_backup.php        # کنترلر پنل مدیریت (رابط کاربری و بک‌آپ دستی)
│   ├── language/
│   │   ├── en-gb/extension/module/
│   │   │   └── caspian_backup.php    # زبان انگلیسی
│   │   └── fa-ir/extension/module/
│   │       └── caspian_backup.php    # زبان فارسی
│   └── view/template/extension/module/
│       └── caspian_backup.twig       # رابط کاربری پنل مدیریت
└── system/
    ├── caspian_cron.php              # فایل اجرایی امن برای کرون‌جاب
    └── library/
        └── caspian_backup_engine.php # موتور اصلی (منطق بک‌آپ‌گیری و بازیابی)
```

### 🚀 نصب افزونه

1. محتویات داخل پوشه `upload` را در روت هاست اپن‌کارت خود آپلود کنید.
2. وارد پنل مدیریت اپن‌کارت شوید > **افزونه‌ها > افزونه‌ها > ماژول‌ها**.
3. افزونه **"Caspian Backup & Recovery | CaspianCMS"** را نصب کنید.
4. روی **ویرایش** کلیک کنید تا وارد پنل مدیریت شوید.

### 🛠️ نحوه استفاده

- **بک‌آپ خودکار**: دستور «کرون‌جاب» را از پنل مدیریت کپی کرده و در بخش Cron Jobs هاست خود قرار دهید (پیشنهاد: روزی یک‌بار).
- **بک‌آپ دستی**: دکمه «ساخت بک‌آپ دستی» را بزنید.
- **بازیابی سایت هک‌شده**:
  1. آخرین فایل زیپ بک‌آپ را دانلود کنید.
  2. آن را در روت هاست آلوده خود آپلود کنید.
  3. فایل زیپ را خارج از حالت فشرده کنید (این کار فایل‌های `restore.php`، `backup_files.zip` و `database.enc` را در روت قرار می‌دهد).
  4. مرورگر خود را باز کرده و به آدرس `your-domain.com/restore.php` بروید.
  5. روی «شروع بازیابی» کلیک کنید. سایت شما در عرض چند ثانیه کاملاً بازیابی و پاکسازی می‌شود.

---

Developed with ❤️ by **CaspianCMS**
