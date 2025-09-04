<?php
/**
 * @link              https://www.fromdb.com/
 * @since             1.0.0
 * @package           hkota_course
 *
 * Plugin Name: HKOTA Courses and Memberships
 * Version: 1.0
 * Description: Customized plugin for hkota Courses and Memberships requirement.
 * Author:            FROMDB LIMITED
 * Version:           1.0.1
 * Author URI:        https://www.fromdb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hkota
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

//composer autoload
require __DIR__ . '/vendor/autoload.php';

include dirname(__FILE__) . '/include/init.php' ;


define('COURSE_FILE_DIR', wp_upload_dir()['basedir'].'/course-files/');
define('COURSE_POSTER_DIR', wp_upload_dir()['basedir'].'/course-poster/');
define('COURSE_QR_CODE_DIR', wp_upload_dir()['basedir'].'/course-qr-code/');
define('COURSE_PUPIL_FILE_DIR', wp_upload_dir()['basedir'].'/pupil-uploaded-files/');
define('COURSE_CERTIFICATE_DIR', wp_upload_dir()['basedir'].'/certificate/');
define('COURSE_FILE_URL', wp_upload_dir()['url'].'/course-files/');
define('COURSE_POSTER_URL', wp_upload_dir()['url'].'/course-poster/');
define('COURSE_QR_CODE_URL', wp_upload_dir()['url'].'/course-qr-code/');
define('COURSE_PUPIL_FILE_URL', wp_upload_dir()['url'].'/pupil-uploaded-files/');
define('COURSE_CERTIFICATE_URL', wp_upload_dir()['url'].'/certificate/');
define('HKOTA_PLUGIN_DIR',  dirname(__FILE__)  );

// daily check and delete upload file under that cart item in session.
register_activation_hook( __FILE__, 'schedule_woocommerce_sessions_check' );
function schedule_woocommerce_sessions_check() {
    if (!wp_next_scheduled('delete_expired_woocommerce_sessions')) {
        wp_schedule_event(time(), 'daily', 'delete_expired_woocommerce_sessions');
    }
}

add_action( 'delete_expired_woocommerce_sessions', 'delete_expired_woocommerce_sessions');
function delete_expired_woocommerce_sessions() {

  global $wpdb;

  // Define the table name
  $table_name = $wpdb->prefix . 'woocommerce_sessions';

  // Get the current time
  $current_time = time();

  // Find rows where session_expiry is in the past (already expired)
  $expired_sessions = $wpdb->get_results(
      $wpdb->prepare("SELECT * FROM $table_name WHERE session_expiry < %d", $current_time)
  );
  // If there are expired sessions, delete them
  if (!empty($expired_sessions)) {
      foreach ($expired_sessions as $session) {
          delete_uploaded_files_on_session($session);
          $wpdb->delete($table_name, ['session_key' => $session->session_key]);
      }
      // Optionally, you can log the number of sessions deleted
      error_log(count($expired_sessions) . ' expired WooCommerce sessions were deleted.');
  } else {
      error_log('No expired WooCommerce sessions found.');
  }
}

register_deactivation_hook(__FILE__, 'unschedule_woocommerce_sessions_check');
function unschedule_woocommerce_sessions_check() {
    $timestamp = wp_next_scheduled('delete_expired_woocommerce_sessions');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'delete_expired_woocommerce_sessions');
    }
}

// daily check and reject status, if the enrollment already passed 5 days.
register_activation_hook( __FILE__, 'schedule_pending_status_check' );
function schedule_pending_status_check() {
    if (!wp_next_scheduled('set_expired_pending_enrollment_status_to_reject')) {
        wp_schedule_event(time(), 'daily', 'set_expired_pending_enrollment_status_to_reject');
    }
}

add_action( 'set_expired_pending_enrollment_status_to_reject', 'set_expired_pending_enrollment_status_to_reject');
function set_expired_pending_enrollment_status_to_reject(){
  global $wpdb;

  // Define the table name
  $table_name = $wpdb->prefix . 'hkota_course_enrollment';

  // Get the current GMT time
  $current_time_gmt = current_time('mysql', true);

  // Calculate the date 5 days ago
  $date_5_days_ago = date('Y-m-d H:i:s', strtotime($current_time_gmt . ' -5 days'));

  // Update all pending records older than 5 days to rejected
  $result = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $table_name
       WHERE status = 'pending'
       AND date_created_gmt < %s",
      $date_5_days_ago
  ));

  // Return the number of rows updated
  foreach ( $result as $row ) {
    $enrollment = new Enrollment($row->ID);
    $enrollment->set('status','rejected');
    $course = new Course($row->course_id);
    $course->trigger_rejected_email($row->user_id);
  }
}

register_deactivation_hook(__FILE__, 'unschedule_pending_status_check');
function unschedule_pending_status_check() {
    $timestamp = wp_next_scheduled('set_expired_pending_enrollment_status_to_reject');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'set_expired_pending_enrollment_status_to_reject');
    }
}

// daily check and issue certificate for fully_attended enrollments but fail to auto cert issuance
register_activation_hook( __FILE__, 'schedule_fail_certificate_issuance_check' );
function schedule_fail_certificate_issuance_check() {
    if (!wp_next_scheduled('fallback_certificate_issuance')) {
        wp_schedule_event(time(), 'daily', 'fallback_certificate_issuance');
    }
}

add_action( 'single_fallback_certificate_issuance', 'fallback_certificate_issuance');
add_action( 'fallback_certificate_issuance', 'fallback_certificate_issuance');
function fallback_certificate_issuance() {
    global $wpdb;
    $enroll_table = $wpdb->prefix . 'hkota_course_enrollment';
    $posts_table  = $wpdb->posts;

    // Grab just one pending certificate for published courses
    $enrollment = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT e.*
              FROM {$enroll_table} e
         INNER JOIN {$posts_table} p ON p.ID = e.course_id
             WHERE e.certificate_status = %s
               AND e.attendance LIKE %s
               AND p.post_status IN (%s,%s)
             LIMIT 1
            ",
            'not_issue',
            '%fully_attended%',
            'publish',
            'private'
        )
    );

    if ( $enrollment ) {
        // process single enrollment
        $course = new Course( $enrollment->course_id );
        if ( $course->type === 'training' && get_user_by( 'ID', $enrollment->user_id ) ) {
            $result = $course->create_certificate( $enrollment->user_id );
            if ( $result ) {
                // optionally trigger email
                $course->trigger_issue_certificate_email( $enrollment->user_id );
            }
        }

        // if more remain, schedule next run in 30 seconds
        $remaining = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*) 
                  FROM {$enroll_table} e
             INNER JOIN {$posts_table} p ON p.ID = e.course_id
                 WHERE e.certificate_status = %s
                   AND e.attendance LIKE %s
                   AND p.post_status IN (%s,%s)
                ",
                'not_issue',
                '%fully_attended%',
                'publish',
                'private'
            )
        );
        if ( intval( $remaining ) > 0 ) {
            wp_schedule_single_event( time() + 30, 'single_fallback_certificate_issuance' );
        }
    }
}

register_deactivation_hook(__FILE__, 'unschedule_fail_certificate_issuance_check');
function unschedule_fail_certificate_issuance_check() {
    $timestamp = wp_next_scheduled('fallback_certificate_issuance');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'fallback_certificate_issuance');
    }
}

?>
