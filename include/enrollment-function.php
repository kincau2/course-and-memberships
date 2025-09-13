<?php

// Course enrollment related function


// Create a new empty enrollment row in table wp_hkota_course_enrollment
// return ncew create class Enrollment object
function create_enrollment(){
  global $wpdb;

  // Define the table name
  $table_name = $wpdb->prefix . 'hkota_course_enrollment';

  // Prepare the data for insertion
  $data = [
      'date_created_gmt' => current_time('mysql', true),  // Get current GMT time
  ];

  // Insert the placeholder row
  $wpdb->insert($table_name, $data, ['%s']);  // %s is for the date_created_gmt field

  // Return the inserted row ID
  $new_row_id = $wpdb->insert_id;

  if($new_row_id){
    return new Enrollment($new_row_id); // Return the newly created class object Enrollment
  }else{
    return false;
  }

}

// Override the price in the cart with the custom price
add_action('woocommerce_before_calculate_totals', 'override_custom_price_in_cart', 10, 1);
function override_custom_price_in_cart($cart) {
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['course_fee'])) {
            $cart_item['data']->set_price($cart_item['course_fee']);
        }
        if (isset($cart_item['membership_fee'])) {
            $cart_item['data']->set_price($cart_item['membership_fee']);
        }
        if (isset($cart_item['custom_price'])) {
            $cart_item['data']->set_price($cart_item['custom_price']);
        }
    }
}

// Display custom meta data in the cart
add_filter('woocommerce_get_item_data', 'display_custom_meta_in_cart', 10, 2);
function display_custom_meta_in_cart($item_data, $cart_item) {

  if (isset($cart_item['course_title'])) {
      $item_data[] = array(
          'name' => 'Course title',
          'value' => sanitize_text_field($cart_item['course_title']),
      );
  }
  if (isset($cart_item['course_code'])) {
      $item_data[] = array(
          'name' => 'Course code',
          'value' => sanitize_text_field($cart_item['course_code']),
      );
  }
  if (isset($cart_item['date'])) {
      $item_data[] = array(
          'name' => 'Date',
          'value' => sanitize_text_field($cart_item['date']),
      );
  }
  if (isset($cart_item['time'])) {
      $item_data[] = array(
          'name' => 'Time',
          'value' => sanitize_text_field($cart_item['time']),
      );
  }
  if (isset($cart_item['cpd_point'])) {
      $item_data[] = array(
          'name' => 'Course CPD point',
          'value' => sanitize_text_field($cart_item['cpd_point']),
      );
  }
  if (isset($cart_item['uploads'])) {
    foreach ($cart_item['uploads'] as $cert_id => $filename) {
      $item_data[] = array(
          'name' => 'Doucment submitted for ' . str_replace("cert_","",$cert_id),
          'value' => sanitize_text_field($filename),
      );
    }

  }

  if (isset($cart_item['membership_plan_id'])) {
      $membership = wc_memberships_get_membership_plan($cart_item['membership_plan_id']);
      $item_data[] = array(
          'name' => 'Membership',
          'value' => $membership->name,
      );
  }

  if (isset($cart_item['application_type'])) {
      $item_data[] = array(
          'name' => 'Applcation type',
          'value' => ucfirst( $cart_item['application_type'] ),
      );
  }

  if ( isset($cart_item['start_year']) && isset($cart_item['years']) ) {
      $start_date = date( 'Y' . '-05-01' , strtotime( $cart_item['start_year'] . '-05-01' ) );
      $end_date = date( 'Y' . '-04-30' , strtotime( '+ ' . $cart_item['years'] . ' years' , strtotime( $start_date ) ) );
      $item_data[] = array(
          'name' => 'Period',
          'value' => $start_date.' to '.$end_date,
      );
  }

  return $item_data;

}

// Check if user have add the same course to cart.
function is_course_in_cart($course_id) {
    foreach (WC()->cart->get_cart() as $cart_item) {
        if ($cart_item['course_id'] == $course_id) {
            return true; // Product is in the cart
        }
    }
    return false; // Product is not in the cart
}

