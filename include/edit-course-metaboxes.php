<?php
/**
 * Metabox of custom post type course
 */


function update_edit_form() {
     echo ' enctype="multipart/form-data"';
 }

add_action( 'post_edit_form_tag', 'update_edit_form' );

display_admin_notice();

function display_admin_notice(){
	$admin_notices = get_transient('admin_notice');
	if(!empty($admin_notices)){
		foreach ($admin_notices as $admin_notice) {
			edit_post_add_admin_notice($admin_notice['type'],$admin_notice['message']);
		}
	}
}

function edit_post_add_admin_notice($type,$message){
	add_action( 'admin_notices', function() use ($type,$message){
		global $pagenow;
		if ( 'post.php' === $pagenow ) {
			echo '<div class="notice '. $type . ' is-dismissible">
			<p>'. $message .'</p>
			</div>';
		}
	} );
}

function add_course_metaboxes() {

	add_meta_box(
		'course_details',
		'Course Details',
		'course_details',
		'course',
		'normal',
		'high'
	);

  add_meta_box(
    'course_status',
    'Course Status',
    'course_status',
    'course',
    'normal',
    'high'
  );

  global $post;
  $type = get_post_meta( $post->ID , 'course_type', true );


  if( get_post_status() !== 'auto-draft' &&
      ( $type == 'training' || $type == 'co-organized-event' ) ){

    add_meta_box(
      'course_import_pupil_data',
      'Import Pupil Data',
      'course_import_pupil_data',
      'course',
      'side',
      'low'
    );

    if( get_current_user_id() == 1 ){
      add_meta_box(
        'course_import_pupil_data_administrator',
        'Import Pupil Data (administrator)',
        'course_import_pupil_data_administrator',
        'course',
        'side',
        'low'
      );
    }

  }

}

/**
 * Output the HTML for the metabox.
 */

function course_details() {
    include_once HKOTA_PLUGIN_DIR . '/template/metabox-course-details.php';
}


function course_status(){

    include_once HKOTA_PLUGIN_DIR . '/template/metabox-course-status.php';

}

function course_import_pupil_data(){
  global $post;
  ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-csv/1.0.21/jquery.csv.js" integrity="sha512-2ypsPur7qcA+2JjmmIJR1c4GWFqTLIe1naXXplraMg0aWyTOyAMpOk+QL+ULpzwrO/GdwA3qB3FhVyyiR8gdhw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <div id="msg-hook" style=" display: none; ">
    <div id="error-notice"></div>
    <div id="respond-message"></div>
  </div>
  <p>Please upload the pupil file csv to import pupil data to this course. You may download the upload templete <a target="_blank" href="https://docs.google.com/spreadsheets/d/15WJ6O25TpARssfNLOx1DQ2nBClHdob7h0_wh3yopicY/edit?gid=0#gid=0">here</a>.</p>
  <input id="pupil-upload-csv" type="file" name="pupil-upload-csv" accept=".csv" >
  <br>
  <br>
  <button data-course-id="<?php echo $post->ID ;?>" type="button" class="button secondary-button" id="upload-pupil-data">Submit</button>
	<?php
}

function course_import_pupil_data_administrator(){
  global $post;
  ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-csv/1.0.21/jquery.csv.js" integrity="sha512-2ypsPur7qcA+2JjmmIJR1c4GWFqTLIe1naXXplraMg0aWyTOyAMpOk+QL+ULpzwrO/GdwA3qB3FhVyyiR8gdhw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <div id="admin-msg-hook" style=" display: none; ">
    <div id="admin-error-notice"></div>
    <div id="admin-respond-message"></div>
  </div>
  <p>Please upload the pupil file csv to import pupil data to this course. You may download the upload templete <a target="_blank" href="https://docs.google.com/spreadsheets/d/15WJ6O25TpARssfNLOx1DQ2nBClHdob7h0_wh3yopicY/edit?gid=0#gid=0">here</a>.</p>
  <input id="admin-pupil-upload-csv" type="file" name="admin-pupil-upload-csv" accept=".csv" >
  <br>
  <br>
  <button data-course-id="<?php echo $post->ID ;?>" type="button" class="button secondary-button" id="admin-upload-pupil-data">Submit</button>
	<?php
}

/**
 * Save the metabox data
 */

