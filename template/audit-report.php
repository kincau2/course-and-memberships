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
    $financial_year = sanitize_text_field($_POST['membership_financial_year']);
    if (empty($financial_year) || strpos($financial_year, '|') === false) {
        wp_die('Invalid date range selected');
    }
    
    list($start_date, $end_date) = explode('|', $financial_year);
    
    // Calculate query start date (6 years back to catch multi-year memberships)
    $query_start_date = date('Y-m-d', strtotime('-6 years', strtotime($start_date)));
    
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
        ", $membership_product_id, $query_start_date, $end_date);
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
        ", $membership_product_id, $query_start_date, $end_date);
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
        'Pro-rata Net Income',
        'Pro-rata Stripe Fee',
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
                        $duration_years = 1;
                        $include_in_report = false;

                        if (!empty($period) && strpos($period, ' to ') !== false) {
                            $dates = explode(' to ', $period);
                            if (count($dates) === 2) {
                                $mem_start_str = trim($dates[0]);
                                $mem_end_str = trim($dates[1]);
                                $mem_start_ts = strtotime($mem_start_str);
                                $mem_end_ts = strtotime($mem_end_str);
                                
                                $membership_start_year = date('Y', $mem_start_ts);
                                $membership_end_year = date('Y', $mem_end_ts);
                                
                                // Calculate duration in years
                                $duration_years = (int)$membership_end_year - (int)$membership_start_year;
                                if ($duration_years < 1) $duration_years = 1;
                                
                                // Check if this membership covers the report period
                                // Report period: $start_date to $end_date
                                $report_start_ts = strtotime($start_date);
                                $report_end_ts = strtotime($end_date);
                                
                                // Check if the report period is covered by the membership period
                                // Logic: Membership Start <= Report Start AND Membership End >= Report End
                                // Or simply if the membership is active during this financial year
                                // Since FYs are aligned (May-Apr), we check if report start >= mem start AND report end <= mem end
                                if ($report_start_ts >= $mem_start_ts && $report_end_ts <= $mem_end_ts) {
                                    $include_in_report = true;
                                }
                            }
                        } else {
                            // Fallback for old data or missing period: check order date
                            // If order date is within report range, include it as 1 year
                            $order_date_ts = strtotime($row->date_created_gmt);
                            $report_start_ts = strtotime($start_date);
                            $report_end_ts = strtotime($end_date);
                            
                            if ($order_date_ts >= $report_start_ts && $order_date_ts <= $report_end_ts) {
                                $include_in_report = true;
                                $duration_years = 1;
                            }
                        }
                        
                        if (!$include_in_report) {
                            continue;
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
                        $net_income = 0;
                        
                        if (is_numeric($order_total)) {
                            $fee_val = (!empty($stripe_fee) && is_numeric($stripe_fee)) ? $stripe_fee : 0;
                            $net_income = $order_total - $fee_val;
                        }

                        if (!empty($stripe_fee) && is_numeric($stripe_fee) && is_numeric($order_total)) {
                            $stripe_payout = number_format($order_total - $stripe_fee, 2, '.', '');
                        }
                        
                        // Calculate Pro-rata Net Income
                        $pro_rata_income = '';
                        if (is_numeric($net_income)) {
                             $pro_rata_income = number_format($net_income / $duration_years, 2, '.', '');
                        }

                        // Calculate Pro-rata Stripe Fee
                        $pro_rata_stripe_fee = '';
                        if (is_numeric($stripe_fee) && !empty($stripe_fee)) {
                             $pro_rata_stripe_fee = number_format($stripe_fee / $duration_years, 2, '.', '');
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
                            $pro_rata_income,
                            $pro_rata_stripe_fee,
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
    $financial_year = sanitize_text_field($_POST['course_financial_year']);
    if (empty($financial_year) || strpos($financial_year, '|') === false) {
        wp_die('Invalid date range selected');
    }
    
    list($start_date, $end_date) = explode('|', $financial_year);
    
    // Calculate query start date (1 year back to catch early course purchases)
    $query_start_date = date('Y-m-d', strtotime('-1 year', strtotime($start_date)));
    
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
        ", $course_product_id, $query_start_date, $end_date);
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
        ", $course_product_id, $query_start_date, $end_date);
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
        'Net Income',
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
                        $course_title = html_entity_decode($course_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $course_code = wc_get_order_item_meta($item_id, 'course_code', true);
                        
                        // Get course dates from course post meta (serialized data)
                        $course_dates = get_post_meta($course_id, 'course_dates', true);
                        $course_dates_array = maybe_unserialize($course_dates);
                        
                        // Determine if course should be included in this report
                        $include_in_report = false;
                        $report_start_ts = strtotime($start_date);
                        $report_end_ts = strtotime($end_date);
                        
                        if (is_array($course_dates_array) && !empty($course_dates_array)) {
                            // Sort to get the earliest date
                            $temp_dates = $course_dates_array;
                            sort($temp_dates);
                            $course_start_date = $temp_dates[0];
                            $course_start_ts = strtotime($course_start_date);
                            
                            if ($course_start_ts >= $report_start_ts && $course_start_ts <= $report_end_ts) {
                                $include_in_report = true;
                            }
                        } else {
                            // Fallback: if no course dates, use order date
                            // Only include if order date is within report period
                            $order_date_ts = strtotime($row->date_created_gmt);
                            if ($order_date_ts >= $report_start_ts && $order_date_ts <= $report_end_ts) {
                                $include_in_report = true;
                            }
                        }
                        
                        if (!$include_in_report) {
                            continue;
                        }
                        
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
                        $net_income = '';
                        
                        if (is_numeric($order_total)) {
                            $fee_val = (!empty($stripe_fee) && is_numeric($stripe_fee)) ? $stripe_fee : 0;
                            $net_income = number_format($order_total - $fee_val, 2, '.', '');
                        }

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
                            $net_income,
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

// Generate Financial Year Options
$current_year = (int)date('Y');
$current_month = (int)date('n');
$current_day = (int)date('j');

// Determine the start year of the current financial year
// If today is after 1 May of current year, current FY starts 1 May of current year
// If today is before 1 May of current year, current FY starts 1 May of previous year
if ($current_month > 5 || ($current_month == 5 && $current_day >= 1)) {
    $latest_start_year = $current_year;
} else {
    $latest_start_year = $current_year - 1;
}

$start_year_data = 2024; // Data starts Oct 2024, so first FY is May 2024 - Apr 2025
$fy_options = [];

// Ensure we at least have the base year
if ($latest_start_year < $start_year_data) {
    $latest_start_year = $start_year_data;
}

for ($y = $latest_start_year; $y >= $start_year_data; $y--) {
    $start = $y . '-05-01';
    $end = ($y + 1) . '-04-30';
    // Format: 1May2024-30Apr2025
    $label = date('jMY', strtotime($start)) . '-' . date('jMY', strtotime($end));
    $fy_options[] = [
        'value' => $start . '|' . $end,
        'label' => $label,
        'selected' => ($y === $latest_start_year)
    ];
}
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
                    <div class="date-input-group" style="width: 100%;">
                        <label for="course_financial_year">Financial Year</label>
                        <select id="course_financial_year" name="course_financial_year" class="financial-year-select">
                            <?php foreach ($fy_options as $option): ?>
                                <option value="<?php echo esc_attr($option['value']); ?>" <?php selected($option['selected'], true); ?>>
                                    <?php echo esc_html($option['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                    <div class="date-input-group" style="width: 100%;">
                        <label for="membership_financial_year">Financial Year</label>
                        <select id="membership_financial_year" name="membership_financial_year" class="financial-year-select">
                            <?php foreach ($fy_options as $option): ?>
                                <option value="<?php echo esc_attr($option['value']); ?>" <?php selected($option['selected'], true); ?>>
                                    <?php echo esc_html($option['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

.financial-year-select {
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.2s ease;
    background: #fff;
    cursor: pointer;
    width: 100%;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007CB2%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat;
    background-position: right 16px top 50%;
    background-size: 12px auto;
}

.financial-year-select:hover {
    border-color: #667eea;
}

.financial-year-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
    
    .section-content {
        padding: 24px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle Course Audit Form submission
    $('#course-audit-form').on('submit', function(e) {
        // No validation needed for select box
        return true;
    });
    
    // Handle Membership Audit Form submission
    $('#membership-audit-form').on('submit', function(e) {
        // No validation needed for select box
        return true;
    });
});
</script>
