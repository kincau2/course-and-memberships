<?php

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

add_action( 'admin_enqueue_scripts', 'membership_enqueue_plugin_assets_backend', 20 );

function membership_enqueue_plugin_assets_backend() {
	wp_enqueue_script( 'hkota_backend_ajax', plugins_url( '/hkota-courses-and-memberships/include/js/backend-ajax.js' ) );
	wp_localize_script( 'hkota_backend_ajax', 'hkota_backend_ajax', array(
	'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_enqueue_scripts', 'membership_enqueue_plugin_assets_frontend', 20 );

function membership_enqueue_plugin_assets_frontend() {
	wp_enqueue_script( 'hkota_frontend_ajax', plugins_url( '/hkota-courses-and-memberships/include/js/frontend-ajax.js' ) );
	wp_localize_script( 'hkota_frontend_ajax', 'hkota_frontend_ajax', array(
	'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

function enqueue_datepicker_script() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}
add_action('wp_enqueue_scripts', 'enqueue_datepicker_script');

add_action( 'wp_ajax_delete_course_media', 'delete_course_media' );

function delete_course_media(){

	$post_id = stripslashes($_POST['post_id']);
  $input_key = stripslashes($_POST['input_key']);

  $filename = get_post_meta($post_id,$input_key,true);

	if( delete_post_meta($post_id,$input_key) ){

    $upload_dir = wp_upload_dir();

    if ( !empty( $upload_dir['basedir'] ) ) {
			if( $input_key == 'course_poster' ){
				$course_file_dir = $upload_dir['basedir'].'/course-poster';
	      wp_delete_file( $course_file_dir . "/" . $filename );
			} elseif( $input_key == 'course_qr_code' ){
				$course_file_dir = $upload_dir['basedir'].'/course-qr-code';
	      wp_delete_file( $course_file_dir . "/" . $filename );
			} else {
				$course_file_dir = $upload_dir['basedir'].'/course-files';
	      wp_delete_file( $course_file_dir . "/" . $filename );
			}
    }

		if( $input_key == 'course_external_poster' ){
			delete_post_meta($post_id,'course_snapshot');
		}

    $respond = array(
      'success' 	=> true,
      'post_id'   => $post_id,
      'input_key' => $input_key
    );

  } else {

    $respond = array(
      'success' 	=> false
    );

  }

  echo json_encode($respond);

  exit();

}

add_action( 'wp_ajax_save_rundown', 'save_rundown' );

function save_rundown(){

    $post_id = stripslashes($_POST['post_id']);
    $input_key = stripslashes($_POST['input_key']);
	$relatedness = stripslashes($_POST['course_relatedness']);
    $rundown = json_decode(stripslashes($_POST['rundown']), true);
    
	if( !empty($rundown) ){
		if( !is_array($rundown) ){
			$respond['success'] = false;
			$respond['message_type'] = 'error';
			$respond['message'] = 'Rundown data error.';
		}
		usort($rundown, function($a, $b) {
				return strtotime($a['date']) - strtotime($b['date']);
		});
	}

    if( update_post_meta( $post_id, $input_key, $rundown ) || update_post_meta( $post_id, 'course_relatedness', $relatedness ) ){

            $old_qr_codes = get_post_meta($post_id,'course_qr_code',true);

            if( !empty( $old_qr_codes ) ){
                foreach ($old_qr_codes as $old_qr_code ) {
                    wp_delete_file( COURSE_QR_CODE_DIR . $old_qr_code['filename'] );
                }
            }

            delete_post_meta($post_id,'course_qr_code');

            $course = new Course($post_id);

            $cpd_point = $course->calculate_cpd_points();

            $respond['cpd-point'] = $cpd_point;

            $return = $course->init_attendance_record();

            $course->save_rundown_dates();

            if( is_wp_error($return) ){
                $respond['success'] = false;
                $respond['message_type'] = 'error';
                $respond['message'] = 'Rundown saved but ' . $return->get_error_message();
            } else{
                $respond['success'] = true;
                $respond['message_type'] = 'notice';
                $respond['message'] = "Rundown Saved and attendance record initialized.";
            }

    } else {

        $respond['success'] = false;
        $respond['message_type'] = 'warning';
        $respond['message'] = 'Rundown not saved, you have not make any changes.';

    }

    echo json_encode($respond);

    exit();

}

add_action( 'wp_ajax_save_form', 'save_form' );
//Save survey and quiz form
function save_form(){

  $post_id = stripslashes($_POST['post_id']);
  $input_key = stripslashes($_POST['input_key']);
  $formdata = json_decode(stripslashes($_POST['formdata']), true);
	$course = new Course($post_id);

  update_post_meta( $post_id, $input_key, $formdata );

	switch( $input_key ){
		case 'course_survey':
			$return = $course->init_attendance_record();
			$course->delete_all_survey_data();
			if( is_wp_error($return) ){
				wp_send_json_error( array(
					'message_type' => 'error',
					'message'			 => 'Survey form down saved but ' . $return->get_error_message(),
				));
			} else{
				wp_send_json_success( array(
					'message_type' => 'notice',
					'message'			 => 'Survey Saved and attendance record initialized.',
					'type'				 => 'survey'
				));
			}
			break;
		case 'course_quiz':
			$respond['type'] = 'quiz';
			//delete all qr-code file
			$old_qr_codes = get_post_meta($post_id,'course_qr_code',true);
			if( !empty( $old_qr_codes ) ){
				foreach ($old_qr_codes as $old_qr_code ) {
					wp_delete_file( COURSE_QR_CODE_DIR . $old_qr_code['filename'] );
				}
			}
			delete_post_meta($post_id,'course_qr_code');
			$return = $course->delete_all_quiz_data();
			if( is_wp_error($return) ){
				wp_send_json_error( array(
					'message_type' => 'error',
					'message'			 => 'Quiz form down saved but ' . $return->get_error_message(),
				));
			} else{
				wp_send_json_success( array(
					'message_type' => 'notice',
					'message'			 => 'Quiz Saved and quiz record initialized.',
					'type'				 => 'quiz'
				));
			}
			break;
	}
}

add_action( 'wp_ajax_generate_qr_code', 'generate_qr_code' );
// Function to generate qr codes set base on $_GET['course_id']
function generate_qr_code(){

  $course_id = stripslashes($_POST['post_id']);
	$generate_count = 0;

  if( empty($course_id) ) {
		wp_send_json_error( array(
			'message'			 => 'Course ID not specified.'
		));
  }

  $course = new Course($course_id);
  // Generate a random filename
  $rundown = $course->rundown;

	// array to store all qr code data
	$course_qr_code = array();

	if( !empty($rundown) && is_array($rundown) ){

		usort($rundown, function($a, $b) {
	      return strtotime($a['date']) - strtotime($b['date']);
	  });

		foreach ($rundown as $section) {
	    if( $section['type'] == 'registration' ||
	        $section['type'] == 'end' ||
	        $section['type'] == 'end_survey'){

	      $filename = generateRandomFilename() . '.jpg';

				$filename = wp_unique_filename( COURSE_QR_CODE_DIR , $filename );
				// Specify the path where the QR code will be saved
				$savePath = COURSE_QR_CODE_DIR . $filename;
				// The content that will be encoded in the QR code
				$qrContent = home_url( '/pupil/?section-id=' . + $section['id'] . '&course-id=' . $course_id ) ;

				$result = Builder::create()
				->writer(new PngWriter())
				->writerOptions([])
				->data($qrContent)
				->encoding(new Encoding('UTF-8'))
				->errorCorrectionLevel(ErrorCorrectionLevel::High)
				->size(500)
				->margin(10)
				->roundBlockSizeMode(RoundBlockSizeMode::Margin)
				->labelText($section['type'] . ' @ ' . $section['startTime'] . "/" . $section['date'] )
				->labelFont(new NotoSans(22))
				->labelAlignment(LabelAlignment::Center)
				->validateResult(false)
				->build();

				// Save it to a file
				$result->saveToFile( $savePath );

				$course_qr_code[] = array(
					'id' 						=> 	$section['id'],
					'section_name'	=> 	$section['name'],
					'date'					=>  $section['date'],
					'type'					=> 	$section['type'],
					'url'						=>	home_url('/wp-content/uploads/course-qr-code/' . $filename ),
					'filename' 			=> 	$filename,
					'time'					=>	$section['startTime']
				);

				$generate_count++;

	    }
	  }
	}

	$quizs = $course->quiz;

	if( !empty($quizs) && is_array($quizs) ){

		foreach ($quizs as $quiz_id => $quiz_content ) {

			$filename = generateRandomFilename() . '.jpg';

			$filename = wp_unique_filename( COURSE_QR_CODE_DIR , $filename );
			// Specify the path where the QR code will be saved
			$savePath = COURSE_QR_CODE_DIR . $filename;
			// The content that will be encoded in the QR code
			$qrContent = home_url( '/quiz/?quiz-id=' . + $quiz_id . '&course-id=' . $course_id ) ;

			$result = Builder::create()
			->writer(new PngWriter())
			->writerOptions([])
			->data($qrContent)
			->encoding(new Encoding('UTF-8'))
			->errorCorrectionLevel(ErrorCorrectionLevel::High)
			->size(500)
			->margin(10)
			->roundBlockSizeMode(RoundBlockSizeMode::Margin)
			->labelText($quiz_content['name'] . ' (Quiz)')
			->labelFont(new NotoSans(22))
			->labelAlignment(LabelAlignment::Center)
			->validateResult(false)
			->build();

			// Save it to a file
			$result->saveToFile( $savePath );

			$course_qr_code[] = array(
				'id' 						=> 	$quiz_id,
				'section_name'	=> 	$section['name'],
				'date'					=>  $quiz_content['name'],
				'type'					=> 	'quiz',
				'url'						=>	home_url('/wp-content/uploads/course-qr-code/' . $filename ),
				'filename' 			=> 	$filename,
			);

			$generate_count++;

	  }

	}

	if( $generate_count == 0 ){
		wp_send_json_error( array(
			'message'			 => 'No qr code generated.'
		));
	}

	$old_qr_codes = $course->qr_code;

	if( !empty( $old_qr_codes ) ){
		foreach ($old_qr_codes as $old_qr_code ) {
			wp_delete_file( $course_qrcode_dir . '/' . $old_qr_code['filename'] );
		}
	}

	update_post_meta($course_id, "course_qr_code" , $course_qr_code );

	wp_send_json_success( array(
		'qrcodes'			 => $course_qr_code
	));

}

// Function to generate a random 30-character filename
function generateRandomFilename($length = 30) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

add_action( 'wp_ajax_get_poster', 'get_poster' );

// Function to display poster on admin panel after it was being created.
function get_poster(){

  $course_id = stripslashes($_POST['post_id']);

  if( empty($course_id) ) {
		$respond['success'] = false;
		$respond['message'] = 'Course ID not specified.';
		echo json_encode($respond);
		exit();
  }

	$poster_file_name = get_post_meta($course_id,'course_poster',true);

	if(!empty($poster_file_name)){
		$respond['success'] = true;
		$respond['url'] = home_url('/wp-content/uploads/course-poster/' . $poster_file_name );
		$respond['filename'] = $poster_file_name;
		echo json_encode($respond);
		exit();
	}

}

add_action( 'wp_ajax_nopriv_handle_ajax_add_to_cart', 'handle_ajax_add_to_cart' );
add_action( 'wp_ajax_handle_ajax_add_to_cart', 'handle_ajax_add_to_cart' );

// Add course product to cart and add order-meta to it for later enrollment logic use.
function handle_ajax_add_to_cart() {

		$user_id = get_current_user_id();
		if( empty($user_id)){
			wp_send_json_error('Sorry, please login or create an account before registration.');
		}
    $product_id = intval(sanitize_text_field($_POST['product_id']));
    $course_id = sanitize_text_field($_POST['course_id']);
		$uploads = isset($_POST['uploads']) ? $_POST['uploads'] : [];
    $quantity = 1;

    // Check if the product exists
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Invalid product ID.');
        return;
    }

		$course = new Course($course_id);

		if( is_product_in_cart($product_id) ){
			wp_send_json_error('Sorry: You can only enroll one course at each time, please complete or delete course registration in your cart first.');
		}

		$fee = $course->get_user_course_fee($user_id);

		$clean_uploads = array();

		foreach ($uploads as $key => $value) {
			$clean_uploads[sanitize_text_field($key)] = sanitize_text_field($value);
		}

    // Add the product to the cart with custom meta
		$cart_item_data = array(
        'course_id' => $course_id,
        'course_code' => $course->code,
        'course_title' => $course->title,
        'date' => $course->createDateString(),
        'time' => $course->createTimeString(),
        'cpd_point' => $course->cpd_point,
        'course_fee' => $course->get_user_course_fee($user_id),
        'uploads' => $clean_uploads // Store uploaded document URLs in cart meta
    );

    $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

    if ($cart_item_key) {
        wp_send_json_success('Course added to cart successfully.');
    } else {
        wp_send_json_error('Failed to add Course to cart.');
    }

    wp_die();

}

add_action( 'wp_ajax_nopriv_handle_ajax_add_to_waiting_list', 'handle_ajax_add_to_waiting_list' );
add_action( 'wp_ajax_handle_ajax_add_to_waiting_list', 'handle_ajax_add_to_waiting_list' );

// Add course product to cart and add order-meta to it for later enrollment logic use.
function handle_ajax_add_to_waiting_list() {

		$user_id = get_current_user_id();
		if( empty($user_id)){
			wp_send_json_error('Sorry, please login or create an account before registration.');
		}

    $course_id = sanitize_text_field($_POST['course_id']);

		$course = new Course($course_id);

    if ( $course->register_to_waiting_list($user_id) ) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to register waiting list.');
    }

    wp_die();

}

add_action('wp_ajax_check_course_eligibility', 'check_course_eligibility');
add_action('wp_ajax_nopriv_check_course_eligibility', 'check_course_eligibility');

// Check user eligibility to enroll a course
function check_course_eligibility() {
    $course_id = sanitize_text_field($_POST['course_id']);
		$is_waiting_list_request = sanitize_text_field($_POST['is_waiting_list']);
		if( $is_waiting_list_request == 'true' ){
			$is_waiting_list_request = true;
		} elseif( $is_waiting_list_request == 'false' ){
			$is_waiting_list_request = false;
		}
    $course = new Course($course_id);
    $user_id = get_current_user_id();

		if(!$user_id){
			wp_send_json_error(
				[
					'message' => 'Sorry, You must login/register on this website first.',
					'redirect'=> true
				]
			);
		}
		$status = $course->get_enrollment_status();
		if( !$is_waiting_list_request ){
			// Ajax is send via add to cart button
			if( $status !== 'available' ){
				wp_send_json_error(['message' => 'Sorry, this course is not open for register, please refresh the page.']);
			}
		}else{
			// Ajax is send via add to wait list button
			if( $status == 'available' ){
				wp_send_json_error(['message' => 'The course is open for enrollment now, please refresh the page to register.']);
			} elseif( $status !== 'full' || $course->is_waiting_list !== 'true' ){
				wp_send_json_error(['message' => 'The course is not open for waiting list register.']);
			}
		}
		if( is_course_in_cart($course_id) ){
			wp_send_json_error(['message' => 'Error: You have added this course to cart already.']);
		}
		$enrollment_status = $course->get_user_enrollment_status($user_id);
		switch($enrollment_status){
			case 'enrolled':
				wp_send_json_error(['message' => 'Error: You have enrolled to this course already.']);
				wp_die();
				break;
			case 'waiting_list':
				if($is_waiting_list_request){
					wp_send_json_error(['message' => 'Error: You are already on the waiting list of this course.']);
					wp_die();
				}
				break;
			case 'awaiting_approval ':
				wp_send_json_error(['message' => 'Error: You already submitted application on this course, the application status is pending.']);
				wp_die();
				break;
			case 'pending':
				wp_send_json_error(['message' => 'Error: You already submitted application on this course, the application status is pending.
														You will enroll to this course automatically if your membership status is confirm.']);
				wp_die();
				break;
			case 'rejected':
				wp_send_json_error(['message' => 'Sorry, You can not apply this course.']);
				wp_die();
				break;
			case 'on_hold':
				wp_send_json_error(['message' => 'Error: You already applied this course. The status is awaiting payment.']);
				wp_die();
				break;
		}

    // Check user eligibility
		if( $course->is_restricted ){
			$eligibility = $course->get_user_eligibility($user_id);

	    if ($eligibility['is_eligible']) {
	        // If eligible, check if uploads are required
	        if ($course->is_uploads_required) {
	            wp_send_json_success([
	                'requires_upload' => true,
	                'required_certs' => $course->cert_requirment
	            ]);
	        } else {
	            wp_send_json_success(['requires_upload' => false]);
	        }
	    } else {
	        wp_send_json_error(['message' => $eligibility['message']]);
	    }
		} else{
			wp_send_json_success(['requires_upload' => false]);
		}
    wp_die();
}

add_action('wp_ajax_handle_file_upload', 'handle_file_upload');
add_action('wp_ajax_nopriv_handle_file_upload', 'handle_file_upload');

// Handling file up when adding course product to cart.
function handle_file_upload() {

	if(isset($_POST['unable'])){

		foreach ( $_POST as $key => $value ) {
			if( str_contains( $key , 'text_cert_' ) ){
				$cert_serial[ str_replace("text_","", $key ) ] = $value;
			}
		}

		wp_send_json_success([
	        'document' 	=> $cert_serial
	  ]);

	} else {

		if ( !isset($_FILES['cert_files']) ) {
        wp_send_json_error('No files provided.');
        return;
    }

    $allowed_file_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    $uploaded_files = $_FILES['cert_files'];
    $upload_results = [];

    foreach ($uploaded_files['name'] as $cert_id => $filename) {

        $file = [
            'name' => sanitize_text_field($uploaded_files['name'][$cert_id]),
            'type' => sanitize_text_field($uploaded_files['type'][$cert_id]),
            'tmp_name' => sanitize_text_field($uploaded_files['tmp_name'][$cert_id]),
            'error' => $uploaded_files['error'][$cert_id],
            'size' => $uploaded_files['size'][$cert_id]
        ];

        // Validate file type
        if (!in_array($file['type'], $allowed_file_types)) {
            wp_send_json_error('Invalid file type for ' . $cert_id . '. Only JPG, PNG, and PDF files are allowed.');
            return;
        }

        // Validate file size
        if ($file['size'] > $max_file_size) {
            wp_send_json_error('File size exceeds the maximum limit of 5MB for ' . $cert_id);
            return;
        }

				$filename = $file['name'];
				$filename = wp_unique_filename( COURSE_PUPIL_FILE_DIR , $filename );
				$upload = move_uploaded_file( $file['tmp_name'], COURSE_PUPIL_FILE_DIR . $filename );


        if ($upload) {
					$upload_results[$cert_id] = $filename; // Store uploaded file URL along with cert ID
        } else {
					wp_send_json_error('File upload failed for ' . $cert_id . ': ' . $upload['error']);
					return;
        }
    }

		wp_send_json_success([
	        'document' => $upload_results
	  ]);

	}



}

add_action('wp_ajax_fetch_pupil_details', 'fetch_pupil_details');
add_action('wp_ajax_nopriv_fetch_pupil_details', 'fetch_pupil_details');

// PHP AJAX handler to fetch pupil details
function fetch_pupil_details() {
    global $wpdb;

    $course_id = intval($_POST['course_id']);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Fetch pupils for the course
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT ue.user_id, ue.status, ue.attendance, ue.uploads, ue.certificate_status
        FROM $table_name as ue
        WHERE ue.course_id = %d
    ", $course_id));

    if (!$results) {
        wp_send_json_error('No pupils found for this course.');
    }

    $pupils = [];
    $cpd_table = $wpdb->prefix . 'hkota_cpd_records';
    
    foreach ($results as $row) {
        // Fetch user details
        $user = get_userdata($row->user_id);
        if ($user) {
            $attendance_data = maybe_unserialize($row->attendance);
            $uploads = maybe_unserialize($row->uploads);

            // Get certificate file from CPD records
            $certificate_file = '';
            if ($row->certificate_status == 'issued') {
                $cpd_record = $wpdb->get_row($wpdb->prepare(
                    "SELECT file FROM $cpd_table WHERE user_id = %d AND course_id = %d",
                    $row->user_id, $course_id
                ));
                if ($cpd_record && !empty($cpd_record->file)) {
                    $certificate_file = $cpd_record->file;
                }
            }

            // Prepare uploaded document display
            $uploaded_documents = '';
            if (!empty($uploads)) {
                foreach ($uploads as $cert => $filename) {
                    if (!empty($filename)) {
						if( file_exists( COURSE_PUPIL_FILE_DIR . $filename ) ){
							$uploaded_documents .= "<div class='cert-list-item'><span style='margin-right: 5px;'>$cert: </span><a target='_blank' href='" . COURSE_PUPIL_FILE_URL . $filename . "'>view file</a></div>";
						} else{
							$uploaded_documents .= "<div class='cert-list-item'><span style='margin-right: 5px;'>$cert: </span><span>$filename</span></div>";
						}
                    }
                }
            }

            $pupils[] = [
                'first_name'         => $user->first_name? $user->first_name : '' ,
                'last_name'          => $user->last_name ? $user->last_name : '' ,
                'email'              => $user->user_email,
                'enrollment_status'  => $row->status,
                'attendance_status'  => $attendance_data['attendance_status'] ? $attendance_data['attendance_status'] : 'N/A',
								'certificate_status' => $row->certificate_status ? $row->certificate_status : '' ,
                'certificate_file'   => $certificate_file,
                'uploaded_documents' => $uploaded_documents ? $uploaded_documents : 'Nil',
                'user_id'     			 => $row->user_id
            ];
        }
    }

    wp_send_json_success( [ 'pupil' => $pupils , 'capability' => current_user_can('edit_course') ] );
}

add_action('wp_ajax_save_pupil_enrollment_data', 'save_pupil_enrollment_data');
add_action('wp_ajax_nopriv_save_pupil_enrollment_data', 'save_pupil_enrollment_data');

// Edit pupil's detail in course admin panel popup box
function save_pupil_enrollment_data() {

    // Validate inputs
    $user_id = intval($_POST['user_id']);
    $course_id = intval($_POST['course_id']);
    $enrollment_status = sanitize_text_field($_POST['enrollment_status']);
    $attendance_status = sanitize_text_field($_POST['attendance_status']);

    if (!$user_id || !$course_id || !$enrollment_status || !$attendance_status) {
        wp_send_json_error('Invalid input data.');
        return;
    }

		$enrollment_id = get_user_enrollment_id($user_id,$course_id);
		$enrollment = new Enrollment($enrollment_id);
		$course = new Course($course_id);

    // Update the enrollment status and attendance status

		if( $attendance_status !== $enrollment->attendance['attendance_status'] ){
			if( $enrollment_status == 'enrolled' ){
				if( empty( $enrollment->attendance ) ){
					$course->set_attendance_record($user_id);
				} else{
					$attendance = $enrollment->attendance;
					$attendance['attendance_status'] = $attendance_status;
					$enrollment->set('attendance',$attendance);
					if( $attendance_status == 'fully_attended' ){
		        $result = $course->create_certificate($user_id);
		        if($result){
		          $course->trigger_issue_certificate_email($user_id);
		        }
		      }
				}
			}
			$is_updated_attendance_status = true;
		}

		if( $enrollment_status !== $enrollment->status ){
			$result = $enrollment->set('status',$enrollment_status);
			if ($result !== false) {

				// Enrollment completed, now send email base on enrollment status
				switch($enrollment_status){
					case 'enrolled':
						$course->trigger_enrolled_email($user_id);
						break;
					case 'awaiting_approval':
						$course->trigger_awaiting_approval_email($user_id);
						break;
					case 'pending':
						$course->trigger_pending_email($user_id);
						break;
					case 'rejected':
						$course->trigger_admin_rejected_email($user_id);
						break;
				}	
				$is_updated_enrollment_status = true;
			}
		}

		if( $is_updated_attendance_status && $is_updated_enrollment_status ){
			wp_send_json_success('Enrollment and attendance status updated.');
		} elseif( $is_updated_enrollment_status ){
			wp_send_json_success('Enrollment status updated.');
		} elseif( $is_updated_attendance_status ){
			wp_send_json_success('Attendance status updated.');
		} else{
			wp_send_json_success('Data not saved. You have made no change.');
		}

}

add_action('wp_ajax_check_quiz_data', 'check_quiz_data');

function check_quiz_data() {
    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Check if there is any enrolled pupil with quiz data
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, quiz FROM $table_name WHERE course_id = %d AND status = 'enrolled' AND quiz IS NOT NULL",
        $course_id
    ));

    if (!empty($results)) {
        wp_send_json_success([
            'download_url' => admin_url('admin-post.php?action=download_quiz_answers&course_id=' . $course_id)
        ]);
    } else {
        wp_send_json_error();
    }

}

add_action('wp_ajax_check_survey_data', 'check_survey_data');
function check_survey_data() {
    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Check if there is any enrolled pupil with quiz data
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, survey FROM $table_name WHERE course_id = %d AND status = 'enrolled' AND survey IS NOT NULL",
        $course_id
    ));

    if (!empty($results)) {
        wp_send_json_success([
            'download_url' => admin_url('admin-post.php?action=export_survey_data&course_id=' . $course_id)
        ]);
    } else {
        wp_send_json_error();
    }

}