add_action( 'save_post', 'save_course_meta', 1, 2 );
function save_course_meta( $post_id, $post ) {
    
    $course = new Course($post_id);

	$admin_notice = [];

	// Return if the user doesn't have edit permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	// Verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times.
    if ( isset( $_POST['hkota_course_nonce'] ) && !wp_verify_nonce( $_POST['hkota_course_nonce'], 'hkota_course_metabox_action' ) ) {
        return $post_id;
    }

	// Only run un course post type
	if ( 'course' !== $post->post_type ) {
		return $post_id;
	}

	$course_meta = $_POST;

	foreach ( $course_meta as $key => $value ){
    if( !str_contains( $key , 'course_' ) ) {
      unset($course_meta[$key]);
      continue;
    }
		$course_meta[$key] = sanitize_textarea_field($course_meta[$key]);
	}

  if( $course_meta['course_type'] == 'training' ){
    unset($course_meta['course_cpd_point']);
  }

  if(isset($course_meta['course_cert_requirment'])){
    $course_meta['course_cert_requirment'] = stripslashes($course_meta['course_cert_requirment']);
    $course_meta['course_cert_requirment'] = json_decode($course_meta['course_cert_requirment']);
  }

  if( isset( $course_meta['course_capacity'] ) && $course->capacity !== $course_meta['course_capacity'] ){
       $course_capacity_changed = true;
  }

  if(isset($course_meta)){
  	foreach ( $course_meta as $key => $value ){
  		update_post_meta( $post_id, $key, $value);
  		if ( ! $value ) {
  			delete_post_meta( $post_id, $key );
    	}
    }
  }

  if( $course_meta['course_is_overide_cpd'] ){
    update_post_meta( $post_id, 'course_cpd_point', $course_meta['course_overide_cpd_point'] );
  }

	//Check Checkbox input

	empty( $course_meta['course_is_early_bird'] )? delete_post_meta( $post_id, 'course_is_early_bird' ) : "" ;
	empty( $course_meta['course_is_waiting_list'] )? delete_post_meta( $post_id, 'course_is_waiting_list' ) : "" ;
	empty( $course_meta['course_is_co_organized'] )? delete_post_meta( $post_id, 'course_is_co_organized' ) : "" ;
	empty( $course_meta['course_is_second_signee'] )? delete_post_meta( $post_id, 'course_is_second_signee' ) : "" ;
	empty( $course_meta['course_is_appendix'] )? delete_post_meta( $post_id, 'course_is_appendix' ) : "" ;
	empty( $course_meta['course_is_restricted'] )? delete_post_meta( $post_id, 'course_is_restricted' ) : "" ;
    empty( $course_meta['course_is_disable_rundown'] )? delete_post_meta( $post_id, 'course_is_disable_rundown' ) : "" ;
	empty( $course_meta['course_is_member_only'] )? delete_post_meta( $post_id, 'course_is_member_only' ) : "" ;
    empty( $course_meta['course_is_uploads_required'] )? delete_post_meta( $post_id, 'course_is_uploads_required' ) : "" ;
    empty( $course_meta['course_is_display_frontend'] )? delete_post_meta( $post_id, 'course_is_display_frontend' ) : "" ;
    empty( $course_meta['course_is_issue_cpd'] )? delete_post_meta( $post_id, 'course_is_issue_cpd' ) : "" ;
    empty( $course_meta['course_is_overide_cpd'] )? delete_post_meta( $post_id, 'course_is_overide_cpd' ) : "" ;
    empty( $course_meta['course_is_private'] )? delete_post_meta( $post_id, 'course_is_private' ) : "" ;

	// File handling
	$admin_notice = upload_file_handling($post_id,$_FILES,$admin_notice);

	// Multi-file handling for the Learning Material tab. Stored as a serialized
	// array of filenames in the `course_learning_material` postmeta.
	$admin_notice = upload_learning_material_handling( $post_id, $_FILES, $admin_notice );

  $course = new Course($post->ID);

  if( isset($_FILES['course_external_poster']) ){
    $file_type = mime_content_type( COURSE_FILE_DIR . $course->external_poster );
    switch($file_type){
      case 'image/png':
      case 'image/jpeg':
        update_post_meta( $course->id ,'course_snapshot', $course->external_poster );
        break;
      case 'application/pdf':
        generate_poster_snapshot( COURSE_FILE_DIR . $course->external_poster , COURSE_FILE_DIR , $course->external_poster, $course->id );
        break;
    }
  }

  if( isset($course_meta['course_is_uploads_required']) && empty($course_meta['course_cert_requirment']) ){
    $admin_notice[] = array(
      'type' => 'error',
      'message' => 'Error: Please at least input one required certificate.');
  }

	set_transient( 'admin_notice', $admin_notice, 3 );

  if( empty( get_post_meta( $post_id , 'course_survey' ,true ) ) ){
    $survey_default = array (
      0 =>
      array (
        'label' => 'The content of this course enhances a better understanding of the subject.',
        'id' => 'the-content-of-this-course-enhances-a-better-understanding-of-the-subject.',
        'value' => '',
        'type' => 'select',
        'options' => 'Definitely agree, Agree with reservations, Neither agree nor disagree, Disagree with reservations, Definitely disagree',
      ),
      1 =>
      array (
        'label' => 'The content of this course meet my training need.',
        'id' => 'the-content-of-this-course-meet-my-training-need.',
        'value' => '',
        'type' => 'select',
        'options' => 'Definitely agree, Agree with reservations, Neither agree nor disagree, Disagree with reservations, Definitely disagree',
      ),
      2 =>
      array (
        'label' => 'The content of this course is relevant to my clinical work.',
        'id' => 'the-content-of-this-course-is-relevant-to-my-clinical-work.',
        'value' => '',
        'type' => 'select',
        'options' => 'Definitely agree, Agree with reservations, Neither agree nor disagree, Disagree with reservations, Definitely disagree',
      ),
      3 =>
      array (
        'label' => 'The course is well organised to facilitate understanding of the material presented.',
        'id' => 'the-course-is-well-organised-to-facilitate-understanding-of-the-material-presented.',
        'value' => '',
        'type' => 'select',
        'options' => 'Definitely agree, Agree with reservations, Neither agree nor disagree, Disagree with reservations, Definitely disagree',
      ),
      4 =>
      array (
        'label' => 'To conclude, I am satisfied with the course.',
        'id' => 'to-conclude,-i-am-satisfied-with-the-course.',
        'value' => '',
        'type' => 'select',
        'options' => 'Definitely agree, Agree with reservations, Neither agree nor disagree, Disagree with reservations, Definitely disagree',
      ),
      5 =>
      array (
        'label' => 'What did you like the most about this course?',
        'id' => 'what-did-you-like-the-most-about-this-course?',
        'value' => '',
        'type' => 'text',
        'options' => '',
      ),
      6 =>
      array (
        'label' => 'What did you like the least about this course? Please suggest how it could be improved.',
        'id' => 'what-did-you-like-the-least-about-this-course?-please-suggest-how-it-could-be-improved.',
        'value' => '',
        'type' => 'text',
        'options' => '',
      ),
      7 =>
      array (
        'label' => 'What other topics you would be interested if more of this sort of course is to be offered?',
        'id' => 'what-other-topics-you-would-be-interested-if-more-of-this-sort-of-course-is-to-be-offered?',
        'value' => '',
        'type' => 'text',
        'options' => '',
      ),
      8 =>
      array (
        'label' => 'Other comments:',
        'id' => 'other-comments:',
        'value' => '',
        'type' => 'text',
        'options' => '',
      ),
    );
    update_post_meta( $post_id , 'course_survey' , $survey_default );
  }

  if( $course_capacity_changed && $course->get_enrollment_status() == 'available' ){
    $course->trigger_waiting_list_email();
  }

}

