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

  if ( isset($cart_item['end_year']) && isset($cart_item['years']) ) {
      $start_date = date( 'Y' . '-05-01' , strtotime( ' - 1 year' , strtotime( $cart_item['end_year'] . '-01-01' ) ) );
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
          empty( $cart_item['end_year'] ) ||
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
    if ( isset( $values['end_year'] ) && isset( $values['years'] ) ) {
        $item->add_meta_data( "end_year" , $values['end_year'] , true);
        $item->add_meta_data( "years" , $values['years'] , true);
        $start_date = date( 'Y' . '-05-01' , strtotime( ' - 1 year' , strtotime( $values['end_year'] . '-01-01' ) ) );
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
            str == 'end_year:' ||
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
        if( $enrollment->status == 'waiting_list' ){
          $course->set_attendance_record($user_id);
          $enrollment->set('amount',$item->get_total());
          $enrollment->set('status',$status);
          $enrollment->set('order_id',$order_id);
          $enrollment->set('payment_method',$order->get_payment_method_title());
          $enrollment->set('uploads',wc_get_order_item_meta( $item_id, 'uploads'));
          $enrollment->set('certificate_status','not_issue');
        } elseif( $enrollment->status == 'on_hold' ){
          $enrollment->set('status','enrolled');
        }
      } else{
        $enrollment = $course->enroll($user_id,$status); // enrollment status: enrolled, awaiting_approval, pending, waiting_list, rejected, on-hold
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
          $course->trigger_pending_email($user_id);
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
        $enrollment = $course->enroll($user_id,$status); // enrollment status: enrolled, awaiting_approval, pending, waiting_list, rejected, on-hold
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
             $meta->key == 'end_year' ||
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


//  Membership related function



// Action after admin change user's paused membership status to active
// Fire  admin change a user membership status
add_action( 'wc_memberships_user_membership_status_changed', 'change_enrollment_status_when_member_active', 10, 3 );
function change_enrollment_status_when_member_active( $user_membership, $old_status, $new_status ) {

  global $wpdb;
  $user_id = $user_membership->get_user_id();

  // Bail if the member doesn't currently have the Site Member role or is an active member
  if ( !empty(wc_memberships_get_user_active_memberships( $user_id ) ) ) {

    if( $old_status == 'paused' && $new_status == 'active' ){
      send_active_membership_email($user_id);

      $args = array(
        'status' => [ 'pending' ]
      );
      $enrollments = get_user_enrollments($user_id,$args);
      foreach ( $enrollments as $enrollment ) {
        $course = new Course($enrollment->course_id);
        if( $course->is_uploads_required ){
          $enrollment->set('status','awaiting_approval');
          $course->trigger_awaiting_approval_email($user_id);
        } else{
          $enrollment->set('status','enrolled');
          $course->trigger_enrolled_email($user_id);
        }
      }
    }
  }
}

// Hook into order status change (to completed), and set user membership status to pause if for new purchase.
add_action('woocommerce_order_status_changed', 'set_default_membership_status_to_pause', 10, 4);
function set_default_membership_status_to_pause($order_id, $old_status, $new_status, $order){

  if ( $new_status !== 'completed') return;

  $membership_product_id = get_membership_dummy_product_id();// Get the product ID dedicated for course registration only.

  foreach ( $order->get_items() as $item_id => $item ) {
    if( $item->get_product_id() == $membership_product_id ){
      // Run if order item contain product for granting membership
      $membership_plan_id = wc_get_order_item_meta( $item_id, 'membership_plan_id', true );
      $application_type = wc_get_order_item_meta( $item_id, 'application_type', true );
      $years = wc_get_order_item_meta( $item_id, 'years', true );
      $next_end_year = wc_get_order_item_meta( $item_id, 'end_year', true );
      $membership_start_date = date( 'Y' . '-05-01' , strtotime( ' - 1 year' , strtotime( $next_end_year . '-01-01' ) ) );
      $membership_end_date = date( 'Y' . '-04-30 16:00:00' , strtotime( '+ ' . $years . ' years' , strtotime( $membership_start_date ) ) );

      $user_id = $order->get_user_id();

      if( $application_type == 'new' ){
        // New membership applcation
        $args = array(
          'plan_id'	=> $membership_plan_id,
          'user_id'	=> $user_id,
        );

        $user_membership = wc_memberships_create_user_membership( $args,'create');

        if($user_membership){
          update_post_meta($user_membership->id,'_end_date', $membership_end_date );
          set_membership_status( $user_id ,$user_membership->id,"wcm-paused");
          expire_user_other_membership($user_id,$user_membership->id);
          send_paused_membership_email($user_id);
        }

      } elseif( $application_type == 'renew' ){

        $args = array(
          'user_membership_id' => wc_get_order_item_meta( $item_id, 'user_membership_id', true ),
          'plan_id'	           => $membership_plan_id,
          'user_id'	           => $user_id,
        );

        $user_membership = wc_memberships_create_user_membership( $args,'renew');
        set_transient('debug', $user_membership, 30 );
        update_post_meta($user_membership->id,'_end_date', $membership_end_date );
        set_membership_status( $user_id ,$user_membership->id,"wcm-active");
        expire_user_other_membership($user_id,$user_membership->id);
        send_renew_membership_email($user_id);
      }
      // Generate membership number. Format: pwm-672-20-23
      $surname = strtolower( get_user_meta($user_id,'member_last_name_eng',true) );
      $firstname = strtolower( get_user_meta($user_id,'member_first_name_eng',true) );
      // Split the string into words.
      $words = explode(' ', $firstname);
      $initials = substr( $surname , 0 , 1 );
      foreach ($words as $word) {
          $initials .= substr( $word , 0 , 1 );
      }
      $hkid = substr( get_user_meta($user_id,'member_hkid',true) , -3);
      $membership_start_date = date('y' , strtotime( $membership_start_date ) );
      $membership_end_date = date('y' , strtotime( $membership_end_date ) );
      $membership_number = $initials.'-'.$hkid.'~'.$membership_start_date.'-'.$membership_end_date;
      update_user_meta($user_id, 'member_number',$membership_number);
    }
  }
}

// Expire user other user membership except the new one
function expire_user_other_membership($user_id,$new_user_membership_id){

  $user_memberships = wc_memberships_get_user_memberships($user_id);

  foreach ($user_memberships as $user_membership) {
    if( $user_membership->id !== $new_user_membership_id ){
      if( $user_membership->status == 'wcm-active' ||
          $user_membership->status == 'wcm-delay' ||
          $user_membership->status == 'wcm-paused'
        ){
        update_post_meta( $user_membership->id, '_end_date' , date('Y-m-d') );
        set_membership_status( $user_id ,$user_membership->id,"wcm-expired");
      }
    }
  }

}

// Set status of a user's membership plan
function set_membership_status($user_id, $user_membership_id, $status) {
    global $wpdb;

    // Allowed statuses for membership
    $allowed_statuses = [
        'wcm-active',
        'wcm-cancelled',
        'wcm-complimentary',
        'wcm-delayed',
        'wcm-expired',
        'wcm-paused',
        'wcm-pending'
    ];

    // Check if the input status is valid
    if (!in_array($status, $allowed_statuses)) {
        return new WP_Error('invalid_status', 'Invalid membership status.');
    }

    // Update the membership status (post_status)
    $updated = $wpdb->update(
        $wpdb->posts,
        ['post_status' => $status], // Set the new status
        ['ID' => $user_membership_id] // Where the post ID matches
    );

    // Check if the update was successful
    if ($updated === false) {
        return new WP_Error('update_failed', 'Failed to update membership status.');
    }

    return true; // Successfully updated
}

// Send email on new membership purchase
function send_paused_membership_email($user_id) {

  $headers = array('Content-Type: text/html; charset=UTF-8');
  $subject = '[HKOTA] Your Membership Application to the Hong Kong Occupational Therapy Association';
  ob_start();

  $user = get_user_by('ID', $user_id);
  // Include the PHP file that contains the HTML email structure
  $args = array(
    'user'          => $user
  );

  load_template( HKOTA_PLUGIN_DIR . '/email/new_membership.php' ,true, $args );

  // Get the content from the buffer and clean the buffer
  $html_content = ob_get_clean();

  $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

  if ( ! $sent ) {
      // Handle email not sent, you could log the error here
      error_log( 'Email failed to send to ' . $to );
  }

}

// Send email when membership activated
function send_active_membership_email($user_id) {

  $headers = array('Content-Type: text/html; charset=UTF-8');
  $subject = '[HKOTA] Your Membership with the Hong Kong Occupational Therapy Association is Approved';
  ob_start();

  $user = get_user_by('ID', $user_id);
  // Include the PHP file that contains the HTML email structure
  $args = array(
    'user'          => $user
  );

  load_template( HKOTA_PLUGIN_DIR . '/email/membership_activated.php' ,true, $args );

  // Get the content from the buffer and clean the buffer
  $html_content = ob_get_clean();

  $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

  if ( ! $sent ) {
      // Handle email not sent, you could log the error here
      error_log( 'Email failed to send to ' . $to );
  }

}

// Send email when membership activated
function send_renew_membership_email($user_id) {

  $headers = array('Content-Type: text/html; charset=UTF-8');
  $subject = '[HKOTA] Your Membership with the Hong Kong Occupational Therapy Association is renewed';
  ob_start();

  $user = get_user_by('ID', $user_id);
  // Include the PHP file that contains the HTML email structure
  $args = array(
    'user'          => $user
  );

  load_template( HKOTA_PLUGIN_DIR . '/email/membership_renewed.php' ,true, $args );

  // Get the content from the buffer and clean the buffer
  $html_content = ob_get_clean();

  $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

  if ( ! $sent ) {
      // Handle email not sent, you could log the error here
      error_log( 'Email failed to send to ' . $to );
  }

}

// remove the "Cancel" action for members
add_filter( 'wc_memberships_members_area_my-memberships_actions', 'disable_membership_cancel_memberships_actions' );
add_filter( 'wc_memberships_members_area_my-membership-details_actions', 'disable_membership_cancel_memberships_actions' );
function disable_membership_cancel_memberships_actions( $actions ) {
	// remove the "Cancel" action for members
	unset( $actions['cancel'] );
	return $actions;
}

// Add tenures setting at membership plan
add_action('wc_membership_plan_options_membership_plan_data_general','display_tenure_form');
function display_tenure_form(){

  global $post;
  $post_id = $post->ID;
  $saved_tenures = get_post_meta($post_id, 'tenures', true);
   if (empty($saved_tenures) || !is_array($saved_tenures)) {
       $saved_tenures = []; // Fallback to empty array if no data found
   }

  ?>
  <style>
    .tenures-custom-field input {
        width: 190px !important;
        margin-right: 20px;
    }
    p.form-field.plan-access-method-field,
    p.form-field.plan-access-length-field {
        display: none!important;
    }
  </style>
  <?php
    if( $post->ID == 1405 ){
      return;
    }
  ?>
  <div id="tenures-wrapper">
        <?php
        // Loop through each saved tenure and generate input fields
        foreach ($saved_tenures as $index => $tenure) {
            $is_first = ($index === 0); // Check if it's the first row
        ?>
            <div class="options_group tenures-custom-field">
                <p class="form-field post_name_field">
                    <label for="post_name">Tenures:</label>
                    <input type="number" name="tenures[years][]" value="<?php echo esc_attr($tenure['Years']); ?>" placeholder="Years">
                    <input type="number" name="tenures[new_fee][]" value="<?php echo esc_attr($tenure['new']); ?>" placeholder="New membership fee">
                    <input type="number" name="tenures[renew_fee][]" value="<?php echo esc_attr($tenure['renew']); ?>" placeholder="Renewal fee">
                    <?php if ($is_first) { ?>
                        <button type="button" class="add-tenure-set button button-primary">+</button>
                    <?php } else { ?>
                        <button type="button" class="remove-tenure-set button button-secondary">-</button>
                    <?php } ?>
                </p>
            </div>
        <?php
        }
        // If there are no saved tenures, create a default set
        if (empty($saved_tenures)) {
        ?>
            <div class="options_group tenures-custom-field">
                <p class="form-field post_name_field">
                    <label for="post_name">Tenures:</label>
                    <input type="number" name="tenures[years][]" value="" placeholder="Years">
                    <input type="number" name="tenures[new_fee][]" value="" placeholder="New membership fee">
                    <input type="number" name="tenures[renew_fee][]" value="" placeholder="Renewal fee">
                    <button type="button" class="add-tenure-set button button-primary">+</button>
                </p>
            </div>
        <?php } ?>
  </div>
  <?php
  // echo "fired";

}

add_action( 'save_post', 'save_membership_tenures_data', 1, 2 );
function save_membership_tenures_data( $post_id, $post ) {

	// Only run un course post type
	if ( 'wc_membership_plan' !== $post->post_type ) {
		return $post_id;
	}

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenures_data = [];

    if (isset($_POST['tenures']) && is_array($_POST['tenures'])) {
        $years = $_POST['tenures']['years'];
        $new_fees = $_POST['tenures']['new_fee'];
        $renew_fees = $_POST['tenures']['renew_fee'];

        // Loop through the submitted tenures data and build the array
        foreach ($years as $index => $year) {
            // Make sure data exists and is valid for new_fee and renew_fee
            if (!empty($year) && isset($new_fees[$index]) && isset($renew_fees[$index])) {
                $tenures_data[] = [
                    'Years' => (int)$year,
                    'new' => (float)$new_fees[$index],
                    'renew' => (float)$renew_fees[$index],
                ];
            }
        }
    }

    // Now $tenures_data contains the structured array
    update_post_meta($post_id, 'tenures' , $tenures_data );
  }

}

add_action( 'woocommerce_after_order_notes', 'display_form_for_new_member' );
function display_form_for_new_member( $checkout ) {

  $is_membership_product = false;

  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    $product_id = $cart_item['product_id'];
    if( $product_id == get_membership_dummy_product_id() ){
      $is_membership_product = true;
      $is_membership_renew = ( $cart_item['application_type'] == 'renew' )? true : false ;
    }

  }

  if( $is_membership_product ){

    $current_user_id = get_current_user_id();

      ?>
      <h3>Membership Application Form</h3>
      <p><strong>Please ensure that the name provided is accurate and matches the name on your HKID.
         The HKOTA course certificate will be issued under this name.<br>You will not be able to change this later.</strong></p>
      <br>
      <p style='color:#000;'><b>Personal Particulars</b></p>
      <?php

      woocommerce_form_field( 'member_title', array(
         'type' => 'select',
         'class' => array( 'form-row-first' ),
         'label' => 'Title',
         'required' => true,
         'options' => array( 'Dr.', 'Mr.' ,'Mrs.' , 'Miss' , 'Ms.' ),
      ), $checkout->get_value( 'member_title' ) );

      woocommerce_form_field( 'member_full_name_zh', array(
         'type' => 'text',
         'class' => array( 'form-row-last' ),
         'label' => 'Name in Chinese',
         'required' => false,
      ), $checkout->get_value( 'member_full_name_zh' ) );

      woocommerce_form_field( 'member_last_name_eng', array(
         'type' => 'text',
         'class' => array( 'form-row-first' ),
         'label' => 'Last Name in English',
         'required' => true,
      ), $checkout->get_value( 'member_last_name_eng' ) );

      woocommerce_form_field( 'member_first_name_eng', array(
         'type' => 'text',
         'class' => array( 'form-row-last' ),
         'label' => 'First Name in English',
         'required' => true,
      ), $checkout->get_value( 'member_first_name_eng' ) );

      woocommerce_form_field( 'ot_reg_number', array(
         'type' => 'text',
         'class' => array( 'form-row-first' ),
         'label' => 'OT Registration number:',
         'required' => false,
      ), $checkout->get_value( 'ot_reg_number' ) );

      woocommerce_form_field( 'ot_reg_date', array(
         'type' => 'date',
         'class' => array( 'form-row-last' ),
         'label' => 'OT Registration date:',
         'required' => false,
      ), $checkout->get_value( 'ot_reg_date' ) );

      woocommerce_form_field( 'member_hkid', array(
         'type' => 'text',
         'class' => array( 'form-row-first' ),
         'label' => 'HKID (first 4 Characters)',
         'placeholder' => 'i.e.：A123',
         'maxlength' => 4,
         'required' => true,
      ), $checkout->get_value( 'member_hkid' ) );

      woocommerce_form_field( 'member_field', array(
         'type' => 'select',
         'class' => array( 'form-row-last' ),
         'label' => 'Nature of Work',
         'required' => true,
         'options' => array('Hospital Authority' , 'Non-Government Organization', 'Private Practice' ,
                           'Academic Institute' , 'Department of Health' , 'Retired' , 'Student (PolyU)',
                           'Student (TWC)', 'Others (please specify)' ),
      ), $checkout->get_value( 'member_field' ) );

      ?>

      <p class="form-row form-row-wide validate-required" id="field_others_field" data-priority="" style="display:none;">
        <label for="member_field_others" class="">Nature of work</label>
        <span class="woocommerce-input-wrapper">
          <input type="text" class="input-text " name="member_field_others" id="member_field_others" aria-required="true" value="<?php echo $_POST['field_others'] ;?>">
        </span>
      </p>

      <?php

      woocommerce_form_field( 'member_working_place', array(
         'type' => 'text',
         'class' => array( 'form-row-wide' ),
         'label' => 'Name of Working Place',
         'required' => true,
      ), $checkout->get_value( 'member_working_place' ) );

      ?>
      <br>
      <p style='color:#000;'><b>Contact Information</b></p>

      <?php

      woocommerce_form_field( 'member_mailing_address', array(
         'type' => 'text',
         'class' => array( 'form-row-wide' ),
         'label' => 'Mailing Address',
         'required' => true,
      ), $checkout->get_value( 'member_mailing_address' ) );

      woocommerce_form_field( 'member_mobile', array(
         'type' => 'tel',
         'class' => array( 'form-row-wide' ),
         'label' => 'Phone',
         'placeholder' => '(with Whatsapp is preferred)',
         'maxlength' => 8,
         'required' => true,
      ), $checkout->get_value( 'member_mobile' ) );

      ?>

      <br>
      <p style='color:#000;'><b>Professional qualifications</b></p>

      <?php

      woocommerce_form_field( 'member_basic_qualification', array(
         'type' => 'text',
         'class' => array( 'form-row-wide' ),
         'label' => 'Basic Qualification',
         'placeholder' => '(Name of the undergraduate program)',
         'required' => true,
      ), $checkout->get_value( 'member_basic_qualification' ) );

      woocommerce_form_field( 'member_basic_qualification_year', array(
         'type' => 'number',
         'class' => array( 'form-row-first' ),
         'label' => 'Year of Graduation',
         'custom_attributes' => array(
            'min'       =>  1950,
            'max'       =>  2050,
         ),
         'required' => true,
      ), $checkout->get_value( 'member_basic_qualification_year' ) );

      woocommerce_form_field( 'member_basic_qualification_institution', array(
         'type' => 'text',
         'class' => array( 'form-row-last' ),
         'label' => 'Name of Academic Institution',
         'required' => true,
      ), $checkout->get_value( 'member_basic_qualification_institution' ) );

      woocommerce_form_field( 'member_highest_qualification', array(
         'type' => 'text',
         'class' => array( 'form-row-wide' ),
         'label' => 'Highest Academic Qualification',
         'placeholder' => '(Name of the highest academic program)',
         'required' => true,
      ), $checkout->get_value( 'member_highest_qualification' ) );

      woocommerce_form_field( 'member_highest_qualification_year', array(
         'type' => 'number',
         'class' => array( 'form-row-first' ),
         'label' => 'Year Obtained',
         'custom_attributes' => array(
            'min'       =>  1950,
            'max'       =>  2050,
         ),
         'required' => true,
      ), $checkout->get_value( 'member_highest_qualification_year' ) );

      woocommerce_form_field( 'member_highest_qualification_institution', array(
         'type' => 'text',
         'class' => array( 'form-row-last' ),
         'label' => 'Name of Academic Institution',
         'required' => true,
      ), $checkout->get_value( 'member_highest_qualification_institution' ) );

      if(!$is_membership_renew):
      ?>

        <p class="form-row" style='color:#000;'><b>Please upload the supporting document base on your applied membership type.</b></p>

        <p class="form-row form-row-wide validate-required" id="certificate_field" data-priority="">
          <label for="member_certificate" class="">OT Graduation Certificate or OT Practicing Certificate&nbsp;</label>
          <span class="woocommerce-input-wrapper">
            <input type="file" class="input-text " name="member_certificate" id="member_certificate" aria-required="true" accept=".jpg,.jpeg,.png,.pdf">
            <input type="hidden" name="member_certificate_hidden" id="member_certificate_hidden">
          </span>
        </p>

        <p class="form-row form-row-wide validate-required" id="student-id_field" data-priority="">
          <label for="member_student_id" class="">OT Student ID Card&nbsp;</label>
          <span class="woocommerce-input-wrapper">
            <input type="file" class="input-text " name="member_student_id" id="member_student_id" aria-required="true" accept=".jpg,.jpeg,.png,.pdf">
            <input type="hidden" name="member_student_id_hidden" id="member_student_id_hidden">
          </span>
        </p>
      <?php endif; ?>
      <p class="form-row" style='color:#000;'></p>
      <style>
        .woocommerce-additional-fields__field-wrapper{
          display:none!important;
        }
        .woocommerce-additional-fields > h3:nth-child(2){
          display:none!important;
        }
      </style>
      <script>
        jQuery(document).ready(function(){
          jQuery(document).on('focusout','#member_field',function(){
            if(jQuery(this).val() == 'Others (please specify)'){
              jQuery('#field_others_field').show();
            } else{
              jQuery('#field_others_field').hide();
              jQuery('#field_others').val('');
            }
          });
        });
      </script>

      <?php

    } else {
      ?>
      <style>
        .woocommerce-additional-fields{
          display:none!important;
        }
      </style>

      <?php
  }

}