add_action('wp_ajax_check_pupil_data', 'check_pupil_data');
function check_pupil_data() {
    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Check if there is any enrolled pupil with quiz data
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE course_id = %d",
        $course_id
    ));

    if (!empty($results)) {
        wp_send_json_success([
            'download_url' => admin_url('admin-post.php?action=export_pupil_data&course_id=' . $course_id)
        ]);
    } else {
        wp_send_json_error();
    }

}

add_action('wp_ajax_check_attendance_data', 'check_attendance_data');
function check_attendance_data() {
    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Check if there is any enrolled pupil and if course has QR codes for attendance
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE course_id = %d",
        $course_id
    ));

    $course = new Course($course_id);
    $qr_codes = $course->qr_code;

    if (!empty($results) && !empty($qr_codes)) {
        wp_send_json_success([
            'download_url' => admin_url('admin-post.php?action=export_attendance_data&course_id=' . $course_id)
        ]);
    } else {
        wp_send_json_error();
    }

}

add_action('wp_ajax_fetch_courses_by_day', 'fetch_courses_by_day');
add_action('wp_ajax_nopriv_fetch_courses_by_day', 'fetch_courses_by_day');
function fetch_courses_by_day() {
    $date = sanitize_text_field($_POST['date']);
    $args = array(
        'post_type' => array('course', 'event'),
				'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'course_dates',
                'value' => $date,
                'compare' => 'LIKE'
            )
        )
    );
    $query = new WP_Query($args);
    $courses = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $course_id = get_the_ID();
            if( get_post_meta($course_id, 'course_is_private', true) == 'true' ) break;
            $courses[] = array(
								'id'				 => $course_id,
                'title' 		 => get_the_title(),
                'venue' 		 => get_post_meta($course_id, 'course_venue', true),
                'code' 		 	 => get_post_meta($course_id, 'course_code', true),
								'cpd_point'  => get_post_meta($course_id, 'course_cpd_point', true),
								'start_date' => get_post_meta($course_id, 'course_start_date', true),
								'type'			 => get_post_meta($course_id, 'course_type', true)
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success([ 'course' => $courses ]);
}