function upload_file_handling($post_id,$files,$admin_notice){

	foreach ( $files as $Key => $file) {

		if ( $Key === 'course_learning_material' ) {
			continue; // handled separately by upload_learning_material_handling()
		}

		if( !empty( $file['name'] ) && empty( $file['error'] ) ) {

			$filepath = $file['tmp_name'];
			$fileSize = filesize($filepath);
			$fileinfo = finfo_open(FILEINFO_MIME_TYPE);
			$filetype = finfo_file($fileinfo, $filepath);

			if ($fileSize === 0) {
				$admin_notice[] = array(
					'type' => 'error',
					'message' => 'Input field ' . str_replace( "_" , " " , $Key ) . ' file is empty.'
				 );
				continue;
			}

			if ($fileSize > 5242880) { // 5 MB
				$admin_notice[] = array(
          'type'    => 'error',
          'message' => 'Error: Input field (' . str_replace( "_" , " " , $Key ) . ') file is over 5MB, please upload a smaller size file.');
				continue;
			}

			$all_allowedTypes = [
				 'image/png' => 'png',
				 'image/jpeg' => 'jpg',
				 'application/pdf' => 'pdf'
			];

			$img_allowedTypes = [
				 'image/png' => 'png',
				 'image/jpeg' => 'jpg'
			];

			$pdf_allowedTypes = [
				'application/pdf' => 'pdf'
			];

			if( strval( $Key ) == 'course_appendix' ){

				if(!in_array($filetype, array_keys($pdf_allowedTypes))) {
					$admin_notice[] = array(
						'type' => 'error',
						'message' => 'Error: Input field (' . str_replace( "_" , " " , $Key ) . ') only PDF file type is allowed.');
					continue;
				}

			} elseif( strval( $Key ) == 'course_external_poster' ){

        if(!in_array($filetype, array_keys($all_allowedTypes))) {
					$admin_notice[] = array(
						'type' => 'error',
						'message' => 'Error: Input field (' . str_replace( "_" , " " , $Key ) . ') only PDF/JPG/PNG file type is allowed.');
					continue;
				}

      } else {

				if(!in_array($filetype, array_keys($img_allowedTypes))) {
					$admin_notice[] = array(
						'type' => 'error',
						'message' => 'Error: Input field (' . str_replace( "_" , " " , $Key ) . ') only png/jpg file type is allowed.');
					continue;
				}
			}

			$upload_dir = wp_upload_dir();

			if ( !empty( $upload_dir['basedir'] ) ) {
				$course_file_dir = $upload_dir['basedir'].'/course-files';
				$filename = $file['name'];
				$extension = $all_allowedTypes[$filetype];
				$filename = wp_unique_filename( $course_file_dir, $filename );
				move_uploaded_file( $file['tmp_name'], COURSE_FILE_DIR . $filename );
				update_post_meta( $post_id, $Key , $filename );
			}
		}
	}



	return $admin_notice;
}