add_action( 'woocommerce_checkout_process', 'validate_new_membership_form' );
function validate_new_membership_form() {

  $is_membership_product = false;

  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    $product_id = $cart_item['product_id'];
    if( $product_id == get_membership_dummy_product_id() ){
      $is_membership_product = true;
      $is_membership_renew = ( $cart_item['application_type'] == 'renew' )? true : false ;
    }
  }

  if( $is_membership_product ){

   if ( empty( sanitize_text_field( $_POST['member_title'] ) ) ) {
      wc_add_notice( '[Title] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_last_name_eng'] ) ) ) {
      wc_add_notice( '[Last Name] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_first_name_eng'] ) ) ) {
      wc_add_notice( '[First Name] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_hkid'] ) ) ) {
      wc_add_notice( '[HKID] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_field'] ) ) ) {
      wc_add_notice( '[Nature of Work] is mandatory.', 'error' );
   }
   if( $_POST['member_field'] == 'Others (please specify)' && empty( sanitize_text_field( $_POST['member_field_others'] ) ) ){
      wc_add_notice( 'Please specific nature of work.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_working_place'] ) ) ) {
      wc_add_notice( '[Name of Working Place] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_mailing_address'] ) ) ) {
      wc_add_notice( '[Mailing address] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_mobile'] ) ) ) {
      wc_add_notice( '[Phone] is mandatory.', 'error' );
   }

   if ( empty( sanitize_text_field( $_POST['member_basic_qualification'] ) ) ) {
      wc_add_notice( '[Basic qualification] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_basic_qualification_year'] ) ) ) {
      wc_add_notice( '[Graduation year of basic qualification] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_basic_qualification_institution'] ) ) ) {
      wc_add_notice( '[Institution of basic qualification] is mandatory.', 'error' );
   }

   if ( empty( sanitize_text_field( $_POST['member_highest_qualification'] ) ) ) {
      wc_add_notice( '[Highest Academic Qualification] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_highest_qualification_year'] ) ) ) {
      wc_add_notice( '[Graduation year of highest qualification] is mandatory.', 'error' );
   }
   if ( empty( sanitize_text_field( $_POST['member_highest_qualification_institution'] ) ) ) {
      wc_add_notice( '[Institution of highest qualification] is mandatory.', 'error' );
   }
   if(!$is_membership_renew){
     if ( empty( $_POST['member_certificate_hidden'] ) && empty( $_POST['member_student_id_hidden'] ) ){
       wc_add_notice( 'Please at least upload one type of supporting doucment for your membership application.', 'error' );
     }
   }
 }
}