//
add_action('wp_ajax_fetch_courses_by_month', 'fetch_courses_by_month');
add_action('wp_ajax_nopriv_fetch_courses_by_month', 'fetch_courses_by_month');
function fetch_courses_by_month() {
    // Get the month and year from AJAX request
    $month = isset($_POST['month']) ? absint($_POST['month']) : date('m');
    $year = isset($_POST['year']) ? absint($_POST['year']) : date('Y');

    $start_date = date('Y-m-01', strtotime("$year-$month-01"));
    $end_date = date('Y-m-t', strtotime("$year-$month-01"));

    // Fetch all courses that have events during this month
    $args = array(
        'post_type' => array('course', 'event'),
        'posts_per_page' => -1,
				'post_status' => 'publish',
				'meta_key'    => 'course_start_date', // Meta key to order by
				'orderby'     => 'meta_value', // Order by the meta value
				'order'       => 'ASC', // Order by ascending date
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'course_start_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
            array(
                'key' => 'course_end_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            ),
            array(
                'relation' => 'AND',
                array(
                    'key' => 'course_start_date',
                    'value' => $start_date,
                    'compare' => '<',
                    'type' => 'DATE',
                ),
                array(
                    'key' => 'course_end_date',
                    'value' => $end_date,
                    'compare' => '>',
                    'type' => 'DATE'
                )
            )
        )
    );


    $query = new WP_Query($args);
    $events = array();
		$courses = array();
		$debug = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $course_id = get_the_ID();
            if( get_post_meta($course_id, 'course_is_private', true) == 'true' ) continue;
            $course_dates = get_post_meta($course_id, 'course_dates', true); // Get array of course dates
            foreach ($course_dates as $date) {
                if (strtotime($date) >= strtotime($start_date) && strtotime($date) <= strtotime($end_date)) {
									$events[$date][] = get_post_meta($course_id, 'course_type', true);
									$courses[] = array(
											'id'				 => $course_id,
											'title' 		 => get_the_title(),
											'cpd_point'  => get_post_meta($course_id, 'course_cpd_point', true),
											'start_date' => $date,
											'type'			 => get_post_meta($course_id, 'course_type', true)
									);
                }
            }
						usort($courses, function ($a, $b) {
						    return strtotime($a['start_date']) - strtotime($b['start_date']);
						});
						$debug[] = get_the_title();
        }
        wp_reset_postdata();
    }

    wp_send_json_success([ 'dates' => $events, 'course' => $courses ]);
}

add_action('wp_ajax_get_course_loop', 'get_course_loop');
add_action('wp_ajax_nopriv_get_course_loop', 'get_course_loop');
function get_course_loop(){
	$start_date = sanitize_text_field($_POST['start_date']);
	$end_date = sanitize_text_field($_POST['end_date']);
	echo do_shortcode("[display_course start-date='{$start_date}' end-date='{$end_date}' tab-button='enable' ]");

	wp_die(); // Required to terminate properly in AJAX
}
//
add_action('wp_ajax_filter_courses', 'filter_courses');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses');
function filter_courses() {
    $course_type = isset($_POST['course_type']) ? sanitize_text_field($_POST['course_type']) : '';
    $year = isset($_POST['year']) ? intval($_POST['year']) : '';
    $month = isset($_POST['month']) ? intval($_POST['month']) : '';

    // Construct the start and end dates
    $start_date = '';
    $end_date = '';
    if ($year && $month) {
      $start_date = sprintf('%04d-%02d-01', $year, $month);
      $end_date = date('Y-m-t', strtotime($start_date)); // Get the last day of the month
    } elseif( $year ){
			$start_date = sprintf('%04d-01-01', $year);
			$end_date = date('Y-12-31', strtotime($start_date)); // Get the last day of the month
		}

    // Call the shortcode with the appropriate attributes
    echo do_shortcode("[display_course type='{$course_type}' start-date='{$start_date}' end-date='{$end_date}']");

    wp_die(); // Required to terminate properly in AJAX
}

//
add_action('wp_ajax_check_membership_renewal', 'check_membership_renewal');
add_action('wp_ajax_nopriv_check_membership_renewal', 'check_membership_renewal');
function check_membership_renewal() {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
				wp_send_json_error(
					[
						'message' => 'Sorry, You must login/register on this website first.',
						'redirect'=> true
					]
				);
    }

    $user_id = get_current_user_id();

    // Fetch user memberships
    $args = array(
        'status' => array('active','expired','paused'),
    );
    $user_memberships = wc_memberships_get_user_memberships($user_id, $args);

    if (empty($user_memberships)) {
        wp_send_json_error(['message' => 'You do not have any active memberships to renew.']);
    }

    // Current date and the renewal time window
		$timezone = new DateTimeZone('Asia/Hong_Kong');
		$current_time = new DateTime('now',$timezone);
		$current_year = date('Y');
    $renewal_start = new DateTime("$current_year-04-01",$timezone);
    $renewal_end = new DateTime("$current_year-07-31 23:59:59", $timezone);

    // Check the latest memberships and check if they are renewable
    $membership = $user_memberships[0];
		$expiry_date = get_post_meta($membership->id, '_end_date', true);
		$expiry_year = date('Y', strtotime($expiry_date));

		if( date('Y', strtotime( $expiry_date) ) == $current_year ){
			if ($current_time < $renewal_start) {
					wp_send_json_error(['message' => 'Your HKOTA membership is not expiring. ']);
			} elseif ($current_time > $renewal_end) {
					wp_send_json_error(['message' => 'Your HKOTA membership is expired and the renew grace period is over. You must submit a new membership application now.']);
			} else {
					// User is eligible to renew
					$tenures = get_post_meta($membership->plan->id, 'tenures', true);

					wp_send_json_success([
							'user_membership_id'=> $membership->id,
							'membership_plan_id' => $membership->plan->id,
							'membership_plan_title' => $membership->plan->name,
							'application_type' => 'renew',
							'tenure'					 => $tenures
					]);
			}
		}

    // Check if membership is expiring on 30/04 of the current year
    wp_send_json_error(['message' => 'Your membership is not expiring.']);
}

add_action('wp_ajax_add_membership_product_to_cart', 'add_membership_product_to_cart');
add_action('wp_ajax_nopriv_add_membership_product_to_cart', 'add_membership_product_to_cart');

