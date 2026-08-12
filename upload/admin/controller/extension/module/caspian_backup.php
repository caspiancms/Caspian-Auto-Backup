<?php
class ControllerExtensionModuleCaspianBackup extends Controller {
    private $error = array();

    // تابع تشخیص دامنه و پوشه واقعی از آدرس مرورگر
    private function getRealDomain() {
        $script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // حذف بخش /admin از انتهای مسیر تا مسیر روت سایت به دست بیاید
        $script_path = preg_replace('#/admin$#', '', $script_path);
        
        $raw_domain = $_SERVER['HTTP_HOST'] . $script_path;
        
        $domain = preg_replace('#^https?://#', '', $raw_domain);
        $domain = str_replace('www.', '', $domain);
        $domain = rtrim($domain, '/\\');
        $domain = strtolower($domain);
        
        return $domain;
    }

    public function index() {
        $this->load->language('extension/module/caspian_backup');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['button_create'] = $this->language->get('button_create');
        $data['column_filename'] = $this->language->get('column_filename');
        $data['column_size'] = $this->language->get('column_size');
        $data['column_date'] = $this->language->get('column_date');
        $data['column_action'] = $this->language->get('column_action');
        $data['text_no_backups'] = $this->language->get('text_no_backups');
        $data['text_cron_info'] = $this->language->get('text_cron_info');
        $data['entry_cron_url'] = $this->language->get('entry_cron_url');
        $data['button_download'] = $this->language->get('button_download');

        $data['action_create'] = $this->url->link('extension/module/caspian_backup/create', 'user_token=' . $this->session->data['user_token'], true);

        // تولید کد لایسنس اختصاصی برای دامنه فعلی (استفاده از آدرس واقعی مرورگر)
        $secret_key = 'C@sp!an_CMS_S3cr3t_K3y';
        $clean_domain = $this->getRealDomain();
        $data['current_domain'] = $clean_domain;
        $data['domain_license'] = md5($clean_domain . $secret_key);

        // نمایش مسیر فیزیکی فایل برای اجرا در Cron Job هاست
        $data['cron_url'] = 'php ' . DIR_SYSTEM . 'caspian_cron.php';

        // خواندن لیست بک‌آپ‌ها
        $data['backups'] = array();
        $backup_dir = DIR_SYSTEM . 'storage/backups/';
        if (is_dir($backup_dir)) {
            $files = glob($backup_dir . 'caspian_backup_*.zip');
            rsort($files); // جدیدترین در بالای لیست
            
            foreach ($files as $file) {
                $filename = basename($file);
                $data['backups'][] = array(
                    'filename' => $filename,
                    'size'     => round(filesize($file) / 1048576, 2) . ' MB',
                    'date'     => date('Y-m-d H:i:s', filemtime($file)),
                    'download' => $this->url->link('extension/module/caspian_backup/download', 'user_token=' . $this->session->data['user_token'] . '&filename=' . urlencode($filename), true)
                );
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/caspian_backup', $data));
    }

    public function create() {
        $this->load->language('extension/module/caspian_backup');
        
        if (!$this->user->hasPermission('modify', 'extension/module/caspian_backup')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
        } else {
            // لود کردن موتور بک‌آپ
            require_once(DIR_SYSTEM . 'library/caspian_backup_engine.php');
            $engine = new CaspianBackupEngine($this->registry);
            
            // گرفتن دامنه واقعی از مرورگر (بدون اتکا به دیتابیس)
            $domain = $this->getRealDomain();
            $engine->createBackup($domain);
            
            $this->session->data['success'] = $this->language->get('text_success');
        }
        
        $this->response->redirect($this->url->link('extension/module/caspian_backup', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function download() {
        if (!$this->user->hasPermission('modify', 'extension/module/caspian_backup')) {
            die('Permission Denied');
        }

        $filename = basename($this->request->get['filename'] ?? '');
        $file = DIR_SYSTEM . 'storage/backups/' . $filename;
        
        if (file_exists($file)) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
        die('File not found.');
    }
}