add_action( 'woocommerce_checkout_update_order_meta', 'save_new_membership_form' );
function save_new_membership_form( $order_id ) {

  $is_membership_product = false;

  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    $product_id = $cart_item['product_id'];
    if( $product_id == get_membership_dummy_product_id() ){
      $is_membership_product = true;
    }
  }

  if( $is_membership_product ){

    $user_id = get_current_user_id();

    $current_user = get_user_by( 'ID' , $user_id );

    update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['member_last_name_eng'] ) );

    update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['member_first_name_eng'] ) );

    update_user_meta( $user_id, 'ot_reg_date', sanitize_text_field( $_POST['ot_reg_date'] ) );

    update_user_meta( $user_id, 'ot_reg_number', sanitize_text_field( $_POST['ot_reg_number'] ) );

    foreach ( $_POST as $key => $value ) {
      if( str_contains( $key , 'hidden') ){
        continue;
      }
      if( str_contains( $key , 'member') ){
        update_user_meta( $user_id, $key , sanitize_text_field( $value ) );
      }
    }
  }

}

add_action('wc_memberships_after_user_membership_details','display_application_form');
function display_application_form(){

  $post_id = $_GET['post'];
  $user_id = get_post($post_id)->post_author;

  ?>
  <style>
    .member-data-row {
        display: flex;
        flex: 1 0 50%;
    }
    .title {
        flex: 0 0 250px;
        font-size: 13px;
        font-weight: 500;
    }
    .member-data {
        display: flex;
        flex-direction: row;
        row-gap: 15px;
        flex-wrap: wrap;
        width: 1000px;
        margin: 30px 0 60px 0;
    }
    .member-data-row img {
        height: 40px;
    }
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .popup-content {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        width: 600px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        height: 80%;
        overflow-y: auto;
    }

    .popup-content h2 {
        margin-bottom: 20px;
        font-size: 20px;
    }

    .popup-content form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .popup-content form input {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .popup-content form button {
        margin-right: 10px;
    }
    form#edit-member-form {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 2%;
    }

    form#edit-member-form p {
        flex: 1 0 48%;
        margin: unset !important;
    }
    .pupil-file-preview {
        position: relative;
    }
    i.fa-solid.fa-circle-xmark {
        position: absolute;
        right: -10px;
        top: -10px;
        color: #eb3a3a;
        cursor: pointer;
    }


  </style>
  <br><br>
  <h3>Member details:</h3>
  <span>(Base on latest membership application form.)</span>
  <div class="member-data">
    <div class="member-data-row">
      <div class="title">Member number:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_number" , true); ?></div>
    </div>
    <div class="member-data-row">
    </div>
    <div class="member-data-row">
      <div class="title">Title:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_title" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Chinese name:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_full_name_zh" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Last Name:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_last_name_eng" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">First Name:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_first_name_eng" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">OT Registration number:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "ot_reg_number" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">OT Registration date:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "ot_reg_date" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">HKID:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_hkid" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Nature of Work:</div>
      <div class="value">
        <?php
          if( get_user_meta( $user_id, "member_field" , true) == 'Others (please specify)' ){
            echo 'Others: ' . get_user_meta( $user_id, "member_field_others" , true);
          } else{
            echo get_user_meta( $user_id, "member_field" , true);
          }
        ?>
      </div>
    </div>
    <div class="member-data-row">
      <div class="title">Name of Working Place:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_working_place" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Mailing address:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_mailing_address" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Mobile</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_mobile" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Email:</div>
      <div class="value"><?php echo get_user_by('id',$user_id)->user_email ;?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Basic Qualification:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_basic_qualification" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Year of Graduation:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_basic_qualification_year" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Name of Basic Academic Institution:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_basic_qualification_institution" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Highest Academic Qualification:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_highest_qualification" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Year Obtained:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_highest_qualification_year" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">Name of Highest Academic Institution:</div>
      <div class="value"><?php echo get_user_meta( $user_id, "member_highest_qualification_institution" , true); ?></div>
    </div>
    <div class="member-data-row">
      <div class="title">OT Graduation Certificate / OT Practicing Certificate:</div>
      <div class="value">
        <?php
          $certificate_file_name = get_user_meta( $user_id, "member_certificate" , true);
          if( !empty( $certificate_file_name ) ){
            $url = COURSE_PUPIL_FILE_URL . $certificate_file_name;
            ?>
              <div class="pupil-file-preview">
                <a href="<?php echo $url ?>" target="_blank">
                  <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/certificate_icon.png' ) ?>">
                </a>
                <i data-user-id="<?php echo $user_id ;?>" data-input-key="member_certificate" class="fa-solid fa-circle-xmark"></i>
              </div>
            <?php
          } else{
            echo 'No record.';
          }
        ?>
      </div>
    </div>
    <div class="member-data-row">
      <div class="title">OT Student ID Card:</div>
      <div class="value">
        <?php
          $student_id_file_name = get_user_meta( $user_id, "member_student_id" , true);
          if( !empty( $student_id_file_name ) ){
            $url = COURSE_PUPIL_FILE_URL . $student_id_file_name;
            ?>
              <div class="pupil-file-preview">
                <a href="<?php echo $url ?>" target="_blank">
                  <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/certificate_icon.png' ) ?>">
                </a>
                <i data-user-id="<?php echo $user_id ;?>" data-input-key="member_student_id" class="fa-solid fa-circle-xmark"></i>
              </div>
            <?php
          } else{
            echo 'No record.';
          }
        ?>
      </div>
    </div>
    <button data-user-id="<?php echo $user_id ;?>" type="button" id="edit-member-info" class="button button-primary">Edit</button>
  </div>

  <?php
  // echo "fired";

}

















?>