function add_membership_product_to_cart() {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
				wp_send_json_error(
					[
						'message' => 'Sorry, You must login/register on this website first.',
						'redirect'=> true
					]
				);
    }

    // Retrieve necessary data from the request
    $membership_plan_id = isset($_POST['membership_plan_id']) ? sanitize_text_field($_POST['membership_plan_id']) : '';
		$type = isset($_POST['application_type']) ? sanitize_text_field($_POST['application_type']) : '';
    $years = isset($_POST['years']) ? intval($_POST['years']) : 0;
		$start_year = isset($_POST['start_year']) ? intval($_POST['start_year']) : 0;
		$user_membership_id = isset($_POST['user_membership_id']) ? intval($_POST['user_membership_id']) : 0;

    if (empty($membership_plan_id) || $years <= 0 || empty($start_year) || empty($type) ) {
        wp_send_json_error(['message' => 'Invalid membership application request.']);
    }

		if( $type == 'renew' && empty($user_membership_id) ){
				wp_send_json_error(['message' => 'Invalid membership application request.']);
		}


		$product_id = get_membership_dummy_product_id();

		if( is_product_in_cart($product_id) ){
				wp_send_json_error(['message' => 'You can not add another membership item to cart.']);
		}

    // Retrieve the tenures data saved in the membership plan's meta
    $tenures = get_post_meta($membership_plan_id, 'tenures', true);
    if (empty($tenures)) {
        wp_send_json_error(['message' => 'No tenure data found for this membership plan.']);
    }

    // Find the corresponding fee based on the selected years
    $fee = null;
    foreach ($tenures as $tenure) {
        if ($tenure['Years'] == $years) {

			
			//check if student applying full membership
			$membership_plan = wc_memberships_get_membership_plan($membership_plan_id);
			$current_plan_name = $membership_plan->name;
			$user_id = get_current_user_id();
			$args = array(
					'status' => array('active')
			);	
			$user_memberships = wc_memberships_get_user_memberships($user_id, $args);
			$user_plan_name = $user_memberships[0]->plan->name;
			if( $current_plan_name === 'Full member' && $user_plan_name === 'Student member' ){
				$fee = $tenure['renew']; // Use the renewal fee
				break;
			}

            $fee = $tenure[$type]; // Use the renewal fee
            break;
        }
    }

    if ($fee === null) {
        wp_send_json_error(['message' => 'No matching tenure found for the selected number of years.']);
    }

    // Add the product to the cart for renewal
    $product_id = get_membership_dummy_product_id();
    if (!$product_id) {
        wp_send_json_error(['message' => 'The membership product could not be found.']);
    }

		$result = check_user_membership_eligiblilty(get_current_user_id(),$membership_plan_id,$type);
		if( !$result['permitted'] ){
			wp_send_json_error(['message' => $result['message'] ]);
		}

    // Add the product to the cart with custom meta
    $cart_item_data = array(
        'membership_plan_id' => $membership_plan_id,
				'user_membership_id' => $user_membership_id,
        'application_type' => $type,
        'years' => $years,
				'start_year' => $start_year,
        'membership_fee' => $fee // Add the calculated fee to the item meta
    );

    $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

    if ($cart_item_key) {
        wp_send_json_success(['message' => 'Membership renewal product added to cart successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to add membership renewal product to cart.']);
    }
}

add_action('wp_ajax_get_membership_plans', 'get_membership_plans');
add_action('wp_ajax_nopriv_get_membership_plans', 'get_membership_plans');

function get_membership_plans() {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
				wp_send_json_error(
					[
						'message' => 'Sorry, You must login/register on this website first.',
						'redirect'=> true
					]
				);
    }

    // Get all available membership plans
    $membership_plans = wc_memberships_get_membership_plans();

    $available_plans = array();

	//check if current user is student member
	$is_student_member = false;
	$user_id = get_current_user_id();
	$args = array(
			'status' => array('active')
	);	
	$user_memberships = wc_memberships_get_user_memberships($user_id, $args);
	if (!empty($user_memberships)) {
		$user_plan_name = $user_memberships[0]->plan->name;
		$is_student_member = ($user_plan_name === 'Student member')? true : false;
	}
	
    // Loop through the membership plans and check for tenure data
    foreach ($membership_plans as $plan) {
        $tenures = get_post_meta($plan->get_id(), 'tenures', true);
        if (!empty($tenures)) {
            $available_plans[] = array(
                'id' => $plan->get_id(),
                'name' => $plan->get_name(),
                'tenures' => $tenures,
				'is_student_member' => $is_student_member
            );
        }
    }

    if (empty($available_plans)) {
        wp_send_json_error(['message' => 'No membership plans available for selection.']);
    }

    wp_send_json_success($available_plans);
}

function check_user_membership_eligiblilty($user_id,$membership_plan_id,$type) {

	// Fetch user memberships
	$args = array(
			'status' => array('active', 'expired','paused')
	);

	$user_memberships = wc_memberships_get_user_memberships($user_id, $args);

	if (empty($user_memberships)) {
			return ['permitted' => true ];
	}
	$timezone = new DateTimeZone('Asia/Hong_Kong');
	$current_time = new DateTime('now',$timezone);
	$current_year = date('Y');
	$renewal_start = new DateTime("$current_year-04-01",$timezone);
	$renewal_end = new DateTime("$current_year-07-31 23:59:59", $timezone);

	// Check the latest memberships and check if they are renewable
	$membership = $user_memberships[0];

	$expiry_date = get_post_meta($membership->id, '_end_date', true);
	$expiry_year = date('Y', strtotime($expiry_date));
	//Handle Case: user apply a new membership same as current membership
	if( $membership_plan_id == $membership->plan_id ){
		if( date('Y', strtotime($expiry_date)) == $current_year && $type == 'new' ){
			if ($current_time < $renewal_end) {
				// When the membership is expiring but still in grace period.
				return ['permitted' => false, 'message'=>'You membership plan are still in renew grace period, please choose renew instead of applying new membership.' ];
			} elseif ($current_time > $renewal_end) {
				// When the membership is expiried but out of in grace period.
				return ['permitted' => true ];
			}
		} elseif( date('Y', strtotime($expiry_date)) > $current_year && $type == 'new' ){
			// When the membership is not expiring.
			return ['permitted' => false, 'message'=>'You can not apply the same membership as your current membership.' ];
		}
	}

	//Handle Case: user renew membership
	if( $membership_plan_id == $membership->plan_id  && $type == 'renew'){
		if( date('Y', strtotime($expiry_date)) == $current_year ){
			if ($current_time < $renewal_start) {
				return ['permitted' => false, 'message'=>'Our membership renew window open between 1th April until 31th July of your membership expiring year. Please come back later.' ];
			} elseif( $renewal_start < $current_time && $current_time < $renewal_end ){
				return ['permitted' => true ];
			} elseif ($current_time > $renewal_end) {
				return ['permitted' => false, 'message'=>'You membership renew grace period has ended, please apply a new membership.' ];
			}
		} else{
			return ['permitted' => false, 'message'=>'Our membership renew window open between 1th April until 31th July of your membership expiring year. Please come back later.' ];
		}
	}
	// All other situatration
	return ['permitted' => true ];


}

add_action('wp_ajax_membership_application_upload_file', 'membership_application_upload_file');
add_action('wp_ajax_nopriv_membership_application_upload_file', 'membership_application_upload_file');

function membership_application_upload_file() {

		if( isset( $_POST['document_type'] ) ){
			$document_type = sanitize_text_field( $_POST['document_type'] );
		} else{
			wp_send_json_error(['message' => 'Document type not specified.']);
		}

		if ( !empty( $_FILES['upload_document']['name'] ) )  {
			$uploaded_file = $_FILES['upload_document'];
      $allowed_file_types = ['image/jpeg', 'image/png', 'application/pdf'];
      $max_file_size = 5 * 1024 * 1024; // 5MB

      $file = [
          'name' => sanitize_text_field($uploaded_file['name']),
          'type' => sanitize_text_field($uploaded_file['type']),
          'tmp_name' => sanitize_text_field($uploaded_file['tmp_name']),
          'error' => $uploaded_file['error'],
          'size' => $uploaded_file['size']
      ];

      // Validate file type
      if (!in_array($file['type'], $allowed_file_types)) {
          wp_send_json_error(['message' => 'Only JPG, PNG, and PDF files are allowed.']);
      }

      // Validate file size
      if ($file['size'] > $max_file_size) {
         wp_send_json_error( ['message' => 'File size exceeds the maximum limit of 5MB.' ] );
      }

			$user_id = get_current_user_id();

			//Check previous uploaded file_exists
			$old_file = get_user_meta( $user_id , $document_type , true );
			if( $old_file ){
				wp_delete_file( COURSE_PUPIL_FILE_DIR . $old_file );
			}
			$filename = $file['name'];
	    $filename = wp_unique_filename( COURSE_PUPIL_FILE_DIR , $filename );
	    $upload = move_uploaded_file( $file['tmp_name'], COURSE_PUPIL_FILE_DIR . $filename );
	    update_user_meta( $user_id , $document_type , $filename );
			wp_send_json_success(['message' => 'uploaded']);

    } else {
        wp_send_json_error(['message' => 'No file provided.']);
    }

    wp_die();
}

add_action('wp_ajax_delete_membership_uploaded_file', 'delete_membership_uploaded_file');
add_action('wp_ajax_nopriv_delete_membership_uploaded_file', 'delete_membership_uploaded_file');

function delete_membership_uploaded_file() {

		if( isset( $_POST['document_type'] ) ){
			$document_type = sanitize_text_field( $_POST['document_type'] );
		} else{
			wp_send_json_error(['message' => 'Document type not specified.']);
		}

		//Check previous uploaded file_exists
		$user_id = get_current_user_id();
		$old_file = get_user_meta( $user_id , $document_type , true );
		if( $old_file ){
			wp_delete_file( COURSE_PUPIL_FILE_DIR . $old_file );
			delete_user_meta( $user_id , $document_type );
			wp_send_json_success(['message' => 'deleted']);
		}


}

// AJAX handler for fetching the OT list based on search input or page number
add_action('wp_ajax_hkota_ot_search', 'hkota_ot_search');
add_action('wp_ajax_nopriv_hkota_ot_search', 'hkota_ot_search');
function hkota_ot_search() {
    global $wpdb;
    $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;

    $table_name = $wpdb->prefix . 'hkota_ot_list';
    $limit = 50;
    $offset = ($page - 1) * $limit;

    // Build the query with LIKE for all columns if a search term is provided
    $where_clause = '';
    if (!empty($search_query)) {
        $where_clause = $wpdb->prepare(
            " WHERE Registration_no LIKE %s OR eng_name LIKE %s OR chi_name LIKE %s",
            '%' . $search_query . '%',
            '%' . $search_query . '%',
            '%' . $search_query . '%'
        );
    }

    // Fetch filtered results
    $results = $wpdb->get_results("SELECT * FROM $table_name $where_clause ORDER BY ID ASC LIMIT $limit OFFSET $offset");

    // Get the total number of rows that match the search query for pagination
    $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where_clause");
    $total_pages = ceil($total_rows / $limit);

    // Render the table rows and pagination using the existing render function
    $response = [
        'table' => hkota_ot_render_list($results, $page, $total_rows),
        'pagination' => hkota_ot_render_pagination($page, $total_pages)
    ];

    wp_send_json_success($response);
}

add_action('wp_ajax_filter_posts_by_year', 'filter_posts_by_year');
add_action('wp_ajax_nopriv_filter_posts_by_year', 'filter_posts_by_year');

function filter_posts_by_year() {
    $year = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '';
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
		$start_date = $year.'-01-01';
		$end_date = $year.'-12-31';

		do_shortcode("[display_post category=$category start-date=$start_date end-date=$end_date ]");

    wp_die();
}

