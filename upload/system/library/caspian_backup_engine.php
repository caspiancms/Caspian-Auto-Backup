<?php
class CaspianBackupEngine {
    private $secret_key = 'C@sp!an_CMS_S3cr3t_K3y';
    private $max_backups = 5;
    private $backup_dir;
    private $root_dir;

    public function __construct($registry) {
        $this->backup_dir = DIR_SYSTEM . 'storage/backups/';
        $this->root_dir = str_replace('\\', '/', realpath(DIR_SYSTEM . '../')) . '/';
        
        if (!is_dir($this->backup_dir)) {
            @mkdir($this->backup_dir, 0755, true);
        }
    }

    // پاکسازی دامنه برای تطابق دقیق در فایل restore.php
    private function cleanDomain($url) {
        $domain = preg_replace('#^https?://#', '', $url);
        $domain = str_replace('www.', '', $domain);
        $domain = rtrim($domain, '/\\');
        $domain = strtolower($domain);
        return $domain;
    }

    // متد اصلی ساخت بک‌آپ
    public function createBackup($locked_domain) {
        // پاکسازی دامنه قبل از تزریق به فایل بازیابی
        $locked_domain = $this->cleanDomain($locked_domain);

        // انتقال پوشه موقت به حافظه موقت سیستم عامل تا در زمان زیپ کردن تداخل ایجاد نکند
        $temp_dir = sys_get_temp_dir() . '/caspian_backup_' . time();
        if (is_dir($temp_dir)) {
            $this->deleteDir($temp_dir);
        }
        mkdir($temp_dir, 0755, true);

        // ۱. استخراج و رمزنگاری دیتابیس
        $db_file = $temp_dir . '/database.sql';
        $this->exportDatabase($db_file);
        $enc_file = $temp_dir . '/database.enc';
        $this->encryptDatabaseFile($db_file, $enc_file);
        unlink($db_file);

        // ۲. فشرده‌سازی فایل‌های سایت
        $files_zip = $temp_dir . '/backup_files.zip';
        $this->createFilesZip($this->root_dir, $files_zip);

        // ۳. ساخت فایل restore.php (موتور بازیابی)
        $restore_php = $temp_dir . '/restore.php';
        file_put_contents($restore_php, $this->getRestoreScript($locked_domain));

        // ۴. قرار دادن همه در یک فایل زیپ نهایی با فرمت تاریخ خواناتر
        $backup_filename = 'caspian_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $final_zip = $this->backup_dir . $backup_filename;
        
        $zip = new ZipArchive();
        if ($zip->open($final_zip, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($enc_file, 'database.enc');
            $zip->addFile($files_zip, 'backup_files.zip');
            $zip->addFile($restore_php, 'restore.php');
            $zip->close();
        }

        // ۵. پاکسازی پوشه موقت
        $this->deleteDir($temp_dir);

        // ۶. مدیریت چرخش بک‌آپ‌ها (نگهداری ۵ بک‌آپ آخر)
        $this->rotateBackups();

        return $backup_filename;
    }

    private function exportDatabase($file) {
        $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        $tables = $db->query("SHOW TABLES FROM `" . DB_DATABASE . "`")->rows;
        
        $fp = fopen($file, 'w');
        fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($fp, "SET time_zone = '+00:00';\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($fp, "SET NAMES utf8mb4;\n\n");

        foreach ($tables as $table) {
            $table_name = current($table);
            fwrite($fp, "DROP TABLE IF EXISTS `$table_name`;\n");
            $create = $db->query("SHOW CREATE TABLE `$table_name`")->row;
            fwrite($fp, $create['Create Table'] . ";\n");
            
            $rows = $db->query("SELECT * FROM `$table_name`")->rows;
            foreach ($rows as $row) {
                $values = array();
                foreach (array_values($row) as $val) {
                    if (is_null($val)) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $db->escape($val) . "'";
                    }
                }
                fwrite($fp, "INSERT INTO `$table_name` VALUES (" . implode(", ", $values) . ");\n");
            }
            fwrite($fp, "\n");
        }
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($fp);
    }

    private function encryptDatabaseFile($plain_file, $enc_file) {
        $sql_content = file_get_contents($plain_file);
        $key = hash('sha256', $this->secret_key, true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($sql_content, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        file_put_contents($enc_file, $iv . $encrypted);
    }

    private function createFilesZip($source, $destination) {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            // فقط پوشه‌های خطرناک یا بی‌استفاده مستثنی می‌شوند
            $excluded_dirs = [
                $source . 'system/storage/logs',
                $source . 'system/storage/session',
                $source . 'system/storage/backups',
                $source . '.git'
            ];

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $file_path = str_replace('\\', '/', $file->getRealPath());
                    $skip = false;
                    
                    foreach ($excluded_dirs as $ex_dir) {
                        if (strpos($file_path, $ex_dir) === 0) {
                            $skip = true;
                            break;
                        }
                    }
                    
                    if (!$skip) {
                        $relative_path = substr($file_path, strlen($source));
                        $zip->addFile($file_path, $relative_path);
                    }
                }
            }
            $zip->close();
        }
    }