// Final check if the course has capacity to enroll.
add_action('woocommerce_after_checkout_validation', 'final_validation_before_payment', 10, 3);
function final_validation_before_payment($fields, $errors) {

  $course_product_id = get_dummy_product_id();// Get the product ID dedicated for course registration only.
  $membership_product_id = get_membership_dummy_product_id();// Get the product ID dedicated for course registration only.

  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    if( $cart_item['product_id'] == $course_product_id ) {
      $course_id = $cart_item['course_id'];
      if( !empty($course_id) ){
        $course = new Course($course_id);
        if( $course->get_enrollment_status() !== 'available' || !$course->get_user_eligibility( get_current_user_id() )['is_eligible'] ){
          wc_add_notice(__('Sorry, ' . $course->title . ' is either not available
          for register or you do not meet the enrollment requirment. We have removed this item from your cart.', 'woocommerce'), 'error');
          WC()->cart->remove_cart_item( $cart_item_key );
        }
      } else{
        wc_add_notice(__('Sorry, ' . 'unexpected error occurred.', 'woocommerce'), 'error');
        WC()->cart->remove_cart_item( $cart_item_key );
      }
    }

    if( $cart_item['product_id'] == $membership_product_id ){
      $membership_plan_id = $cart_item['membership_plan_id'];
      if( empty( $membership_plan_id ) ||
          empty( $cart_item['start_year'] ) ||
          empty( $cart_item['years'] ) ||
          empty( $cart_item['application_type'] ) ){
        wc_add_notice(__('Sorry, ' . 'unexpected error occurred.', 'woocommerce'), 'error');
        WC()->cart->remove_cart_item( $cart_item_key );
      }

      if( $cart_item['application_type'] == 'renew' && empty( $cart_item['user_membership_id'] ) ){
        wc_add_notice(__('Sorry, ' . 'unexpected error occurred.', 'woocommerce'), 'error');
        WC()->cart->remove_cart_item( $cart_item_key );
      }

      if( $cart_item['application_type'] == 'renew' && !empty( $cart_item['user_membership_id'] ) ){
          $user_memberships = new WC_Memberships_User_Membership($cart_item['user_membership_id']);
          if( get_current_user_id() !== $user_memberships->user_id ){
            wc_add_notice(__('Sorry, ' . 'unexpected error occurred.', 'woocommerce'), 'error');
            WC()->cart->remove_cart_item( $cart_item_key );
          }
      }

      if( $cart_item['years'] > date('Y') + 2 ){
        wc_add_notice(__('Sorry, ' . 'unexpected error occurred.', 'woocommerce'), 'error');
        WC()->cart->remove_cart_item( $cart_item_key );
      }

      $application_type = $cart_item['application_type'];
      $result = check_user_membership_eligiblilty(get_current_user_id(),$membership_plan_id,$application_type);
      if( !$result['permitted'] ){
        wc_add_notice( $result['message'] . ' We have removed this item from your cart.' , 'error');
        WC()->cart->remove_cart_item( $cart_item_key );
      }

    }
 }

}

// Check if same product already in cart to ensure only one course will be registered each time.
function is_product_in_cart($product_id) {
    // Get the cart items
    $cart_items = WC()->cart->get_cart();

    // Loop through cart items and check for the product ID
    foreach ($cart_items as $cart_item_key => $cart_item) {
        if ($cart_item['product_id'] == $product_id) {
            return true; // Product is in the cart
        }
    }
    return false; // Product is not in the cart
}

// Add course data to order meta
add_action('woocommerce_checkout_create_order_line_item', 'add_custom_meta_to_order_items', 10, 4);
function add_custom_meta_to_order_items($item, $cart_item_key, $values, $order) {
    if (isset($values['course_id'])) {
        $item->add_meta_data( "course_id" , sanitize_text_field($values['course_id']), true);
    }
    if (isset($values['course_title'])) {
        $item->add_meta_data( "course_title" , sanitize_text_field($values['course_title']), true);
    }
    if (isset($values['course_code'])) {
        $item->add_meta_data( "course_code" , sanitize_text_field($values['course_code']), true);
    }
    if (isset($values['date'])) {
        $item->add_meta_data( "date" , sanitize_text_field($values['date']), true);
    }
    if (isset($values['time'])) {
        $item->add_meta_data( "time" , sanitize_text_field($values['time']), true);
    }
    if (isset($values['cpd_point'])) {
        $item->add_meta_data( "cpd_point" , sanitize_text_field($values['cpd_point']), true);
    }
    if (isset($values['uploads'])) {
        $item->add_meta_data( "uploads" , $values['uploads'] , true);
    }

    if (isset($values['membership_plan_id'])) {
        $membership_plan = new WC_Memberships_Membership_Plan($values['membership_plan_id']);

        $item->add_meta_data( "membership" , $membership_plan->name , true);
        $item->add_meta_data( "membership_plan_id" , $values['membership_plan_id'] , true);
    }
    if (isset($values['application_type'])) {
        $item->add_meta_data( "application_type" , $values['application_type'] , true);
    }
    if ( isset( $values['start_year'] ) && isset( $values['years'] ) ) {
        $item->add_meta_data( "start_year" , $values['start_year'] , true);
        $item->add_meta_data( "years" , $values['years'] , true);
        $start_date = date( 'Y' . '-05-01' , strtotime( $values['start_year'] . '-05-01' ) );
        $end_date = date( 'Y' . '-04-30' , strtotime( '+ ' . $values['years'] . ' years' , strtotime( $start_date ) ) );
        $item->add_meta_data( "period" , $start_date.' to '.$end_date , true);
    }

    if (isset($values['user_membership_id'])) {
        $item->add_meta_data( "user_membership_id" , $values['user_membership_id'] , true);
    }

}

// Modify the product meta output on frontend.
add_action('woocommerce_order_item_meta_end','modify_order_details_page_meta_strings');
function modify_order_details_page_meta_strings(){
  ?>
  <script>
    jQuery(document).ready(function(){
      jQuery('.wc-item-meta-label').each( function(){
        var str = jQuery(this).text();
        if( str == 'course_id:' ||
            str == 'start_year:' ||
            str == 'years:' ||
            str == 'membership_plan_id:' ||
            str == 'user_membership_id:'
          ){
          jQuery(this).parent().remove();
        } else {
          str = str.replace('_', ' ');
          jQuery(this).text(str.charAt(0).toUpperCase() + str.slice(1));
        }
      });
    });
  </script>
  <?php
}

// Hook into order status change (to completed), and enroll user to course
add_action('woocommerce_order_status_changed', 'order_completed_register_pupil_to_course', 10, 4);
function order_completed_register_pupil_to_course($order_id, $old_status, $new_status, $order) {

  if ( $new_status !== 'completed') return;

  $product_id = get_dummy_product_id();// Get the product ID dedicated for course registration only.
  $course_ids = array();

  foreach ( $order->get_items() as $item_id => $item ) {
    if( $item->get_product_id() == $product_id ){
      // Run if order item contain product for course register
      $course_id = wc_get_order_item_meta( $item_id, 'course_id', true );
      $user_id = $order->get_customer_id();
      if(empty($course_id)) continue;
      $course = new Course($course_id);
      if ( function_exists( 'wc_memberships_get_user_memberships' ) && $course->is_member_only ){
        $memberships = wc_memberships_get_user_memberships($user_id);
        foreach ($memberships as $membership ) {
          // can be: wcm-active, wcm-cancelled, wcm-complimentary, wcm-delayed, wcm-expired, wcm-paused, wcm-pending
          $membership_status = get_post_status($membership->id);

          switch($membership_status){
            case 'wcm-active':
              $status = 'enrolled';
              break;
            case 'wcm-paused':
              $status = ($status == 'enrolled')? 'enrolled' : 'pending' ;
              break;
          }
        }
      } else {
        $status = 'enrolled';
      }
      if( $course->is_uploads_required && $status == 'enrolled' ){
        $status = 'awaiting_approval';
      }

      // Now check if user has waiting list on this course.
      $enrollment_id = get_user_enrollment_id($user_id, $course_id);
      if( $enrollment_id ){
        $enrollment = new Enrollment($enrollment_id);
        if( $enrollment->status == 'waiting_list' || $enrollment->status == 'on_hold' ){
          $course->set_attendance_record($user_id);
          $enrollment->set('amount',$item->get_total());
          set_transient('debug', 'fired, status: '.$status, 30);
          $enrollment->set('status',$status);
          $enrollment->set('order_id',$order_id);
          $enrollment->set('payment_method',$order->get_payment_method_title());
          $enrollment->set('uploads',wc_get_order_item_meta( $item_id, 'uploads'));
          $enrollment->set('certificate_status','not_issue');
        } else{
          // User already enrolled or pending or rejected, do nothing.
          continue;
        }
      } else{
        $enrollment = $course->enroll($user_id,$status); // enrollment status: enrolled, awaiting_approval, pending, waiting_list, rejected, on_hold
        $course->set_attendance_record($user_id);
        $enrollment->set('amount',$item->get_total());
        $enrollment->set('order_id',$order_id);
        $enrollment->set('payment_method',$order->get_payment_method_title());
        $enrollment->set('uploads',wc_get_order_item_meta( $item_id, 'uploads'));
        $enrollment->set('certificate_status','not_issue');
      }

      // Enrollment completed, now send email base on enrollment status
      switch($status){
        case 'enrolled':
          $course->trigger_enrolled_email($user_id);
          break;
        case 'awaiting_approval':
          $course->trigger_awaiting_approval_email($user_id);
          break;
        case 'pending':
          $course->trigger_pending_email($user_id);
          break;
      }


    }
  }
}

// Hook into order status change (to on-hold), and enroll user to course
add_action('woocommerce_order_status_changed', 'order_on_hold_register_pupil_to_course', 10, 4);
function order_on_hold_register_pupil_to_course($order_id, $old_status, $new_status, $order) {

  if ( $new_status !== 'on-hold') return;

  $product_id = get_dummy_product_id();// Get the product ID dedicated for course registration only.
  $course_ids = array();

  foreach ( $order->get_items() as $item_id => $item ) {
    if( $item->get_product_id() == $product_id ){
      // Run if order item contain product for course register
      $course_id = wc_get_order_item_meta( $item_id, 'course_id', true );
      $user_id = $order->get_customer_id();
      if(empty($course_id)) continue;
      $course = new Course($course_id);
      $status = 'on_hold';
      // Now check if user has waiting list on this course.
      $enrollment_id = get_user_enrollment_id($user_id, $course_id);
      if( $enrollment_id ){
        $enrollment = new Enrollment($enrollment_id);
        if( $enrollment->status == 'waiting_list' ){
          $course->set_attendance_record($user_id);
          $enrollment->set('amount',$item->get_total());
          $enrollment->set('status',$status);
          $enrollment->set('order_id',$order_id);
          $enrollment->set('payment_method',$order->get_payment_method_title());
          $enrollment->set('uploads',wc_get_order_item_meta( $item_id, 'uploads'));
          $enrollment->set('certificate_status','not_issue');
        }
      } else{
        $enrollment = $course->enroll($user_id,$status); // enrollment status: enrolled, awaiting_approval, pending, waiting_list, rejected, on_hold
        $course->set_attendance_record($user_id);
        $enrollment->set('amount',$item->get_total());
        $enrollment->set('order_id',$order_id);
        $enrollment->set('payment_method',$order->get_payment_method_title());
        $enrollment->set('uploads',wc_get_order_item_meta( $item_id, 'uploads'));
        $enrollment->set('certificate_status','not_issue');
      }

      $course->trigger_on_hold_email($user_id);

    }
  }
}

// skip customer processing & completed emails on zero‑total orders
add_filter('woocommerce_email_enabled_customer_processing_order', 'hkota_disable_zero_total_email', 10, 2);
add_filter('woocommerce_email_enabled_customer_completed_order',  'hkota_disable_zero_total_email', 10, 2);

function hkota_disable_zero_total_email( $enabled, $order ) {
    if ( is_a( $order, 'WC_Order' ) && $order->get_total() == 0 ) {
        return false;
    }
    return $enabled;
}

// Return user enrollment ID of a specific course.
function get_user_enrollment_id($user_id, $course_id) {
    global $wpdb;

    // Define the table name
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Prepare the query to fetch the enrollment ID
    $enrollment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $table_name WHERE user_id = %d AND course_id = %d",
        $user_id, $course_id
    ));

    // Check if the enrollment ID was found
    if ($enrollment_id) {
        return $enrollment_id;
    } else {
        return false; // Return false if no enrollment record is found
    }
}