add_action('wp_ajax_add_to_cart_with_membership_price', 'add_to_cart_with_membership_price');
add_action('wp_ajax_nopriv_add_to_cart_with_membership_price', 'add_to_cart_with_membership_price');

function add_to_cart_with_membership_price() {
    // Check if the user is logged in
		if (!is_user_logged_in()) {
				wp_send_json_error(
					[
						'message' => 'Sorry, You must login/register on this website first.',
						'redirect'=> true
					]
				);
    }

    // Get the product ID from the request
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product.']);
    }

    // Get the current user ID
    $user_id = get_current_user_id();

    // Fetch user memberships
    $args = array(
        'status' => array('active'),
    );
    $user_memberships = wc_memberships_get_user_memberships($user_id, $args);

    // Determine the price based on the membership status
    $product = wc_get_product($product_id);
    $price = $product->get_price(); // Default price (non-member)
    $member_price = get_post_meta($product_id, 'member_price', true);
    $student_price = get_post_meta($product_id, 'student_price', true);

    // Loop through memberships to find if the user is a student or regular member
    $is_student_member = false;
    $is_regular_member = false;

    foreach ($user_memberships as $membership) {
        if ( str_contains( 'student' , strtolower($membership->plan->name) ) ) {
            $is_student_member = true;
        } else {
            $is_regular_member = true;
        }
    }

    // Set the price based on the membership type
    if ($is_student_member && !empty($student_price)) {
        $price = $student_price;
    } elseif ($is_regular_member && !empty($member_price)) {
        $price = $member_price;
    }

    // Add the product to the WooCommerce cart
    $cart_item_data = array(
        'custom_price' => $price, // Store the price in item meta
    );

    $added = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

    if ($added) {
        wp_send_json_success(['message' => 'Product added to cart with adjusted price.']);
    } else {
        wp_send_json_error(['message' => 'Failed to add product to cart.']);
    }
}

add_action('wp_ajax_import_user_membership_ajax', 'import_user_membership_ajax');
add_action('wp_ajax_nopriv_import_user_membership_ajax', 'import_user_membership_ajax');

function import_user_membership_ajax() {
    // Check if a file was uploaded

		$row = json_decode(stripslashes($_POST['row']));

    if ( $_POST['row'] ) {
			$email = trim($row[12]);

			// Skip if user already exists
			if (email_exists($email)) {
					wp_send_json_error(['message' => 'User already exist for email: ' . $email ]);
			}

			// Create new user
			$username = sanitize_user(current(explode('@', $email)));
			$user_id = wp_create_user($username, wp_generate_password(), $email);

			if (is_wp_error($user_id)) {
					wp_send_json_error(['message' => 'Unable to create user for email: ' . $email]);
			}

			// Update user meta data

			$membership_number_array = explode('-', $row[0]);
			if( count( $membership_number_array ) !== 4 ){
					wp_send_json_error(['message' => 'Unable to create membership for email: ' . $email]);
			}

			$membership_number = $membership_number_array[0] . '-' .
													 $membership_number_array[1] . '~' .
													 $membership_number_array[2] . '-' .
													 $membership_number_array[3];

			update_user_meta($user_id, 'member_number', $membership_number );

			if( !empty($row[2]) ){
					$title = in_array($row[2], ['Dr.', 'Mr.', 'Mrs.', 'Miss', 'Ms.']) ? $row[2] : '';
					update_user_meta($user_id, 'member_title', $title);
			}

			if( !empty($row[3]) ){
					update_user_meta($user_id, 'member_full_name_zh', sanitize_text_field($row[3]));
			}

			$name_eng = explode(',', $row[4]);
			update_user_meta( $user_id, 'member_last_name_eng', trim($name_eng[0]) );
			update_user_meta( $user_id, 'last_name', trim($name_eng[0]) );
			update_user_meta( $user_id, 'billing_last_name', trim($name_eng[0]) );

			update_user_meta( $user_id, 'member_first_name_eng', trim($name_eng[1]) );
			update_user_meta( $user_id, 'first_name', trim($name_eng[1]) );
			update_user_meta( $user_id, 'billing_first_name', trim($name_eng[1]) );

			if( !empty($row[5]) ){
					update_user_meta($user_id, 'member_hkid', sanitize_text_field($row[5]));
			}

			update_user_meta($user_id, 'member_mobile', sanitize_text_field($row[9]));
			update_user_meta($user_id, 'member_working_place', sanitize_text_field($row[10]));
			update_user_meta($user_id, 'member_field', 'Others (please specify)');
			update_user_meta($user_id, 'member_field_others', sanitize_text_field($row[11]));

			update_user_meta($user_id, 'member_imported_user', 'yes' );

			//Handle membership

			global $wpdb;

			$prefix = $wpdb->prefix;

			$plan_id = 129;

			$args = array(
					'plan_id'	=> $plan_id,
					'user_id'	=> $user_id,
			);

			wc_memberships_create_user_membership( $args );

			global $wpdb;

			$prefix = $wpdb->prefix;

			$sql = "SELECT ID FROM {$prefix}posts
							WHERE
							`post_author` = %d AND
							`post_type` = %s AND
							`post_parent` = %d
							ORDER BY ID DESC" ;

			$membership_post_id = $wpdb->get_var( $wpdb->prepare( $sql, $user_id , "wc_user_membership" , $plan_id ) );

			$start	= '20'.$membership_number_array[2].'-04-30 16:00:00';

			if( $membership_number_array[3] == 'L' ){
				$membership_number_array[3] = '25';
			}

			$expiry =  '20'.$membership_number_array[3].'-04-30 16:00:00';

			update_post_meta($membership_post_id,'_start_date',$start);
			update_post_meta($membership_post_id,'_end_date',$expiry);

			$headers = array('Content-Type: text/html; charset=UTF-8');
		  $subject = '[HKOTA] Welcome to the New HKOTA Membership System';

		  ob_start();

		  $user = get_user_by('ID', $user_id );
		  $reset_key = get_password_reset_key( $user );
			$reset_url = wc_get_endpoint_url( 'lost-password', '', get_permalink( wc_get_page_id( 'myaccount' ) ) );
			$reset_url = add_query_arg( [
						  'key'   => $reset_key,
							'login' => rawurlencode( $user->user_login )
			], $reset_url );

		  $args = array(
		    'user'          => $user,
				'reset_url'			=> $reset_url,
		  );
		  // Include the PHP file that contains the HTML email structure

		  load_template( HKOTA_PLUGIN_DIR . '/email/import-welcome.php' ,true, $args );

		  // Get the content from the buffer and clean the buffer

		  $html_content = ob_get_clean();

		  $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

		  if ( ! $sent ) {
		      // Handle email not sent, you could log the error here
		      error_log( 'Email failed to send to ' . $to );
		  }
			//
			wp_send_json_success("Successfully imported users.");

    } else {
				wp_send_json_error(['message' => 'Row is empty']);
    }

}

add_action('wp_ajax_process_name_check_form', 'process_name_check_form');

function process_name_check_form() {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to submit this information.']);
    }

    $user_id = get_current_user_id();
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);

    // Ensure both fields are filled in
    if (empty($first_name) || empty($last_name)) {
        wp_send_json_error(['message' => 'Please provide both your first and last name.']);
    }

    // Update user meta
		update_user_meta( $user_id, 'member_last_name_eng', $last_name );
		update_user_meta( $user_id, 'last_name', $last_name );
		update_user_meta( $user_id, 'billing_last_name', $last_name );

		update_user_meta( $user_id, 'member_first_name_eng', $first_name );
		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'billing_first_name', $first_name );

    // Mark that the user has completed this check
    delete_user_meta($user_id, 'member_imported_user');

    wp_send_json_success(['message' => 'Your information has been saved.']);
}

add_action('wp_ajax_save_member_info', 'save_member_info');
function save_member_info() {

    // Get the user ID from the request
    $user_id = intval($_POST['user_id']);
		if( empty($user_id) ){
			wp_send_json_error( ['message' => 'Invaild user id' ]);
	    return;
		}
    $form_data = $_POST;

    // Initialize an array to collect updated fields for debugging/logging
    $updated_fields = [];

    // Loop through the form data to update user meta and email
    foreach ($form_data as $key => $value ) {
        $key = sanitize_text_field($key);
        $value = sanitize_text_field($value);

				if($key == 'action') continue;

        // Handle the email field specifically
        if ($key === 'user_email') {
            // Validate the email address
            if (!is_email($value)) {
                wp_send_json_error(['message' => 'Invalid email address.']);
            }

            // Check if the email is already used by another user
            if (email_exists($value) && email_exists($value) != $user_id) {
                wp_send_json_error(['message' => 'Email address is already in use.']);
            }

            // Update the user's email
            $update_email = wp_update_user([
                'ID' => $user_id,
                'user_email' => $value,
            ]);

            if (is_wp_error($update_email)) {
                wp_send_json_error(['message' => 'Failed to update email address.']);
            }

            $updated_fields[] = 'user_email';
        } else {
            // Update other user meta fields
            update_user_meta($user_id, $key, $value);
            $updated_fields[] = $key;
        }

    }

		if ( isset( $_FILES['member_certificate'] ) || isset( $_FILES['member_student_id'] ) ) {

			$allowed_file_types = ['image/jpeg', 'image/png', 'application/pdf'];
			$max_file_size = 5 * 1024 * 1024; // 5MB

			foreach ( $_FILES as $Key => $file ) {
	        if( empty( $file['name'] ) ){
						continue;
					}

	        // Validate file type
	        if (!in_array($file['type'], $allowed_file_types)) {
	            wp_send_json_error( ['message' => 'Invalid file type for ' . $file['name'] . '. Only JPG, PNG, and PDF files are allowed.' ]);
	            return;
	        }

	        // Validate file size
	        if ($file['size'] > $max_file_size) {
							wp_send_json_error( ['message' => 'File size exceeds the maximum limit of 5MB for ' . $file['name'] ]);
	            return;
	        }

					$filename = $file['name'];
					$filename = wp_unique_filename( COURSE_PUPIL_FILE_DIR , $filename );
					$upload = move_uploaded_file( $file['tmp_name'], COURSE_PUPIL_FILE_DIR . $filename );


	        if ($upload) {
						update_user_meta( $user_id , $Key , $filename );
	        } else {
						wp_send_json_error( [ 'message' => 'File upload failed for ' . $file['name']. ': ' . $upload['error'] ] );
						return;
	        }
	    }

    }

    // Respond with success and log the updated fields
    wp_send_json_success(['message' => 'Member details updated successfully.', 'updated_fields' => $updated_fields]);
}

add_action( 'wp_ajax_delete_pupil_document', 'delete_pupil_document' );

function delete_pupil_document(){

	$user_id = stripslashes($_POST['user_id']);
	if( empty($user_id) ){
		wp_send_json_error( ['message' => 'Invaild user id' ]);
		return;
	}
  $input_key = stripslashes($_POST['input_key']);

  $filename = get_user_meta( $user_id , $input_key,true );

	if( empty( $filename ) ) {
		wp_send_json_error( [ 'message' => 'Unable to delete file.' ] );
		return;
	}

	delete_user_meta($user_id,$input_key);

 	if( wp_delete_file( COURSE_PUPIL_FILE_DIR . $filename ) ){
		wp_send_json_success( [ 'message' => 'File deleted successfully.' ] );
		return;
	} else {
    wp_send_json_error( [ 'message' => 'Unable to delete file.' ] );
		return;
  }

}

