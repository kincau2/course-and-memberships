<?php

add_filter('woocommerce_account_menu_items', 'add_my_courses_tab', 10, 1);
function add_my_courses_tab($items) {
    // Insert "My Courses" right after "Orders"
    $new_items = array();
    foreach ($items as $key => $value) {
        $new_items[$key] = $value;
        if ($key === 'orders') {
            $new_items['my-courses'] = __('My Courses', 'textdomain');
        }
    }
    return $new_items;
}

// Register the endpoint for the "My Courses" tab
add_action('init', 'my_courses_endpoint');
function my_courses_endpoint() {
    add_rewrite_endpoint('my-courses', EP_ROOT | EP_PAGES);
}

// Handle the content for the "My Courses" tab
add_action('woocommerce_account_my-courses_endpoint', 'my_courses_content');
function my_courses_content() {

  // Get the current user ID
  $user_id = get_current_user_id();

  // Fetch all enrollments for the user (you'll need to use the Enrollment class here)
  global $wpdb;
  $table = $wpdb->prefix . 'hkota_course_enrollment';

  // Fetch all enrollments for the current user, regardless of status
  $enrollments = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $table WHERE user_id = %d",
      $user_id
  ));

  // If no enrollments are found, display a message
  if (empty($enrollments)) {
      echo '<p>' . __('You have no course enrollments.', 'textdomain') . '</p>';
      return;
  }

  // Create an array to hold the courses and their dates
  $courses_with_dates = [];

  // Loop through enrollments and fetch course details
  foreach ($enrollments as $enrollment) {
      // Create a new Course object using the course_id from the enrollment
      $course = new Course($enrollment->course_id);

      // Get the earliest date in the rundown
      $rundown = $course->rundown;
      if (!empty($rundown)) {
          // Sort the rundown by date (in case it's not already sorted)
          usort($rundown, function($a, $b) {
              return strtotime($a['date']) - strtotime($b['date']);
          });

          // Get the earliest date in the rundown
          $earliest_date = $rundown[0]['date'];

          // Add the course and its earliest date to the array
          $courses_with_dates[] = [
              'course' => $course,
              'date' => $earliest_date,
              'status' => $enrollment->status,
          ];
      }
  }

  // Sort the courses by date (latest first)
  usort($courses_with_dates, function($a, $b) {
      return strtotime($b['date']) - strtotime($a['date']);
  });

  // Display course information
  echo '<h2>' . __('My Courses', 'textdomain') . '</h2>';
  ?>
  <div class="course-catalog my-course"> <?php

  // Loop through enrollments and fetch course details
  foreach ($courses_with_dates as $course_data):
    // Create a new Course object using the course_id from the enrollment
    $course = $course_data['course'];
    $status = $course_data['status'];
    ?>

    <div class="course-wrapper">
      <div class="course-heading">
        <h6 class="theme-green"><?php echo $course->title ;?></h6>
      </div>
      <div class="course-info">
        <div class="course-details">
          <?php if($course->type == 'training' ): ?>
            <div class="details-row">
              <span class="type">Course code:</span>
              <span class="value"><?php echo $course->code; ?></span>
            </div>
          <?php endif; ?>
          <div class="details-row">
            <span class="type">Date:</span>
            <span class="value"><?php echo $course->createDateString(); ?></span>
          </div>
          <div class="details-row">
            <span class="type">Time:</span>
            <span class="value"><?php echo $course->createTimeString(); ?></span>
          </div>
          <div class="details-row">
            <span class="type">Venue:</span>
            <span class="value"><?php echo $course->venue ;?></span>
          </div>
          <div class="details-row">
            <span class="type">Details:</span>
            <span class="value">
              <?php if($course->type == 'training' ): ?>
                <?php if( !empty($course->poster) ): ?>
                  <a target="_blank" href="<?php echo COURSE_POSTER_URL . $course->poster ;?>">Course poster</a>
                <?php endif; ?>
              <?php else: ?>
                <?php if( !empty($course->external_poster) ): ?>
                  <a target="_blank" href="<?php echo COURSE_FILE_URL . $course->external_poster ;?>">Event poster</a>
                <?php endif; ?>
              <?php endif; ?>
            </span>
          </div>
          <?php if($course->type == 'training' ): ?>
            <div class="details-row">
              <span class="type">Enrollment Status:</span>
              <span class="value
                <?php
                  // Status can be either enrolled, pending, waiting_list or rejected.
                  switch( $status ){
                    case 'enrolled':
                      echo "theme-green";
                      break;
                    case 'pending':
                      echo "theme-amber";
                      break;
                    case 'waiting_list':
                      echo "theme-amber";
                      break;
                    case 'awaiting_approval':
                      echo "theme-amber";
                      break;
                    case 'rejected':
                      echo "theme-red";
                      break;
                  }
                ?>"><?php echo ucfirst(str_replace("_"," ",$status)) ;?></span>
            </div>
          <?php endif; ?>
            <div class="details-row footer-button">
              <a class="button" href="<?php echo $course->get_ical_url() ; ?>" download>Add to you calendar</a>
              <?php
                if($course->type == 'training' ){
                  $course->display_certificate_button($user_id);
                }
              ?>
            </div>
          <?php if( $course->type == 'co-organized-event' && $course->is_issue_cpd == 'true' ): ?>
            <br>
            <div class="details-row">
              <span class="value">*Certificate of this course is not issued by HKOTA</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
  <?php
}

