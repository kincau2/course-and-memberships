<?php

//register custom registration fields
add_action( 'woocommerce_register_form_start', 'extra_register_fields' );
function extra_register_fields() {
  ?>
    <p class="form-row form-row-first">
      <label for="reg_billing_first_name">First name:<span class="required">*</span></label>
      <input type="text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
    </p>
    <p class="form-row form-row-last">
      <label for="reg_billing_last_name">Last name:<span class="required">*</span></label>
      <input type="text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
    </p>
    <p class="form-row form-row-wide">
      <span>Please ensure that the name provided is accurate and matches the name on your HKID.
         The HKOTA course certificate will be issued under this name. <br><b>You will not be able to change this later.</b></span>
    </p>
    <p class="form-row form-row-wide">
        <label for="reg_ot_reg_number">Mobile:<span class="required">*</span></label>
        <input type="tel" name="mobile" id="mobile" value="<?php if ( ! empty( $_POST['mobile'] ) ) esc_attr_e( $_POST['mobile'] );?>"/>
    </p>
    <p class="form-row form-row-wide">
      <label for="reg_ot_reg_number">OT Registration number:</label>
      <input type="text" name="ot_reg_number" id="reg_ot_reg_number" value="<?php if ( ! empty( $_POST['ot_reg_number'] ) ) esc_attr_e( $_POST['ot_reg_number'] );?>"/>
    </p>
    <p class="form-row form-row-wide">
      <label for="ot_reg_date">OT Registration date:</label>
      <input type="date" name="ot_reg_date" id="ot_reg_date" value="<?php if ( ! empty( $_POST['ot_reg_date'] ) ) esc_attr_e( $_POST['ot_reg_date'] );?>"/>
    </p>
    <div class="clear"></div>
  <?php
 }

//custom register fields Validating.
add_action( 'woocommerce_register_post', 'validate_custom_register_fields', 10, 3 );
function validate_custom_register_fields( $username, $email, $validation_errors ) {

		global $wpdb;
		if ( isset( $_POST['billing_first_name'] ) && empty( $_POST['billing_first_name'] ) ) {
			$validation_errors->add( 'billing_first_name_error', __( 'First name is required!', 'woocommerce' ) );
		}

		if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
			$validation_errors->add( 'billing_last_name_error', __( 'Last name is required!.', 'woocommerce' ) );
		}

    if ( isset( $_POST['mobile'] ) && empty( $_POST['mobile'] ) ) {
			$validation_errors->add( 'mobile_error', __( 'Mobile number is required!.', 'woocommerce' ) );
		}else{
      $pattern = "/^[356789]{1}[0-9]{7}$/";
      // Check if the mobile number matches the pattern
      if ( !preg_match( $pattern, $_POST['mobile']) ) {
          $validation_errors->add( 'mobile_error', __( 'Please enter a valid Hong Kong mobile number.', 'woocommerce' ) );
      }
    }

    if( isset( $_POST['ot_reg_number'] ) && !empty( $_POST['ot_reg_number'] ) ){

      $registration_number = sanitize_text_field($_POST['ot_reg_number']);

      global $wpdb;

      // Check if registration number matches any user
      $user_id = $wpdb->get_var($wpdb->prepare(
          "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'ot_reg_number' AND meta_value = %s",
          $registration_number
      ));

      if($user_id){
        $validation_errors->add( 'ot_reg_number_error', __( 'The OT Registration Number you entered is registered by another user.', 'woocommerce' ) );
      }

    }

	  return $validation_errors;
}

// Custom register fields saving.
add_action( 'woocommerce_created_customer', 'save_custom_register_fields' );
function save_custom_register_fields( $customer_id ) {

	if ( isset( $_POST['billing_first_name'] ) ) {
		update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
		update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
	}

	if ( isset( $_POST['billing_last_name'] ) ) {
		update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
		update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
	}

	if ( isset( $_POST['mobile'] ) && !empty( $_POST['mobile'] ) && !empty( $_POST['mobile'] ) ) {
		update_user_meta( $customer_id, 'mobile', sanitize_text_field( $_POST['mobile'] ) );
    update_user_meta( $customer_id, 'billing_phone', sanitize_text_field( $_POST['mobile'] ) );
	}

  if ( isset( $_POST['ot_reg_date'] ) ) {
		update_user_meta( $customer_id, 'ot_reg_date', sanitize_text_field( $_POST['ot_reg_date'] ) );
	}

	if ( isset( $_POST['ot_reg_number'] ) ) {
		update_user_meta( $customer_id, 'ot_reg_number', sanitize_text_field( $_POST['ot_reg_number'] ) );
	}

}

function app_output_buffer() {
    ob_start();
} // soi_output_buffer
add_action('init', 'app_output_buffer');

add_shortcode('login_check','login_check');
function login_check(){

	if(!is_user_logged_in() && !is_wc_endpoint_url('lost-password')){
		wp_safe_redirect("/login");
    exit;
	}
}

add_shortcode('login_register_user_check','login_register_user_check');
function login_register_user_check(){

	if( is_user_logged_in() && !current_user_can( 'edit_posts' ) ){
		wp_redirect('/my-account');
    exit();
	}
}

?>