add_action('wp_ajax_import_pupil_data', 'import_pupil_data');
add_action('wp_ajax_nopriv_import_pupil_data', 'import_pupil_data');

function import_pupil_data() {
    // Check if a file was uploaded

		$row = json_decode(stripslashes($_POST['row']));
		$course_id = stripslashes($_POST['course_id']);
		if( empty($course_id) ){
			wp_send_json_error(['message' => 'Course id not specified.']);
		}

		$course = new Course($course_id);

		if( $course->type !== 'training' && $course->type !== 'co-organized-event' ){
			wp_send_json_error(['message' => 'Import is not supported on this course type.']);
		}

    if ( empty( $row ) ) {
			wp_send_json_error(['message' => 'Row is empty']);
		}

		$email = trim($row[0]);
		$user_id = email_exists($email);

		if ( $user_id ) {

			if( $course->get_user_enrollment_id($user_id) ){
				wp_send_json_error(['message' => $email . ' already registered in this course']);
			}

			// User exist now insert enrollment data roll
			$enrollment = $course->enroll( $user_id, 'enrolled' );
			if( $enrollment ){
				$course->set_attendance_record($user_id);
				$enrollment->set('certificate_status','not_issue');
				$enrollment->set('payment_method','import');
				if( $course->type == 'training' ){
					$course->trigger_enrolled_email($user_id);
				} elseif( $course->type == 'co-organized-event' && $course->is_issue_cpd == 'true' ){
					$attendance = $enrollment->attendance;
					$attendance['attendance_status'] = 'fully_attended';
					$enrollment->set('attendance',$attendance);
					$result = $course->create_cpd_record($user_id);
				}
				wp_send_json_success("Successfully enroll users.");
			} else{
				wp_send_json_error(['message' => $email . ' unable to enroll.']);
			}

		} else {

			if( !empty( strtolower( trim( $row[1] ) ) ) ){
				wp_send_json_error(['message' => 'User not exist for email: ' . $email . ' but claimed to be HKOTA member.']);
			}

			// Create new user

			$username = sanitize_user(current(explode('@', $email)));
			$user_id = wp_create_user($username, wp_generate_password(), $email);
			if (is_wp_error($user_id)) {
					wp_send_json_error(['message' => 'Unable to create user for email: ' . $email]);
			}

			if( !empty($row[2]) ){
				update_user_meta( $user_id, 'mobile', $row[2] );
				update_user_meta( $user_id, 'billing_phone', $row[2] );
			}

			if( !empty($row[3]) ){
				update_user_meta($user_id, 'ot_reg_number', $row[3]);
			}

			if( !empty($row[4]) ){
				update_user_meta( $user_id, 'last_name', $row[4] );
				update_user_meta( $user_id, 'billing_last_name', $row[4] );
			}

			if( !empty($row[5]) ){
				update_user_meta( $user_id, 'first_name', $row[5] );
				update_user_meta( $user_id, 'billing_first_name', $row[5] );
			}

			update_user_meta($user_id, 'member_imported_user', 'yes' );

			$headers = array('Content-Type: text/html; charset=UTF-8');
			$subject = '[HKOTA] Welcome to Hong Kong Occupational Therapy Association';

			ob_start();

			$user = get_user_by('ID', $user_id );
			$reset_key = get_password_reset_key( $user );
			$reset_url = wc_get_endpoint_url( 'lost-password', '', get_permalink( wc_get_page_id( 'myaccount' ) ) );
			$reset_url = add_query_arg( [
							'key'   => $reset_key,
							'login' => rawurlencode( $user->user_login )
			], $reset_url );

			$args = array(
				'user'          => $user,
				'reset_url'			=> $reset_url,
			);
			// Include the PHP file that contains the HTML email structure

			load_template( HKOTA_PLUGIN_DIR . '/email/import-welcome-non-member.php' ,true, $args );

			// Get the content from the buffer and clean the buffer

			$html_content = ob_get_clean();

			$sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

			if( $course->get_user_enrollment_id($user_id) ){
				wp_send_json_error(['message' => $email . ' already registered in this course']);
			}

			$enrollment = $course->enroll( $user_id, 'enrolled' );
			if( $enrollment ){
				$course->set_attendance_record($user_id);
				$enrollment->set('certificate_status','not_issue');
				$enrollment->set('payment_method','import');
				if( $course->type == 'training' ){
					$course->trigger_enrolled_email($user_id);
					// $attendance['attendance_status'] = 'fully_attended';
					// $enrollment->set('attendance',$attendance);
					// $result = $course->create_certificate($enrollment->user_id);
          // if( $result ){
          //   $course->trigger_issue_certificate_email($enrollment->user_id);
					// }
				} elseif( $course->type == 'co-organized-event' && $course->is_issue_cpd == 'true' ){
					$attendance = $enrollment->attendance;
					$attendance['attendance_status'] = 'fully_attended';
					$enrollment->set('attendance',$attendance);
					$result = $course->create_cpd_record($user_id);
				}
				wp_send_json_success("Successfully enroll users.");
			} else{
				wp_send_json_error(['message' => $email . ' unable to enroll.']);
			}

		}

}

add_action('wp_ajax_admin_import_pupil_data', 'admin_import_pupil_data');
add_action('wp_ajax_nopriv_admin_import_pupil_data', 'admin_import_pupil_data');

// function admin_import_pupil_data() {
//     // Check if a file was uploaded
//
// 		$row = json_decode(stripslashes($_POST['row']));
// 		$course_id = stripslashes($_POST['course_id']);
// 		if( empty($course_id) ){
// 			wp_send_json_error(['message' => 'Course id not specified.']);
// 		}
//
//     if ( empty( $row ) ) {
// 			wp_send_json_error(['message' => 'Row is empty']);
// 		}
//
// 		$email = trim($row[0]);
// 		$user_id = email_exists($email);
//
// 		if ( $user_id ) {
//
// 			$course = new Course($course_id);
//
// 			if( $course->get_user_enrollment_id($user_id) ){
// 				wp_send_json_error(['message' => $email . ' already registered in this course']);
// 			}
//
// 			// User exist now create order for user to enroll into the course
//
// 			$user = get_user_by('id', $user_id );
// 			$order = wc_create_order();
// 			$order->set_created_via( 'Import' );
// 			$product = wc_get_product( get_dummy_product_id() );
// 			if (!$product) {
// 					wp_send_json_error(['message' => 'Course product not exist.']);
// 			}
// 			$item_id = $order->add_product($product, 1); // Quantity = 1
// 			$item = new WC_Order_Item_Product($item_id);
// 			$item->set_total( trim( $row[2] ) );
//       $item->set_subtotal( trim( $row[2] ) );
// 			$item->save();
// 			$cart_item_data = array(
// 					'course_id' => $course_id,
// 					'course_code' => $course->code,
// 					'course_title' => $course->title,
// 					'date' => $course->createDateString(),
// 					'time' => $course->createTimeString(),
// 					'cpd_point' => $course->cpd_point,
// 					'course_fee' => trim($row[2]),
// 			);
// 			foreach ($cart_item_data as $key => $value) {
//             $item->add_meta_data($key, $value, true);
//       }
//       $order->add_item($item);
// 			$order->set_customer_id($user_id);
// 			$order->set_payment_method('bacs');
// 	    $order->set_payment_method_title('Direct Bank Transfer');
// 			$order->calculate_totals();
// 			$order->update_status('wc-completed');
// 			$order->save();
//
// 			$enrollment_id = get_user_enrollment_id($user_id, $course_id);
// 			$enrollment = new Enrollment($enrollment_id);
//
// 			if( $enrollment ){
// 				if( $course->type == 'training' ){
// 					$attendance['attendance_status'] = 'fully_attended';
// 					$enrollment->set('attendance',$attendance);
// 					$result = $course->create_certificate($enrollment->user_id);
//           if( $result ){
//             $course->trigger_issue_certificate_email($enrollment->user_id);
// 					}
// 				}
// 			}
//
// 			wp_send_json_success("Successfully enroll users.");
//
// 		} else {
//
// 			if( strtolower( trim( $row[1] ) ) == 'yes' ){
// 				wp_send_json_error(['message' => 'User not exist for email: ' . $email . ' but claimed to be HKOTA member.']);
// 			}
//
// 			// Create new user
//
// 			$username = sanitize_user(current(explode('@', $email)));
// 			$user_id = wp_create_user($username, wp_generate_password(), $email);
// 			if (is_wp_error($user_id)) {
// 					wp_send_json_error(['message' => 'Unable to create user for email: ' . $email]);
// 			}
//
// 			if( !empty($row[3]) ){
// 				update_user_meta( $user_id, 'mobile', $row[3] );
// 				update_user_meta( $user_id, 'billing_phone', $row[3] );
// 			}
//
// 			if( !empty($row[4]) ){
// 				update_user_meta($user_id, 'ot_reg_number', $row[4]);
// 			}
//
// 			if( !empty($row[5]) ){
// 				update_user_meta( $user_id, 'last_name', $row[5] );
// 				update_user_meta( $user_id, 'billing_last_name', $row[5] );
// 			}
//
// 			if( !empty($row[6]) ){
// 				update_user_meta( $user_id, 'first_name', $row[6] );
// 				update_user_meta( $user_id, 'billing_first_name', $row[6] );
// 			}
//
// 			update_user_meta($user_id, 'member_imported_user', 'yes' );
//
// 			$headers = array('Content-Type: text/html; charset=UTF-8');
// 			$subject = '[HKOTA] Welcome to Hong Kong Occupational Therapy Association';
//
// 			ob_start();
//
// 			$user = get_user_by('ID', $user_id );
// 			$reset_key = get_password_reset_key( $user );
// 			$reset_url = wc_get_endpoint_url( 'lost-password', '', get_permalink( wc_get_page_id( 'myaccount' ) ) );
// 			$reset_url = add_query_arg( [
// 							'key'   => $reset_key,
// 							'login' => rawurlencode( $user->user_login )
// 			], $reset_url );
//
// 			$args = array(
// 				'user'          => $user,
// 				'reset_url'			=> $reset_url,
// 			);
// 			// Include the PHP file that contains the HTML email structure
//
// 			load_template( HKOTA_PLUGIN_DIR . '/email/import-welcome-non-member.php' ,true, $args );
//
// 			// Get the content from the buffer and clean the buffer
//
// 			$html_content = ob_get_clean();
//
// 			$sent = wp_mail( $user->user_email, $subject, $html_content, $headers );
//
// 			$course = new Course($course_id);
//
// 			if( $course->get_user_enrollment_id($user_id) ){
// 				wp_send_json_error(['message' => $email . ' already registered in this course']);
// 			}
//
// 			// User exist now create order for user to enroll into the course
//
// 			$user = get_user_by('id', $user_id );
// 			$order = wc_create_order();
// 			$order->set_created_via( 'Import' );
// 			$product = wc_get_product( get_dummy_product_id() );
// 			if (!$product) {
// 					wp_send_json_error(['message' => 'Course product not exist.']);
// 			}
// 			$item_id = $order->add_product($product, 1); // Quantity = 1
// 			$item = new WC_Order_Item_Product($item_id);
// 			$item->set_total( trim( $row[2] ) );
//       $item->set_subtotal( trim( $row[2] ) );
// 			$item->save();
// 			$cart_item_data = array(
// 					'course_id' => $course_id,
// 					'course_code' => $course->code,
// 					'course_title' => $course->title,
// 					'date' => $course->createDateString(),
// 					'time' => $course->createTimeString(),
// 					'cpd_point' => $course->cpd_point,
// 					'course_fee' => trim($row[2]),
// 			);
// 			foreach ($cart_item_data as $key => $value) {
//             $item->add_meta_data($key, $value, true);
//       }
//       $order->add_item($item);
// 			$order->set_customer_id($user_id);
// 			$order->set_payment_method('bacs');
// 	    $order->set_payment_method_title('Direct Bank Transfer');
// 			$order->calculate_totals();
// 			$order->update_status('wc-completed');
// 			$order->save();
//
// 			$enrollment_id = get_user_enrollment_id($user_id, $course_id);
// 			$enrollment = new Enrollment($enrollment_id);
//
// 			if( $enrollment ){
// 				if( $course->type == 'training' ){
// 					$attendance['attendance_status'] = 'fully_attended';
// 					$enrollment->set('attendance',$attendance);
// 					$result = $course->create_certificate($enrollment->user_id);
//           if( $result ){
//             $course->trigger_issue_certificate_email($enrollment->user_id);
// 					}
// 				}
// 			}
//
// 			wp_send_json_success("Successfully enroll users.");
//
// 		}
//
// }

