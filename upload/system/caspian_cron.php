<?php
// کرون‌جاب بک‌آپ‌گیر خودکار کاسپین مارکت

// لود کردن تنظیمات اپن کارت (چون در پوشه system هستیم، یک پوشه عقب می‌رویم)
require_once('../config.php');
require_once(DIR_SYSTEM . 'startup.php');

// رجیستری اپن کارت
 $registry = new Registry();

 $loader = new Loader($registry);
 $registry->set('load', $loader);

 $config = new Config();
 $registry->set('config', $config);

 $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
 $registry->set('db', $db);

// خواندن تنظیمات فروشگاه از دیتابیس
 $query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");
foreach ($query->rows as $setting) {
    if (!$setting['serialized']) {
        $config->set($setting['key'], $setting['value']);
    } else {
        $config->set($setting['key'], json_decode($setting['value'], true));
    }
}

// بررسی امنیتی توکن
 $cron_token = md5('caspian_cron_salt_' . DB_DATABASE);
if (!isset($_GET['token']) || $_GET['token'] !== $cron_token) {
    die('Access Denied!');
}

// لود کردن موتور بک‌آپ
require_once(DIR_SYSTEM . 'library/caspian_backup_engine.php');
 $engine = new CaspianBackupEngine($registry);

// گرفتن دامنه فعلی و ساخت بک‌آپ
 $domain = $config->get('config_url');
 $engine->createBackup($domain);

echo "Caspian Backup Created Successfully at " . date('Y-m-d H:i:s');