// Return all enrollments objects of a user base on enrollment status specified in $args
function get_user_enrollments($user_id, $args = []) {
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Base query to select user enrollments
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id);

    // Check if $args is specified and if 'status' is provided in the array
    if (!empty($args['status'])) {
        // Prepare placeholders for the status values
        $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));

        // Append the status condition to the query
        $query .= $wpdb->prepare(" AND status IN ($placeholders)", ...$args['status']);
    }

    // Execute the query
    $results = $wpdb->get_results($query);

    // Return the results
    if (!empty($results)) {
        $enrollments = array();
        foreach ($results as $row ) {
          $enrollments[] = new Enrollment($row->ID);
        }
        return $enrollments;
    } else {
        return false;  // No results found
    }
}

// Get user CPD record by 'enrollment_id' or course_id'
function get_user_cpd_record_by($method, $arg) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hkota_cpd_records';

    // Determine the query based on the method
    if ($method === 'enrollment_id') {
        // Fetch based on user_id and enrollment_id
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND enrollment_id = %d",
            $arg['user_id'], $arg['enrollment_id']
        ));
    } elseif ($method === 'course_id') {
        // Fetch based on user_id and course_id
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND course_id = %d",
            $arg['user_id'], $arg['course_id']
        ));
    } else {
        return false; // Invalid method
    }

    // Return the result or false if not found
    return $result ? $result : false;
}

