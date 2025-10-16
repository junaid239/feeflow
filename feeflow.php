<?php
/**
 * Plugin Name: FeeFlow - Tuition Management System
 * Plugin URI: https://thejunaid.in/feeflow
 * Description: A comprehensive tuition center management system with AJAX-powered interface for transactions, student management, and reporting.
 * Version: 1.4.0
 * Author: Junaid Ahmed
 * Author URI: https://thejunaid.in
 * License: GPL v2 or later
 * Text Domain: feeflow
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FEEFLOW_VERSION', '1.4.0');
define('FEEFLOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FEEFLOW_PLUGIN_URL', plugin_dir_url(__FILE__));

class FeeFlow {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new FeeFlow();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register shortcode
        add_shortcode('feeflow', array($this, 'render_feeflow'));
        
        // AJAX handlers
        add_action('wp_ajax_feeflow_search_student', array($this, 'ajax_search_student'));
        add_action('wp_ajax_feeflow_get_student_details', array($this, 'ajax_get_student_details'));
        add_action('wp_ajax_feeflow_add_transaction', array($this, 'ajax_add_transaction'));
        add_action('wp_ajax_feeflow_get_students', array($this, 'ajax_get_students'));
        add_action('wp_ajax_feeflow_add_student', array($this, 'ajax_add_student'));
        add_action('wp_ajax_feeflow_update_student', array($this, 'ajax_update_student'));
        add_action('wp_ajax_feeflow_toggle_student_status', array($this, 'ajax_toggle_student_status'));
        add_action('wp_ajax_feeflow_delete_student', array($this, 'ajax_delete_student'));
        add_action('wp_ajax_feeflow_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_feeflow_export_excel', array($this, 'ajax_export_excel'));
        add_action('wp_ajax_feeflow_get_trash', array($this, 'ajax_get_trash'));
        add_action('wp_ajax_feeflow_restore_item', array($this, 'ajax_restore_item'));
        add_action('wp_ajax_feeflow_permanent_delete', array($this, 'ajax_permanent_delete'));
        add_action('wp_ajax_feeflow_delete_transaction', array($this, 'ajax_delete_transaction'));
        add_action('wp_ajax_feeflow_export_students', array($this, 'ajax_export_students'));
        add_action('wp_ajax_feeflow_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_feeflow_get_settings', array($this, 'ajax_get_settings'));
        add_action('wp_ajax_feeflow_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        
        // Login handler (no authentication required)
        add_action('wp_ajax_nopriv_feeflow_login', array($this, 'ajax_handle_login'));
        
        // Database upgrade handler
        add_action('wp_ajax_feeflow_upgrade_database', array($this, 'ajax_upgrade_database'));
    }
    
    public function ajax_upgrade_database() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        
        // Check if grade column exists
        $grade_column = $wpdb->get_results("SHOW COLUMNS FROM $students_table LIKE 'grade'");
        
        if (empty($grade_column)) {
            // Add grade column
            $result = $wpdb->query("ALTER TABLE $students_table ADD COLUMN grade varchar(50) DEFAULT NULL AFTER syllabus");
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Database upgraded successfully! Grade column added.'));
            } else {
                wp_send_json_error('Failed to add grade column: ' . $wpdb->last_error);
            }
        } else {
            wp_send_json_success(array('message' => 'Grade column already exists. No upgrade needed.'));
        }
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Students table with grade field
        $students_table = $wpdb->prefix . 'feeflow_students';
        $sql_students = "CREATE TABLE IF NOT EXISTS $students_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_name varchar(255) NOT NULL,
            parent_phone varchar(20) NOT NULL,
            subjects text NOT NULL,
            syllabus varchar(100) NOT NULL,
            grade varchar(50) DEFAULT NULL,
            monthly_fee decimal(10,2) NOT NULL,
            status enum('active','inactive') DEFAULT 'active',
            is_deleted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parent_phone (parent_phone),
            KEY status (status),
            KEY is_deleted (is_deleted)
        ) $charset_collate ENGINE=InnoDB;";
        
        // Transactions table with receipt_number
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        $sql_transactions = "CREATE TABLE IF NOT EXISTS $transactions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            receipt_number varchar(10) DEFAULT NULL,
            student_id mediumint(9) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_month date NOT NULL,
            payment_method enum('Cash','Bank','PhonePe','GooglePay') NOT NULL,
            payment_date date NOT NULL,
            notes text,
            is_deleted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY receipt_number (receipt_number),
            KEY student_id (student_id),
            KEY payment_month (payment_month),
            KEY is_deleted (is_deleted)
        ) $charset_collate ENGINE=InnoDB;";
        
        // Settings table
        $settings_table = $wpdb->prefix . 'feeflow_settings';
        $sql_settings = "CREATE TABLE IF NOT EXISTS $settings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL UNIQUE,
            setting_value text,
            PRIMARY KEY  (id),
            KEY setting_key (setting_key)
        ) $charset_collate ENGINE=InnoDB;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_students);
        dbDelta($sql_transactions);
        dbDelta($sql_settings);
        
        // Initialize receipt counter if not exists
        $counter_exists = $wpdb->get_var("SELECT COUNT(*) FROM $settings_table WHERE setting_key = 'receipt_counter'");
        if (!$counter_exists) {
            $wpdb->insert($settings_table, array(
                'setting_key' => 'receipt_counter',
                'setting_value' => '10000'
            ));
        }
    }
    
    public function init() {
        // Check and update database schema if needed
        $this->check_database_schema();
    }
    
    private function check_database_schema() {
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        
        // Check if grade column exists
        $grade_column = $wpdb->get_results("SHOW COLUMNS FROM $students_table LIKE 'grade'");
        
        if (empty($grade_column)) {
            // Add grade column if it doesn't exist
            $wpdb->query("ALTER TABLE $students_table ADD COLUMN grade varchar(50) DEFAULT NULL AFTER syllabus");
        }
    }
    
    public function enqueue_scripts() {
        global $post;
        
        // Check if we're on a page with the shortcode or in admin
        $load_scripts = false;
        
        if (is_admin()) {
            $load_scripts = true;
        } elseif (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'feeflow')) {
            $load_scripts = true;
        }
        
        if ($load_scripts) {
            wp_enqueue_style('feeflow-style', FEEFLOW_PLUGIN_URL . 'assets/css/feeflow.css', array(), FEEFLOW_VERSION);
            
            // Enqueue Chart.js from CDN
            wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
            
            wp_enqueue_script('feeflow-script', FEEFLOW_PLUGIN_URL . 'assets/js/feeflow.js', array('jquery', 'chartjs'), FEEFLOW_VERSION, true);
            
            wp_localize_script('feeflow-script', 'feeflowAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('feeflow_nonce')
            ));
        }
    }
    
    public function render_feeflow($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            ob_start();
            include FEEFLOW_PLUGIN_DIR . 'templates/login-page.php';
            return ob_get_clean();
        }
        
        ob_start();
        include FEEFLOW_PLUGIN_DIR . 'templates/main-interface.php';
        return ob_get_clean();
    }
    
    // AJAX: Search student
    public function ajax_search_student() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $search = sanitize_text_field($_POST['search']);
        $students_table = $wpdb->prefix . 'feeflow_students';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, student_name, parent_phone, subjects, monthly_fee 
            FROM $students_table 
            WHERE (student_name LIKE %s OR parent_phone LIKE %s) 
            AND status = 'active' AND is_deleted = 0
            ORDER BY student_name ASC",
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        ));
        
        wp_send_json_success($results);
    }
    
    // AJAX: Get student details
    public function ajax_get_student_details() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $student_id = intval($_POST['student_id']);
        $students_table = $wpdb->prefix . 'feeflow_students';
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $student = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $students_table WHERE id = %d AND is_deleted = 0",
            $student_id
        ));
        
        if (!$student) {
            wp_send_json_error('Student not found');
        }
        
        // Get last payment
        $last_payment = $wpdb->get_row($wpdb->prepare(
            "SELECT amount, payment_date FROM $transactions_table 
            WHERE student_id = %d AND is_deleted = 0 
            ORDER BY payment_date DESC LIMIT 1",
            $student_id
        ));
        
        $student->last_payment = $last_payment;
        
        wp_send_json_success($student);
    }
    
    // AJAX: Add transaction
    public function ajax_add_transaction() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        $students_table = $wpdb->prefix . 'feeflow_students';
        
        // Generate unique receipt number
        $receipt_number = $this->generate_receipt_number();
        
        $data = array(
            'receipt_number' => $receipt_number,
            'student_id' => intval($_POST['student_id']),
            'amount' => floatval($_POST['amount']),
            'payment_month' => sanitize_text_field($_POST['payment_month']) . '-01',
            'payment_method' => sanitize_text_field($_POST['payment_method']),
            'payment_date' => sanitize_text_field($_POST['payment_date']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );
        
        $result = $wpdb->insert($transactions_table, $data);
        
        if ($result) {
            $transaction_id = $wpdb->insert_id;
            
            // Get student details for PDF
            $student = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $students_table WHERE id = %d",
                $data['student_id']
            ));
            
            // Get institution settings
            $settings = $this->get_institution_settings();
            
            // Generate PDF receipt
            $pdf_url = $this->generate_pdf_receipt($transaction_id, $receipt_number, $student, $data, $settings);
            
            wp_send_json_success(array(
                'message' => 'Transaction added successfully',
                'receipt_number' => $receipt_number,
                'pdf_url' => $pdf_url
            ));
        } else {
            wp_send_json_error('Failed to add transaction');
        }
    }
    
    // Generate unique 5-digit receipt number
    private function generate_receipt_number() {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'feeflow_settings';
        
        // Get and increment counter
        $counter = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
            'receipt_counter'
        ));
        
        if (!$counter) {
            $counter = 10000;
        }
        
        $new_counter = intval($counter) + 1;
        
        // Update counter
        $wpdb->update(
            $settings_table,
            array('setting_value' => $new_counter),
            array('setting_key' => 'receipt_counter')
        );
        
        return str_pad($new_counter, 5, '0', STR_PAD_LEFT);
    }
    
    // Get institution settings
    private function get_institution_settings() {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'feeflow_settings';
        
        $settings = array(
            'institution_name' => $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
                'institution_name'
            )),
            'institution_address' => $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
                'institution_address'
            )),
            'institution_phone' => $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
                'institution_phone'
            )),
            'institution_email' => $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
                'institution_email'
            ))
        );
        
        return $settings;
    }
    
    // Generate PDF Receipt
    private function generate_pdf_receipt($transaction_id, $receipt_number, $student, $transaction_data, $settings) {
        // Create HTML receipt
        $receipt_html = $this->create_receipt_html($transaction_id, $receipt_number, $student, $transaction_data, $settings);
        
        $upload_dir = wp_upload_dir();
        $receipt_dir = $upload_dir['basedir'] . '/feeflow-receipts/';
        
        if (!file_exists($receipt_dir)) {
            mkdir($receipt_dir, 0755, true);
        }
        
        $filename = 'receipt_' . $receipt_number . '.html';
        $filepath = $receipt_dir . $filename;
        
        file_put_contents($filepath, $receipt_html);
        
        return $upload_dir['baseurl'] . '/feeflow-receipts/' . $filename;
    }
    
    private function create_receipt_html($transaction_id, $receipt_number, $student, $transaction_data, $settings) {
        $date = date('d/m/Y', strtotime($transaction_data['payment_date']));
        $month = date('F Y', strtotime($transaction_data['payment_month']));
        
        $institution_name = !empty($settings['institution_name']) ? $settings['institution_name'] : 'Institution Name';
        $institution_address = !empty($settings['institution_address']) ? nl2br($settings['institution_address']) : 'Institution Address';
        $institution_phone = !empty($settings['institution_phone']) ? $settings['institution_phone'] : '';
        $institution_email = !empty($settings['institution_email']) ? $settings['institution_email'] : '';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt #' . $receipt_number . '</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Inter", sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .receipt-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; }
        .receipt-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px 30px; text-align: center; position: relative; }
        .logo { width: 80px; height: 80px; margin: 0 auto 12px; background: white; border-radius: 50%; padding: 10px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); position: relative; z-index: 1; }
        .logo img { width: 100%; height: 100%; object-fit: contain; }
        .institution-name { font-size: 22px; font-weight: 700; margin-bottom: 6px; position: relative; z-index: 1; }
        .institution-details { font-size: 12px; opacity: 0.95; line-height: 1.4; position: relative; z-index: 1; }
        .receipt-title { font-size: 18px; font-weight: 600; margin: 12px 0 8px; position: relative; z-index: 1; }
        .receipt-number { display: inline-block; background: rgba(255, 255, 255, 0.25); padding: 8px 20px; border-radius: 30px; font-size: 15px; font-weight: 600; border: 2px solid rgba(255, 255, 255, 0.3); position: relative; z-index: 1; }
        .receipt-body { padding: 25px 30px; }
        .info-card { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .info-item { padding: 10px 0; border-bottom: 1px solid rgba(0, 0, 0, 0.08); }
        .info-item:last-child { border-bottom: none; }
        .info-label { font-size: 10px; text-transform: uppercase; font-weight: 600; color: #667eea; margin-bottom: 3px; }
        .info-value { font-size: 14px; color: #2d3748; font-weight: 500; }
        .amount-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0; position: relative; }
        .amount-label { font-size: 12px; opacity: 0.9; margin-bottom: 6px; text-transform: uppercase; }
        .amount-value { font-size: 36px; font-weight: 700; }
        .buttons { display: flex; gap: 12px; justify-content: center; margin: 20px 0; flex-wrap: wrap; }
        .btn { padding: 12px 30px; border: none; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; color: white; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-secondary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .footer { text-align: center; padding: 20px 30px; background: #f7fafc; border-top: 1px solid #e2e8f0; }
        .footer-message { font-size: 15px; color: #2d3748; font-weight: 600; margin-bottom: 6px; }
        .footer-timestamp { font-size: 11px; color: #718096; }
        .verified-badge { display: inline-flex; align-items: center; gap: 6px; background: #48bb78; color: white; padding: 6px 16px; border-radius: 30px; font-size: 11px; font-weight: 600; margin-top: 10px; }
        @media print {
            @page { size: A4; margin: 10mm; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: white; padding: 0; margin: 0; }
            .receipt-container { box-shadow: none; border-radius: 0; max-width: 100%; page-break-inside: avoid; }
            .receipt-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important; padding: 20px !important; }
            .receipt-body { padding: 20px !important; }
            .logo { width: 70px !important; height: 70px !important; margin-bottom: 10px !important; }
            .institution-name { font-size: 20px !important; }
            .receipt-title { font-size: 16px !important; margin: 10px 0 6px !important; }
            .receipt-number { font-size: 14px !important; padding: 6px 16px !important; }
            .info-card { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important; padding: 15px !important; margin-bottom: 15px !important; }
            .info-grid { gap: 8px !important; }
            .info-item { padding: 8px 0 !important; }
            .info-label { font-size: 9px !important; }
            .info-value { font-size: 13px !important; }
            .amount-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important; padding: 15px !important; margin: 15px 0 !important; }
            .amount-label { font-size: 11px !important; }
            .amount-value { font-size: 32px !important; }
            .verified-badge { background: #48bb78 !important; color: white !important; }
            .footer { padding: 15px 20px !important; }
            .footer-message { font-size: 14px !important; }
            .footer-timestamp { font-size: 10px !important; }
            .buttons { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="logo">
                <img src="http://stemify.in/wp-content/uploads/2022/11/stemify-logo.png" alt="Logo">
            </div>
            <div class="institution-name">' . esc_html($institution_name) . '</div>
            <div class="institution-details">' . $institution_address . '</div>
            ' . ($institution_phone ? '<div class="institution-details">📞 ' . esc_html($institution_phone) . '</div>' : '') . '
            ' . ($institution_email ? '<div class="institution-details">✉️ ' . esc_html($institution_email) . '</div>' : '') . '
            <div class="receipt-title">FEE RECEIPT</div>
            <div class="receipt-number">#' . $receipt_number . '</div>
        </div>
        <div class="receipt-body">
            <div class="info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Student Name</div>
                        <div class="info-value">' . esc_html($student->student_name) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Parent Phone</div>
                        <div class="info-value">' . esc_html($student->parent_phone) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Subjects</div>
                        <div class="info-value">' . esc_html($student->subjects) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Syllabus</div>
                        <div class="info-value">' . esc_html($student->syllabus) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Date</div>
                        <div class="info-value">' . $date . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Month</div>
                        <div class="info-value">' . $month . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value">' . esc_html($transaction_data['payment_method']) . '</div>
                    </div>
                    ' . (!empty($transaction_data['notes']) ? '
                    <div class="info-item">
                        <div class="info-label">Notes</div>
                        <div class="info-value">' . esc_html($transaction_data['notes']) . '</div>
                    </div>' : '') . '
                </div>
            </div>
            <div class="amount-section">
                <div class="amount-label">Amount Paid</div>
                <div class="amount-value">₹' . number_format($transaction_data['amount'], 2) . '</div>
            </div>
            <div class="buttons">
                <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
                <button onclick="window.print()" class="btn btn-secondary">Download PDF</button>
            </div>
        </div>
        <div class="footer">
            <div class="footer-message">✨ Thank you for your payment! ✨</div>
            <div class="footer-timestamp">Generated on ' . date('d M Y, h:i A') . '</div>
            <div class="verified-badge">✓ Verified Payment</div>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    // AJAX: Get students
    public function ajax_get_students() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $students = $wpdb->get_results(
            "SELECT * FROM $students_table WHERE is_deleted = 0 ORDER BY student_name ASC"
        );
        
        // Get last payment for each student
        foreach ($students as $student) {
            $last_payment = $wpdb->get_row($wpdb->prepare(
                "SELECT payment_date FROM $transactions_table 
                WHERE student_id = %d AND is_deleted = 0 
                ORDER BY payment_date DESC LIMIT 1",
                $student->id
            ));
            
            $student->last_payment_date = $last_payment ? $last_payment->payment_date : 'Never';
        }
        
        wp_send_json_success($students);
    }
    
    // AJAX: Add student
    public function ajax_add_student() {
        try {
            check_ajax_referer('feeflow_nonce', 'nonce');
            
            global $wpdb;
            $students_table = $wpdb->prefix . 'feeflow_students';
            
            // Validate required fields
            if (empty($_POST['student_name']) || empty($_POST['parent_phone']) || 
                empty($_POST['subjects']) || empty($_POST['syllabus']) || 
                empty($_POST['monthly_fee'])) {
                wp_send_json_error('All fields are required');
                return;
            }
            
            // Grade is optional for backward compatibility
            $grade = !empty($_POST['grade']) ? sanitize_text_field($_POST['grade']) : null;
            
            $data = array(
                'student_name' => sanitize_text_field($_POST['student_name']),
                'parent_phone' => sanitize_text_field($_POST['parent_phone']),
                'subjects' => sanitize_text_field($_POST['subjects']),
                'syllabus' => sanitize_text_field($_POST['syllabus']),
                'grade' => $grade,
                'monthly_fee' => floatval($_POST['monthly_fee']),
                'status' => 'active'
            );
            
            $result = $wpdb->insert($students_table, $data);
            
            if ($result) {
                $data['id'] = $wpdb->insert_id;
                $data['last_payment_date'] = 'Never';
                $data['created_at'] = current_time('mysql');
                $data['updated_at'] = current_time('mysql');
                wp_send_json_success($data);
            } else {
                error_log('FeeFlow DB Error: ' . $wpdb->last_error);
                wp_send_json_error('Database error: ' . $wpdb->last_error);
            }
        } catch (Exception $e) {
            error_log('FeeFlow Exception: ' . $e->getMessage());
            wp_send_json_error('Server error: ' . $e->getMessage());
        }
    }
    
    // AJAX: Update student
    public function ajax_update_student() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        
        $student_id = intval($_POST['student_id']);
        
        // Validate required fields
        if (empty($_POST['student_name']) || empty($_POST['parent_phone']) || 
            empty($_POST['subjects']) || empty($_POST['syllabus']) || 
            empty($_POST['monthly_fee'])) {
            wp_send_json_error('All fields are required');
            return;
        }
        
        // Grade is optional for backward compatibility
        $grade = !empty($_POST['grade']) ? sanitize_text_field($_POST['grade']) : null;
        
        $data = array(
            'student_name' => sanitize_text_field($_POST['student_name']),
            'parent_phone' => sanitize_text_field($_POST['parent_phone']),
            'subjects' => sanitize_text_field($_POST['subjects']),
            'syllabus' => sanitize_text_field($_POST['syllabus']),
            'grade' => $grade,
            'monthly_fee' => floatval($_POST['monthly_fee'])
        );
        
        $result = $wpdb->update($students_table, $data, array('id' => $student_id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Student updated successfully'));
        } else {
            error_log('FeeFlow DB Error: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }
    
    // AJAX: Toggle student status
    public function ajax_toggle_student_status() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        
        $student_id = intval($_POST['student_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        $result = $wpdb->update(
            $students_table,
            array('status' => $new_status),
            array('id' => $student_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Status updated successfully'));
        } else {
            wp_send_json_error('Failed to update status');
        }
    }
    
    // AJAX: Delete student (soft delete)
    public function ajax_delete_student() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        
        $student_id = intval($_POST['student_id']);
        
        $result = $wpdb->update(
            $students_table,
            array('is_deleted' => 1),
            array('id' => $student_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Student moved to trash'));
        } else {
            wp_send_json_error('Failed to delete student');
        }
    }
    
    // AJAX: Delete transaction (soft delete)
    public function ajax_delete_transaction() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $transaction_id = intval($_POST['transaction_id']);
        
        $result = $wpdb->update(
            $transactions_table,
            array('is_deleted' => 1),
            array('id' => $transaction_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Transaction moved to trash'));
        } else {
            wp_send_json_error('Failed to delete transaction');
        }
    }
    
    // AJAX: Generate report
    public function ajax_generate_report() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $report_type = sanitize_text_field($_POST['report_type']);
        
        if ($report_type === 'transaction_range') {
            $from_date = sanitize_text_field($_POST['from_date']);
            $to_date = sanitize_text_field($_POST['to_date']);
            
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT t.*, s.student_name, s.parent_phone, s.subjects 
                FROM $transactions_table t
                JOIN $students_table s ON t.student_id = s.id
                WHERE t.payment_date BETWEEN %s AND %s
                AND t.is_deleted = 0 AND s.is_deleted = 0
                ORDER BY t.payment_date DESC",
                $from_date, $to_date
            ));
            
            wp_send_json_success(array('data' => $results, 'type' => 'transactions'));
            
        } elseif ($report_type === 'fee_status') {
            $month = sanitize_text_field($_POST['month']);
            $status = sanitize_text_field($_POST['status']);
            
            $month_date = $month . '-01';
            
            if ($status === 'paid') {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.*, t.payment_date as last_payment_date, t.amount, t.payment_method, t.receipt_number
                    FROM $students_table s
                    JOIN $transactions_table t ON s.id = t.student_id
                    WHERE t.payment_month = %s
                    AND s.status = 'active' AND s.is_deleted = 0 AND t.is_deleted = 0
                    GROUP BY s.id
                    ORDER BY s.student_name ASC",
                    $month_date
                ));
            } else {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.*, 
                    (SELECT payment_date FROM $transactions_table 
                     WHERE student_id = s.id AND is_deleted = 0 
                     ORDER BY payment_date DESC LIMIT 1) as last_payment_date
                    FROM $students_table s
                    WHERE s.id NOT IN (
                        SELECT student_id FROM $transactions_table 
                        WHERE payment_month = %s AND is_deleted = 0
                    )
                    AND s.status = 'active' AND s.is_deleted = 0
                    ORDER BY s.student_name ASC",
                    $month_date
                ));
            }
            
            wp_send_json_success(array('data' => $results, 'type' => 'fee_status', 'status' => $status));
        }
    }
    
    // AJAX: Export to Excel
    public function ajax_export_excel() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $report_type = sanitize_text_field($_POST['report_type']);
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="feeflow_report_' . date('d-m-Y') . '.xls"');
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"><style>td{mso-number-format:"\@";}</style></head>';
        echo '<body>';
        echo '<table border="1">';
        
        if ($report_type === 'transaction_range') {
            $from_date = sanitize_text_field($_POST['from_date']);
            $to_date = sanitize_text_field($_POST['to_date']);
            
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT t.*, s.student_name, s.parent_phone, s.subjects 
                FROM $transactions_table t
                JOIN $students_table s ON t.student_id = s.id
                WHERE t.payment_date BETWEEN %s AND %s
                AND t.is_deleted = 0 AND s.is_deleted = 0
                ORDER BY t.payment_date DESC",
                $from_date, $to_date
            ));
            
            echo '<thead><tr>';
            echo '<th>Receipt #</th><th>Date</th><th>Student Name</th><th>Phone</th><th>Amount</th><th>Method</th><th>Month</th><th>Notes</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($results as $row) {
                $date = date('d/m/Y', strtotime($row->payment_date));
                $month = date('F Y', strtotime($row->payment_month));
                
                echo '<tr>';
                echo '<td>' . esc_html($row->receipt_number ? $row->receipt_number : $row->id) . '</td>';
                echo '<td>' . $date . '</td>';
                echo '<td>' . esc_html($row->student_name) . '</td>';
                echo '<td>' . esc_html($row->parent_phone) . '</td>';
                echo '<td>' . esc_html($row->amount) . '</td>';
                echo '<td>' . esc_html($row->payment_method) . '</td>';
                echo '<td>' . $month . '</td>';
                echo '<td>' . esc_html($row->notes) . '</td>';
                echo '</tr>';
            }
            
        } else {
            $month = sanitize_text_field($_POST['month']);
            $status = sanitize_text_field($_POST['status']);
            $month_date = $month . '-01';
            
            if ($status === 'paid') {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.*, t.payment_date as last_payment_date, t.amount, t.payment_method, t.receipt_number
                    FROM $students_table s
                    JOIN $transactions_table t ON s.id = t.student_id
                    WHERE t.payment_month = %s
                    AND s.status = 'active' AND s.is_deleted = 0 AND t.is_deleted = 0
                    GROUP BY s.id
                    ORDER BY s.student_name ASC",
                    $month_date
                ));
            } else {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.*, 
                    (SELECT payment_date FROM $transactions_table 
                     WHERE student_id = s.id AND is_deleted = 0 
                     ORDER BY payment_date DESC LIMIT 1) as last_payment_date
                    FROM $students_table s
                    WHERE s.id NOT IN (
                        SELECT student_id FROM $transactions_table 
                        WHERE payment_month = %s AND is_deleted = 0
                    )
                    AND s.status = 'active' AND s.is_deleted = 0
                    ORDER BY s.student_name ASC",
                    $month_date
                ));
            }
            
            echo '<thead><tr>';
            echo '<th>Student Name</th><th>Phone</th><th>Subjects</th><th>Syllabus</th><th>Grade</th><th>Monthly Fee</th><th>Last Payment Date</th>';
            if ($status === 'paid') {
                echo '<th>Receipt #</th><th>Amount Paid</th><th>Payment Method</th>';
            }
            echo '</tr></thead><tbody>';
            
            foreach ($results as $row) {
                $last_payment = $row->last_payment_date ? date('d/m/Y', strtotime($row->last_payment_date)) : 'Never';
                
                echo '<tr>';
                echo '<td>' . esc_html($row->student_name) . '</td>';
                echo '<td>' . esc_html($row->parent_phone) . '</td>';
                echo '<td>' . esc_html($row->subjects) . '</td>';
                echo '<td>' . esc_html($row->syllabus) . '</td>';
                echo '<td>' . esc_html($row->grade) . '</td>';
                echo '<td>' . esc_html($row->monthly_fee) . '</td>';
                echo '<td>' . $last_payment . '</td>';
                if ($status === 'paid') {
                    echo '<td>' . esc_html($row->receipt_number ? $row->receipt_number : '-') . '</td>';
                    echo '<td>' . esc_html($row->amount) . '</td>';
                    echo '<td>' . esc_html($row->payment_method) . '</td>';
                }
                echo '</tr>';
            }
        }
        
        echo '</tbody></table></body></html>';
        exit;
    }
    
    // AJAX: Export students list
    public function ajax_export_students() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $students = $wpdb->get_results(
            "SELECT * FROM $students_table WHERE is_deleted = 0 ORDER BY student_name ASC"
        );
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="feeflow_students_' . date('d-m-Y') . '.xls"');
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"><style>td{mso-number-format:"\@";}</style></head>';
        echo '<body>';
        echo '<table border="1">';
        echo '<thead><tr>';
        echo '<th>Student Name</th><th>Phone</th><th>Subjects</th><th>Syllabus</th><th>Grade</th><th>Monthly Fee</th><th>Status</th><th>Last Payment Date</th><th>Created Date</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($students as $student) {
            $last_payment = $wpdb->get_var($wpdb->prepare(
                "SELECT payment_date FROM $transactions_table 
                WHERE student_id = %d AND is_deleted = 0 
                ORDER BY payment_date DESC LIMIT 1",
                $student->id
            ));
            
            $last_payment_formatted = $last_payment ? date('d/m/Y', strtotime($last_payment)) : 'Never';
            $created_date = date('d/m/Y', strtotime($student->created_at));
            
            echo '<tr>';
            echo '<td>' . esc_html($student->student_name) . '</td>';
            echo '<td>' . esc_html($student->parent_phone) . '</td>';
            echo '<td>' . esc_html($student->subjects) . '</td>';
            echo '<td>' . esc_html($student->syllabus) . '</td>';
            echo '<td>' . esc_html($student->grade) . '</td>';
            echo '<td>' . esc_html($student->monthly_fee) . '</td>';
            echo '<td>' . ucfirst($student->status) . '</td>';
            echo '<td>' . $last_payment_formatted . '</td>';
            echo '<td>' . $created_date . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></body></html>';
        exit;
    }
    
    // AJAX: Get trash items
    public function ajax_get_trash() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $deleted_students = $wpdb->get_results(
            "SELECT *, 'student' as item_type FROM $students_table WHERE is_deleted = 1 ORDER BY updated_at DESC"
        );
        
        $deleted_transactions = $wpdb->get_results(
            "SELECT t.*, s.student_name, 'transaction' as item_type 
            FROM $transactions_table t
            LEFT JOIN $students_table s ON t.student_id = s.id
            WHERE t.is_deleted = 1 
            ORDER BY t.created_at DESC"
        );
        
        wp_send_json_success(array(
            'students' => $deleted_students,
            'transactions' => $deleted_transactions
        ));
    }
    
    // AJAX: Restore item
    public function ajax_restore_item() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $item_type = sanitize_text_field($_POST['item_type']);
        $item_id = intval($_POST['item_id']);
        
        $table = $item_type === 'student' ? 
            $wpdb->prefix . 'feeflow_students' : 
            $wpdb->prefix . 'feeflow_transactions';
        
        $result = $wpdb->update(
            $table,
            array('is_deleted' => 0),
            array('id' => $item_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => ucfirst($item_type) . ' restored successfully'));
        } else {
            wp_send_json_error('Failed to restore ' . $item_type);
        }
    }
    
    // AJAX: Permanent delete
    public function ajax_permanent_delete() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $item_type = sanitize_text_field($_POST['item_type']);
        $item_id = intval($_POST['item_id']);
        
        $table = $item_type === 'student' ? 
            $wpdb->prefix . 'feeflow_students' : 
            $wpdb->prefix . 'feeflow_transactions';
        
        $result = $wpdb->delete($table, array('id' => $item_id));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => ucfirst($item_type) . ' permanently deleted'));
        } else {
            wp_send_json_error('Failed to delete ' . $item_type);
        }
    }
    
    // AJAX: Handle login
    public function ajax_handle_login() {
        check_ajax_referer('feeflow_login_action', 'feeflow_login_nonce');
        
        $username = sanitize_text_field($_POST['log']);
        $password = $_POST['pwd'];
        $remember = isset($_POST['rememberme']);
        $redirect_to = esc_url_raw($_POST['redirect_to']);
        
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember
        );
        
        $user = wp_signon($creds, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array(
                'message' => $user->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'Login successful',
                'redirect' => $redirect_to
            ));
        }
    }
    
    // AJAX: Save settings
    public function ajax_save_settings() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $settings_table = $wpdb->prefix . 'feeflow_settings';
        
        $settings = array(
            'institution_name' => sanitize_text_field($_POST['institution_name']),
            'institution_address' => sanitize_textarea_field($_POST['institution_address']),
            'institution_phone' => sanitize_text_field($_POST['institution_phone']),
            'institution_email' => sanitize_email($_POST['institution_email'])
        );
        
        foreach ($settings as $key => $value) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $settings_table WHERE setting_key = %s",
                $key
            ));
            
            if ($exists) {
                $wpdb->update(
                    $settings_table,
                    array('setting_value' => $value),
                    array('setting_key' => $key)
                );
            } else {
                $wpdb->insert(
                    $settings_table,
                    array(
                        'setting_key' => $key,
                        'setting_value' => $value
                    )
                );
            }
        }
        
        wp_send_json_success(array('message' => 'Settings saved successfully'));
    }
    
    // AJAX: Get settings
    public function ajax_get_settings() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        $settings = $this->get_institution_settings();
        
        wp_send_json_success($settings);
    }
    
    // AJAX: Get dashboard data
    public function ajax_get_dashboard_data() {
        check_ajax_referer('feeflow_nonce', 'nonce');
        
        global $wpdb;
        $students_table = $wpdb->prefix . 'feeflow_students';
        $transactions_table = $wpdb->prefix . 'feeflow_transactions';
        
        $month = sanitize_text_field($_POST['month']);
        $month_date = $month . '-01';
        
        // Get total active students
        $total_students = $wpdb->get_var(
            "SELECT COUNT(*) FROM $students_table 
            WHERE status = 'active' AND is_deleted = 0"
        );
        
        // Get students who paid this month
        $paid_students = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT student_id) FROM $transactions_table 
            WHERE payment_month = %s AND is_deleted = 0",
            $month_date
        ));
        
        // Calculate unpaid students
        $unpaid_students = $total_students - $paid_students;
        
        // Get monthly revenue (total collected this month)
        $monthly_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $transactions_table 
            WHERE payment_month = %s AND is_deleted = 0",
            $month_date
        ));
        
        // Calculate expected amount (sum of all active students' monthly fees)
        $expected_amount = $wpdb->get_var(
            "SELECT COALESCE(SUM(monthly_fee), 0) FROM $students_table 
            WHERE status = 'active' AND is_deleted = 0"
        );
        
        // Calculate remaining amount
        $remaining_amount = max(0, $expected_amount - $monthly_revenue);
        
        $data = array(
            'total_students' => intval($total_students),
            'paid_students' => intval($paid_students),
            'unpaid_students' => intval($unpaid_students),
            'monthly_revenue' => floatval($monthly_revenue),
            'expected_amount' => floatval($expected_amount),
            'collected_amount' => floatval($monthly_revenue),
            'remaining_amount' => floatval($remaining_amount)
        );
        
        wp_send_json_success($data);
    }
}

// Initialize plugin
FeeFlow::getInstance();

// Create necessary directories and files on activation
function feeflow_create_files() {
    $plugin_dir = FEEFLOW_PLUGIN_DIR;
    
    // Create assets directories
    if (!file_exists($plugin_dir . 'assets')) {
        mkdir($plugin_dir . 'assets');
    }
    if (!file_exists($plugin_dir . 'assets/css')) {
        mkdir($plugin_dir . 'assets/css');
    }
    if (!file_exists($plugin_dir . 'assets/js')) {
        mkdir($plugin_dir . 'assets/js');
    }
    if (!file_exists($plugin_dir . 'templates')) {
        mkdir($plugin_dir . 'templates');
    }
}
register_activation_hook(__FILE__, 'feeflow_create_files');