function admin_import_pupil_data() {
    // Check if a file was uploaded

		$row = json_decode(stripslashes($_POST['row']));
		$course_id = stripslashes($_POST['course_id']);
		if( empty($course_id) ){
			wp_send_json_error(['message' => 'Course id not specified.']);
		}

		$course = new Course($course_id);

		if( $course->type !== 'training' && $course->type !== 'co-organized-event' ){
			wp_send_json_error(['message' => 'Import is not supported on this course type.']);
		}

    if ( empty( $row ) ) {
			wp_send_json_error(['message' => 'Row is empty']);
		}

		$email = trim($row[0]);
		$user_id = email_exists($email);

		if ( $user_id ) {

			if( $course->get_user_enrollment_id($user_id) ){
				wp_send_json_error(['message' => $email . ' already registered in this course']);
			}

			// User exist now insert enrollment data roll
			$enrollment = $course->enroll( $user_id, 'enrolled' );
			if( $enrollment ){
				$course->set_attendance_record($user_id);
				$enrollment->set('certificate_status','not_issue');
				if( $course->type == 'training' ){
					// $course->trigger_enrolled_email($user_id);
					$attendance['attendance_status'] = 'fully_attended';
					$enrollment->set('attendance',$attendance);
					$result = $course->create_certificate($enrollment->user_id);
          // if( $result ){
          //   $course->trigger_issue_certificate_email($enrollment->user_id);
					// }
				} elseif( $course->type == 'co-organized-event' && $course->is_issue_cpd == 'true' ){
					$attendance = $enrollment->attendance;
					$attendance['attendance_status'] = 'fully_attended';
					$enrollment->set('attendance',$attendance);
					$result = $course->create_cpd_record($user_id);
				}
				wp_send_json_success("Successfully enroll users.");
			} else{
				wp_send_json_error(['message' => $email . ' unable to enroll.']);
			}

		} else {

			if( !empty( strtolower( trim( $row[1] ) ) ) ){
				wp_send_json_error(['message' => 'User not exist for email: ' . $email . ' but claimed to be HKOTA member.']);
			}

			// Create new user

			$username = sanitize_user(current(explode('@', $email)));
			$user_id = wp_create_user($username, wp_generate_password(), $email);
			if (is_wp_error($user_id)) {
					wp_send_json_error(['message' => 'Unable to create user for email: ' . $email]);
			}

			if( !empty($row[2]) ){
				update_user_meta( $user_id, 'mobile', $row[2] );
				update_user_meta( $user_id, 'billing_phone', $row[2] );
			}

			if( !empty($row[3]) ){
				update_user_meta($user_id, 'ot_reg_number', $row[3]);
			}

			if( !empty($row[4]) ){
				update_user_meta( $user_id, 'last_name', $row[4] );
				update_user_meta( $user_id, 'billing_last_name', $row[4] );
			}

			if( !empty($row[5]) ){
				update_user_meta( $user_id, 'first_name', $row[5] );
				update_user_meta( $user_id, 'billing_first_name', $row[5] );
			}

			update_user_meta($user_id, 'member_imported_user', 'yes' );

			$headers = array('Content-Type: text/html; charset=UTF-8');
			$subject = '[HKOTA] Welcome to Hong Kong Occupational Therapy Association';

			ob_start();

			$user = get_user_by('ID', $user_id );
			$reset_key = get_password_reset_key( $user );
			$reset_url = wc_get_endpoint_url( 'lost-password', '', get_permalink( wc_get_page_id( 'myaccount' ) ) );
			$reset_url = add_query_arg( [
							'key'   => $reset_key,
							'login' => rawurlencode( $user->user_login )
			], $reset_url );

			$args = array(
				'user'          => $user,
				'reset_url'			=> $reset_url,
			);
			// Include the PHP file that contains the HTML email structure

			load_template( HKOTA_PLUGIN_DIR . '/email/import-welcome-non-member.php' ,true, $args );

			// Get the content from the buffer and clean the buffer

			$html_content = ob_get_clean();

			$sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

			if( $course->get_user_enrollment_id($user_id) ){
				wp_send_json_error(['message' => $email . ' already registered in this course']);
			}

			$enrollment = $course->enroll( $user_id, 'enrolled' );
			if( $enrollment ){
				$course->set_attendance_record($user_id);
				$enrollment->set('certificate_status','not_issue');
				if( $course->type == 'training' ){
					// $course->trigger_enrolled_email($user_id);
					$attendance['attendance_status'] = 'fully_attended';
					$enrollment->set('attendance',$attendance);
					$enrollment->set('payment_method','import');
					$result = $course->create_certificate($enrollment->user_id);
          // if( $result ){
          //   $course->trigger_issue_certificate_email($enrollment->user_id);
					// }
				} elseif( $course->type == 'co-organized-event' && $course->is_issue_cpd == 'true' ){
					$attendance = $enrollment->attendance;
					$attendance['attendance_status'] = 'fully_attended';
					$enrollment->set('attendance',$attendance);
					$enrollment->set('payment_method','import');
					$result = $course->create_cpd_record($user_id);
				}
				wp_send_json_success("Successfully enroll users.");
			} else{
				wp_send_json_error(['message' => $email . ' unable to enroll.']);
			}

		}

}

add_action('wp_ajax_fetch_user_cpd_data', 'fetch_user_cpd_data');
add_action('wp_ajax_nopriv_fetch_user_cpd_data', 'fetch_user_cpd_data');

function fetch_user_cpd_data() {

	// Check if the user is logged in
	if (!is_user_logged_in()) {
			wp_send_json_error( 'Sorry, Your current session has been logged out, please login again.');
	}

	$results = get_user_cpd_records();
	$data = array();

	if($results){
		foreach ($results as $result){
			$data[] = array(
				'Date'					=> $result->date_issued,
				'Course'				=> $result->title,
				'Course Code'		=> $result->code,
				'organization'	=> $result->organization,
				'CPD Point'			=> $result->cpd_point,
			);
		}
	}

	wp_send_json_success($data);

}

add_action('wp_ajax_resend_certificate_email', 'resend_certificate_email');

function resend_certificate_email() {
    // Check if user has proper permissions
    if (!current_user_can('edit_courses')) {
        wp_send_json_error('Insufficient permissions.');
        return;
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

    if (empty($user_id) || empty($course_id)) {
        wp_send_json_error('Missing required parameters.');
        return;
    }

    // Validate user exists
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_send_json_error('User not found.');
        return;
    }

    // Validate course exists
    $course = new Course($course_id);
    if (!$course->id) {
        wp_send_json_error('Course not found.');
        return;
    }

    // Check if user has a certificate issued for this course
    $args = [
        'user_id' => $user_id,
        'course_id' => $course_id,
    ];
    $cpd_record = get_user_cpd_record_by('course_id', $args);
    
    if (!$cpd_record || empty($cpd_record->file)) {
        wp_send_json_error('No certificate found for this user and course.');
        return;
    }

    // Trigger the certificate email
    try {
        $result = $course->trigger_issue_certificate_email($user_id);
        if ($result) {
            wp_send_json_success('Certificate email sent successfully.');
        } else {
            wp_send_json_error('Failed to send certificate email.');
        }
    } catch (Exception $e) {
        wp_send_json_error('Error sending email: ' . $e->getMessage());
    }
}

// Single pupil edit for sequential processing
add_action('wp_ajax_bulk_edit_single_pupil', 'bulk_edit_single_pupil');
function bulk_edit_single_pupil() {
    // Security check
    if (!current_user_can('edit_course')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
    }

    $pupil_id = intval($_POST['pupil_id']);
    $course_id = intval($_POST['course_id']);
    $enrollment_status = sanitize_text_field($_POST['enrollment_status']);
    $attendance_status = sanitize_text_field($_POST['attendance_status']);

    if (!$pupil_id || !$course_id) {
        wp_send_json_error(['message' => 'Invalid pupil ID or course ID.']);
    }

    if (empty($enrollment_status) && empty($attendance_status)) {
        wp_send_json_error(['message' => 'No changes specified.']);
    }

    // Get user information
    $user = get_user_by('ID', $pupil_id);

	if( !$user ){
		wp_send_json_error(['message' => 'User not found.']);
	}

    $user_name = $user->display_name;
    $user_email = $user->user_email;

    try {
        $enrollment_id = get_user_enrollment_id($pupil_id, $course_id);
        
        if (!$enrollment_id) {
            wp_send_json_error([
                'message' => 'User is not enrolled in this course.',
                'user_name' => $user_name,
                'user_email' => $user_email
            ]);
        }

        $enrollment = new Enrollment($enrollment_id);
        $course = new Course($course_id);
        
        // Track changes for response
        $is_updated_attendance_status = false;
        $is_updated_enrollment_status = false;

        // Update attendance status if provided and different from current
        if (!empty($attendance_status) && isset($enrollment->attendance['attendance_status']) && $attendance_status !== $enrollment->attendance['attendance_status']) {
            if ($enrollment->status == 'enrolled' || (!empty($enrollment_status) && $enrollment_status == 'enrolled')) {
                if (empty($enrollment->attendance)) {
                    $course->set_attendance_record($pupil_id);
                } else {
                    $attendance = $enrollment->attendance;
                    $attendance['attendance_status'] = $attendance_status;
                    $enrollment->set('attendance', $attendance);
                    if ($attendance_status == 'fully_attended') {
                        $result = $course->create_certificate($pupil_id);
                        if ($result) {
                            $course->trigger_issue_certificate_email($pupil_id);
                        }
                    }
                }
            }
            $is_updated_attendance_status = true;
        }

        // Update enrollment status if provided and different from current
        if (!empty($enrollment_status) && $enrollment_status !== $enrollment->status) {
            $result = $enrollment->set('status', $enrollment_status);
            if ($result !== false) {
                // Send email based on enrollment status
                switch ($enrollment_status) {
                    case 'enrolled':
                        $course->trigger_enrolled_email($pupil_id);
                        break;
                    case 'awaiting_approval':
                        $course->trigger_awaiting_approval_email($pupil_id);
                        break;
                    case 'pending':
                        $course->trigger_pending_email($pupil_id);
                        break;
                    case 'rejected':
                        $course->trigger_admin_rejected_email($pupil_id);
                        break;
                }
                $is_updated_enrollment_status = true;
            }
        }

        // Return appropriate response based on what was updated
        if ($is_updated_attendance_status && $is_updated_enrollment_status) {
            wp_send_json_success([
                'message' => 'Enrollment and attendance status updated.',
                'user_name' => $user_name,
                'user_email' => $user_email
            ]);
        } elseif ($is_updated_enrollment_status) {
            wp_send_json_success([
                'message' => 'Enrollment status updated.',
                'user_name' => $user_name,
                'user_email' => $user_email
            ]);
        } elseif ($is_updated_attendance_status) {
            wp_send_json_success([
                'message' => 'Attendance status updated.',
                'user_name' => $user_name,
                'user_email' => $user_email
            ]);
        } else {
            wp_send_json_success([
                'message' => 'Data not saved. You have made no change.',
                'user_name' => $user_name,
                'user_email' => $user_email
            ]);
        }

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error: ' . $e->getMessage(),
            'user_name' => $user_name,
            'user_email' => $user_email
        ]);
    }
}