// Get user all CPD records
function get_user_cpd_records($status = null, $user_id = null ) {
    global $wpdb;

    // If $user_id is not passed, default to the current user ID
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $table_name = $wpdb->prefix . 'hkota_cpd_records';

    // Prepare the query to fetch 'issued' CPD records for the user
    if (!$status) {

      $cpd_records = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM $table_name WHERE user_id = %d",
          $user_id
      ));

    } else{

      $cpd_records = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM $table_name WHERE user_id = %d AND status = %s",
          $user_id, $status
      ));

    }

    // Return results if found, otherwise false
    if (!empty($cpd_records)) {
        return $cpd_records;
    } else {
        return false;
    }
}

// Automatically complete order once payment is complete.
add_action( 'woocommerce_payment_complete', 'auto_complete_paid_order' );
function auto_complete_paid_order( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    // Get the order object
    $order = wc_get_order( $order_id );

    // Check if the order is already completed
    if ( $order->get_status() === 'completed' ) {
        return;
    }

    $order->update_status( 'completed' );

}

// Disable woocommerce persistent cart function
add_filter('woocommerce_persistent_cart_enabled', '__return_false');

// Set user session to 5 days
add_filter('wc_session_expiration' , 'custom_woocommerce_session_expiry_for_all');
function custom_woocommerce_session_expiry_for_all($seconds) {
   return 60*60*24*5;
}