// Handle multiple files uploaded under <input name="course_learning_material[]" multiple>.
// Files are validated (PDF / JPG / PNG, max 5MB each), moved to COURSE_FILE_DIR, and the
// resulting filenames are appended to the existing serialized array stored in postmeta.
function upload_learning_material_handling( $post_id, $files, $admin_notice ) {

	if ( empty( $files['course_learning_material'] ) || empty( $files['course_learning_material']['name'] ) ) {
		return $admin_notice;
	}

	$file_field = $files['course_learning_material'];

	// Single-file uploads still arrive as scalars; normalize into arrays.
	if ( ! is_array( $file_field['name'] ) ) {
		$file_field = array(
			'name'     => array( $file_field['name'] ),
			'type'     => array( $file_field['type'] ),
			'tmp_name' => array( $file_field['tmp_name'] ),
			'error'    => array( $file_field['error'] ),
			'size'     => array( $file_field['size'] ),
		);
	}

	$existing = get_post_meta( $post_id, 'course_learning_material', true );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}

	$allowed_types = array(
		'image/png'       => 'png',
		'image/jpeg'      => 'jpg',
		'application/pdf' => 'pdf',
	);

	$file_count = count( $file_field['name'] );
	for ( $i = 0; $i < $file_count; $i++ ) {

		$original_name = $file_field['name'][ $i ];
		$tmp_path      = $file_field['tmp_name'][ $i ];
		$error_code    = $file_field['error'][ $i ];

		if ( empty( $original_name ) || $error_code !== UPLOAD_ERR_OK || ! is_uploaded_file( $tmp_path ) ) {
			continue;
		}

		$file_size = filesize( $tmp_path );
		if ( $file_size === 0 ) {
			$admin_notice[] = array(
				'type'    => 'error',
				'message' => 'Error: Learning material file "' . $original_name . '" is empty.',
			);
			continue;
		}

		if ( $file_size > 5242880 ) {
			$admin_notice[] = array(
				'type'    => 'error',
				'message' => 'Error: Learning material file "' . $original_name . '" exceeds 5MB.',
			);
			continue;
		}

		$fileinfo = finfo_open( FILEINFO_MIME_TYPE );
		$filetype = finfo_file( $fileinfo, $tmp_path );
		finfo_close( $fileinfo );

		if ( ! isset( $allowed_types[ $filetype ] ) ) {
			$admin_notice[] = array(
				'type'    => 'error',
				'message' => 'Error: Learning material file "' . $original_name . '" must be PDF, JPG, or PNG.',
			);
			continue;
		}

		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['basedir'] ) ) {
			continue;
		}

		$course_file_dir = $upload_dir['basedir'] . '/course-files';
		$filename        = wp_unique_filename( $course_file_dir, sanitize_file_name( $original_name ) );

		if ( move_uploaded_file( $tmp_path, COURSE_FILE_DIR . $filename ) ) {
			$existing[] = $filename;
		}
	}

	if ( ! empty( $existing ) ) {
		update_post_meta( $post_id, 'course_learning_material', $existing );
	}

	return $admin_notice;
}
























?>
