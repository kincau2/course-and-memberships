<?php
/**
 * HKOTA Audit Report Admin Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle Membership Audit Export BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['membership_audit_nonce'])) {
    if (!wp_verify_nonce($_POST['membership_audit_nonce'], 'export_membership_audit')) {
        wp_die('Security check failed');
    }
    
    // Sanitize and validate dates
    $start_date = sanitize_text_field($_POST['membership_start_date']);
    $end_date = sanitize_text_field($_POST['membership_end_date']);
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        wp_die('Invalid date format');
    }
    
    global $wpdb;
    
    $membership_product_id = get_membership_dummy_product_id();
    
    // Query to fetch orders containing the membership product within date range
    // For HPOS compatibility, we check both wc_orders and posts tables
    $orders_table = $wpdb->prefix . 'wc_orders';
    $order_items_table = $wpdb->prefix . 'woocommerce_order_items';
    $order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
    
    // Check if HPOS is enabled
    $hpos_enabled = class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
                    method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
                    \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    
    if ($hpos_enabled) {
        // HPOS query
        $query = $wpdb->prepare("
            SELECT DISTINCT o.id as order_id, o.date_created_gmt
            FROM {$orders_table} o
            INNER JOIN {$order_items_table} oi ON o.id = oi.order_id
            INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
            WHERE oi.order_item_type = 'line_item'
            AND oim.meta_key = '_product_id'
            AND oim.meta_value = %d
            AND DATE(o.date_created_gmt) >= %s
            AND DATE(o.date_created_gmt) <= %s
            ORDER BY o.date_created_gmt DESC
        ", $membership_product_id, $start_date, $end_date);
    } else {
        // Legacy query using posts table
        $posts_table = $wpdb->posts;
        $query = $wpdb->prepare("
            SELECT DISTINCT p.ID as order_id, p.post_date_gmt as date_created_gmt
            FROM {$posts_table} p
            INNER JOIN {$order_items_table} oi ON p.ID = oi.order_id
            INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
            WHERE p.post_type = 'shop_order'
            AND oi.order_item_type = 'line_item'
            AND oim.meta_key = '_product_id'
            AND oim.meta_value = %d
            AND DATE(p.post_date_gmt) >= %s
            AND DATE(p.post_date_gmt) <= %s
            ORDER BY p.post_date_gmt DESC
        ", $membership_product_id, $start_date, $end_date);
    }
    
    $results = $wpdb->get_results($query);
    
    // Prepare CSV data
    $csv_data = [];
    $csv_data[] = [
        'Member Type',
        'Member Start Year',
        'Member End Year',
        'Name',
        'Email',
        'Order Total',
        'Stripe Fee',
        'Stripe Payout',
        'Payment Date',
        'Order Number',
        'Charge ID',
        'Payment Status'
    ];
    
    foreach ($results as $row) {
        $order = wc_get_order($row->order_id);
        
        if ($order) {
            $user_id = $order->get_user_id();
            $user = get_user_by('ID', $user_id);
            
            if ($user) {
                // Get order items to find membership metadata
                foreach ($order->get_items() as $item_id => $item) {
                    if ($item->get_product_id() == $membership_product_id) {
                        
                        // Get membership metadata from order item meta
                        $membership_plan_id = wc_get_order_item_meta($item_id, 'membership_plan_id', true);
                        $application_type = wc_get_order_item_meta($item_id, 'application_type', true);
                        $period = wc_get_order_item_meta($item_id, 'period', true);
                        
                        // Get membership plan name
                        $member_type = '';
                        if ($membership_plan_id) {
                            $membership_plan = wc_memberships_get_membership_plan($membership_plan_id);
                            if ($membership_plan) {
                                $member_type = $membership_plan->get_name();
                                if ($application_type) {
                                    $member_type .= ' (' . ucfirst($application_type) . ')';
                                }
                            }
                        }
                        
                        // Extract membership start and end years from period
                        // Period format: "2024-05-01 to 2025-04-30"
                        $membership_start_year = '';
                        $membership_end_year = '';
                        if (!empty($period) && strpos($period, ' to ') !== false) {
                            $dates = explode(' to ', $period);
                            if (count($dates) === 2) {
                                $membership_start_year = date('Y', strtotime(trim($dates[0])));
                                $membership_end_year = date('Y', strtotime(trim($dates[1])));
                            }
                        }
                        
                        // Get user information
                        $user_name = $user->last_name . ', ' . $user->first_name;
                        $user_email = $user->user_email;
                        
                        // Get order information
                        $order_total = $order->get_total();
                        $payment_date = $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d') : '';
                        $order_number = $order->get_order_number();
                        $payment_status = $order->get_status();
                        
                        // Get Stripe data from order meta (HPOS compatible)
                        $charge_id = $order->get_meta('_stripe_charge_id', true);
                        if (empty($charge_id)) {
                            $charge_id = $order->get_meta('_transaction_id', true);
                        }
                        
                        // Calculate Stripe fee (HPOS compatible)
                        $stripe_fee = $order->get_meta('_stripe_fee', true);
                        if (empty($stripe_fee)) {
                            $stripe_fee = $order->get_meta('_payment_fee', true);
                        }
                        if (empty($stripe_fee)) {
                            $stripe_fee = $order->get_meta('_stripe_net', true);
                        }
                        
                        // Calculate payout (order total - stripe fee)
                        $stripe_payout = '';
                        if (!empty($stripe_fee) && is_numeric($stripe_fee) && is_numeric($order_total)) {
                            $stripe_payout = number_format($order_total - $stripe_fee, 2, '.', '');
                        }
                        
                        $csv_data[] = [
                            $member_type,
                            $membership_start_year,
                            $membership_end_year,
                            $user_name,
                            $user_email,
                            $order_total,
                            $stripe_fee,
                            $stripe_payout,
                            $payment_date,
                            $order_number,
                            $charge_id,
                            ucfirst($payment_status)
                        ];
                    }
                }
            }
        }
    }
    
    // Generate CSV file
    $filename = 'membership-audit-report-' . $start_date . '-to-' . $end_date . '.csv';
    
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($csv_data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Handle Course Audit Export BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_audit_nonce'])) {
    if (!wp_verify_nonce($_POST['course_audit_nonce'], 'export_course_audit')) {
        wp_die('Security check failed');
    }
    
    // Sanitize and validate dates
    $start_date = sanitize_text_field($_POST['course_start_date']);
    $end_date = sanitize_text_field($_POST['course_end_date']);
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        wp_die('Invalid date format');
    }
    
    global $wpdb;
    
    $course_product_id = get_dummy_product_id();
    
    // Query to fetch orders containing the course product within date range
    // For HPOS compatibility, we check both wc_orders and posts tables
    $orders_table = $wpdb->prefix . 'wc_orders';
    $order_items_table = $wpdb->prefix . 'woocommerce_order_items';
    $order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
    
    // Check if HPOS is enabled
    $hpos_enabled = class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
                    method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
                    \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    
    if ($hpos_enabled) {
        // HPOS query
        $query = $wpdb->prepare("
            SELECT DISTINCT o.id as order_id, o.date_created_gmt
            FROM {$orders_table} o
            INNER JOIN {$order_items_table} oi ON o.id = oi.order_id
            INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
            WHERE oi.order_item_type = 'line_item'
            AND oim.meta_key = '_product_id'
            AND oim.meta_value = %d
            AND DATE(o.date_created_gmt) >= %s
            AND DATE(o.date_created_gmt) <= %s
            ORDER BY o.date_created_gmt DESC
        ", $course_product_id, $start_date, $end_date);
    } else {
        // Legacy query using posts table
        $posts_table = $wpdb->posts;
        $query = $wpdb->prepare("
            SELECT DISTINCT p.ID as order_id, p.post_date_gmt as date_created_gmt
            FROM {$posts_table} p
            INNER JOIN {$order_items_table} oi ON p.ID = oi.order_id
            INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
            WHERE p.post_type = 'shop_order'
            AND oi.order_item_type = 'line_item'
            AND oim.meta_key = '_product_id'
            AND oim.meta_value = %d
            AND DATE(p.post_date_gmt) >= %s
            AND DATE(p.post_date_gmt) <= %s
            ORDER BY p.post_date_gmt DESC
        ", $course_product_id, $start_date, $end_date);
    }
    
    $results = $wpdb->get_results($query);
    
    // Prepare CSV data
    $csv_data = [];
    $csv_data[] = [
        'Course Title',
        'Course Code',
        'Course Date',
        'Name',
        'Email',
        'Order Total',
        'Stripe Fee',
        'Stripe Payout',
        'Payment Date',
        'Order Number',
        'Charge ID',
        'Payment Status'
    ];
    
    foreach ($results as $row) {
        $order = wc_get_order($row->order_id);
        
        if ($order) {
            $user_id = $order->get_user_id();
            $user = get_user_by('ID', $user_id);
            
            if ($user) {
                // Get order items to find course metadata
                foreach ($order->get_items() as $item_id => $item) {
                    if ($item->get_product_id() == $course_product_id) {
                        
                        // Get course metadata from order item meta
                        $course_id = wc_get_order_item_meta($item_id, 'course_id', true);
                        $course_title = wc_get_order_item_meta($item_id, 'course_title', true);
                        $course_code = wc_get_order_item_meta($item_id, 'course_code', true);
                        
                        // Get course dates from course post meta (serialized data)
                        $course_dates = get_post_meta($course_id, 'course_dates', true);
                        $course_dates_array = maybe_unserialize($course_dates);
                        
                        // Format course dates (join all dates with comma)
                        $course_date_formatted = '';
                        if (is_array($course_dates_array) && !empty($course_dates_array)) {
                            $course_date_formatted = implode(', ', $course_dates_array);
                        }
                        
                        // Get user information
                        $user_name = $user->last_name . ', ' . $user->first_name;
                        $user_email = $user->user_email;
                        
                        // Get order information
                        $order_total = $order->get_total();
                        $payment_date = $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d') : '';
                        $order_number = $order->get_order_number();
                        $payment_status = $order_total ? $order->get_status() : 'Not applicable';
                        
                        // Get Stripe data from order meta (HPOS compatible)
                        $charge_id = $order->get_meta('_stripe_charge_id', true);
                        if (empty($charge_id)) {
                            $charge_id = $order->get_meta('_transaction_id', true);
                        }
                        
                        // Calculate Stripe fee (HPOS compatible)
                        $stripe_fee = $order->get_meta('_stripe_fee', true);
                        if (empty($stripe_fee)) {
                            $stripe_fee = $order->get_meta('_payment_fee', true);
                        }
                        if (empty($stripe_fee)) {
                            $stripe_fee = $order->get_meta('_stripe_net', true);
                        }
                        
                        // Calculate payout (order total - stripe fee)
                        $stripe_payout = '';
                        if (!empty($stripe_fee) && is_numeric($stripe_fee) && is_numeric($order_total)) {
                            $stripe_payout = number_format($order_total - $stripe_fee, 2, '.', '');
                        }
                        
                        $csv_data[] = [
                            $course_title,
                            $course_code,
                            $course_date_formatted,
                            $user_name,
                            $user_email,
                            $order_total,
                            $stripe_fee,
                            $stripe_payout,
                            $payment_date,
                            $order_number,
                            $charge_id,
                            ucfirst($payment_status)
                        ];
                    }
                }
            }
        }
    }
    
    // Generate CSV file
    $filename = 'course-audit-report-' . $start_date . '-to-' . $end_date . '.csv';
    
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($csv_data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Enqueue jQuery UI Datepicker
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Export Course Audit Report Section -->
    <div class="hkota-audit-section">
        <div class="section-header">
            <h2>Export Course Audit Report</h2>
            <p class="description">Select date range to export course enrollment and payment data</p>
        </div>
        
        <div class="section-content">
            <form id="course-audit-form" method="post">
                <?php wp_nonce_field('export_course_audit', 'course_audit_nonce'); ?>
                
                <div class="date-range-container">
                    <div class="date-input-group">
                        <label for="course_start_date">Start Date</label>
                        <input type="text" id="course_start_date" name="course_start_date" class="datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                    
                    <div class="date-separator">—</div>
                    
                    <div class="date-input-group">
                        <label for="course_end_date">End Date</label>
                        <input type="text" id="course_end_date" name="course_end_date" class="datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                </div>
                
                <div id="course-error-message" class="error-message"></div>
                
                <button type="submit" class="export-button">
                    <span class="dashicons dashicons-download"></span>
                    Export Course Audit CSV
                </button>
            </form>
        </div>
    </div>
    
    <!-- Export Membership Audit Report Section -->
    <div class="hkota-audit-section">
        <div class="section-header">
            <h2>Export Membership Audit Report</h2>
            <p class="description">Select date range to export membership registration and payment data</p>
        </div>
        
        <div class="section-content">
            <form id="membership-audit-form" method="post">
                <?php wp_nonce_field('export_membership_audit', 'membership_audit_nonce'); ?>
                
                <div class="date-range-container">
                    <div class="date-input-group">
                        <label for="membership_start_date">Start Date</label>
                        <input type="text" id="membership_start_date" name="membership_start_date" class="datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                    
                    <div class="date-separator">—</div>
                    
                    <div class="date-input-group">
                        <label for="membership_end_date">End Date</label>
                        <input type="text" id="membership_end_date" name="membership_end_date" class="datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                </div>
                
                <div id="membership-error-message" class="error-message"></div>
                
                <button type="submit" class="export-button">
                    <span class="dashicons dashicons-download"></span>
                    Export Membership Audit CSV
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.hkota-audit-section {
    background: #fff;
    padding: 0;
    margin: 30px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    overflow: hidden;
}

.section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 24px 32px;
}

.section-header h2 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 600;
    color: #fff;
}

.section-header .description {
    margin: 0;
    color: rgba(255, 255, 255, 0.9);
    font-size: 14px;
}

.section-content {
    padding: 32px;
}

.date-range-container {
    display: flex;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
}

.date-input-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.date-input-group label {
    font-size: 13px;
    font-weight: 600;
    color: #3c4043;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.date-input-group input.datepicker {
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.2s ease;
    background: #fff;
    cursor: pointer;
}

.date-input-group input.datepicker:hover {
    border-color: #667eea;
}

.date-input-group input.datepicker:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.date-separator {
    font-size: 20px;
    color: #9e9e9e;
    padding-bottom: 12px;
    font-weight: 300;
}

.export-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    padding: 14px 28px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
}

.export-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
}

.export-button:active:not(:disabled) {
    transform: translateY(0);
}

.export-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.export-button .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.error-message {
    color: #d32f2f;
    font-size: 14px;
    padding: 12px 16px;
    background: #ffebee;
    border-left: 4px solid #d32f2f;
    border-radius: 4px;
    margin-bottom: 16px;
    display: none;
}

.error-message.show {
    display: block;
}

@media (max-width: 768px) {
    .date-range-container {
        flex-direction: column;
        gap: 16px;
    }
    
    .date-separator {
        display: none;
    }
    
    .section-content {
        padding: 24px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize datepickers
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: 0, // Can't select future dates
        changeMonth: true,
        changeYear: true,
        yearRange: '-10:+0'
    });
    
    // Validate and sanitize date input
    function validateDateRange(startDate, endDate, errorElement) {
        // Clear previous error
        errorElement.removeClass('show').text('');
        
        // Check if dates are provided
        if (!startDate || !endDate) {
            errorElement.addClass('show').text('Please select both start and end dates.');
            return false;
        }
        
        // Sanitize input (remove any non-date characters)
        const datePattern = /^\d{4}-\d{2}-\d{2}$/;
        if (!datePattern.test(startDate) || !datePattern.test(endDate)) {
            errorElement.addClass('show').text('Invalid date format. Please use the date picker.');
            return false;
        }
        
        // Parse dates
        const start = new Date(startDate);
        const end = new Date(endDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Check if dates are valid
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            errorElement.addClass('show').text('Invalid date selected. Please select valid dates.');
            return false;
        }
        
        // Check if start date is before end date
        if (start > end) {
            errorElement.addClass('show').text('Start date must be before or equal to end date.');
            return false;
        }
        
        // Check if dates are not in the future
        if (start > today || end > today) {
            errorElement.addClass('show').text('Cannot select future dates.');
            return false;
        }
        
        // Check if date range is reasonable (max 1 year)
        const daysDiff = (end - start) / (1000 * 60 * 60 * 24);
        if (daysDiff > 365) {
            errorElement.addClass('show').text('Date range cannot exceed 1 year.');
            return false;
        }
        
        return true;
    }
    
    // Handle Course Audit Form submission
    $('#course-audit-form').on('submit', function(e) {
        e.preventDefault();
        
        const startDate = $('#course_start_date').val();
        const endDate = $('#course_end_date').val();
        const errorElement = $('#course-error-message');
        
        if (validateDateRange(startDate, endDate, errorElement)) {
            // Submit form
            this.submit();
        }
    });
    
    // Handle Membership Audit Form submission
    $('#membership-audit-form').on('submit', function(e) {
        e.preventDefault();
        
        const startDate = $('#membership_start_date').val();
        const endDate = $('#membership_end_date').val();
        const errorElement = $('#membership-error-message');
        
        if (validateDateRange(startDate, endDate, errorElement)) {
            // Submit form
            this.submit();
        }
    });
});
</script>