// Delete user uploaded data base on input sessions (single data row in table wp_woocommerce_sessions)
function delete_uploaded_files_on_session($session) {
  // Unserialize the session data to access cart contents
  $session_data = maybe_unserialize($session->session_value);

  if (isset($session_data['cart'])) {
      // Loop through cart items
      $cart_items = maybe_unserialize($session_data['cart']);

      foreach ($cart_items as $cart_item) {
          if (isset($cart_item['uploads']) && is_array($cart_item['uploads'])) {
              // Loop through uploads and delete each file
              foreach ($cart_item['uploads'] as $cert_key => $filename) {
                  $file_path = COURSE_PUPIL_FILE_DIR . $filename;

                  // Check if file exists and delete it
                  if (file_exists($file_path)) {
                      wp_delete_file($file_path);
                  }
              }
          }
      }
  }
}

add_action('woocommerce_remove_cart_item', 'delete_uploaded_files_on_cart_item_removal', 10, 2);
function delete_uploaded_files_on_cart_item_removal($cart_item_key, $cart) {
    // Get the cart item by its key
    $cart_item = $cart->get_cart_item($cart_item_key);

    // Check if the cart item has any uploaded files stored in the 'uploads' meta
    if (isset($cart_item['uploads']) && is_array($cart_item['uploads'])) {
        // Loop through each uploaded file and delete it from the server
        foreach ($cart_item['uploads'] as $cert_key => $filename) {
            $file_path = COURSE_PUPIL_FILE_DIR . '/' . $filename;

            // Check if the file exists and delete it
            if (file_exists($file_path)) {
                wp_delete_file($file_path);
            }
        }
    }
}