add_action('wp_ajax_fetch_attendance_details', 'fetch_attendance_details');

function fetch_attendance_details() {
    // Security check
    if (!current_user_can('edit_course')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
    }

    $user_id = intval($_POST['user_id']);
    $course_id = intval($_POST['course_id']);

    if (!$user_id || !$course_id) {
        wp_send_json_error(['message' => 'Invalid user ID or course ID.']);
    }

    // Get user and course information
    $user = get_userdata($user_id);
    $course = new Course($course_id);
    
    if (!$user || !$course->id) {
        wp_send_json_error(['message' => 'User or course not found.']);
    }

    // Get enrollment information
    $enrollment_id = get_user_enrollment_id($user_id, $course_id);
    if (!$enrollment_id) {
        wp_send_json_error(['message' => 'User is not enrolled in this course.']);
    }

    $enrollment = new Enrollment($enrollment_id);
    $attendance_data = maybe_unserialize($enrollment->attendance);
    
    // Get course QR codes
    $qr_codes = $course->qr_code;
    
    $attendance_sections = [];
    
    if (!empty($qr_codes) && is_array($qr_codes)) {
        foreach ($qr_codes as $qr_code) {
            // Only include attendance-related QR codes
            if ($qr_code['type'] == 'registration' || $qr_code['type'] == 'end' || $qr_code['type'] == 'end_survey') {
                $section_id = $qr_code['id'];
                $attended = false;
                
                // Check if user attended this section
                if (isset($attendance_data['attendance_data'][$section_id]) && $attendance_data['attendance_data'][$section_id] == 1) {
                    $attended = true;
                }
                
                // Format the date and time string like in QR code generation
                $date_time = $qr_code['type'] . ' @ ' . $qr_code['time'] . '/' . $qr_code['date'];
                
                $attendance_sections[] = [
                    'section_id' => $section_id,
                    'section_name' => ucfirst($qr_code['type']),
                    'date_time' => $date_time,
                    'attended' => $attended,
                    'raw_date' => $qr_code['date'],
                    'raw_time' => $qr_code['time']
                ];
            }
        }
    }
    
    // Sort sections by date and time (ascending order)
    usort($attendance_sections, function($a, $b) {
        // First compare by date
        $date_comparison = strtotime($a['raw_date']) - strtotime($b['raw_date']);
        if ($date_comparison !== 0) {
            return $date_comparison;
        }
        // If dates are the same, compare by time
        return strtotime($a['raw_time']) - strtotime($b['raw_time']);
    });
    
    // Remove the raw_date and raw_time from the final output
    foreach ($attendance_sections as &$section) {
        unset($section['raw_date']);
        unset($section['raw_time']);
    }
    
    $response_data = [
        'user_name' => $user->first_name . ' ' . $user->last_name,
        'course_name' => $course->title,
        'overall_status' => isset($attendance_data['attendance_status']) ? $attendance_data['attendance_status'] : 'not_attended',
        'attendance_sections' => $attendance_sections
    ];
    
    wp_send_json_success($response_data);
}

add_action('wp_ajax_search_users_for_course_enrollment', 'search_users_for_course_enrollment');

// Search users for course enrollment (admin only)
function search_users_for_course_enrollment() {
    // Check admin permissions
    if (!current_user_can('edit_course')) {
        wp_send_json_error('Insufficient permissions.');
        return;
    }

    $search_term = sanitize_text_field($_POST['search_term']);
    $course_id = intval($_POST['course_id']);

    if (empty($search_term) || strlen($search_term) < 2) {
        wp_send_json_success([]);
        return;
    }

    global $wpdb;
    
    // Get users already enrolled in this course to exclude them
    $enrolled_users = $wpdb->get_col($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}hkota_course_enrollment WHERE course_id = %d",
        $course_id
    ));

    $exclude_ids = !empty($enrolled_users) ? implode(',', array_map('intval', $enrolled_users)) : '0';

    // Search users by email or display name
    $users = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, user_email, display_name, user_login 
         FROM {$wpdb->users} 
         WHERE (user_email LIKE %s OR display_name LIKE %s OR user_login LIKE %s)
         AND ID NOT IN ($exclude_ids)
         ORDER BY display_name ASC 
         LIMIT 15",
        '%' . $search_term . '%',
        '%' . $search_term . '%',
        '%' . $search_term . '%'
    ));

    $suggestions = [];
    foreach ($users as $user) {
        $user_meta = get_user_meta($user->ID);
        $first_name = isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '';
        $last_name = isset($user_meta['last_name'][0]) ? $user_meta['last_name'][0] : '';
        
        $suggestions[] = [
            'id' => $user->ID,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'full_name' => trim($first_name . ' ' . $last_name) ?: $user->display_name
        ];
    }
    set_transient('debug', $suggestions, 30);
    wp_send_json_success($suggestions);
}

add_action('wp_ajax_handle_admin_create_order_for_course', 'handle_admin_create_order_for_course');

// Create orders for multiple users and enroll them with payment required
function handle_admin_create_order_for_course() {
    // Check admin permissions
    if (!current_user_can('edit_course')) {
        wp_send_json_error('Insufficient permissions.');
        return;
    }

    $user_ids = array_map('intval', $_POST['user_ids']);
    $course_id = intval($_POST['course_id']);
    // Properly handle boolean from JavaScript - check for truthy string values
    $is_waiting_list_acceptance = isset($_POST['is_waiting_list_acceptance']) && 
                                  in_array($_POST['is_waiting_list_acceptance'], ['true', '1', 1, true], true);
	
    if (empty($user_ids) || empty($course_id)) {
        wp_send_json_error('Invalid user IDs or course ID.');
        return;
    }

    $course = new Course($course_id);
    $dummy_product_id = get_dummy_product_id();
    
    if (!$dummy_product_id) {
        wp_send_json_error('Course product not found.');
        return;
    }

    $results = [];
    $success_count = 0;
    $error_count = 0;

    foreach ($user_ids as $user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $results[] = [
                'user_id' => $user_id,
                'success' => false,
                'message' => 'User not found.'
            ];
            $error_count++;
            continue;
        }

        // For waiting list acceptance, check current enrollment status
        $existing_enrollment_id = get_user_enrollment_id($user_id, $course_id);
        if ($is_waiting_list_acceptance) {
            if (!$existing_enrollment_id) {
                $results[] = [
                    'user_id' => $user_id,
                    'user_email' => $user->user_email,
                    'user_name' => $user->display_name,
                    'success' => false,
                    'message' => 'User is not enrolled in this course.'
                ];
                $error_count++;
                continue;
            }
            
            // Check if user is currently on waiting list
            $existing_enrollment = new Enrollment($existing_enrollment_id);
            if ($existing_enrollment->status !== 'waiting_list') {
                $results[] = [
                    'user_id' => $user_id,
                    'user_email' => $user->user_email,
                    'user_name' => $user->display_name,
                    'success' => false,
                    'message' => 'User is not on waiting list.'
                ];
                $error_count++;
                continue;
            }
        } else {
            // For regular admin enrollment, check if user is already enrolled
            if ($existing_enrollment_id) {
                $results[] = [
                    'user_id' => $user_id,
                    'user_email' => $user->user_email,
                    'user_name' => $user->display_name,
                    'success' => false,
                    'message' => 'User already enrolled in this course.'
                ];
                $error_count++;
                continue;
            }
        }

        try {
            // Create WooCommerce order
            $order = wc_create_order([
                'customer_id' => $user_id,
                'status' => 'pending'
            ]);

            if (is_wp_error($order)) {
                throw new Exception('Failed to create order: ' . $order->get_error_message());
            }

            // Add course product to order
            $course_fee = $course->get_user_course_fee($user_id);
            $order->add_product(wc_get_product($dummy_product_id), 1, [
                'subtotal' => $course_fee,
                'total' => $course_fee
            ]);

            // Add course metadata to order
            foreach ($order->get_items() as $item_id => $item) {
                $item->add_meta_data('course_id', $course_id);
                $item->add_meta_data('course_title', $course->title);
                $item->add_meta_data('course_code', $course->code);
                $item->add_meta_data('date', $course->createDateString());
                $item->add_meta_data('time', $course->createTimeString());
                $item->add_meta_data('cpd_point', $course->cpd_point);
                $item->save();
            }

            // Set order total and add admin flag
            $order->calculate_totals();
            $order->update_meta_data('_admin_created_course_order', true);
            $order->save();

            // Handle enrollment based on type
            if ($is_waiting_list_acceptance) {
                // Update existing enrollment from waiting_list to on_hold
                $enrollment = new Enrollment($existing_enrollment_id);
                $enrollment->set('status', 'on_hold');
                $enrollment->set('payment_method', 'admin_created');
            } else {
                // Create new enrollment record with on_hold status
                $enrollment = $course->enroll($user_id, 'on_hold');
                if ($enrollment) {
                    $enrollment->set('payment_method', 'admin_created');
                    $enrollment->set('certificate_status', 'not_issue');
                }
            }
			
            // Handle free vs paid courses differently
            if ($course_fee == 0 || !$course_fee ) {
                // Free course - auto complete order to trigger enrollment hooks
                $order->update_status('completed', 'Auto-completed for free course');
                $order->save();

                $results[] = [
                    'user_id' => $user_id,
                    'user_email' => $user->user_email,
                    'user_name' => $user->display_name,
                    'success' => true,
                    'order_id' => $order->get_id(),
                    'payment_url' => null, // No payment needed for free courses
                    'email_sent' => false, // No payment email for free courses
                    'message' => 'User enrolled successfully (free course).'
                ];
            } else {
                // Paid course - send payment request email
                $payment_url = $order->get_checkout_payment_url();
                $email_sent = $course->trigger_payment_request_email($user_id, $order->get_id());
                
                $results[] = [
                    'user_id' => $user_id,
                    'user_email' => $user->user_email,
                    'user_name' => $user->display_name,
                    'success' => true,
                    'order_id' => $order->get_id(),
                    'payment_url' => $payment_url,
                    'email_sent' => $email_sent,
                    'message' => 'Order created and payment email sent successfully.'
                ];
            }
            $success_count++;
			
        } catch (Exception $e) {
            $results[] = [
                'user_id' => $user_id,
                'user_email' => $user->user_email,
                'user_name' => $user->display_name,
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
            $error_count++;
        }
    }

    wp_send_json_success([
        'results' => $results,
        'success_count' => $success_count,
        'error_count' => $error_count,
        'total_processed' => count($user_ids)
    ]);
}
?>