add_action( 'init', 'add_cpd_endpoint' );

function add_cpd_endpoint() {
    add_rewrite_endpoint( 'cpd-records', EP_ROOT | EP_PAGES );
}

add_action('woocommerce_account_cpd-records_endpoint', 'cpd_records_content');
function cpd_records_content() {

  $from_date = (int)date('Y');
  $from_date = strtotime( $from_date.'-01-01' );
  $to_date = strtotime( date( 'Y-m-d' ) );

  if( isset($_POST['cpd-from-date']) && !empty($_POST['cpd-from-date']) ){
    $from_date = strtotime($_POST['cpd-from-date']);
  }
  if( isset($_POST['cpd-to-date']) && !empty($_POST['cpd-to-date']) ){
    $to_date = strtotime($_POST['cpd-to-date']);
  }

  $query = new WP_Query( array(
    'post_type' => 'cpd_record',
    'author'    => get_current_user_id(),
    'order'     => 'DESC',
    'orderby'   => 'meta_value_num',
    'meta_query' => array(
      'relation' => 'AND',
      array(
        'key' => 'cpd-date',
        'value' => $from_date,
        'compare' => '>=',
      ),
      array(
        'key' => 'cpd-date',
        'value' => $to_date,
        'compare' => '<=',
      )
    )
  ));

  $cpd_record_ids= array();

  ?>

  <div class="unit-member-info">

    <div class='unit-member-info-heading' style=" justify-content: space-between; ">

      <div style=" display: flex; align-items: center; gap: 10px; ">
        <p>CPD Records</p>
      </div>
    </div>

    <div class="filter-wrapper" >
      <div class="select-wrapper">
        <form method="post" class="membership-form">
          <div class="date-wrapper">
              <label style=" position: absolute; top: -16px; font-size: 12px; ">Filter by date:</label>
              <input class="date-filter" type="date" name="cpd-from-date"
                value='<?php
                  if( isset( $_POST['cpd-from-date'] )  && !empty( $_POST['cpd-from-date'] ) ){
                    echo sanitize_text_field( $_POST['cpd-from-date'] );
                  } else {
                    echo ((int)date('Y')).'-01-01' ;
                  }
                ?>'>
              <p style=" font-size: 18px; margin:unset!important"> - </p>
              <input class="date-filter" type="date" name="cpd-to-date"
              value='<?php
                if( isset( $_POST['cpd-to-date'] )  && !empty( $_POST['cpd-to-date'] ) ){
                  echo sanitize_text_field( $_POST['cpd-to-date'] );
                } else {
                  echo date('Y-m-d');
                }
              ?>'>
            </div>
          <input type="hidden" name="filter" value='yes'>
          <button class="filter-button" type="submit">Submit</button>
        </form>
      </div>
      <button id="export-cpd-records" class="filter-button">Export CPD Records</button>
    </div>

    <table class="cpd-table">
      <tr>
        <th>Date</th>
        <th>Course</th>
        <th>Course code</th>
        <th>Organization</th>
        <th>CPD Points</th>
        <th>Certificate</th>
      </tr>
  <?php
    $results = get_user_cpd_records('issued');

    if($results){
      foreach ($results as $result):
        // Get the course to determine the last date
        $course = new Course($result->course_id);
        $course_last_date = '';
        
        // Get the last date from the course rundown
        if (!empty($course->rundown) && is_array($course->rundown)) {
            // Sort rundown by date to get the last one
            $rundown_sorted = $course->rundown;
            usort($rundown_sorted, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            $course_last_date = date('Y-m-d', strtotime($rundown_sorted[0]['date']));
        } else {
            // Fallback to issue date if no rundown is available
            $course_last_date = $result->date_issued;
        }
        ?>
          <tr>
            <td class="date-issued"><?php echo $course_last_date;?></td>
            <td class="title"><?php echo $result->title;?></td>
            <td class="code"><?php echo $result->code;?></td>
            <td class="organization"><?php echo $result->organization;?></td>
            <td class="cpd-point"><?php echo $result->cpd_point;?></td>
            <?php if( $result->file ): ?>
              <td class="certificate"><?php echo '<a target="_blank" href="' . COURSE_CERTIFICATE_URL . $result->file . '">View</td>' ?>
            <?php endif; ?>
          </tr>
        <?php
      endforeach;
    }




  ?></table></div><?php

}

add_filter('woocommerce_account_menu_items', 'add_cpd_records_tab', 10, 1);
function add_cpd_records_tab($items) {
    // Insert "My Courses" right after "Orders"
    $new_items = array();
    foreach ($items as $key => $value) {
        $new_items[$key] = $value;
        if ($key === 'my-courses') {
            $new_items['cpd-records'] = 'CPD Records';
        }
    }
    return $new_items;
}

add_filter('woocommerce_save_account_details_required_fields', 'hide_name_field');
function hide_name_field($required_fields)
{
  unset($required_fields["account_first_name"]);
  unset($required_fields["account_last_name"]);
  unset($required_fields["account_display_name"]);
  unset($required_fields["account_email"]);
  return $required_fields;
}

add_action( 'woocommerce_save_account_details_errors','custom_field_validate_edit_account', 10, 1 );

function custom_field_validate_edit_account( $errors ){

  $current_user_id = get_current_user_id();
  $user = get_user_by( 'ID' , $current_user_id );

  if( isset( $_POST['account_first_name'] ) && !empty( $_POST['account_first_name'] ) ){
    if( $_POST['account_first_name'] != $user->first_name  ){
      $errors->add( 'data_change_error', __( 'Error: You can not change your name in this form.', 'woocommerce' ) );
    }
  }
  if( isset( $_POST['account_last_name'] ) && !empty( $_POST['account_last_name'] ) ){
    if( $_POST['account_last_name'] != $user->last_name  ){
      $errors->add( 'data_change_error', __( 'Error: You can not change your name in this form.', 'woocommerce' ) );
    }
  }
  if( isset( $_POST['account_email'] ) && !empty( $_POST['account_email'] ) ){
    if( $_POST['account_email'] != $user->user_email  ){
      $errors->add( 'data_change_error', __( 'Error: You can not change your email in this form.', 'woocommerce' ) );
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

    if( $user_id && $user_id != get_current_user_id() ){
      $errors->add( 'ot_reg_number_error', __( 'The OT Registration Number you entered is registered by another user.', 'woocommerce' ) );
    }

  }

}

add_action( 'woocommerce_save_account_details', 'custom_field_save_account_details' );

function custom_field_save_account_details( $user_id ) {

  if( isset( $_POST['ot_reg_number'] ) && !empty( $_POST['ot_reg_number'] ) ){
    update_user_meta( $user_id, 'ot_reg_number', sanitize_text_field( $_POST[ 'ot_reg_number' ] ) );
  } else{
    delete_user_meta( $user_id, 'ot_reg_number' );
  }

  if( isset( $_POST['ot_reg_date'] ) && !empty( $_POST['ot_reg_date'] ) ){
    update_user_meta( $user_id, 'ot_reg_date', sanitize_text_field( $_POST[ 'ot_reg_date' ] ) );
  } else{
    delete_user_meta( $user_id, 'ot_reg_date' );
  }

}
















?>