// Disable cart product item link.
add_filter('woocommerce_cart_item_name', 'remove_product_link_in_cart', 10, 3);
function remove_product_link_in_cart($product_name, $cart_item, $cart_item_key) {
    // Get the product
    $product = $cart_item['data'];

    // Return just the product name without the link
    return '<b><div class="title">'.$product->get_name().'</div></b>';
}

// Disable order details product item link.
add_filter('woocommerce_order_item_name', 'remove_product_link_thank_you_page', 10, 2);
function remove_product_link_thank_you_page($product_name, $item) {

    return $item->get_name();

}

// Remove quantity on checkout for course item
add_filter('woocommerce_checkout_cart_item_quantity', 'remove_quantity_for_specific_product', 10, 2);
function remove_quantity_for_specific_product($quantity_html, $cart_item) {
    // Define the specific product ID(s) where the quantity should be hidden
    $course_product_id = get_dummy_product_id();
    $membership_product_id = get_membership_dummy_product_id();

    // Check if the current cart item matches the product ID
    if ($cart_item['product_id'] == $course_product_id ||
        $cart_item['product_id'] == $membership_product_id ) {
        return ''; // Return empty string to hide the quantity for this product
    }

    // Return the default quantity display for all other products
    return $quantity_html;
}

// Change frontend wording "product" to "item"
add_filter('gettext', 'replace_product_with_item', 10, 3);
add_filter('ngettext', 'replace_product_with_item', 10, 3);
function replace_product_with_item($translated, $text, $domain) {
    // List of translations to replace
    $replacements = array(
        'Product'  => 'Item',
        'Products' => 'Items',
        'product'  => 'item',
        'products' => 'items'
    );

    // Perform replacements
    if (isset($replacements[$text])) {
        $translated = $replacements[$text];
    }

    return $translated;
}

// Remove underscores from item meta key display in WooCommerce emails
add_filter('woocommerce_order_item_display_meta_key', 'remove_underscores_from_item_meta_keys', 10, 2);
function remove_underscores_from_item_meta_keys($display_key, $meta) {
    // Replace underscores with spaces and capitalize first letters
    $display_key = str_replace('_', ' ', $display_key);
    $display_key = ucwords($display_key);

    return $display_key;
}

// Remove backend use only meta data from item meta key display in WooCommerce emails and frontend
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'hide_course_id_meta_in_emails', 10, 2 );
function hide_course_id_meta_in_emails( $formatted_meta, $item ) {
    // Loop through the meta data and unset the 'course_id'
    foreach ( $formatted_meta as $key => $meta ) {
        if ( $meta->key == 'course_id' ||
             $meta->key == 'start_year' ||
             $meta->key == 'years' ||
             $meta->key == 'membership_plan_id' ||
             $meta->key == 'user_membership_id'
           ) {
            unset( $formatted_meta[$key] ); // Remove the 'course_id' meta
        }
    }
    return $formatted_meta;
}

// Add custom CSS to WooCommerce emails
add_filter('woocommerce_email_styles', 'custom_woocommerce_email_styles');
function custom_woocommerce_email_styles($css) {
    $custom_css = "
        /* Style for item meta in WooCommerce order emails */
        ul.wc-item-meta li {
            display: flex;
            flex-direction: row;
        }
        strong.wc-item-meta-label {
            flex: 0 0 90px;
        }
        .email-spacing-wrap, td.address-td, table#addresses, td.address-container  {
            margin-bottom: unset !important;
        }
    ";

    // Append the custom CSS to the existing WooCommerce email styles
    return $css . $custom_css;
}