    private function rotateBackups() {
        $files = glob($this->backup_dir . 'caspian_backup_*.zip');
        if (count($files) > $this->max_backups) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $files_to_delete = array_slice($files, 0, count($files) - $this->max_backups);
            foreach ($files_to_delete as $file) {
                @unlink($file);
            }
        }
    }

    private function deleteDir($dir) {
        if (!is_dir($dir)) return;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

        // تولید کدهای فایل restore.php
    private function getRestoreScript($locked_domain) {
        $template = <<<'EOT'
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

 $locked_domain = '%%LOCKED_DOMAIN%%';
 $secret_key = '%%SECRET_KEY%%';
 $valid_dev_hash = md5('DEV_ROLE' . $secret_key);
 $valid_master_hash = md5('MASTER_SUPER_ADMIN' . $secret_key);

// --- زبان‌ها ---
 $languages = [
    "en" => [
        "title" => "Caspian Recovery System", "heading" => "Disaster Recovery",
        "desc" => "This operation will completely destroy all current files and database, and replace them with the clean backup.",
        "btn_restore" => "Start Recovery (Nuke & Restore)",
        "confirm" => "Are you sure? All current files will be permanently deleted!",
        "license_heading" => "License Verification",
        "license_placeholder" => "Enter your license code",
        "license_btn" => "Verify License",
        "license_error" => "Invalid license code.",
        "error_domain" => "This recovery package is locked to another domain. Current: %s",
        "success" => "Recovery Successful!",
        "success_desc" => "Your website has been fully restored and cleaned. You can now log in to the admin panel.",
        "btn_admin" => "Go to Admin Panel", "view_site" => "View Storefront"
    ],
    "fa" => [
        "title" => "سیستم بازیابی کاسپین", "heading" => "بازیابی فاجعه (Disaster Recovery)",
        "desc" => "این عملیات تمام فایل‌ها و دیتابیس فعلی را کاملاً پاک کرده و نسخه سالم را جایگزین می‌کند.",
        "btn_restore" => "شروع بازیابی (Nuke & Restore)",
        "confirm" => "آیا مطمئن هستید؟ تمام فایل‌های فعلی پاک خواهند شد!",
        "license_heading" => "تایید لایسنس",
        "license_placeholder" => "کد لایسنس را وارد کنید",
        "license_btn" => "بررسی و تایید",
        "license_error" => "کد لایسنس نامعتبر است.",
        "error_domain" => "این پکیج بازیابی برای دامنه دیگری قفل شده است. دامنه فعلی: %s",
        "success" => "بازیابی با موفقیت انجام شد!",
        "success_desc" => "سایت شما به طور کامل بازیابی و پاکسازی شد. اکنون می‌توانید وارد پنل مدیریت شوید.",
        "btn_admin" => "ورود به پنل مدیریت", "view_site" => "مشاهده فروشگاه"
    ]
];
 $selected_lang = isset($_GET['lang']) && array_key_exists($_GET['lang'], $languages) ? $_GET['lang'] : 'fa';
 $lang = $languages[$selected_lang];

// --- تشخیص دامنه و مسیر فعلی ---
 $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
 $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

function cleanUrl($url) {
    $url = preg_replace('#^https?://#', '', $url);
    $url = str_replace('www.', '', $url);
    $url = rtrim($url, '/\\');
    $url = strtolower($url);
    return $url;
}

 $current_domain = cleanUrl($_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
if (empty($current_domain) || $current_domain === '/') $current_domain = $_SERVER['HTTP_HOST'];

 $is_authorized = false;
 $error_msg = '';
 $show_license_form = false;

if ($current_domain === $locked_domain) {
    $is_authorized = true;
} elseif (isset($_SESSION['license_unlocked']) && $_SESSION['license_unlocked'] === $current_domain) {
    $is_authorized = true;
} else {
    $error_msg = sprintf($lang['error_domain'], $current_domain);
    $show_license_form = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_key'])) {
    $input_code = trim($_POST['license_key']);
    $expected_license = md5($current_domain . $secret_key);
    if ($input_code === $expected_license || $input_code === $valid_dev_hash || $input_code === $valid_master_hash) {
        $_SESSION['license_unlocked'] = $current_domain;
        $is_authorized = true;
        $show_license_form = false;
    } else {
        $error_msg = $lang['license_error'];
        $show_license_form = true;
    }
}

// --- شروع عملیات بازیابی (Nuke and Pave) ---
if ($is_authorized && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_restore'])) {
    set_time_limit(0);
    
    // ۱. نابودی کامل فایل‌های فعلی
    $root_path = realpath(dirname(__FILE__)) . '/';
    $keep_files = ['restore.php', 'backup_files.zip', 'database.enc'];
    
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $filename = $file->getFilename();
        $filepath = $file->getRealPath();
        
        if (in_array($filename, $keep_files) || $filepath === realpath('restore.php') || $filepath === realpath('backup_files.zip') || $filepath === realpath('database.enc')) {
            continue;
        }
        if ($file->isDir()) {
            @rmdir($filepath);
        } else {
            @unlink($filepath);
        }
    }

        // ۲. اکسترکت فایل‌های سالم
    $zip = new ZipArchive();
    if ($zip->open('backup_files.zip') === TRUE) {
        $zip->extractTo($root_path);
        $zip->close();
    } else {
        die("Failed to extract backup_files.zip");
    }

    // ۲.۵. بازسازی پوشه‌های امنیتی که در بک‌آپ قرار نداشتند (جلوگیری از خطای log.php)
    @mkdir('system/storage/logs', 0777, true);
    @mkdir('system/storage/session', 0777, true);
    @mkdir('system/storage/cache', 0777, true); // برای اطمینان
    @mkdir('system/storage/backups', 0777, true);
    @mkdir('image/cache', 0777, true); // برای اطمینان از وجود پوشه کش تصاویر

    // ۳. خواندن اطلاعات دیتابیس از فایل کانفیگ سالم
    $config_content = file_get_contents('config.php');
    preg_match("/define\('DB_HOSTNAME', '([^']*)'\);/", $config_content, $db_host_m);
    preg_match("/define\('DB_USERNAME', '([^']*)'\);/", $config_content, $db_user_m);
    preg_match("/define\('DB_PASSWORD', '([^']*)'\);/", $config_content, $db_pass_m);
    preg_match("/define\('DB_DATABASE', '([^']*)'\);/", $config_content, $db_name_m);
    preg_match("/define\('DB_PREFIX', '([^']*)'\);/", $config_content, $db_prefix_m);
    
    $db_host = $db_host_m[1] ?? '';
    $db_user = $db_user_m[1] ?? '';
    $db_pass = $db_pass_m[1] ?? '';
    $db_name = $db_name_m[1] ?? '';
    $db_prefix = $db_prefix_m[1] ?? 'oc_';

    // ۴. نابودی دیتابیس فعلی
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) die("DB Connection Error: " . $mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");
    
    $tables = $mysqli->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    foreach ($tables as $table) {
        $tbl = $table[0];
        $mysqli->query("DROP TABLE IF EXISTS `$tbl`");
    }
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

    // ۵. ایمپورت دیتابیس سالم
    $enc_content = file_get_contents('database.enc');
    $iv = substr($enc_content, 0, 16);
    $ciphertext = substr($enc_content, 16);
    $db_key = hash('sha256', $secret_key, true);
    $sql_content = openssl_decrypt($ciphertext, 'aes-256-cbc', $db_key, OPENSSL_RAW_DATA, $iv);

    $temp_sql_file = 'database_temp.sql';
    file_put_contents($temp_sql_file, $sql_content);
    unset($sql_content, $enc_content, $ciphertext);

    $fp = fopen($temp_sql_file, 'r');
    $query = '';
    while (!feof($fp)) {
        $line = fgets($fp, 102400);
        if (trim($line) == '' || substr($line, 0, 2) == '--') continue;
        $query .= $line;
        if (substr(trim($query), -1) == ';') {
            $mysqli->query($query);
            $query = '';
        }
    }
    fclose($fp);
    
    // ۶. آپدیت آدرس‌های دیتابیس به دامنه/پوشه فعلی (حل مشکل ریدایرکت به دامنه قدیمی)
    $mysqli->query("UPDATE `{$db_prefix}setting` SET `value` = '$base_url' WHERE `key` = 'config_url'");
    $mysqli->query("UPDATE `{$db_prefix}setting` SET `value` = '$base_url' WHERE `key` = 'config_ssl'");
    
    $mysqli->close();

    // ۷. پاکسازی نهایی فایل‌های موقت
    unlink('restore.php');
    unlink('backup_files.zip');
    unlink('database.enc');
    unlink($temp_sql_file);

    // ۸. نمایش صفحه موفقیت زیبا و دوزبانه
    $dir_attr = ($selected_lang == 'fa') ? 'rtl' : 'ltr';
    $admin_url = $base_url . 'admin/';
    echo "<!DOCTYPE html><html dir='$dir_attr' lang='$selected_lang'><head><meta charset='UTF-8'><title>" . $lang['success'] . "</title>";
    echo "<style>body{font-family:Tahoma,Arial,sans-serif;background:#f4f7f6;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}";
    echo ".success-box{background:#fff;padding:50px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.1);text-align:center;max-width:500px;}";
    echo ".icon-circle{width:80px;height:80px;background:#4CAF50;border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(76,175,80,0.3);}";
    echo ".icon-circle svg{width:40px;height:40px;fill:#fff;}";
    echo "h2{color:#333;margin-bottom:10px;}";
    echo "p{color:#666;margin-bottom:30px;line-height:1.6;}";
    echo ".btn-admin{background:#1e91cf;color:#fff;text-decoration:none;padding:12px 30px;border-radius:6px;font-weight:bold;display:inline-block;transition:0.3s;}";
    echo ".btn-admin:hover{background:#157ab0;box-shadow:0 4px 10px rgba(30,145,207,0.3);}";
    echo ".link-site{display:block;margin-top:15px;color:#888;text-decoration:none;font-size:13px;}";
    echo ".footer-copyright{margin-top:30px;font-size:12px;color:#999;}</style></head><body>";
    echo "<div class='success-box'>";
    echo "<div class='icon-circle'><svg viewBox='0 0 24 24'><path d='M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z'/></svg></div>";
    echo "<h2>" . $lang['success'] . "</h2>";
    echo "<p>" . $lang['success_desc'] . "</p>";
    echo "<a href='$admin_url' class='btn-admin'>" . $lang['btn_admin'] . "</a>";
    echo "<a href='$base_url' class='link-site'>" . $lang['view_site'] . "</a>";
    echo "<div class='footer-copyright'>Developed by <a href='https://caspiancms.ir' target='_blank' style='color:#1e91cf;text-decoration:none;'>CaspianCMS</a></div>";
    echo "</div></body></html>";
    exit;
}

 $is_rtl = ($selected_lang == 'fa') ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html dir="<?php echo $is_rtl; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['title']; ?></title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; padding: 50px; flex-direction: column; align-items: center; }
        .box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 450px; margin-bottom: 20px; text-align: center; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px;}
        button { background: #d9534f; color: #fff; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; }
        button.btn-license { background: #1e91cf; }
        .error { color: #a94442; background: #f2dede; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .lang-sel { text-align:center; margin-bottom:15px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="lang-sel">
            <a href="?lang=en">English</a> | <a href="?lang=fa">فارسی</a>
        </div>
        <h2><?php echo $lang['heading']; ?></h2>
        <p><?php echo $lang['desc']; ?></p>
        
        <?php if ($error_msg): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if ($is_authorized): ?>
            <form method="post">
                <input type="hidden" name="do_restore" value="1">
                <button type="submit" onclick="return confirm('<?php echo $lang['confirm']; ?>')"><?php echo $lang['btn_restore']; ?></button>
            </form>
        <?php else: ?>
            <h3><?php echo $lang['license_heading']; ?></h3>
            <form method="post">
                <input type="text" name="license_key" placeholder="<?php echo $lang['license_placeholder']; ?>">
                <button type="submit" class="btn-license"><?php echo $lang['license_btn']; ?></button>
            </form>
        <?php endif; ?>
    </div>
    <div style="text-align:center; color:#888; font-size:12px;">
        Developed by <a href="https://caspiancms.ir" target="_blank" style="color:#1e91cf; font-weight:bold;">CaspianCMS</a>
    </div>
</body>
</html>
EOT;

        $search = ['%%LOCKED_DOMAIN%%', '%%SECRET_KEY%%'];
        $replace = [$locked_domain, $this->secret_key];
        return str_replace($search, $replace, $template);
    }
}