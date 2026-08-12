🛡️ Caspian Auto Backup & Recovery System | CaspianCMS

An enterprise-grade OpenCart extension for automated backups and instant disaster recovery. Features AES-256 database encryption, "Nuke and Pave" restoration to completely wipe hacker backdoors, and domain-locked security.

یک افزونه سازمانی برای اپن‌کارت جهت بک‌آپ‌گیری خودکار و بازیابی فوری در شرایط فاجعه‌بار (Disaster Recovery). دارای رمزنگاری دیتابیس (AES-256)، سیستم بازگردانی نابودی کامل (Nuke and Pave) برای پاکسازی کامل درهای پشتی هکرها، و امنیت لایسنس‌گذاری روی دامنه.

🇬🇧 English | 🇮🇷 فارسی
🇬🇧 English
📝 Description

This extension automatically backs up your entire OpenCart website (files + database) and stores the last 5 backups. If your website gets hacked, you simply upload the latest backup package, run restore.php, and the system will completely destroy all current infected files and database, and rebuild your store from the clean backup in seconds.
✨ Key Features

    Automated Cron Job Backups: A secure PHP-CLI command is generated for your host's Cron Job to take daily automatic backups.
    Smart Rotation Policy: Automatically keeps only the latest 5 backups and deletes older ones to save server space.
    AES-256 Database Encryption: The database is encrypted into database.enc. Hackers cannot import it manually via phpMyAdmin.
    Disaster Recovery (Nuke & Pave): The restore.php script completely deletes all current files (including hidden hacker backdoors) and drops all database tables, then extracts the clean backup.
    Domain-Locked Recovery: The recovery package is locked to the original domain. If a hacker steals the backup and tries to restore it on their own domain, they will need a license key.
    Dynamic URL & Folder Rebuild: Automatically updates config_url in the database and recreates missing essential folders (logs, session, cache) during recovery to prevent OpenCart errors.
    Real Domain Detection: Generates licenses based on the actual browser URL, ensuring unique licenses for subfolders or subdomains.

📂 Folder Structure

upload/
├── admin/
│   ├── controller/extension/module/
│   │   └── caspian_backup.php      # Admin Controller (UI & Manual Backup)
│   ├── language/
│   │   ├── en-gb/extension/module/
│   │   │   └── caspian_backup.php    # English Language
│   │   └── fa-ir/extension/module/
│   │       └── caspian_backup.php    # Persian Language
│   └── view/template/extension/module/
│       └── caspian_backup.twig      # Admin Dashboard UI
└── system/
    ├── caspian_cron.php             # Secure Cron Job Executable
    └── library/
        └── caspian_backup_engine.php # Core Engine (Backup & Restore Logic)

🚀 Installation

    Upload the contents of the upload folder to your OpenCart root directory.
    Go to Admin Panel > Extensions > Extensions > Modules.
    Install "Caspian Backup & Recovery | CaspianCMS".
    Click "Edit" to access the dashboard.

🛠️ How to Use

    Automatic Backup: Copy the "Cron Job Command" from the dashboard and paste it into your hosting control panel's Cron Jobs section (set to run once a day).
    Manual Backup: Click the "Create Manual Backup" button.
    How to Recover a Hacked Site: 
         Download the latest backup ZIP file.
         Upload it to your infected host's root folder.
         Extract it. (This will place restore.php, backup_files.zip, and database.enc in the root).
         Open your browser and go to your-domain.com/restore.php.
         Click "Start Recovery". Your site will be fully restored and cleaned in seconds.

🇮🇷 فارسی
📝 توضیحات

