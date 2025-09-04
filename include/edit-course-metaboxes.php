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

	global $post;

  // echo "<pre>";
  // echo print_r(get_transient('debug'),1);
  // echo "</pre>";

  // echo "<pre>";
  // echo print_r( date() ,1);
  // echo "</pre>";

	wp_nonce_field( basename( __FILE__ ), 'hkota_course_nonce' );

    $type = get_post_meta( $post->ID, 'course_type', true );
    $course_code = get_post_meta( $post->ID, 'course_code', true );
	$speaker = get_post_meta( $post->ID, 'course_speaker', true );
	$venue = get_post_meta( $post->ID, 'course_venue', true );

	$remarks = get_post_meta( $post->ID, 'course_remarks', true );
    $target_participants = get_post_meta( $post->ID, 'course_target_participants', true );

    $is_private = get_post_meta( $post->ID, 'course_is_private', true );
	$open_application = get_post_meta( $post->ID, 'course_open_application', true ); // Time in epoch format
	$is_early_bird = get_post_meta( $post->ID, 'course_is_early_bird', true );
	$early_bird_enddate = get_post_meta( $post->ID, 'course_early_bird_enddate', true );
	$fee_non_member_earlybird = get_post_meta( $post->ID, 'course_fee_non_member_earlybird', true );
	$fee_member_earlybird = get_post_meta( $post->ID, 'course_fee_member_earlybird', true );
	$fee_non_member = get_post_meta( $post->ID, 'course_fee_non_member', true );
	$fee_member = get_post_meta( $post->ID, 'course_fee_member', true );
	$capacity = get_post_meta( $post->ID, 'course_capacity', true );
	$capacity_remarks = get_post_meta( $post->ID, 'course_capacity_remarks', true );
	$is_waiting_list = get_post_meta( $post->ID, 'course_is_waiting_list', true );
	$deadline = get_post_meta( $post->ID, 'course_deadline', true );
    $confirmation_date = get_post_meta( $post->ID, 'course_confirmation_date', true );
    $contact = get_post_meta( $post->ID, 'course_contact', true );
    $external_link = get_post_meta( $post->ID, 'course_external_link', true );
    $external_poster = get_post_meta( $post->ID, 'course_external_poster', true );

	$is_restricted = get_post_meta( $post->ID, 'course_is_restricted', true );
    $is_member_only =  get_post_meta( $post->ID, 'course_is_member_only', true );
    $is_uploads_required = get_post_meta( $post->ID, 'course_is_uploads_required', true );
    $cert_requirment = get_post_meta( $post->ID, 'course_cert_requirment', true );
	$min_years = get_post_meta( $post->ID, 'course_min_years', true );

	$is_co_organized = get_post_meta( $post->ID, 'course_is_co_organized', true );
	$co_organizer_title = get_post_meta( $post->ID, 'course_co_organizer_title', true );
	$co_organizer_logo = get_post_meta( $post->ID, 'course_co_organizer_logo', true );
	$cert_heading = get_post_meta( $post->ID, 'course_cert_heading', true );
    $cert_title = get_post_meta( $post->ID, 'course_cert_title', true );
    $cert_serial_prefix = get_post_meta( $post->ID, 'course_cert_serial_prefix', true );
	$cert_signee_1 = get_post_meta( $post->ID, 'course_cert_signee_1', true );
	$cert_signature_1 = get_post_meta( $post->ID, 'course_cert_signature_1', true );
	$is_second_signee = get_post_meta( $post->ID, 'course_is_second_signee', true );
	$cert_signee_2 = get_post_meta( $post->ID, 'course_cert_signee_2', true );
	$cert_signature_2 = get_post_meta( $post->ID, 'course_cert_signature_2', true );

    $rundown = get_post_meta( $post->ID, 'course_rundown', true );
    $relatedness = get_post_meta( $post->ID, 'course_relatedness', true );
    $is_issue_cpd = get_post_meta( $post->ID, 'course_is_issue_cpd', true );
    $is_overide_cpd = get_post_meta( $post->ID, 'course_is_overide_cpd', true );
    $cpd_issue_org = get_post_meta( $post->ID, 'course_cpd_issue_org', true );
    $cpd_point = get_post_meta( $post->ID, 'course_cpd_point', true );
    $overide_cpd_point = get_post_meta( $post->ID, 'course_overide_cpd_point', true );
    $is_disable_rundown = get_post_meta( $post->ID, 'course_is_disable_rundown', true );
    $is_appendix = get_post_meta( $post->ID, 'course_is_appendix', true );
    $appendix = get_post_meta( $post->ID, 'course_appendix', true );
    $poster = get_post_meta( $post->ID, 'course_poster', true );
    $qr_codes = get_post_meta( $post->ID, 'course_qr_code', true );
    $quiz = get_post_meta( $post->ID, 'course_quiz', true );
    $survey = get_post_meta( $post->ID, 'course_survey', true );

	?>

	<style>
    #course_details .inside{
    border:unset;
    margin:unset;
    padding:unset;
    }

    .tab-container {
      display: flex;
    }

    .tab-buttons {
      flex: 1;
      display: flex;
      flex-direction: column;
      border-right: 1px solid #ccc;
    }

    .tab {
      display: none;
      flex: 3;
    }

    .tab-content {
      padding: 0 30px 30px 30px;
      border: unset;
      width: 100%;
    }

    .tab-button {
       border-top: unset;
       border-left: unset;
       border-right: unset;
       cursor: pointer;
       text-align: left;
       margin: 0;
       padding: 10px;
       display: block;
       box-shadow: none;
       text-decoration: none;
       line-height: 20px !important;
       border-bottom: 1px solid #eee;
       color: #2271b1;
       transition-property: border, background, color;
       transition-duration: .05s;
       transition-timing-function: ease-in-out;
       background: #fafafa;
    }

    .tab-button.active {
       color: #555;
       position: relative;
       background-color: #eee;
    }

    .tab-content label{
      min-width: 170px;
      display: inline-block;
    }

    .tab-content input:not([type='checkbox']),
    .tab-content textarea,
    .tab-content select{
      width: 350px;
    }

    .tab-content textarea{
      min-height: 80px;
    }

    .tab-buttons i {
      width: 20px;
    }

    label.textarea-label{
      vertical-align: top;
    }
    .flex {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    .error {
        color: red;
    }
    .course-file-preview {
        position: relative;
        width: fit-content;
    }
    .course-file-preview i {
      position: absolute;
      right: -8px;
      top: -8px;
      font-size: 17px;
      color: #ff0000;
      cursor: pointer;
    }
    table .time {
        width: 200px;
    }

    table .icon-buttons {
        width: 100px;
    }

    table .icon-buttons i {
        margin: 0 5px;
        cursor: pointer;
    }

     .form-builder {
         margin-bottom: 20px;
     }

     .form-builder button {
         margin-right: 10px!important;
     }
     .popup-form {
         display: none;
         position: fixed;
         top: 30%;
         width: 350px;
         left: 50%;
         transform: translate(-50%, -20%);
         padding: 0 20px 20px 20px;
         background: white;
         border: 1px solid #ddd;
         box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
         z-index: 1000;
     }
     .popup-overlay {
         display: none;
         position: fixed;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: rgba(0, 0, 0, 0.5);
         z-index: 999;
     }
     .popup-form label {
         display: block;
         margin: 10px 0 5px;
     }
     .popup-form input, .popup-form select {
         width: 100%;
         padding: 5px;
         margin-bottom: 10px;
     }
     .popup-error-field {
         color: red;
         margin-bottom: 10px;
     }
     .form-row {
         display: flex;
         align-items: center;
         margin-bottom: 10px;
         padding: 10px;
         border: 1px solid #ddd;
         background-color: #f9f9f9;
         cursor: move;
         max-width: 550px
     }
     .form-row label {
         margin-right: 10px;
     }
     .form-row input, .form-row select, .form-row textarea {
         margin-right: 10px;
     }
     .form-row .form-actions {
         margin-left: auto;
         display: flex;
         gap: 5px;
     }
     .form-row button {
         padding: 3px 8px;
         cursor: pointer;
     }
     .custom-form {
         display: flex;
         flex-direction: column;
         gap: 10px;
     }
     #survey .custom-form input {
          height: 30px;
          margin: 0 6px 0 0 !important;
      }

      #rundown-wrapper{
          position: relative;
          display: inline-block;
          width: 100%
      }

      .cover-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* background-color: rgba(255, 255, 255, 0.4); /* Semi-transparent */ */
            z-index: 10;
            cursor: not-allowed;
      }

      .switch {
            position: relative;
            display: inline-block;
            width: 39px;
            height: 22px;
            min-width: unset!important;
        }

        /* The slider */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:checked + .slider:before {
            transform: translateX(15px);
        }

        .cert-container {
            margin-top: 10px;
            max-width: 350px;
        }
        .cert {
            display: inline-block;
            background-color: #f0f0f0;
            padding: 5px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
            font-size: 14px;
        }
        .cert button {
            margin-left: 10px;
            background: none;
            border: none;
            color: #ff0000;
            cursor: pointer;
            font-size: 14px;
        }
        .quiz-form-container {
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #ccc;
            position: relative;
            max-width: 550px;
            background: #FFF;
            cursor:grab;
        }
        .quiz-form-container button{
          cursor:pointer;
        }
        .quiz-form-header {
            display: flex;
            justify-content: space-between;
        }
        .quiz-form-header input {
            flex: 1;
        }
        button.quiz-toggleFields {
            border: unset;
            background: #FFF;
        }
        .quiz-form-fields {
            margin-top: 15px;
        }
        .quiz-input-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .quiz-input-row button {
            border: unset !important;
            box-shadow: unset !important;
            background: #FFF;
            margin: 0 3px;
            font-size: 16px;
            color: #333333;
        }
        input.quiz-form-name {
            border: unset !important;
            font-size: 16px;
            font-weight: 700;
            padding: unset;
            box-shadow: unset !important;
        }
        .quiz-popup-error-field {
            color: red;
        }
        .quiz-cover-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 10;
        }
        .edit-form-name {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            background-color: #0073aa;
            color: white;
            padding: 5px;
            border-radius: 3px;
        }
        #quiz-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9;
            background: #0000009e;
        }
        #quiz-fieldPopup label, #quiz-fieldPopup input, #quiz-fieldPopup select {
            display: block;
            width: 100%;
        }
        #quiz-fieldPopup {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 0 20px 20px 20px;
            background-color: rgb(255, 255, 255);
            border: 1px solid rgb(204, 204, 204);
            z-index: 100;
            width: 300px;
        }
        /* Tooltip wrapper to position relative to contain the tooltip */
        .woocommerce-tooltip-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-left: 5px;
        }

        /* The hidden tooltip */
        .woocommerce-tooltip {
            display: none;
            position: absolute;
            top: 130%;
            left: 56%;
            transform: translateX(-50%);
            background-color: #f7f7f7; /* WooCommerce light background */
            color: #616161; /* WooCommerce text color */
            padding: 8px 12px;
            font-size: 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            z-index: 9999;
        }

        /* Show tooltip on hover */
        .woocommerce-tooltip-wrapper:hover .woocommerce-tooltip {
            display: block;
            width: 170px;
            height: 150px;
            line-height: 20px;
            font-size: 12px;
            background: #333333;
            color: #FFF;
        }

        /* Small arrow at the top of the tooltip */
        .woocommerce-tooltip::before {
            content: '';
            position: absolute;
            top: -11px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: transparent transparent #333333 transparent; /* Light background for arrow */
        }
        h2.hndle.ui-sortable-handle {
            justify-content: flex-start;
            gap: 13px;
        }
  </style>

	 <div class="tab-container">
	     <div class="tab-buttons">
	         <button class="tab-button basic-info" onclick="openTab(event, 'basic-info')"><i class="fa-solid fa-circle-info"></i> Basic Information</button>
					 <button class="tab-button application" onclick="openTab(event, 'application')"><i class="fa-solid fa-circle-info"></i> Application</button>
					 <button class="tab-button restriction training" onclick="openTab(event, 'restriction')"><i class="fa-solid fa-filter"></i> Restriction Rules</button>
	         <button class="tab-button certification training" onclick="openTab(event, 'certification')"><i class="fa-solid fa-certificate"></i> Certifications</button>
           <button class="tab-button appendix-upload training" onclick="openTab(event, 'appendix-upload')"><i class="fa-solid fa-file-arrow-up"></i> Appendix upload</button>
           <button class="tab-button rundown" onclick="openTab(event, 'rundown')"><i class="fa-solid fa-filter"></i> Rundowns & CPD</button>
           <button class="tab-button quiz training" onclick="openTab(event, 'quiz')"><i class="fa-solid fa-cloud-arrow-down"></i> Quiz</button>
					 <button class="tab-button survey training" onclick="openTab(event, 'survey')"><i class="fa-solid fa-cloud-arrow-down"></i> End course survey</button>
           <button class="tab-button generate-document training" onclick="openTab(event, 'generate-document')"><i class="fa-solid fa-cloud-arrow-down"></i> Generate Poster & QR code</button>
				</div>
        <div id="basic-info" class="tab tab-content">
          <h3>Basic Information</h3>
            <div class="training co-organized">
              <label for="code">Course code:</label>
              <input type="text" id="code" name="course_code" value="<?php echo $course_code ?>"><br><br>
            </div>
            <div class="training">
              <label class="textarea-label" for="course_speaker">Speakers:</label>
              <textarea type="text" id="course_speaker" name="course_speaker"><?php echo $speaker ?></textarea><br><br>
            </div>
            <label for="course_venue">Venue:</label>
            <input type="text" id="course_venue" name="course_venue" value="<?php echo $venue ?>"><br><br>
            <div class="training">
              <label class="textarea-label" for="course_remarks">Remarks:</label>
              <textarea id="course_remarks" name="course_remarks" ><?php echo $remarks ?></textarea><br><br>
            </div>
            <label class="textarea-label" for="course_target_participants">Target Participants:</label>
            <textarea id="course_target_participants" name="course_target_participants" ><?php echo $target_participants ?></textarea><br><br>
            <div class="event co-organized">
              <label for="course_external_poster">Upload poster:</label>
                <input id="course_external_poster" type="file" name="course_external_poster"><br><br>
               <?php
                if( !empty( $external_poster ) ){
                  ?>
                    <div>
                      <div class="course-file-preview">
                        <a href="<?php echo home_url('/wp-content/uploads/course-files/') . $external_poster ?>" target="_blank">
                          <img src="
                          <?php
                              $file_type = mime_content_type( COURSE_FILE_DIR . $external_poster );
                              switch($file_type){
                                case 'image/png':
                                case 'image/jpeg':
                                  echo home_url('/wp-content/uploads/course-files/') . $external_poster;
                                  break;
                                case 'application/pdf':
                                  echo plugins_url( '/hkota-courses-and-memberships/asset/pdf-icon.png' );
                                  break;
                              }
                          ?>" width='50px' >
                        </a>
                        <i data-post-id="<?php echo $post->ID ;?>" data-input-key="course_external_poster" class="fa-solid fa-circle-xmark"></i>
                      </div>
                      <span><?php echo $external_poster ?><span>
                      <br><br>
                    </div>
                  <?php
                }
               ?>
              <span>(Filesize maximum: 5MB in jpg/png/pdf only)</span><br><br>
          </div>
        </div>

        <div id="application" class="tab tab-content">
            <h3>Application</h3>
            <label for="course_is_private">Private mode:</label>
            <input type="checkbox" value="true" id="course_is_private" name="course_is_private" <?php echo ($is_private) ? "checked" : "" ; ?>>
            <span>Enable private mode for this course.</span><br><br>
            <span>* In private mode, course will not be visible in calandar or catalog, but still accessible via direct link of the course.</span><br><br>
            <div class="training">
              <label for="course_open_application">Open application date:</label>
              <input type="date" id="course_open_application" name="course_open_application" value="<?php echo $open_application ?>"><br><br>
              <span>* Default starting time is 18:00 of the day set above.</span><br><br>
              <label for="course_is_early_bird">Early bird:</label>
              <input type="checkbox" value="true" id="course_is_early_bird" name="course_is_early_bird" class="trigger" <?php echo ($is_early_bird) ? "checked" : "" ; ?>>
              <span>Enable early bird discount for this course.</span><br><br>
              <div id="course_is_early_bird_details" <?php echo ($is_early_bird) ? "" : 'style="display:none;"' ; ?> >
                <label for="course_early_bird_enddate">Early bird Deadline:</label>
                <input type="date" id="course_early_bird_enddate" name="course_early_bird_enddate" value="<?php echo $early_bird_enddate ?>"><br><br>
                <label for="course_fee_non_member_earlybird">Early bird Non member fee:</label>
                <input type="number" id="course_fee_non_member_earlybird" name="course_fee_non_member_earlybird" value="<?php echo $fee_non_member_earlybird ?>"><br><br>
                <label for="course_fee_member_earlybird">Early bird member fee:</label>
                <input type="number" id="course_fee_member_earlybird" name="course_fee_member_earlybird" value="<?php echo $fee_member_earlybird ?>"><br><br>
              </div>
              <label for="course_fee_non_member">Non member fee:</label>
              <input type="number" id="course_fee_non_member" name="course_fee_non_member" value="<?php echo $fee_non_member ?>"><br><br>
              <label for="course_fee_member">Member fee:</label>
              <input type="number" id="course_fee_member" name="course_fee_member" value="<?php echo $fee_member ?>"><br><br>
              <label for="course_capacity">Capacity:</label>
              <input type="number" id="course_capacity" name="course_capacity" value="<?php echo $capacity ?>"><br><br>
              <label class="textarea-label" for="course_capacity_remarks">Remarks on capacity:</label>
              <textarea type="text" id="course_capacity_remarks" name="course_capacity_remarks"><?php echo $capacity_remarks ?></textarea><br><br>
              <label for="course_is_waiting_list">Waiting List:</label>
              <input type="checkbox" value="true" id="course_is_waiting_list" name="course_is_waiting_list" <?php echo ($is_waiting_list) ? "checked" : "" ; ?>>
              <span>Enable waiting list for this course.</span><br><br>
              <label for="course_deadline">Application Deadline:</label>
              <input type="date" id="course_deadline" name="course_deadline" value="<?php echo $deadline ?>"><br><br>
              <label for="course_confirmation_date">confirmation Date:</label>
              <input type="date" id="course_confirmation_date" name="course_confirmation_date" value="<?php echo $confirmation_date ?>"><br><br>
              <label for="course_contact">Contact person:</label>
              <input type="text" id="course_contact" name="course_contact" value="<?php echo $contact ?>"><br><br>
            </div>
            <div class="event co-organized">
              <label for="course_contact">External URL:</label>
              <input type="text" id="course_external_link" name="course_external_link" value="<?php echo $external_link ?>"><br><br>
              <span>The external application link of this course (i.e. Google form link).</span><br><br>
              <span class="theme-red">You must provide full url (including https:// or http:// ) here.</span><br><br>
            </div>
        </div>

        <div id="restriction" class="tab tab-content training">
            <h3>Restriction Rules</h3>
            <label for="course_is_restricted">Restriction Rules:</label>
            <input type="checkbox" value="true" id="course_is_restricted" name="course_is_restricted" class="trigger" <?php echo ($is_restricted) ? "checked" : "" ; ?>>
            <span>Enable restriction rules for this course.</span><br><br>
            <div id="course_is_restricted_details" <?php echo ($is_restricted) ? "" : 'style="display:none;"' ; ?>>
              <label for="course_min_years">Min Years Required:</label>
              <input type="number" id="course_min_years" name="course_min_years" value="<?php echo $min_years ?>"><br><br>
              <label for="course_is_member_only">HKOTA Members only:</label>
              <input type="checkbox" value="true" id="course_is_member_only" name="course_is_member_only" class="trigger" <?php echo ($is_member_only) ? "checked" : "" ; ?>><br><br>
              <label for="course_is_uploads_required">Require certificate upload:</label>
              <input type="checkbox" value="true" id="course_is_uploads_required" name="course_is_uploads_required" class="trigger" <?php echo ($is_uploads_required) ? "checked" : "" ; ?>><br>
              <div id="course_is_uploads_required_details" <?php echo ($is_uploads_required) ? "" : 'style="display:none;"' ; ?>>
                <p style="color:red">Alert: If you turn on this function, the default enrollment status is "Awaiting approval".<br>You will need to approval them manually.</p>
                <label style=" vertical-align: top; margin-top: 5px;" for="cert-input">Required Certificate:</label>
                <div style=" display: inline-block; ">
                  <input type="text" id="cert-input" placeholder="Enter required certificate" />
                  <button type="button" class="button secondary-button" id="add-cert">Add</button>
                  <div class="cert-container" id="cert-list"></div>
                </div>
                <!-- Hidden field to store the certs -->
                <input type="hidden" name="course_cert_requirment" id="course_cert_requirment" />
              </div>
            </div>
        </div>

        <div id="certification" class="tab tab-content training">
            <h3>Certification</h3>

            <label for="course_is_co_organized">Co-organizer:</label>
            <input type="checkbox" value="true" id="course_is_co_organized" name="course_is_co_organized" class="trigger" <?php echo ($is_co_organized) ? "checked" : "" ; ?>>
            <span>Enable co-organizer for this course.</span><br><br>
            <div id="course_is_co_organized_details" <?php echo ($is_co_organized) ? "" : 'style="display:none;"' ; ?>>
              <label for="course_co_organizer_title">Co-organizer title:</label>
              <input type="text" id="course_co_organizer_title" name="course_co_organizer_title" value="<?php echo $co_organizer_title ?>"><br><br>
              <label for="course_co_organizer_logo">Co-organizer logo:</label>
              <input id="course_co_organizer_logo" type="file" name="course_co_organizer_logo" ><br><br>
              <?php
               if( !empty( $co_organizer_logo ) ){
                 ?>
                   <div>
                     <div class="course-file-preview">
                       <a href="<?php echo home_url('/wp-content/uploads/course-files/') . $co_organizer_logo ?>" target="_blank">
                         <img src="<?php echo home_url('/wp-content/uploads/course-files/') . $co_organizer_logo ?>" width='100px' >
                       </a>
                       <i data-post-id="<?php echo $post->ID ;?>" data-input-key="course_co_organizer_logo" class="fa-solid fa-circle-xmark"></i>
                     </div>
                     <span><?php echo $co_organizer_logo ?><span>
                     <br><br>
                   </div>
                 <?php
               }
              ?>
              <span>(Filesize maximum: 5MB in jpg/png only)</span><br><br>
             </div>
             <label for="course_cert_heading">Certificate Heading:</label>
             <input type="text" id="course_cert_heading" name="course_cert_heading" value="<?php echo $cert_heading ?>"><br><br>
             <label class="textarea-label" for="course_cert_title">Course Title:</label>
             <textarea type="text" id="course_cert_title" name="course_cert_title"><?php echo htmlspecialchars($cert_title); ?></textarea><br><br>
             <label for="course_cert_serial_prefix">Serial number:</label>
             <input type="text" id="course_cert_serial_prefix" name="course_cert_serial_prefix" value="<?php echo $cert_serial_prefix ?>">
             <div class="woocommerce-tooltip-wrapper">
                 <span class="woocommerce-tooltip-trigger"><i class="fa-solid fa-circle-question"></i></span>
                 <div class="woocommerce-tooltip">The starting prefix of the certificate serial number.
                               For example, if your input 2023/0003, the serial number
                               will be starting with 2023/0003/0001, 2023/0003/0002 ... and so on.</div>
             </div><br><br>
             <label class="textarea-label" for="course_cert_signee_1">Signee Title:</label>
             <textarea type="text" id="course_cert_signee_1" name="course_cert_signee_1"><?php echo htmlspecialchars($cert_signee_1); ?></textarea><br><br>
             <label for="course_cert_signature_1">Signature image:</label>
             <input id="course_cert_signature_1" type="file" name="course_cert_signature_1"><br><br>
             <?php
              if( !empty( $cert_signature_1 ) ){
                ?>
                 <div>
                    <div class="course-file-preview">
                      <a href="<?php COURSE_FILE_URL . $cert_signature_1 ?>" target="_blank">
                        <img src="<?php echo COURSE_FILE_URL . $cert_signature_1 ?>" width='100px' >
                      </a>
                      <i data-post-id="<?php echo $post->ID ;?>" data-input-key="course_cert_signature_1" class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <span><?php echo $cert_signature_1 ?><span>
                    <br><br>
                  </div>
                <?php
              }
             ?>
             <span>(Filesize maximum: 5MB in jpg/png only)</span><br><br>
             <label for="course_is_second_signee">Second Signee:</label>
             <input type="checkbox" value="true" id="course_is_second_signee" name="course_is_second_signee" class="trigger" <?php echo ($is_second_signee) ? "checked" : "" ; ?>>
             <span>Enable second signee for this course.</span><br><br>
             <div id="course_is_second_signee_details" <?php echo ($is_second_signee) ? "" : 'style="display:none;"' ; ?>>
               <label class="textarea-label" for="course_cert_signee_2">Second Signee Title:</label>
               <textarea type="text" id="course_cert_signee_2" name="course_cert_signee_2"><?php echo $cert_signee_2 ?></textarea><br><br>
               <label for="course_cert_signature_2">Second Signature image:</label>
               <input id="course_cert_signature_2" type="file" name="course_cert_signature_2"><br><br>
               <?php
                if( !empty( $cert_signature_2 ) ){
                  ?>
                    <div>
                      <div class="course-file-preview">
                        <a href="<?php echo COURSE_FILE_URL . $cert_signature_2 ?>" target="_blank">
                          <img src="<?php echo COURSE_FILE_URL . $cert_signature_2 ?>" width='100px' >
                        </a>
                        <i data-post-id="<?php echo $post->ID ;?>" data-input-key="course_cert_signature_2" class="fa-solid fa-circle-xmark"></i>
                      </div>
                      <span><?php echo $cert_signature_2 ?><span>
                      <br><br>
                    </div>
                  <?php
                }
               ?>
               <span>(Filesize maximum: 5MB in jpg/png only)</span><br><br>
            </div>

            <a id="get-poster" target="_blank" href="/wp-admin/admin-post.php?action=preview_certificate&course_id=<?php echo $post->ID ?>">
              <button type="button" class="button button-primary">Preview Certificate</button>
            </a><br><br>

        </div>

        <div id="appendix-upload" class="tab tab-content training">
            <h3>Appendix</h3>
            <label for="course_is_appendix">Appendix:</label>
            <input type="checkbox" value="true" id="course_is_appendix" name="course_is_appendix" class="trigger" <?php echo ($is_appendix) ? "checked" : "" ; ?>>
            <span>Enable appendix for this course.</span><br><br>
            <div id="course_is_appendix_details" <?php echo ($is_appendix) ? "" : 'style="display:none;"' ; ?>>
              <label for="course_appendix">Upload appendix:</label>
                <input id="course_appendix" type="file" name="course_appendix"><br><br>
               <?php
                if( !empty( $appendix ) ){
                  ?>
                    <div>
                      <div class="course-file-preview">
                        <a href="<?php echo home_url('/wp-content/uploads/course-files/') . $appendix ?>" target="_blank">
                          <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/pdf-icon.png' ) ?>" width='50px' >
                        </a>
                        <i data-post-id="<?php echo $post->ID ;?>" data-input-key="course_appendix" class="fa-solid fa-circle-xmark"></i>
                      </div>
                      <span><?php echo $appendix ?><span>
                      <br><br>
                    </div>
                  <?php
                }
               ?>
              <span>(Filesize maximum: 5MB in pdf only)</span><br><br>
            </div>
        </div>

        <div id="rundown" class="tab tab-content">

          <h3>Course Rundown & CPD</h3>

          <div class="co-organized">
            <label for="course_is_issue_cpd">Enable CPD point:</label>
            <input type="checkbox" value="true" id="course_is_issue_cpd" name="course_is_issue_cpd" class="trigger" <?php echo ($is_issue_cpd) ? "checked" : "" ; ?>>
            <span>Enable CPD points record for this course.</span><br><br>
            <div id="course_is_issue_cpd_details" <?php echo ($is_issue_cpd) ? "" : 'style="display:none;"' ; ?>>
              <label for="course_cpd_issue_org">CPD Issuing Organization:</label>
              <input type="text" id="course_cpd_issue_org" name="course_cpd_issue_org" value="<?php echo $cpd_issue_org ?>"><br><br>
              <label for="course_cpd_point">CPD Points:</label>
              <input type="number" id="course_cpd_point" name="course_cpd_point"
                value="<?php echo ($type == 'co-organized-event')? $cpd_point : "" ;?>" step="0.5"><br><br>
            </div>
          </div>
          <div class="training">
            <label for="course_is_overide_cpd">Overide CPD Points:</label>
            <input type="checkbox" value="true" id="course_is_overide_cpd" name="course_is_overide_cpd" class="trigger" <?php echo ($is_overide_cpd) ? "checked" : "" ; ?>>
            <span>Enable manual CPD point input.</span><br><br>
            <div id="course_is_overide_cpd_details" <?php echo ($is_overide_cpd) ? "" : 'style="display:none;"' ; ?>>
              <label for="course_overide_cpd_point">CPD Points:</label>
              <input type="number" sid="course_overide_cpd_point" name="course_overide_cpd_point" value="<?php echo $overide_cpd_point ?>" step="0.5"><br><br>
            </div>
            <label for="course_relatedness">Course relatedness:</label>
            <select id="course_relatedness" name="course_relatedness">
                <option <?php echo ($relatedness == "total" )? "selected" : "" ?> value="total">Total relatedness</option>
                <option <?php echo ($relatedness == "partial" )? "selected" : "" ?> value="partial">Partial relatedness</option>
            </select><br><br>
            <div id="rundown-warning-hook">
              <?php echo ($qr_codes)?
                 "<p style='color:red;max-width:500px;'>Alert: You have already generated QR-code for this course,
                                  editing rundown after QR code generate will result in earsing
                                  all QR codes and pupil sign in / out datas.<br><br>You will need to re-generate QR code manually later.</p>" : "" ?>
            </div>
          </div>
          <div style="width:100%;">
            <br>
            <span style="width:170px;display: inline-block;">Disable rundown editing</span>
            <label class="switch">
                 <input type="checkbox" value="true" id="toggle-button" name="course_is_disable_rundown">
                 <span class="slider"></span>
            </label>
            <span> (Lock the rundown to avoid unintented changes)</<span>
            <br>
            <br>
            <br>
          </div>
          <div id="rundown-wrapper">
            <!-- Form -->
            <div id="form-container">
            <label for="section_date">Date:<span style="color:red;">*</span></label>
            <input type="date" id="section_date"><br><br>
            <label for="section_start_time">Start Time:<span style="color:red;">*</span></label>
            <input type="time" id="section_start_time" /><br><br>
            <div id="conditional-display-duration">
              <label for="section_duration">Duration (mins):<span style="color:red;">*</span></label>
              <input type="number" id="section_duration" min="1"><br><br>
            </div>
            <label for="section_type">Section Type:<span style="color:red;">*</span></label>
            <select id="section_type">
                <option class="training co-organized" value="registration">Registration</option>
                <option class="training" value="cpd_section">CPD Section</option>
                <option class="training" value="break">Break</option>
                <option class="training co-organized" value="quiz">Quiz</option>
                <option class="training co-organized" value="end">End</option>
                <option class="training co-organized" value="end_survey">End & Survey</option>
                <option class="co-organized event" value="others">Others</option>
            </select><br><br>
            <label for="section_name">Section Name:<span style="color:red;">*</span></label>
            <input type="text" id="section_name"><br><br>
            <div style="display:none;" id="conditional-display-speaker">
              <label for="section_speaker">Speaker:<span style="color:red;">*</span></label>
              <input type="text" id="section_speaker"><br><br>
            </div>
            <label class="textarea-label" for="section_description_textarea">Description:</label>
            <textarea id="section_description_textarea" name="section_description_textarea"></textarea><br><br>
            <button type="button" id="submit-section" class="button button-primary">Submit Section</button>
            <button type="button" id="cancel-edit" class="button button-primary" style="display: none;">Cancel</button>
            <p id="error" class="error"></p><br>

            </div>

            <!-- Rundown Table -->
            <div id="rundown-container"></div>
            <div id="cpd-point-hook" class="training">
              <b>CPD point of this rundown: <?php echo $cpd_point ;?></b>
            </div><br><br>
            <button type="button" id="save-rundown" class="button button-primary">Save</button><br><br>

          </div>
        </div>

        <div id="quiz" class="tab tab-content training co-organized">
            <h3>Quiz</h3>

            <div id="quiz-warning-hook">
              <?php echo ($qr_codes)?
                 "<p style='color:red;max-width:500px;'>Alert: You have already generated QR-code for this course,
                                  editing quiz after QR code generate will result in earsing
                                  all QR codes and pupil's quiz datas.<br><br>You will need to re-generate QR code manually later.</p>" : "" ?>
            </div>

            <h4>Quiz form Builder</h4>

            <div>
                <button type="button" class="button button-primary" id="quiz-addFormButton">Add New Quiz Form</button>
            </div>

            <div id="quiz-formsContainer"></div>

            <!-- Pop-up to add/edit quiz fields -->
            <div id="quiz-fieldPopup" style="display:none;" >
                <h3>Add Input Field</h3>
                <label for="quiz-fieldLabel">Label:</label>
                <input type="text" id="quiz-fieldLabel">
                <br>
                <label for="quiz-fieldType">Type:</label>
                <select id="quiz-fieldType">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="select">Select</option>
                    <option value="email">Email</option>
                    <option value="textarea">Textarea</option>
                </select>
                <br>
                <label for="quiz-fieldOptions" id="quiz-fieldOptionsLabel" style="display:none;">Options (comma separated):</label>
                <input type="text" id="quiz-fieldOptions" style="display:none;">
                <br>
                <span class="quiz-popup-error-field" id="quiz-popupError"></span>
                <br>
                <button type="button" class="button button-primary" id="quiz-saveFieldButton">Save Field</button>
                <button type="button" class="button button-secondary" id="quiz-cancelFieldButton">Cancel</button>
            </div>
            <div style="display:none;" id="quiz-popup-overlay"></div>

            <br><br>
            <button type="button" class="quiz-save-form button button-primary">Save</button>




        </div>

        <div id="survey" class="tab tab-content training co-organized">
            <h3>End course survey</h3>

            <div id="survey-warning-hook">
              <?php echo ($qr_codes)?
                 "<p style='color:red;max-width:500px;'>Alert: You have already generated QR-code for this course,
                                  editing survey after QR code generate will result in earsing pupil's sign in/out and survey datas.</p>" : "" ?>
            </div>

            <h4>Survey form Builder</h4>

            <div class="form-builder">
              <button type="button" class="button button-secondary add-text-field">Add Text Field</button>
              <button type="button" class="button button-secondary add-number-field">Add Number Field</button>
              <button type="button" class="button button-secondary add-select-field">Add Select Field</button>
              <button type="button" class="button button-secondary add-email-field">Add Email Field</button>
              <button type="button" class="button button-secondary add-textarea-field">Add Textarea Field</button>
            </div>

            <div class="custom-form">
                <!-- Dynamic form elements will be appended here -->
            </div><br><br>

            <button type="button" class="save-form button button-primary">Save</button>

            <div class="popup-overlay"></div>
            <div class="popup-form">
                <h3>Add Input Field</h3>
                <div class="popup-error-field error-message"></div>

                <label for="input-label">Label (required)</label>
                <input type="text" class="input-label">

                <label for="input-value">Preset Value</label>
                <input type="text" class="input-value">

                <div class="select-options-container" style="display: none;">
                    <label for="input-options">Select Options (comma-separated)</label>
                    <input type="text" class="input-options">
                </div>
                <br><br>
                <button type="button" class="button button-primary save-input">Save</button>
                <button type="button" class="button button-secondary cancel-input">Cancel</button>
            </div>

        </div>

        <div id="generate-document" class="tab tab-content training">
            <h3>Generate Poster & QR code</h3>
            <div class="training">
              <a id="get-poster" target="_blank" href="/wp-admin/admin-post.php?action=generate_poster&course_id=<?php echo $post->ID ?>">
                <button type="button" class="button button-primary">Generate Poster</button>
              </a><br><br>
              <div id="poster-hook">
                <?php
                 if( !empty( $poster ) ){
                   ?>
                     <div>
                       <div class="course-file-preview">
                         <a href="<?php echo home_url('/wp-content/uploads/course-poster/') . $poster ?>" target="_blank">
                           <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/pdf-icon.png' ) ?>" width='50px' >
                         </a>
                         <i data-post-id="<?php echo $post->ID ;?>" data-input-key="course_poster" class="fa-solid fa-circle-xmark"></i>
                       </div>
                       <span><?php echo $poster ?><span>
                       <br><br>
                     </div>
                   <?php
                 }
                ?>
              </div>
            </div>
            <button type="button" id="generate-qr-code" class="button button-primary">Generate QR Code</button><br><br>
            <div id="qr-code-hook">
              <?php
               if( !empty( $qr_codes ) ){
                 ?>
                   <div class="flex">
                     <?php foreach ($qr_codes as $qr_code): ?>
                     <div class="course-file-preview">
                       <a href="<?php echo $qr_code['url'] ?>" target="_blank">
                         <img src="<?php echo $qr_code['url'] ?>" width='140px' >
                       </a>
                     </div>
                     <?php endforeach; ?>
                   </div>
                   <br>
                   <a target="_blank" href="/wp-admin/admin-post.php?action=download_qrcode&course_id=<?php echo $post->ID ?>"><button type="button" class="button button-primary">Download QR Code</button></a><br><br>

                 <?php

               }
              ?>
            </div>
        </div>
	 </div>

   <link rel="stylesheet" type="text/css" href="/wp-content/plugins/hkota-courses-and-memberships/lib/clockpicker/clockpicker-gh-pages/dist/jquery-clockpicker.css">
 	 <script type="text/javascript" src="/wp-content/plugins/hkota-courses-and-memberships/lib/clockpicker/clockpicker-gh-pages/dist/jquery-clockpicker.js"></script>
   <script src="/wp-content/plugins/hkota-courses-and-memberships/lib/jQuery-ui/jquery-ui.js"></script>

   <!-- Course type add-on -->
   <script>

    jQuery(document).ready(function() {
      jQuery('div#course_details').removeClass('closed');
      jQuery('div#course_details h2.hndle').each( function(){
        if( jQuery(this).text() == 'Course Details' ){
          console.log( jQuery(this).text() );
          var html = `<span> —
                        <label for="course_type" style=" margin-left: 10px; ">
                          <select id="course_type" name="course_type">
                            <optgroup label="Course Type">
                                      <option <?php echo ($type == "training" || empty($type) )? "selected" : "" ?> value="training">CPD Programme</option>
                                      <option <?php echo ($type == "hkota-event")? "selected" : "" ?> value="hkota-event">HKOTA Event</option>
                                      <option <?php echo ($type == "supporting-event")? "selected" : "" ?> value="supporting-event">Supporting Event</option>
                                      <option <?php echo ($type == "co-organized-event")? "selected" : "" ?> value="co-organized-event">Co-organized Event</option>
                                    </optgroup>
                          </select>
                        </label>
                      </span>`;
          jQuery(this).append(html);
        }
      });



      jQuery(document).on('click','select#course_type',function(){
        jQuery('div#course_details').removeClass('closed');
      });

      jQuery(document).on('change','select#course_type',function(){
        courseFunctionFilter(jQuery(this).val());
      });

      courseFunctionFilter( jQuery( 'select#course_type' ).val() );

      function courseFunctionFilter(courseType){

        if( courseType == '' ){
          return;
        }

        switch(courseType){
          case 'training' :
          case 'semi-private-event' :
            jQuery('.tab-button').show();
            jQuery('button.tab-button.active').removeClass('active');
            jQuery('button.tab-button.basic-info').addClass('active');
            jQuery('.event, .co-organized').hide();
            jQuery('.training').show();
            jQuery('.tab-container > .tab-content').hide();
            jQuery('div#basic-info').show();
            jQuery('#course_is_issue_cert_details').show();
            break;
          case 'hkota-event':
            jQuery('.training, .co-organized').hide();
            jQuery('.event').show();
            jQuery('button.tab-button.active').removeClass('active');
            jQuery('button.tab-button.basic-info').addClass('active');
            jQuery('.tab-container > .tab-content').hide();
            jQuery('div#basic-info').show();
            break;
          case 'supporting-event':
            jQuery('.training, .co-organized').hide();
            jQuery('.event').show();
            jQuery('button.tab-button.active').removeClass('active');
            jQuery('button.tab-button.basic-info').addClass('active');
            jQuery('.tab-container > .tab-content').hide();
            jQuery('div#basic-info').show();
            break;
          case 'co-organized-event':
            jQuery('.training, .event').hide();
            jQuery('.co-organized').show();
            jQuery('button.tab-button.active').removeClass('active');
            jQuery('button.tab-button.basic-info').addClass('active');
            jQuery('.tab-container > .tab-content').hide();
            jQuery('div#basic-info').show();
            break;
        }

      }

    });

   </script>

   <!-- Quiz multible form builder -->
   <script>
      // jQuery function to handle multi quiz form builder.
      jQuery(document).ready(function() {

        let quizFormsData = <?php echo empty( $quiz )? "{}" : json_encode( $quiz, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) ; ?>;
        renderQuizForm();

        function renderQuizForm(){
          jQuery('#quiz-formsContainer').html('');
          for (const [key, value] of Object.entries(quizFormsData)){
            let formHtml = `
                <div class="quiz-form-container" id="${key}">
                    <div class="quiz-form-header">
                        <input type="text" value="${value.name}" class="quiz-form-name" />
                        <button type="button" class="quiz-toggleFields"><i class="fa-solid fa-minus"></i></button>
                    </div>
                    <div id="quiz-formFields-${key}" class="quiz-form-fields"></div>
                    <button type="button" class="button button-primary quiz-addField" data-formid="${key}">Add Field</button>
                    <button type="button" class="button button-warning quiz-delFrom" data-formid="${key}" style="margin-left: 3px;">Delete</button>
                </div>
            `;
            jQuery('#quiz-formsContainer').append(formHtml);
            renderFromElement(key);
          }

          // Make forms sortable
          jQuery('#quiz-formsContainer').sortable({
              stop: function(event, ui) {
                  // Re-order quizFormsData based on new order of the forms
                  const newOrder = jQuery(this).children('.quiz-form-container').map(function() {
                      return jQuery(this).attr('id');
                  }).get();

                  let sortedFormsData = {};
                  newOrder.forEach(function(formId) {
                      sortedFormsData[formId] = quizFormsData[formId];
                  });

                  quizFormsData = sortedFormsData;
              }
          });
          // console.log(quizFormsData);

        }

        // Function to render the form with fields
        function renderFromElement(formId) {
            let formData = quizFormsData[formId]['data'];
            let formFieldsContainer = jQuery('#quiz-formFields-' + formId);

            formFieldsContainer.empty();
            if( typeof formData !== 'undefined' ){
              formData.forEach(function(field, index) {

                  if( field.type == 'select'){
                    var inputElement = `<select disabled  style=" margin-right: 10px; "><option>${field.options[0]}</option></select>`;
                  } else if( field.type == 'textarea') {
                    var inputElement = `<textarea style=" margin-right: 10px; "type="${field.type}" disabled></textarea>`;
                  } else {
                    var inputElement = `<input style=" margin-right: 10px; "type="${field.type}" disabled>`;
                  }
                  let rowHtml = `
                      <div class="quiz-input-row" data-index="${index}">
                          <label>${field.label} (${field.type})</label>
                          ` + inputElement + `
                          <button type="button" class="quiz-editField" data-formid="${formId}" data-index="${index}"><i class="fa-regular fa-pen-to-square"></i></button>
                          <button type="button" class="quiz-deleteField" data-formid="${formId}" data-index="${index}"><i class="fa-regular fa-trash-can"></i></button>
                      </div>
                  `;
                  formFieldsContainer.append(rowHtml);
              });

              // Make input elements inside form sortable
              formFieldsContainer.sortable({
                  stop: function(event, ui) {
                      // Re-order the quizFormsData based on the new order
                      const newOrder = jQuery(this).children('.quiz-input-row').map(function() {
                          return jQuery(this).data('index');
                      }).get();
                      quizFormsData[formId]['data'] = newOrder.map(index => quizFormsData[formId]['data'][index]);
                      //console.log(quizFormsData);
                      // Re-render the form
                      renderFromElement(formId);

                  }
              });
            }
        }

        // Function to open the popup to add/edit fields
        function openQuizPopup(formId, fieldIndex = null) {

            let formData = quizFormsData[formId]['data'];
            let isEdit = fieldIndex !== null;
            if (isEdit) {
                let field = formData[fieldIndex];
                jQuery('#quiz-fieldLabel').val(field.label);
                jQuery('#quiz-fieldType').val(field.type);
                if (field.type === 'select') {
                    jQuery('#quiz-fieldOptionsLabel, #quiz-fieldOptions').show();
                    jQuery('#quiz-fieldOptions').val(field.options.join(','));
                } else {
                    jQuery('#quiz-fieldOptionsLabel, #quiz-fieldOptions').hide();
                }
            } else {
                jQuery('#quiz-fieldLabel').val('');
                jQuery('#quiz-fieldType').val('text');
                jQuery('#quiz-fieldOptionsLabel, #quiz-fieldOptions').hide();
            }

            jQuery('#quiz-saveFieldButton').attr('data-formid', formId).attr('data-fieldindex', fieldIndex);

            jQuery('#quiz-fieldPopup').show();
            jQuery('#quiz-popup-overlay').show();
        }

        // Close popup
        jQuery('#quiz-cancelFieldButton').click(function() {
            jQuery('#quiz-fieldPopup').hide();
            jQuery('#quiz-popup-overlay').hide();
        });

        // Change field type behavior
        jQuery('#quiz-fieldType').change(function() {
            if (jQuery(this).val() === 'select') {
                jQuery('#quiz-fieldOptionsLabel, #quiz-fieldOptions').show();
            } else {
                jQuery('#quiz-fieldOptionsLabel, #quiz-fieldOptions').hide();
            }
        });

        // Add or edit field in the form
        jQuery('#quiz-saveFieldButton').click(function() {
            let formId = jQuery(this).attr('data-formid');
            let fieldIndex = jQuery(this).attr('data-fieldindex');
            let fieldLabel = jQuery('#quiz-fieldLabel').val();
            let fieldType = jQuery('#quiz-fieldType').val();
            let fieldOptions = jQuery('#quiz-fieldOptions').val().split(',');
            console.log(jQuery(this).data('fieldindex'))
            if (!fieldLabel) {
                jQuery('#quiz-popupError').text('Label is required.');
                return;
            }

            let newField = {
                label: fieldLabel,
                type: fieldType,
                options: fieldType === 'select' ? fieldOptions : []
            };
            console.log(fieldIndex);
            if ( typeof fieldIndex !== "undefined" ) {
                quizFormsData[formId]['data'][fieldIndex] = newField;
            } else {
                quizFormsData[formId]['data'].push(newField);
            }

            console.log(quizFormsData);
            renderQuizForm();
            jQuery('#quiz-popupError').text('');
            jQuery('#quiz-fieldPopup').hide();
            jQuery('#quiz-popup-overlay').hide();
        });

        // Add a new quiz form
        jQuery('#quiz-addFormButton').click(function() {

            let formId = new Date().getTime();

            // Initialize form data storage
            quizFormsData[formId] = {
              'name': `New Form ${Object.keys(quizFormsData).length + 1}`,
              'data': []
            };

            renderQuizForm();

        });

        jQuery(document).on('click','#quiz-popup-overlay',function(){
          jQuery('#quiz-fieldPopup').hide();
          jQuery('#quiz-popup-overlay').hide();
        });

        jQuery(document).on('click', '.quiz-delFrom' ,function() {
            let formId = jQuery(this).data('formid');
            delete quizFormsData[formId];
            // console.log(quizFormsData);
            renderQuizForm();
        });

        // Event delegation for dynamically added addField buttons
        jQuery(document).on('click', '.quiz-addField', function() {
            let formId = jQuery(this).data('formid');
            openQuizPopup(formId);
        });

        // Event delegation for dynamically added editField buttons
        jQuery(document).on('click', '.quiz-editField', function() {
            let formId = jQuery(this).data('formid');
            let fieldIndex = jQuery(this).data('index');
            openQuizPopup(formId, fieldIndex);
        });

        // Event delegation for dynamically added deleteField buttons
        jQuery(document).on('click', '.quiz-deleteField', function() {
            let formId = jQuery(this).data('formid');
            let fieldIndex = jQuery(this).data('index');
            quizFormsData[formId]['data'].splice(fieldIndex, 1); // Remove field
            renderFromElement(formId);
        });

        // Collapse/Expand functionality
        jQuery(document).on('click', '.quiz-toggleFields', function() {
            let formContainer = jQuery(this).closest('.quiz-form-container');
            let formFields = formContainer.find('.quiz-form-fields');
            let formButton = formContainer.find('.quiz-addField, .quiz-delFrom');

            if (formFields.is(':visible')) {
                formFields.slideUp();
                formButton.fadeOut();
                jQuery(this).html('<i class="fa-solid fa-plus"></i>');
            } else {
                formFields.slideDown();
                formButton.fadeIn();
                jQuery(this).html('<i class="fa-solid fa-minus"></i>');
            }
        });

        jQuery(document).on('click', '.quiz-save-form',function(){
          savingForm("<?php echo $post->ID ;?>",'course_quiz', quizFormsData );
        });

        jQuery(document).on('focusout','.quiz-form-name',function(){
          var formID = jQuery(this).closest('.quiz-form-container').attr('id');
          quizFormsData[formID]['name'] = jQuery(this).val();
        });

      });


   </script>

   <!-- Cert restriction input function -->
   <script>
     let certs = <?php echo empty( $cert_requirment )? "[]" : json_encode( $cert_requirment, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) ; ?>;
     renderCerts();
     jQuery('#add-cert').on('click', function () {
         const certInput = jQuery('#cert-input');
         const certValue = certInput.val().trim();

         if (certValue && !certs.includes(certValue)) {
             certs.push(certValue);
             renderCerts();
             certInput.val(''); // Clear input field
         }
     });

     function renderCerts() {
         const certList = jQuery('#cert-list');
         certList.html('');

         certs.forEach(function (cert, index) {
             const certItem = `<div class="cert">${cert} <button type="button" class="remove-cert" data-index="${index}">x</button></div>`;
             certList.append(certItem);
         });
         //console.log(JSON.stringify(certs));
         // Update the hidden field with the certs as a JSON string
         jQuery('#course_cert_requirment').val(JSON.stringify(certs));
     }

     jQuery(document).on('click', '.remove-cert', function () {
         const index = jQuery(this).data('index');
         certs.splice(index, 1);
         renderCerts();
     });
    </script>

   <!-- Rundown builder -->
   <script>
     jQuery(document).ready(function() {

       <?php
        if( $is_disable_rundown ){
          ?>
          jQuery('#toggle-button').trigger('click');
          jQuery('#rundown-wrapper').append('<div class="cover-layer"></div>');
          jQuery('#rundown-wrapper input, #rundown-wrapper select, #rundown-wrapper textarea, #rundown-wrapper button').prop('disabled', true);
          <?php
        }
       ?>

       let isDisabled = false; // Keep track of toggle state

       // Toggle switch click event
       jQuery('#toggle-button').change(function() {
           isDisabled = jQuery(this).is(':checked'); // Toggle the state

           if (isDisabled) {
               // Append the cover layer to visually disable the form
               jQuery('#rundown-wrapper').append('<div class="cover-layer"></div>');

               // Disable the form inputs
               jQuery('#rundown-wrapper input, #rundown-wrapper select, #rundown-wrapper textarea, #rundown-wrapper button').prop('disabled', true);
           } else {
               // Remove the cover layer
               jQuery('.cover-layer').remove();

               // Enable the form inputs
               jQuery('#rundown-wrapper input, #rundown-wrapper select, #rundown-wrapper textarea, #rundown-wrapper button').prop('disabled', false);
           }
       });

       let sections = <?php echo empty( $rundown )? "[]" : json_encode( $rundown, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) ; ?>;

       let editIndex = null;

       displayRundown(sections);

       jQuery(".tab-button")[0].click();

       jQuery('#section_start_time').clockpicker({
        autoclose: true
       });

       jQuery("#section_type").on('change',function(){
         var section_type = jQuery(this).val();
         if( section_type == 'cpd_section' ){
           jQuery("#conditional-display-speaker").show();

         } else{
           jQuery("#conditional-display-speaker").find("#section_speaker").val("");
           jQuery("#conditional-display-speaker").hide();
         }
         if( section_type == 'end' || section_type == 'end_survey'  ){
           jQuery("#conditional-display-duration").find("#section_duration").val("");
           jQuery("#conditional-display-duration").hide();
         }else{
           jQuery("#conditional-display-duration").show();
         }
       })

       jQuery('#submit-section').click(function() {
           const date = jQuery('#section_date').val();
           const startTime = jQuery('#section_start_time').val();
           const duration = jQuery('#section_duration').val();
           const type = jQuery('#section_type').val();
           const name = jQuery('#section_name').val();
           const speaker = jQuery('#section_speaker').val();
           const description = jQuery('#section_description_textarea').val();

           const error = jQuery('#error');

           if ( !date || !startTime || !type || !name ) {
               error.text("Fields with (*) are required.");
               return;
           }

           if( type == "cpd_section" && !speaker ){
             error.text("Fields with (*) are required.");
             return;
           }

           if( type != 'end' && type != 'end_survey' ){
             if( !duration || duration == 0 ){
               error.text("Duration must not be zero for this section type.");
               return;
             }
           }

           const endTime = calculateEndTime(startTime, duration);

           const newSection = {
               id: editIndex !== null ? sections[editIndex].id : Date.now(),
               date,
               startTime,
               endTime,
               type,
               name,
               speaker,
               description
           };

           if (isOverlap(sections,newSection)) {
               error.text("Time interval overlaps with an existing section.");
               return;
           }

           if (editIndex !== null) {
               sections[editIndex] = newSection;
               editIndex = null;
               jQuery('#submit-section').text('Submit Section');
               jQuery('#cancel-edit').hide();
           } else {
               sections.push(newSection);
           }

           displayRundown(sections);

           jQuery('#form-container input, #form-container select, #form-container textarea').val('');
           jQuery("#conditional-display-duration").show();

           error.text("");

       });

       jQuery('#save-rundown').click(function() {
         savingRundown( "<?php echo $post->ID ;?>", "course_rundown", sections, jQuery('#course_relatedness').val() );
       });

       jQuery('#cancel-edit').click(function() {
           editIndex = null;
           jQuery('#submit-section').text('Submit Section');
           jQuery('#cancel-edit').hide();
           jQuery('#form-container input, #form-container select, #form-container textarea').val('');
           jQuery("#conditional-display-speaker").hide();
           jQuery("#conditional-display-duration").show();
           jQuery('#error').text('');
           displayRundown(sections);
       });

       jQuery(document).on('click', '.delete-section', function() {
           const id = jQuery(this).data('id');
           sections = sections.filter(section => section.id != id);
           displayRundown(sections);
       });

       jQuery(document).on('click', '.edit-section', function() {
           const id = jQuery(this).data('id');
           const section = sections.find( (sec) => sec.id == id);
           editIndex = sections.indexOf(section);
           console.log(section);
           jQuery('#section_date').val(section.date);
           jQuery('#section_start_time').val(section.startTime);
           //jQuery('#section_duration').val((new Date(`1970-01-01T${section.endTime}Z`) - new Date(`1970-01-01T${section.startTime}Z`)) / 60000);
           const duration = (new Date(`1970-01-01T${section.endTime}Z`) - new Date(`1970-01-01T${section.startTime}Z`)) / 60000;
           jQuery("#section_duration").val(duration === 0 ? "" : duration);
           jQuery('#section_type').val(section.type);
           if(section.type == 'end_survey'){
             jQuery('#section_type option[value="end_survey"]').removeAttr('disabled','');
           }
           if(section.type == 'quiz'){
             jQuery('#section_type option[value="quiz"]').removeAttr('disabled','');
           }
           jQuery('#section_name').val(section.name);
           jQuery('#section_description_textarea').val(section.description);
           if( section.type == 'cpd_section' ){
             jQuery("#conditional-display-speaker").show();
             jQuery('#section_speaker').val(section.speaker);
           }
           if( section.type == 'end' || section.type == 'end_survey' ){
             jQuery("#conditional-display-duration").hide();
             jQuery('#duration').val("");
           }
           jQuery('#submit-section').text('Save Section');
           jQuery('#cancel-edit').show();
           jQuery("html, body").animate({
             scrollTop: jQuery("#rundown").offset().top - 25}, 'slow');
       });

       function displayRundown(sections) {
           jQuery('#rundown-container').empty();
           const groupedByDate = sections.reduce((acc, section) => {
               (acc[section.date] = acc[section.date] || []).push(section);
               return acc;
           }, {});

           Object.keys(groupedByDate).sort().forEach(date => {
               const table = jQuery('<table class="rundown"></table>');
               table.append(`
                   <thead>
                       <tr>

                           <th class="time">Time</th>
                           <th class="content">Contents</th>
                           <th class="icon-buttons"></th>
                       </tr>
                   </thead>
               `);
               const tbody = jQuery('<tbody></tbody>');
               groupedByDate[date].sort((a, b) => a.startTime.localeCompare(b.startTime)).forEach(section => {
                 if(section.speaker){
                   var speakText = `<br><br>Speaker: ${section.speaker}` ;
                 } else{
                    var speakText = '';
                 }
                 if(section.description){
                   var descriptionText = `<br><br>${section.description}` ;
                 } else{
                    var descriptionText = '';
                 }
                 tbody.append(`
                     <tr data-id="${section.id}">
                         <td class="time">${section.startTime} - ${section.endTime} (${section.type})</td>
                         <td class="content">
                           <b> ${section.name} </b>
                           ` + descriptionText + speakText + `
                         </td>
                         <td class="icon-buttons">
                             <i class="fas fa-edit edit-section" data-id="${section.id}"></i>
                             <i class="fas fa-trash-alt delete-section" data-id="${section.id}"></i>
                         </td>
                     </tr>
                 `);
               });
               table.append(tbody);
               jQuery('#rundown-container').append(`<h3>${date}</h3>`);
               jQuery('#rundown-container').append(table);
           });

           var isQuizExist = false;
           var isSurveyExist = false;

           sections.forEach((item, i) => {
             for (const [key, value] of Object.entries(item)) {
               if( value == 'quiz' ){
                 isQuizExist = true;
               }
               if( value == 'end_survey' ){
                 isSurveyExist = true;
               }
             }
           });

           if( isQuizExist ){
             jQuery('#section_type option[value="quiz"]').attr('disabled','');
           } else{
             jQuery('#section_type option[value="quiz"]').removeAttr('disabled','');
           }
           if( isSurveyExist ){
             jQuery('#section_type option[value="end_survey"]').attr('disabled','');
           } else{
             jQuery('#section_type option[value="end_survey"]').removeAttr('disabled','');
           }

       }

       function calculateEndTime(startTime, duration) {
           if (duration === "0" || duration === "") {
             return startTime; // Zero duration means end time is the same as start time
           }
           const [hours, minutes] = startTime.split(':').map(Number);
           const endMinutes = minutes + parseInt(duration, 10);
           const endHours = hours + Math.floor(endMinutes / 60);
           const endMins = endMinutes % 60;
           return `${String(endHours).padStart(2, '0')}:${String(endMins).padStart(2, '0')}`;
       }

       function isOverlap(sections,newSection) {
         if (newSection.startTime === newSection.endTime) {
           return sections.some(section =>
               section.date === newSection.date &&
               section.id !== newSection.id &&
               ( newSection.startTime > section.startTime && newSection.startTime < section.endTime )
           );
         } else {
           return sections.some(section =>
               section.date === newSection.date &&
               section.id !== newSection.id &&
               (
                   (newSection.startTime >= section.startTime && newSection.startTime < section.endTime) ||
                   (newSection.endTime > section.startTime && newSection.endTime <= section.endTime)
               )
           );
         }
       }
     });
   </script>

   <!-- Survey form builder -->
   <script>
    jQuery(document).ready(function() {




      <?php

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

        // Convert the PHP array into a JSON string
        $survey_default = json_encode( $survey_default, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE  )
      ?>


      let surveyFormData = <?php echo empty( $survey )? $survey_default : json_encode( $survey, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) ; ?>;
      let currentFieldType = '';
      let currentEditIndex = null;

      renderForm( jQuery('#survey .custom-form'), surveyFormData );

      // Initialize drag-and-drop functionality
      jQuery('#survey .custom-form').sortable({
           stop: function(event, ui) {
               // Reorder surveyFormData based on the new order of .form-row elements in the DOM
               const newOrder = jQuery(this).children('.form-row').map(function() {
                   return jQuery(this).find('.edit-input').data('index');
               }).get();

               // Reorder surveyFormData based on newOrder array
               surveyFormData = newOrder.map(index => surveyFormData[index]);

               // Re-render the form to update the data-index attributes
               renderForm(jQuery(this), surveyFormData );
           }
      });

      // Prevent form submission on Enter keypress
      jQuery(document).on('keypress', 'input', function(event) {
          if (event.which === 13) {
              event.preventDefault();
          }
      });

      // Function to store the input field in the data object
      function addOrUpdateField(label, value, options, data, elem) {
          const id = label.toLowerCase().replace(/\s+/g, '-'); // Create id based on label

          let fieldData = {
              label: label,
              id: id,
              value: value || '',
              type: currentFieldType,
              options: options || ''
          };

          if (currentEditIndex !== null) {
              data[currentEditIndex] = fieldData;
              currentEditIndex = null; // Reset after updating
          } else {
              data.push(fieldData);
          }
          renderForm(elem,data);
          hidePopup();
      }

      // Function to render the form from the data object
      function renderForm(elem,data) {
          jQuery(elem).empty();

          data.forEach((field, index) => {
              let inputField = '';

              if (field.type === 'text') {
                  inputField = `<label for="${field.id}">${field.label} (${field.type})</label><input type="text" id="${field.id}" name="${field.id}" value="${field.value}" disabled>`;
              } else if (field.type === 'number') {
                  inputField = `<label for="${field.id}">${field.label} (${field.type})</label><input type="number" id="${field.id}" name="${field.id}" value="${field.value}" disabled>`;
              } else if (field.type === 'select') {
                  const options = field.options.split(',').map(opt => `<option value="${opt.trim()}">${opt.trim()}</option>`).join('');
                  inputField = `<label for="${field.id}">${field.label} (${field.type})</label><select id="${field.id}" name="${field.id}" disabled>${options}</select>`;
              } else if (field.type === 'email') {
                  inputField = `<label for="${field.id}">${field.label} (${field.type})</label><input type="email" id="${field.id}" name="${field.id}" value="${field.value}" disabled>`;
              } else if (field.type === 'textarea') {
                  inputField = `<label for="${field.id}">${field.label} (${field.type})</label><textarea id="${field.id}" name="${field.id}" disabled>${field.value}</textarea>`;
              }

              const formRow = document.createElement('div');
              formRow.className = 'form-row';
              formRow.innerHTML = `
                  ${inputField}
                  <div class="form-actions">
                      <button type="button" class="edit-input button button-primary" data-index="${index}">Edit</button>
                      <button type="button" class="delete-input button button-secondary" data-index="${index}">Delete</button>
                  </div>
              `;
              jQuery(elem).append(formRow);
          });

      }

      // Show popup form
      function showPopup(elem) {
          jQuery('.popup-overlay').show();
          jQuery(elem).find('.popup-form').show();

      }

      // Hide popup form
      function hidePopup() {
          jQuery('.popup-overlay').hide();
          jQuery('.popup-form').hide();
          jQuery('.input-label').val('');
          jQuery('.input-value').val('');
          jQuery('.input-options').val('');
          jQuery('.select-options-container').hide();
          jQuery('.error-message').text('');
          currentEditIndex = null;  // Reset current edit index
      }

      // Check if label is unique
      function isLabelUnique(label,data) {
          let isUnique = true;
          data.forEach((field, index) => {
              if (field.label === label && index !== currentEditIndex) {
                  isUnique = false;
              }
          });
          return isUnique;
      }

      jQuery('.save-form').click(function(){

        var target = jQuery(this).parent().attr('id');

        switch(target){
          case 'survey':
            savingForm( "<?php echo $post->ID ;?>", "course_survey", surveyFormData );
            break;
        }

      });

      // Event handlers for adding fields
      jQuery('.add-text-field').click(function() {
          jQuery('.select-options-container').hide();
          currentFieldType = 'text';
          showPopup(jQuery(this).parent().parent());
      });

      jQuery('.add-number-field').click(function() {
          jQuery('.select-options-container').hide();
          currentFieldType = 'number';
          showPopup(jQuery(this).parent().parent());
      });

      jQuery('.add-select-field').click(function() {
          currentFieldType = 'select';
          jQuery('.select-options-container').show();
          showPopup(jQuery(this).parent().parent());
      });

      jQuery('.add-email-field').click(function() {
          jQuery('.select-options-container').hide();
          currentFieldType = 'email';
          showPopup(jQuery(this).parent().parent());
      });

      jQuery('.add-textarea-field').click(function() {
          jQuery('.select-options-container').hide();
          currentFieldType = 'textarea';
          showPopup(jQuery(this).parent().parent());
      });

      // Save or update input field
      jQuery('.save-input').click(function() {
          const label = jQuery(this).parent().find('.input-label').val().trim();
          const value = jQuery(this).parent().find('.input-value').val();
          const options = jQuery(this).parent().find('.input-options').val();

          if (!label) {
              jQuery('.error-message').text('Label is required.');
              return;
          }

          var target = jQuery(this).parent().parent().attr('id');

          switch(target){
            case 'survey':
              if (!isLabelUnique(label,surveyFormData) ) {
                  jQuery('.error-message').text('Label must be unique.');
                  return;
              }
              addOrUpdateField(label, value, options, surveyFormData, jQuery(this).parent().siblings(".custom-form") );
              break;
          }

      });

      // Cancel input field creation or edit
      jQuery('.cancel-input').click(function() {
          hidePopup();
      });

      // Close popup when clicking outside of it
      jQuery('.popup-overlay').click(function() {
          hidePopup();
      });

      // Edit field
      jQuery(document).on('click', '.edit-input', function() {

        var target = jQuery(this).parent().parent().parent().parent().attr('id');

        currentEditIndex = jQuery(this).data('index');

        switch(target){
          case 'survey':
            currentEditIndex = jQuery(this).data('index');
            var field = surveyFormData[currentEditIndex];
            break;
        }

        jQuery('.input-label').val(field.label);
        jQuery('.input-value').val(field.value);


        if (field.type === 'select') {
            currentFieldType = 'select';
            console.log("field.type: " + field.type);
            jQuery('.input-options').val(field.options);
            jQuery('.select-options-container').show();
        } else if (field.type === 'number') {
            currentFieldType = 'number';
            jQuery('.select-options-container').hide();
        } else if (field.type === 'email') {
            currentFieldType = 'email';
            jQuery('.select-options-container').hide();
        } else if (field.type === 'textarea') {
            currentFieldType = 'textarea';
            jQuery('.select-options-container').hide();
        } else {
            currentFieldType = 'text';
            jQuery('.select-options-container').hide();
        }

        showPopup(jQuery(this).parent().parent().parent().parent());

      });

      // Delete field
      jQuery(document).on('click', '.delete-input', function() {
          const index = jQuery(this).data('index');
          var target = jQuery(this).parent().parent().parent().parent().attr('id');
          switch(target){
            case 'survey':
            surveyFormData.splice(index, 1); // Remove the field from the data object
            renderForm( jQuery(this).parent().parent().parent() , surveyFormData); // Re-render the form
            break;
          }
      });
    });
   </script>

   <!-- Other functions -->
	 <script>

    document.getElementsByClassName("tab-button")[0].click();

    function openTab(evt, tabName) {
        event.preventDefault();
        var i, tab, tabButton;
        tab = document.getElementsByClassName("tab");
        for (i = 0; i < tab.length; i++) {
            tab[i].style.display = "none";
        }
        tabButton = document.getElementsByClassName("tab-button");
        for (i = 0; i < tabButton.length; i++) {
            tabButton[i].className = tabButton[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    jQuery(document).ready(function() {

      jQuery(document).on("click","#message",function(){
          showMessage('warning', "messageContent");
      });

      jQuery('#generate-qr-code').click(function() {
        generateQRCode( "<?php echo $post->ID ;?>");
      });

      jQuery('#get-poster').click(function() {
        getPoster( "<?php echo $post->ID ;?>");
      });

      jQuery(".trigger").click(function(){
        var id = jQuery(this).attr('id');
        if( this.checked ){
          jQuery("#" + id + "_details").css( "display", "block");
        } else {
          jQuery("#" + id + "_details").css( "display", "none");
        }
      });

    });

	 </script>

	<?php


}


function course_status(){

  global $post;
  $course = new Course($post->ID);
  if( $course->type == 'training' || $course->type == 'co-organized-event' ){
    $course->show_dashboard();
    echo "<br>";
    $course->display_pupil_details_button();
    ?>
      <button id="download-quiz-button" type="button" class="button button-primary" data-course-id="<?php echo $post->ID ;?>" >Download Quiz csv</button>
      <button id="download-survey-button" type="button" class="button button-primary" data-course-id="<?php echo $post->ID ;?>" >Download Survey csv</button>
      <button id="download-pupil-button" type="button" class="button button-primary" data-course-id="<?php echo $post->ID ;?>" >Download Pupil csv</button>
    <?php
  } else {
    echo 'Course status not available for this course type.';
  }

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
  if ( isset( $_POST['hkota_course_nonce'] ) && !wp_verify_nonce( $_POST['hkota_course_nonce'], basename(__FILE__) ) ) {
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
























?>
