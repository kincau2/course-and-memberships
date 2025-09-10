<?php

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

// Add custom columns to the WooCommerce memberships list page
add_filter( 'manage_edit-wc_user_membership_columns', 'add_custom_member_columns', 20 );
function add_custom_member_columns( $columns ) {
    // Add your custom columns before the 'plan' column
    $new_columns = array();
    
    foreach ( $columns as $key => $value ) {
        // Add your columns before the 'plan' column
        if( $key === 'title' ){
            $new_columns['member_user_id'] = __( 'ID', 'textdomain' );
            $new_columns['title'] = __( 'Username', 'textdomain' );
            $new_columns['member_first_name'] = __( 'First Name', 'textdomain' );
            $new_columns['member_mobile'] = __( 'Mobile', 'textdomain' );
            continue;
        }
        $new_columns[$key] = $value;
    }
    set_transient( 'debug', $columns, 30 ); // Cache for 12 hours
    return $new_columns;
}

// Populate custom columns with data
add_action( 'manage_wc_user_membership_posts_custom_column', 'populate_custom_member_columns', 20, 2 );
function populate_custom_member_columns( $column, $post_id ) {
    $user_membership = wc_memberships_get_user_membership( $post_id );
    $user = $user_membership ? get_userdata( $user_membership->get_user_id() ) : null;
    
    switch ( $column ) {
        case 'member_mobile':
            if ( $user ) {
                $mobile = get_user_meta( $user->ID, 'member_mobile', true );
                echo $mobile ? esc_html( $mobile ) : '&mdash;';
            }
            break;
            
        case 'member_first_name':
            if ( $user ) {
                echo $user->first_name ? esc_html( $user->first_name ) : '&mdash;';
            }
            break;
            
        case 'member_user_id':
            if ( $user ) {
                echo esc_html( $user->ID );
            }
            break;
    }
}

// Extend the search functionality to include custom fields
add_filter( 'posts_clauses', 'fix_membership_search_for_custom_fields', 15, 2 );
function fix_membership_search_for_custom_fields( $pieces, $wp_query ) {
    global $wpdb;
    
    // Only process user membership searches
    if ( 'wc_user_membership' !== $wp_query->query['post_type'] || ! isset( $wp_query->query['s'] ) ) {
        return $pieces;
    }
    
    $keyword = trim( ltrim( $wp_query->query['s'], '_' ) );
    
    if ( empty( $keyword ) ) {
        return $pieces;
    }
    
    $keyword_like = '%' . $wpdb->esc_like( $keyword ) . '%';
    
    // Ensure we have the users table joined
    if ( strpos( $pieces['join'], "$wpdb->users" ) === false ) {
        $pieces['join'] .= " LEFT JOIN $wpdb->users ON $wpdb->posts.post_author = $wpdb->users.ID ";
    }
    
    // Add usermeta join for custom field searches
    $pieces['join'] .= " LEFT JOIN $wpdb->usermeta AS mobile_meta ON $wpdb->users.ID = mobile_meta.user_id AND mobile_meta.meta_key = 'member_mobile' ";
    $pieces['join'] .= " LEFT JOIN $wpdb->usermeta AS firstname_meta ON $wpdb->users.ID = firstname_meta.user_id AND firstname_meta.meta_key = 'first_name' ";
    
    // Find the search part in WHERE clause and extend it
    // Look for the pattern: ( (condition) OR (condition) OR ... )
    $search_pattern = '/(\(\s*\([^)]+LIKE[^)]+\)\s*(?:OR\s*\([^)]+LIKE[^)]+\)\s*)*\))/';
    
    if ( preg_match( $search_pattern, $pieces['where'], $matches ) ) {
        $original_search = $matches[1];
        
        // Prepare our additional conditions
        $mobile_condition = $wpdb->prepare( "mobile_meta.meta_value LIKE %s", $keyword_like );
        $firstname_condition = $wpdb->prepare( "firstname_meta.meta_value LIKE %s", $keyword_like );
        
        // Remove the closing parenthesis and add our conditions
        $new_search = rtrim( $original_search, ')' ) . " OR ($mobile_condition) OR ($firstname_condition) )";
        
        // Replace in the WHERE clause
        $pieces['where'] = str_replace( $original_search, $new_search, $pieces['where'] );
    }
    
    return $pieces;
}