این افزونه به صورت خودکار از کل سایت اپن‌کارت (فایل‌ها + دیتابیس) بک‌آپ می‌گیرد و ۵ بک‌آپ آخر را نگه می‌دارد. اگر سایت شما هک شد، کافیست آخرین پکیج بک‌آپ را آپلود کرده و فایل restore.php را اجرا کنید. سیستم تمام فایل‌ها و دیتابیس‌های آلوده فعلی را کاملاً نابود کرده و سایت را از روی نسخه سالم در عرض چند ثانیه بازسازی می‌کند.
✨ ویژگی‌های کلیدی

     بک‌آپ‌گیری خودکار (Cron Job): یک دستور امن PHP-CLI برای تنظیم در بخش Cron Job هاست شما تولید می‌شود تا بک‌آپ‌گیری به صورت روزانه و خودکار انجام شود.
     مدیریت هوشمند فضای هاست (Rotation): سیستم به صورت خودکار فقط ۵ بک‌آپ آخر را نگه داشته و بک‌آپ‌های قدیمی‌تر را پاک می‌کند تا هاست شما پر نشود.
     رمزنگاری دیتابیس (AES-256): دیتابیس به database.enc تبدیل می‌شود. هکرها نمی‌توانند آن را مستقیماً در phpMyAdmin ایمپورت کنند.
     بازیابی فاجعه (Nuke and Pave): اسکریپت restore.php تمام فایل‌های فعلی (از جمله درهای پشتی مخفی هکر) را کاملاً پاک کرده و جداول دیتابیس آلوده را Drop می‌کند، سپس فایل‌های سالم را جایگزین می‌کند.
     قفل دامنه در بازیابی: پکیج بازیابی روی دامنه اصلی قفل می‌شود. اگر هکر بک‌آپ را بدزدد و بخواهد روی دامنه خودش بازیابی کند، سیستم از او کد لایسنس می‌خواهد.
     بازسازی هوشمند آدرس‌ها و پوشه‌ها: در زمان بازیابی، آدرس‌های دیتابیس (config_url) را به مسیر فعلی آپدیت کرده و پوشه‌های ضروری (logs، session، cache) را می‌سازد تا اپن کارت بدون خطا بالا بیاید.
     تشخیص دامنه واقعی: کدهای لایسنس بر اساس آدرس واقعی مرورگر ساخته می‌شوند تا برای پوشه‌ها یا ساب‌دامین‌های مختلف کدهای یکسانی تولید نشود.

📂 ساختار پوشه‌ها
text
 
  
 
 
upload/
├── admin/
│   ├── controller/extension/module/
│   │   └── caspian_backup.php       # کنترلر پنل مدیریت (رابط کاربری و بک‌آپ دستی)
│   ├── language/
│   │   ├── en-gb/extension/module/
│   │   │   └── caspian_backup.php   # زبان انگلیسی
│   │   └── fa-ir/extension/module/
│   │       └── caspian_backup.php   # زبان فارسی
│   └── view/template/extension/module/
│       └── caspian_backup.twig      # رابط کاربری پنل مدیریت
└── system/
    ├── caspian_cron.php             # فایل اجرایی امن برای کرون‌جاب
    └── library/
        └── caspian_backup_engine.php # موتور اصلی (منطق بک‌آپ‌گیری و بازیابی)
 
 
🚀 نصب افزونه

    محتویات داخل پوشه upload را در روت هاست اپن‌کارت خود آپلود کنید.
    وارد پنل مدیریت اپن‌کارت شوید > افزونه‌ها > افزونه‌ها > ماژول‌ها.
    افزونه "Caspian Backup & Recovery | CaspianCMS" را نصب کنید.
    روی "ویرایش" کلیک کنید تا وارد پنل مدیریت شوید.

🛠️ نحوه استفاده

    بک‌آپ خودکار: دستور "کرون‌جاب" را از پنل مدیریت کپی کرده و در بخش Cron Jobs هاست خود قرار دهید (پیشنهاد: روزی یک بار).
    بک‌آپ دستی: دکمه "ساخت بک‌آپ دستی" را بزنید.
    بازیابی سایت هک شده:
         آخرین فایل زیپ بک‌آپ را دانلود کنید.
         آن را در روت هاست آلوده خود آپلود کنید.
         فایل زیپ را از حالت فشرده خارج کنید. (این کار فایل‌های restore.php، backup_files.zip و database.enc را در روت قرار می‌دهد).
         مرورگر خود را باز کرده و به آدرس your-domain.com/restore.php بروید.
         روی "شروع بازیابی" کلیک کنید. سایت شما در عرض چند ثانیه کاملاً بازیابی و پاکسازی می‌شود.

Developed with ❤️ by CaspianCMS