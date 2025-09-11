<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\TcpdfFpdi;

class Course {

  public $id;
  public $type;
  public $title;
  public $status; // publich or pending or draft
  public $is_display_frontend;
  public $code;
  public $speaker;
  public $venue;
  public $description;
  public $remarks;
  public $target_participants;
  public $is_private;
  public $open_application;
  public $is_early_bird;
  public $early_bird_enddate;
  public $fee_regular_earlybird;
  public $fee_non_member_earlybird;
  public $fee_member_earlybird;
  public $is_member_fee;
  public $fee_regular;
  public $fee_non_member;
  public $fee_member;
  public $capacity;
  public $capacity_remarks;
  public $is_waiting_list;
  public $deadline;
  public $confirmation_date;
  public $contact;
  public $external_link;
  public $external_poster;
  public $is_restricted;
  public $is_member_only;
  public $is_uploads_required;
  public $cert_requirment;
	public $min_years;
  public $is_co_organized;
  public $co_organizer_title;
  public $co_organizer_logo;
  public $cert_heading;
  public $cert_title;
  public $cert_serial_prefix;
  public $cert_signee_1;
  public $cert_signature_1;
  public $is_second_signee;
  public $cert_signee_2;
  public $cert_signature_2;
  public $start_date;
  public $rundown;
  public $is_disable_rundown;
  public $relatedness;
  public $is_issue_cpd;
  public $is_overide_cpd;
  public $cpd_issue_org;
  public $cpd_point;
  public $overide_cpd_point;
  public $is_appendix;
  public $appendix;
  public $poster;
  public $snapshot;
  public $qr_code;
  public $survey;
  public $quiz;

  public function __construct($id) {
    $this->id = $id;
    $this->type = get_post_meta( $this->id , 'course_type', true );
    $this->title = get_the_title($id);
    $this->status = get_post_status( $this->id );
    $this->code = get_post_meta( $this->id , 'course_code', true );
    $this->speaker = get_post_meta( $this->id, 'course_speaker', true );
    $this->venue = get_post_meta( $this->id, 'course_venue', true );
    $this->description = get_post_meta( $this->id, 'course_description', true );
    $this->remarks = get_post_meta( $this->id, 'course_remarks', true );
    $this->target_participants = get_post_meta( $this->id, 'course_target_participants', true );
    $this->is_private = get_post_meta( $this->id, 'course_is_private', true );
    $this->open_application = get_post_meta( $this->id, 'course_open_application', true ); // Time in epoch format
    $this->is_early_bird = get_post_meta( $this->id, 'course_is_early_bird', true );
    $this->early_bird_enddate = get_post_meta( $this->id, 'course_early_bird_enddate', true );
    $this->fee_regular_earlybird = get_post_meta( $this->id, 'course_fee_regular_earlybird', true );
    $this->fee_non_member_earlybird = get_post_meta( $this->id, 'course_fee_non_member_earlybird', true );
    $this->fee_member_earlybird = get_post_meta( $this->id, 'course_fee_member_earlybird', true );
    $this->is_member_fee = get_post_meta( $this->id, 'course_is_member_fee', true );
    $this->fee_regular = get_post_meta( $this->id, 'course_fee_regular', true );
    $this->fee_non_member = get_post_meta( $this->id, 'course_fee_non_member', true );
    $this->fee_member = get_post_meta( $this->id, 'course_fee_member', true );
    $this->capacity = get_post_meta( $this->id, 'course_capacity', true );
    $this->capacity_remarks = get_post_meta( $this->id, 'course_capacity_remarks', true );
    $this->is_waiting_list = get_post_meta( $this->id, 'course_is_waiting_list', true );
    $this->deadline = get_post_meta( $this->id, 'course_deadline', true );
    $this->confirmation_date = get_post_meta( $this->id, 'course_confirmation_date', true );
    $this->contact = get_post_meta( $this->id, 'course_contact', true );
    $this->external_link = get_post_meta( $this->id, 'course_external_link', true );
    $this->external_poster = get_post_meta( $this->id, 'course_external_poster', true );
    $this->is_restricted = get_post_meta( $this->id, 'course_is_restricted', true );
    $this->is_member_only =  get_post_meta( $this->id, 'course_is_member_only', true );
    $this->is_uploads_required = get_post_meta( $this->id, 'course_is_uploads_required', true );
    $this->cert_requirment = get_post_meta( $this->id, 'course_cert_requirment', true );
  	$this->min_years = get_post_meta( $this->id, 'course_min_years', true );
    $this->is_co_organized = get_post_meta( $this->id, 'course_is_co_organized', true );
    $this->co_organizer_title = get_post_meta( $this->id, 'course_co_organizer_title', true );
    $this->co_organizer_logo = get_post_meta( $this->id, 'course_co_organizer_logo', true );
    $this->cert_heading = get_post_meta( $this->id, 'course_cert_heading', true );
    $this->cert_title = get_post_meta( $this->id, 'course_cert_title', true );
    $this->cert_serial_prefix = get_post_meta( $this->id, 'course_cert_serial_prefix', true );
    $this->cert_signee_1 = get_post_meta( $this->id, 'course_cert_signee_1', true );
    $this->cert_signature_1 = get_post_meta( $this->id, 'course_cert_signature_1', true );
    $this->is_second_signee = get_post_meta( $this->id, 'course_is_second_signee', true );
    $this->cert_signee_2 = get_post_meta( $this->id, 'course_cert_signee_2', true );
    $this->cert_signature_2 = get_post_meta( $this->id, 'course_cert_signature_2', true );
    $this->start_date = get_post_meta( $this->id, 'course_start_date', true );
    $this->rundown = get_post_meta( $this->id, 'course_rundown', true );
    $this->relatedness = get_post_meta( $this->id, 'course_relatedness', true );
    $this->is_disable_rundown = get_post_meta( $this->id, 'course_is_disable_rundown', true );
    $this->is_issue_cpd = get_post_meta( $this->id, 'course_is_issue_cpd', true );
    $this->is_overide_cpd = get_post_meta( $this->id, 'course_is_overide_cpd', true );
    $this->cpd_issue_org = get_post_meta( $this->id, 'course_cpd_issue_org', true );
    $this->cpd_point  = get_post_meta( $this->id, 'course_cpd_point', true );
    $this->overide_cpd_point = get_post_meta( $this->id, 'course_overide_cpd_point', true );
    $this->is_appendix = get_post_meta( $this->id, 'course_is_appendix', true );
    $this->appendix = get_post_meta( $this->id, 'course_appendix', true );
    $this->poster = get_post_meta( $this->id, 'course_poster', true );
    $this->snapshot = get_post_meta( $this->id, 'course_snapshot', true );
    $this->qr_code = get_post_meta( $this->id, 'course_qr_code', true );
    $this->survey = get_post_meta( $this->id, 'course_survey', true );
    $this->quiz = get_post_meta( $this->id, 'course_quiz', true );
  }

  // Helper function to format date (ordinal day)
  public function formatDateOrdinal($date) {
      $dateObj = new DateTime($date);
      return $dateObj->format('jS');
  }

  // Helper function to format full month and year
  public function formatMonthYear($date) {
      $dateObj = new DateTime($date);
      return $dateObj->format('F, Y');
  }

  // Helper function to format full date with ordinal, month, and day of week
  public function formatFullDateWithDay($date) {
      $dateObj = new DateTime($date);
      return $dateObj->format('jS F Y, D');
  }

  // Helper function to detect if two dates are continuous (next day)
  public function areDatesContinuous($date1, $date2) {
      $date1Obj = new DateTime($date1);
      $date2Obj = new DateTime($date2);
      $date1Obj->modify('+1 day');
      return $date1Obj->format('Y-m-d') === $date2Obj->format('Y-m-d');
  }

  // Group the rundown by date
  public function groupRundownByDate() {
      $grouped = [];
      foreach ($this->rundown as $session) {
          $date = $session['date'];
          if (!isset($grouped[$date])) {
              $grouped[$date] = [];
          }
          $grouped[$date][] = $session;
      }
      return $grouped;
  }

  // Helper function to format time range for a single day
  public function formatTimeRangeForDay($sessions, $date, $is_time_only ) {

    $startTimes = [];
    $endTimes = [];

    foreach ($sessions as $session) {
        $startTimes[] = DateTime::createFromFormat('H:i', $session['startTime']);
        $endTimes[] = DateTime::createFromFormat('H:i', $session['endTime']);
    }

    // Find the earliest start time and the latest end time for the day
    $startTime = min($startTimes);
    $endTime = max($endTimes);

    // Format the times in a human-readable format
    $formattedStartTime = $startTime->format('h:ia');
    $formattedEndTime = $endTime->format('h:ia');

    // Add the full date with day of the week
    $fullDate = $this->formatFullDateWithDay($date);

    if($is_time_only){
      return "{$formattedStartTime} to {$formattedEndTime}";
    } else {
      return "{$formattedStartTime} to {$formattedEndTime} ({$fullDate})";
    }

  }

  // Helper function to create date string with hyphen for continuous dates and comma for non-continuous
  public function createDateString() {

    if( empty($this->rundown) ){
      return '';
    }

    // Sort the rundown by date
    usort($this->rundown, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    $groupedRundown = $this->groupRundownByDate();
    $dateArray = array_keys($groupedRundown);
    $result = '';
    $previousDate = null;
    $dateRange = [];
    $currentMonthYear = '';

    foreach ($dateArray as $index => $date) {
        $formattedDate = $this->formatDateOrdinal($date);
        $newMonthYear = $this->formatMonthYear($date);

        if ($previousDate === null) {
            // First date and its month/year
            $dateRange[] = $formattedDate;
            $currentMonthYear = $newMonthYear;
        } else {
            // Check if dates are continuous and belong to the same month/year
            if ($this->areDatesContinuous($previousDate, $date) && $newMonthYear === $currentMonthYear) {
                // Add to the current date range if continuous
                $dateRange[] = $formattedDate;
            } else {
                // Output previous range with month/year
                if (count($dateRange) > 1) {
                    $result .= $dateRange[0] . '-' . end($dateRange);
                } else {
                    $result .= $dateRange[0];
                }
                $result .= " {$currentMonthYear}, ";

                // Start new range
                $dateRange = [$formattedDate];
                $currentMonthYear = $newMonthYear;
            }
        }
        $previousDate = $date;
    }

    // Append the last date range with month/year
    if (count($dateRange) > 1) {
        $result .= $dateRange[0] . '-' . end($dateRange);
    } else {
        $result .= $dateRange[0];
    }
    $result .= " {$currentMonthYear}";

    return $result;

  }

  // Helper function to create time string with date
  public function createTimeString(){

    if( empty($this->rundown) ){
      return '';
    }

    // Sort the rundown by date
    usort($this->rundown, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    $timeRanges = [];

    $groupedRundown = $this->groupRundownByDate();

    $is_time_only = (count($groupedRundown) > 1)? false : true ;

    foreach ($groupedRundown as $date => $sessions) {
        $timeRanges[] = $this->formatTimeRangeForDay($sessions, $date, $is_time_only  );
    }

    $timeOutput = implode(' & <br>', $timeRanges);

    return $timeOutput;

  }

  // Function to generate the HTML table based on the grouped rundown
  public function generateRundownTable() {

    $groupedRundown = $this->groupRundownByDate();

    $output = '';

    $dayNumber = 1;

  	foreach ( $groupedRundown as $date => $session ) {
  		usort( $groupedRundown[$date] , function( $a, $b ) {
  				return strtotime( $a['startTime'] ) - strtotime( $b['startTime'] );
  		});
  	}

    foreach ($groupedRundown as $date => $sessions) {

      // Format the date as "Day 1 - 29 July 2024"
      $dayOfWeek = date('l', strtotime($date));
      $formattedDate = date('j F Y', strtotime($date));

      $output .= '<p class="info-data">Day ' . $dayNumber . ' - ' . $formattedDate . '</p>';
      $output .= '<table class="rundown">';
      $output .= '<tr style="background-color: #c3bd9b;">';
      $output .= '<td class="text-center" style="width: 15%;">Time</td>';
      $output .= '<td class="text-center" style="width: 60%;">Presentation Topic</td>';
      $output .= '<td class="text-center" style="width: 25%;">Speaker</td>';
      $output .= '</tr>';

      foreach ($sessions as $session) {
        if( $session['type'] == 'cpd_section' ){
          $output .= '<tr>';
          $output .= '<td>' . $session['startTime'] . ' – ' . $session['endTime'] . '</td>';
          $output .= '<td>' . $session['name'] . ' <br><p style="white-space: pre-line"> ' . $session['description'] . '</p></td>';
          $output .= '<td>' . $session['speaker'] . '</td>';
          $output .= '</tr>';
        } elseif( $session['type'] == 'end' || $session['type'] == 'end_survey' )  {
          continue;
        } else{
          $output .= '<tr style="background-color: #ddd9c5;">';
          $output .= '<td>' . $session['startTime'] . ' – ' . $session['endTime'] . '</td>';
          $output .= '<td style="border-right:unset!important;" class="text-center" >' . $session['name'] . '</td>';
          $output .= '<td style="border-left:unset!important;"></td>';
          $output .= '</tr>';
        }
      }

      $output .= '</table>';
      $output .= '<br>';

      $dayNumber++;

    }

    return $output;

  }

  // Summarize data in rundown and save all date in an array for
  public function save_rundown_dates() {
    $dates = [];

    // Loop through each rundown session to collect unique dates
    foreach ($this->rundown as $session) {
        if (isset($session['date'])) {
            $dates[] = $session['date'];
        }
    }

    // Remove duplicate dates
    $dates = array_unique($dates);

    // Save the first date as 'course_start_date' if it exists
    if (!empty($dates)) {
        update_post_meta($this->id, 'course_start_date', $dates[0]);
        update_post_meta($this->id, 'course_end_date', end($dates));
        update_post_meta($this->id, 'course_dates', $dates);
    } else {
        // Handle cases where no valid dates are found
        update_post_meta($this->id, 'course_start_date', null);
        update_post_meta($this->id, 'course_end_date', null );
        update_post_meta($this->id, 'course_dates', []);
    }
  }

  // Function to loop througth rundown and calculate total granted CPD Points
  public function calculate_cpd_points(){

    $daily_durations = []; // To store total CPD section time per day
    $cpd_points = 0;       // Final CPD points

    if( $this->is_overide_cpd ){
      return $this->overide_cpd_point;
    }

    if(!empty($this->rundown)){
      // Loop through the rundown to calculate durations of valid sections
      foreach ($this->rundown as $section) {
          // Only consider sections of type 'cpd_section'
          if ($section['type'] === 'cpd_section') {
              $date = $section['date'];

              // Calculate the duration of each section (endTime - startTime in minutes)
              $start = new DateTime($section['startTime']);
              $end = new DateTime($section['endTime']);
              $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60; // duration in minutes

              // Add duration to the respective day
              if (!isset($daily_durations[$date])) {
                  $daily_durations[$date] = 0;
              }
              $daily_durations[$date] += $duration;
          }
      }
    }

    // Loop through each day's total duration and calculate CPD points
    foreach ($daily_durations as $date => $duration) {
        // Apply the rules to calculate points for this day's duration
        $cpd_points += $this->get_points_for_duration($duration);
    }

    // Max 18 CPD points for whole program no matter how long it takes.

    if(is_numeric($cpd_points)){
      $cpd_points = ($cpd_points > 18)? 18 : $cpd_points ;
      update_post_meta( $this->id, 'course_cpd_point', $cpd_points );
    }

    return $cpd_points;

  }

  // Function to calculate points for a day
  public function get_points_for_duration( $duration ) {

    if( empty($this->relatedness) ){
      return "No CPD points calcuated as course relatedness not set.";
    }
    // Convert duration to hours
    $duration_in_hours = $duration / 60;

    // Map duration to CPD points according to the provided table
    if ($duration_in_hours < 1) {
        $points_partial = 0;
        $points_total = 0;
    } elseif ($duration_in_hours < 1.5) {
        $points_partial = 0.5;
        $points_total = 1;
    } elseif ($duration_in_hours < 2) {
        $points_partial = 0.5;
        $points_total = 1.5;
    } elseif ($duration_in_hours < 2.5) {
        $points_partial = 1;
        $points_total = 2;
    } elseif ($duration_in_hours < 3) {
        $points_partial = 1;
        $points_total = 2.5;
    } elseif ($duration_in_hours < 3.5) {
        $points_partial = 1.5;
        $points_total = 3;
    } elseif ($duration_in_hours < 4) {
        $points_partial = 1.5;
        $points_total = 3.5;
    } elseif ($duration_in_hours < 4.5) {
        $points_partial = 2;
        $points_total = 4;
    } elseif ($duration_in_hours < 5) {
        $points_partial = 2;
        $points_total = 4.5;
    } elseif ($duration_in_hours < 5.5) {
        $points_partial = 2.5;
        $points_total = 5;
    } elseif ($duration_in_hours < 6) {
        $points_partial = 2.5;
        $points_total = 5.5;
    } elseif ($duration_in_hours < 6.5) {
        $points_partial = 3;
        $points_total = 6;
    } elseif ($duration_in_hours < 7) {
        $points_partial = 3;
        $points_total = 6;
    } elseif ($duration_in_hours < 7.5) {
        $points_partial = 3;
        $points_total = 6;
    } elseif ($duration_in_hours < 8) {
        $points_partial = 3;
        $points_total = 6;
    } elseif ($duration_in_hours < 8.5) {
        $points_partial = 3;
        $points_total = 6;
    } elseif ($duration_in_hours < 9) {
        $points_partial = 3;
        $points_total = 6;
    } elseif ($duration_in_hours < 9.5) {
        $points_partial = 3;
        $points_total = 6;
    } else {
        $points_partial = 3;
        $points_total = 6;
    }

    // Return points based on relatedness factor
    if ($this->relatedness == 'total') {
        return $points_total;  // Total relatedness
    } elseif ($this->relatedness == 'partial') {
        return $points_partial;  // Partial relatedness
    } else {
        return "Error: Course relatedness setting error.";  // No relatedness means 0 CPD points
    }
  }

  //Get the current enrollment status of the course
  // return string Enrollment status: available, not-open, full, or end.
  public function get_enrollment_status() {
    // Set timezone to GMT +8
    $timezone = new DateTimeZone('Asia/Hong_Kong');

    // Convert the string dates to DateTime objects
    $open_application_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $this->open_application . ' 18:00:00', $timezone);
    $deadline_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $this->deadline . ' 23:59:59', $timezone);
    $current_datetime = new DateTime('now', $timezone); // Current date and time with timezone

    // If current date is before the open_application date
    if ($current_datetime < $open_application_datetime) {
        return 'not_open';
    }

    // If current date is after the deadline date
    if ($current_datetime > $deadline_datetime) {
        return 'end'; // Registration period is over
    }

    if( $this->type == 'training' ){
      // Get remaining seats
      $remaining_seats = $this->get_current_capacity();

      if ($remaining_seats > 0) {
          return 'available'; // Seats available
      } else {
          return 'full'; // No seats available
      }

    } else{
      echo 'external';
    }

  }

  // Get the current capacity, i.e., the number of remaining seats available.
  // return int Remaining number of seats.
  public function get_current_capacity() {

    if( !$this->capacity ){
      return 0;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'hkota_course_enrollment';

    // Query the database to count users with 'enrolled' or 'pending' status
    $enrolled_pending_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE course_id = %d AND status IN ('enrolled', 'pending', 'awaiting_approval' )",
        $this->id
    ));

    // Calculate remaining seats
    $remaining_seats = $this->capacity - intval($enrolled_pending_count);

    return ($remaining_seats > 0) ? $remaining_seats : 0; // Avoid negative numbers
  }

  //function to check if specific user is eligible to enroll.
  public function get_user_eligibility($user_id) {
    $is_eligible = true;
    $error_message = "";

    // Check if the course is restricted
    if ($this->is_restricted) {

      if ( function_exists( 'wc_memberships_get_user_memberships' ) && $this->is_member_only ){
        $memberships = wc_memberships_get_user_memberships($user_id);
        if(empty($memberships)){
          $is_eligible = false;
          $error_message = "You must be HKOTA offical member to apply this course.";
        }
        $is_member = false;
        foreach ($memberships as $membership ) {
          // can be: wcm-active, wcm-cancelled, wcm-complimentary, wcm-delayed, wcm-expired, wcm-paused, wcm-pending
          $status = get_post_status($membership->id);
          if( $status == 'wcm-active' || $status == 'wcm-paused' ){
            $is_member = true;
          }
        }
        if( !$is_member ){
          $is_eligible = false;
          $error_message = "You must be HKOTA offical member to apply this course.";
        }
      }

      if(!empty($this->min_years)){

        date_default_timezone_set('Asia/Hong_Kong'); // or GMT+8
        // Check if the user meets the minimum OT experience requirement
        $user_ot_reg_date = get_user_meta($user_id, 'ot_reg_date', true);

        if ($user_ot_reg_date) {
            try {
              // Get the current date with the correct time zone
                $current_date = date("Y-m-d");

                $ts1 = strtotime($user_ot_reg_date);
                $ts2 = strtotime($current_date);
                // Calculate the years of OT experience
                $year1 = date('Y', $ts1);
                $year2 = date('Y', $ts2);

                $month1 = date('m', $ts1);
                $month2 = date('m', $ts2);

                $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

                $diff = floor($diff/12);

                if ( $this->min_years > $diff ) {
                    $is_eligible = false;
                    $error_message = "You do not meet the minimum required OT experience of {$this->min_years} years.";
                }
            } catch (Exception $e) {
                $is_eligible = false;
                $error_message = "Your OT registration date format is invalid.";
            }
        } else {
            $is_eligible = false;
            $error_message = "Your OT registration date is not set.";
        }
      }

      // Check if uploads are required
      if ($this->is_uploads_required) {
          // Check if required certs are set
          if (empty($this->cert_requirment)) {
              $is_eligible = false;
              $error_message = "This course requires you to upload certification documents, but no specific certs were listed. Please contact HKOTA admin if you see this error.";
          }
      }

    }

    return ['is_eligible' => $is_eligible, 'message' => $error_message];
  }

  public function get_user_enrollment_id($user_id) {
        global $wpdb;

        // Define the table name
        $table = $wpdb->prefix . 'hkota_course_enrollment';

        // Query to get the enrollment ID for the user and this course
        $enrollment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $table WHERE user_id = %d AND course_id = %d LIMIT 1",
            $user_id, $this->id
        ));

        // If no enrollment ID is found, return false
        if (is_null($enrollment_id)) {
            return false;
        }

        // Return the enrollment ID
        return $enrollment_id;
  }

  public function get_user_enrollment_status($user_id) {

        global $wpdb;

        // Define the table name
        $table = $wpdb->prefix . 'hkota_course_enrollment';

        // Query to get the enrollment status for the user and this course
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $table WHERE user_id = %d AND course_id = %d LIMIT 1",
            $user_id, $this->id
        ));

        // If no record is found, return a default message
        if (is_null($status)) {
            return false;
        }

        // Return the enrollment status
        return $status;
    }

  public function get_user_course_fee($user_id) {

    // Check if user has an active membership
    if ( function_exists( 'wc_memberships_get_user_memberships' ) ){
      $memberships = wc_memberships_get_user_memberships($user_id);
      if(empty($memberships)){
        $is_eligible = false;
        $error_message = "You must be HKOTA offical member to apply this course.";
      }
      $is_member = false;
      foreach ($memberships as $membership ) {
        // can be: wcm-active, wcm-cancelled, wcm-complimentary, wcm-delayed, wcm-expired, wcm-paused, wcm-pending
        $status = get_post_status($membership->id);
        if( $status == 'wcm-active' || $status == 'wcm-paused' ){
          $is_member = true;
        }
      }
    }

    // Get the current date for comparison with early bird deadline ( plus 28800 seconds for GMT +8 )
    $current_date = date('Y-m-d', time() + 28800 );

    // Determine if early bird pricing applies
    $is_early_bird_period = $this->is_early_bird && $current_date <= $this->early_bird_enddate;

    // Determine the fee based on membership status and early bird period
    if ($is_member) {
        if ($is_early_bird_period) {
            // Member + Early Bird
            return $this->fee_member_earlybird;
        } else {
            // Member + Regular
            return $this->fee_member;
        }
    } else {
        if ($is_early_bird_period) {
            // Non-Member + Early Bird
            return $this->fee_non_member_earlybird;
        } else {
            // Non-Member + Regular
            return $this->fee_non_member;
        }
    }
  }

  public function enroll($user_id,$status) {

    if( $this->get_user_enrollment_status($user_id) ){
      return false;
    }

    $enrollment = create_enrollment();

    if(!$enrollment) return false; //unable to create enrollment object
    $enrollment->set('user_id',$user_id);
    $enrollment->set('course_id',$this->id);
    $enrollment->set('status',$status);
    $enrollment->set('certificate_status',NULL);
    $enrollment->set('order_id',NULL); // To be updated with the actual order id later
    $enrollment->set('amount',NULL); // To be updated with the actual order id later
    $enrollment->set('date_created_gmt',current_time('mysql', 1));
    $enrollment->set('payment_method',NULL); // To be updated with the actual order id later

    return $enrollment;

  }

  // Remove user from the waiting list
  public function remove_waiting_list($user_id) {
      global $wpdb;

      // Remove from waiting list
      $table = $wpdb->prefix . 'hkota_course_enrollment';
      $deleted = $wpdb->delete($table, [
          'user_id'   => $user_id,
          'course_id' => $this->id,
          'status'    => 'waiting_list'
      ]);

      if ($deleted === false) {
          return new WP_Error('failed_removal', __('Failed to remove the user from the waiting list.'));
      }

      return true;
  }

  // Generate an iCalender (iCAL) file, then output the url of the file as iCalender url.
  public function get_ical_url() {
    // Define the file path and name
    $filename = 'course-' . $this->id . '.ics';
    $filepath = COURSE_FILE_DIR . $filename;
    $ical_url = COURSE_FILE_URL . $filename;

    // Check if the iCal file already exists
    if (file_exists($filepath)) {
        // If the file exists, output the URL
        echo $ical_url;
        return;
    }

    // Start iCAL file content if the file does not exist
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Your Organization//NONSGML v1.0//EN\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "METHOD:PUBLISH\r\n";

    // Group rundown sessions by date and summarize start and end times for each day
    $grouped_by_date = [];

    foreach ($this->rundown as $session) {
        $date = $session['date'];

        // Parse start and end times
        $start_time = new DateTime($session['date'] . ' ' . $session['startTime']);
        $end_time = new DateTime($session['date'] . ' ' . $session['endTime']);

        // Initialize the date if it's not already set
        if (!isset($grouped_by_date[$date])) {
            $grouped_by_date[$date] = [
                'start' => $start_time,
                'end' => $end_time,
            ];
        } else {
            // Update the earliest start time and latest end time for the day
            if ($start_time < $grouped_by_date[$date]['start']) {
                $grouped_by_date[$date]['start'] = $start_time;
            }
            if ($end_time > $grouped_by_date[$date]['end']) {
                $grouped_by_date[$date]['end'] = $end_time;
            }
        }
    }

    // Create one event per day summarizing the entire day's time interval
    foreach ($grouped_by_date as $date => $times) {
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . uniqid() . "@yourdomain.com\r\n";  // Unique ID for the event
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";  // Timestamp of event creation

        // Set the start and end date/time for the day's interval
        $ical .= "DTSTART:" . $times['start']->format('Ymd\THis') . "\r\n";
        $ical .= "DTEND:" . $times['end']->format('Ymd\THis') . "\r\n";  // Event end date and time

        // Set event details
        $ical .= "SUMMARY:" . $this->escape_string($this->title) . "\r\n";  // Course title as the event title
        $ical .= "DESCRIPTION:" . $this->escape_string('Full day session') . "\r\n";  // Optional description
        $ical .= "LOCATION:" . $this->escape_string($this->venue) . "\r\n";  // Course venue

        $ical .= "END:VEVENT\r\n";
    }

    // End iCAL file content
    $ical .= "END:VCALENDAR\r\n";

    // Save the iCal to the server
    file_put_contents($filepath, $ical);

    // Output the URL of the newly created iCal file
    echo $ical_url;
  }

  // Helper function to escape special characters in iCAL format
  private function escape_string($string) {
      return preg_replace('/([\,;])/','\\\$1', $string);
  }

  // Method to show the dashboard with two pie charts
  public function show_dashboard() {

    if( $this->type == 'training' && !$this->capacity ){
      echo "<p>Current course capacity is zero, dashboard is disabled.</p>";
      return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'hkota_course_enrollment';

    // Query for enrollment data (enrolled, pending, awaiting_approval)
    $enrollment_data = $wpdb->get_results($wpdb->prepare(
        "SELECT status, COUNT(*) as count FROM $table WHERE course_id = %d AND status IN ('enrolled', 'pending', 'awaiting_approval','on_hold') GROUP BY status",
        $this->id
    ));

    // Initialize counts
    $enrolled_count = 0;
    $pending_count = 0;
    $awaiting_approval_count = 0;

    foreach ($enrollment_data as $data) {
        if ($data->status == 'enrolled') {
            $enrolled_count = intval($data->count);
        } elseif ($data->status == 'pending') {
            $pending_count = intval($data->count);
        } elseif ($data->status == 'awaiting_approval') {
            $awaiting_approval_count = intval($data->count);
        }elseif ($data->status == 'on_hold') {
            $on_hold_count = intval($data->count);
        }
    }

    // Calculate available spots based on capacity
    $used_spots = $enrolled_count + $pending_count + $awaiting_approval_count + $on_hold_count;

    if( $this->type == 'co-organized-event' && $used_spots == 0 ){
      echo "<p>Current course has no enrollment data, dashboard is disabled.</p>";
      return;
    }

    switch ( $this->type ) {
      case 'training':
        $available_count = $this->capacity - $used_spots;
        break;
      case 'co-organized-event':
        $available_count = 0;
        break;
    }

    // Prepare chart data for enrollment
    $enrollment_chart_data = [
        ['Status', 'Count'],
        ['Enrolled', $enrolled_count],
        ['Pending', $pending_count],
        ['Awaiting Approval', $awaiting_approval_count],
        ['On Hold' , $on_hold_count ],
        ['Available', $available_count > 0 ? $available_count : 0]
    ];

    // Query for attendance data (fully_attended, partially_attended, not_attended)
    $attendance_data = $wpdb->get_results($wpdb->prepare(
        "SELECT attendance FROM $table WHERE course_id = %d AND status = 'enrolled'",
        $this->id
    ));

    // Initialize attendance status counts
    $fully_attended_count = 0;
    $partially_attended_count = 0;
    $not_attended_count = 0;

    foreach ($attendance_data as $row) {
        $attendance = maybe_unserialize($row->attendance);
        if (isset($attendance['attendance_status'])) {
            switch ($attendance['attendance_status']) {
                case 'fully_attended':
                    $fully_attended_count++;
                    break;
                case 'partially_attended':
                    $partially_attended_count++;
                    break;
                default:
                    $not_attended_count++;
                    break;
            }
        }
    }

    // Prepare chart data for attendance
    $attendance_chart_data = [
        ['Attendance Status', 'Count'],
        ['Fully Attended', $fully_attended_count],
        ['Partially Attended', $partially_attended_count],
        ['Not Attended', $not_attended_count]
    ];

    // Query for certificate status data (issued, not issued)
    $certificate_data = $wpdb->get_results($wpdb->prepare(
        "SELECT certificate_status FROM $table WHERE course_id = %d AND status = 'enrolled'",
        $this->id
    ));

    // Initialize certificate status counts
    $certificate_issued_count = 0;
    $certificate_not_issued_count = 0;

    foreach ($certificate_data as $row) {
        if ($row->certificate_status === 'issued') {
            $certificate_issued_count++;
        } else {
            $certificate_not_issued_count++;
        }
    }

    // Prepare chart data for certificate status
    $certificate_chart_data = [
        ['Certificate Status', 'Count'],
        ['Issued', $certificate_issued_count],
        ['Not Issue', $certificate_not_issued_count]
    ];

    // Render the dashboard with all three pie charts
    $this->render_dashboard($enrollment_chart_data, $attendance_chart_data, $certificate_chart_data);
  }

  // Helper method to render the pie charts using Google Charts
  private function render_dashboard($enrollment_chart_data, $attendance_chart_data, $certificate_chart_data) {
    // Convert the chart data to JSON
    $enrollment_chart_data_json = json_encode($enrollment_chart_data);
    $attendance_chart_data_json = json_encode($attendance_chart_data);
    $certificate_chart_data_json = json_encode($certificate_chart_data);
    ?>

    <div id="course-dashboard">
        <h3>Course Overview</h3>
        <div style="display: flex; justify-content: space-around;">
            <div id="enrollmentPieChart" style="width: 400px; height: 400px;"></div>
            <div id="attendancePieChart" style="width: 400px; height: 400px;"></div>
            <div id="certificatePieChart" style="width: 400px; height: 400px;"></div>
        </div>
    </div>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // Enrollment pie chart
            var enrollmentData = google.visualization.arrayToDataTable(<?php echo $enrollment_chart_data_json; ?>);
            var enrollmentOptions = {
                title: 'Registration Status',
                pieHole: 0.4,
                colors: ['#4CAF50', '#FFC107', '#00BFFF', '#FF5A38', '#d4d4d4'], // Custom colors for each status
                legend: { position: 'bottom' }
            };
            var enrollmentChart = new google.visualization.PieChart(document.getElementById('enrollmentPieChart'));
            enrollmentChart.draw(enrollmentData, enrollmentOptions);

            // Attendance pie chart
            var attendanceData = google.visualization.arrayToDataTable(<?php echo $attendance_chart_data_json; ?>);
            var attendanceOptions = {
                title: 'Attendance Status',
                pieHole: 0.4,
                colors: ['#4CAF50', '#FFC107', '#d4d4d4'],
                legend: { position: 'bottom' }
            };
            var attendanceChart = new google.visualization.PieChart(document.getElementById('attendancePieChart'));
            attendanceChart.draw(attendanceData, attendanceOptions);

            // Certificate pie chart
            var certificateData = google.visualization.arrayToDataTable(<?php echo $certificate_chart_data_json; ?>);
            var certificateOptions = {
                title: 'Certificate Status',
                pieHole: 0.4,
                colors: ['#4CAF50', '#d4d4d4'],
                legend: { position: 'bottom' }
            };
            var certificateChart = new google.visualization.PieChart(document.getElementById('certificatePieChart'));
            certificateChart.draw(certificateData, certificateOptions);
        }
    </script>

    <style>
        #course-dashboard {
            width: 100%;
            text-align: center;
        }

        #enrollmentPieChart, #attendancePieChart, #certificatePieChart {
            width: 400px;
            height: 400px;
        }
    </style>

    <?php
  }

  // Reset all attendance status of the user of this course
  public function init_attendance_record(){

    $attendance_data = array(
      'attendance_status' => 'not_attended',
      'attendance_data' => array()
    );

    foreach ( $this->rundown as $section ) {
      if( $section['type'] == 'registration' ||
          $section['type'] == 'end' ||
          $section['type'] == 'end_survey'
      ){
        $attendance_data['attendance_data'][$section['id']] = 0;
      }
    }

    if( !is_serialized( $attendance_data ) ) {
      $attendance_data = maybe_serialize($attendance_data);
    }

    global $wpdb;
    $enrollment_table = $wpdb->prefix . 'hkota_course_enrollment';
    $updated = $wpdb->update(
      $enrollment_table,
      array( 'attendance' => $attendance_data ),
      array( 'course_id' => $this->id ),
      array( '%s' ),
      array( '%d' )
    );

    if ($updated === false) {
        return new WP_Error('failed_update', __('failed to initialize attendance record.'));
    }

    return true;

  }

  // Reset particular user attendance status of this course
  public function set_attendance_record($user_id){

    $attendance_data = array(
      'attendance_status' => 'not_attended',
      'attendance_data' => array()
    );

    foreach ( $this->rundown as $section ) {
      if( $section['type'] == 'registration' ||
          $section['type'] == 'end' ||
          $section['type'] == 'end_survey'
      ){
        $attendance_data['attendance_data'][$section['id']] = 0;
      }
    }

    if( !is_serialized( $attendance_data ) ) {
      $attendance_data = maybe_serialize($attendance_data);
    }

    global $wpdb;
    $enrollment_table = $wpdb->prefix . 'hkota_course_enrollment';
    $updated = $wpdb->update(
      $enrollment_table,
      array( 'attendance' => $attendance_data ),
      array(
        'user_id'   => $user_id,
        'course_id' => $this->id
      ),
      array( '%s' ),
      array( '%d', '%d' )
    );

    if ($updated === false) {
        return new WP_Error('failed_update', __('Failed to initialize attendance record.'));
    }

    return true;

  }

  // Check if any section in $rundown has the matching ID
  public function check_section_id_match( $section_id ) {
    // Loop through the rundown to check if any section has the matching ID
    foreach ( $this->rundown as $section) {
        if ($section['id'] == $section_id) {
            return true; // Found a matching section ID
        }
    }
    return false; // No matching section ID found
  }

  // Check if any section in $rundown has the matching ID
  public function check_quiz_id_match($testing_quiz_id) {
    // Loop through the rundown to check if any section has the matching ID
    foreach ( $this->quiz as $quiz_id => $quiz_content) {
        if ($testing_quiz_id == $quiz_id) {
            return true; // Found a matching section ID
        }
    }
    return false; // No matching section ID found
  }

  // Check if any sections type is end_survey
  public function check_section_is_end_survey($section_id) {
    // Loop through the rundown to check if any section has the matching ID
    foreach ($this->rundown as $section) {
        if ($section['id'] == $section_id && $section['type'] == 'end_survey') {
            return true;
        }
    }
    return false;
  }

  // Add button to show pupil details in the frontend
  public function display_pupil_details_button() {
    ?>
    <style>
      /* Overlay */
      .pupil-popup-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 1000;
          display: none;
      }

      /* Popup content */
      .popup-content {
          background-color: white;
          padding: 20px;
          margin: 50px auto;
          width: 80%;
          max-width: 1024px; /* Set a maximum width */
          max-height: 80%; /* Set a maximum height for the popup */
          position: relative;
          z-index: 1001;
          overflow-y: auto; /* Add vertical scrolling if content exceeds max height */
      }

      /* Popup close button */
      .close-popup {
          cursor: pointer;
          position: absolute;
          top: 10px;
          right: 10px;
          font-size: 20px;
      }

      /* Table inside the popup */
      .popup-content table {
          width: 100%;
          border-collapse: collapse;
      }

      .popup-content table th, .popup-content table td {
          padding: 10px;
          border: 1px solid #ddd;
      }

      .popup-content table th {
          background-color: #f4f4f4;
      }
      .popup-content h2{
        padding: unset;
        margin: 20px 0;
        font-size: 20px;
        font-weight: 600;
      }
      #pupil-details-table {
        width: 100%;
        border-collapse: collapse;
      }

      #pupil-details-table th, #pupil-details-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
      }

      #pupil-details-table th {
        background-color: #f2f2f2;
        font-weight: bold;
      }
      
      .check-column {
        width: 30px;
        text-align: center;
      }
      
      #pupil-bulk-actions {
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 3px;
      }
      
      #pupil-bulk-actions select {
        margin-right: 10px;
      }
      
      #bulk-edit-row {
        background-color: #fff8dc;
        border: 2px solid #ffec8c;
      }
      
      #bulk-edit-row td {
        padding: 15px;
      }
      
      #bulk-edit-row label {
        font-weight: 600;
        margin-right: 5px;
      }
      
      #bulk-edit-row select {
        margin-right: 15px;
        min-width: 150px;
      }
      
      #attendance-details-popup .popup-content {
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      }
      
      #attendance-details-popup table {
        font-size: 14px;
      }
      
      #attendance-details-popup th {
        background-color: #f8f9fa;
        font-weight: 600;
      }
      
      #attendance-details-popup .close-attendance-popup:hover {
        color: #000;
      }
      
      .tablesorter th {
          cursor: pointer;
      }

      .tablesorter th.headerSortUp {
          background-color: #f0f0f0;
      }

      .tablesorter th.headerSortDown {
          background-color: #d0d0d0;
      }

      .tablesorter tbody tr:nth-child(odd) {
          background-color: #f9f9f9;
      }
    </style>
    <script src="/wp-content/plugins/hkota-courses-and-memberships/lib/jquery-tablesorter/jquery.tablesorter.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/wp-content/plugins/hkota-courses-and-memberships/lib/jquery-tablesorter/theme.default.min.css">
    <button type="button" class="button button-primary" id="show-pupil-details" data-course-id="<?php echo $this->id; ?>">Show Pupil Details</button>

        <div id="pupil-details-popup" class="pupil-popup-overlay" style="display:none;">
            <div class="popup-content">
                <h2>Pupil Details</h2>
                <table id="pupil-details-table" class="tablesorter" data-course-id="<?php echo $this->id?>">
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Enrollment Status</th>
                            <th>Attendance Status</th>
                            <th>Certificate Status</th>
                            <th>Submitted Document</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Pupils data will be inserted here via AJAX -->
                    </tbody>
                </table>
                <span class="close-popup"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
    <?php


  }

  // function to display end survey
  public function display_end_survey($section_id) {
    ?>
    <style>
      /* Full-screen overlay */
      #combined-form-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(255, 255, 255, 1); /* Overlay shadow */
          z-index: 9999;
          display: flex;
          align-items: flex-start;
          justify-content: center;
          overflow-y: auto; /* Enable scrolling */
          padding: 20px;
      }

      /* Form container */
      #combined-form-container {
          width: 550px;
          background-color: white;
          padding: 20px;
          border-radius: 10px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
          text-align: center;
          margin-top: 15vh;
          overflow-y: auto;
      }

      #combined-form-container label{
        width: 100%;
        text-align: left;
      }

      #combined-form-container h2 {
          margin-bottom: 45px;
          margin-top: 25px;
      }

      /* Input and button styling */
      #combined-form-container input[type="text"],
      #combined-form-container input[type="number"],
      #combined-form-container input[type="email"],
      #combined-form-container textarea,
      #combined-form-container select {
          width: 100%; /* Full width input */
          padding: 10px;
          margin: 10px 0;
          border: 1px solid #ccc;
          border-radius: 5px;
      }

      #combined-form-container input[type="submit"],
      #combined-form-container button {
          width: 100%;
          padding: 10px;
          margin: 10px 0;
          background-color: #0073aa;
          color: white;
          border: none;
          cursor: pointer;
      }

      #combined-form-container input[type="submit"]:hover,
      #combined-form-container button:hover {
          background-color: #005177;
      }

      .hidden {
          display: none;
      }

    </style>
    <div id="combined-form-overlay">
        <div id="combined-form-container">
            <!-- First Part: Sign In/Out Form -->
            <form method="post">
              <div id="part-one">
                <h2>Sign In/Out</h2>
                <label for="registration_email">Registration Email:</label><br>
                <input type="email" id="registration_email" name="registration_email" required><br>
                <input type="hidden" name="section_id" value="<?php echo esc_attr($section_id); ?>">
                <input type="hidden" name="course_id" value="<?php echo esc_attr($this->id); ?>">
                <button type="button" id="next-button">Next</button>
              </div>

            <!-- Second Part: Survey Form (initially hidden) -->
            <div id="part-two" class="hidden">
                <h2>Course Survey</h2>

                <?php
                if (!empty($this->survey)) {
                    foreach ($this->survey as $question) {
                        $label = esc_html($question['label']);
                        $id = esc_attr($question['id']);
                        $value = isset($question['value']) ? esc_attr($question['value']) : '';

                        echo "<label for='{$id}'>{$label}</label><br>";

                        switch ($question['type']) {
                            case 'select':
                                $options = explode(',', $question['options']);
                                echo "<select id='{$id}' name='{$id}' required>";
                                foreach ($options as $option) {
                                    $selected = ($option === $value) ? "selected" : "";
                                    echo "<option value='{$option}' {$selected}>{$option}</option>";
                                }
                                echo "</select><br>";
                                break;

                            case 'textarea':
                                echo "<textarea id='{$id}' name='{$id}' rows='4' required>{$value}</textarea><br>";
                                break;

                            case 'text':
                            case 'number':
                            case 'email':
                                echo "<input type='{$question['type']}' id='{$id}' name='{$id}' value='{$value}' required><br>";
                                break;
                        }
                    }
                }
                ?>
                <button type="button" id="back-button">Back</button>
                <input type="submit" value="Submit">
              </div>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            var nextButton = document.getElementById('next-button');
            var backButton = document.getElementById('back-button');
            var partOne = document.getElementById('part-one');
            var partTwo = document.getElementById('part-two');

            // Show the survey form when Next button is clicked
            nextButton.addEventListener('click', function (e) {
                e.preventDefault();
                var regEmail = document.getElementById('registration_email').value.trim();
                emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (regEmail === '' || !emailPattern.test(regEmail) ) {
                    showMessage('error','Please enter your registration email.');
                    return;
                }
                partOne.style.display = 'none';
                partTwo.classList.remove('hidden');
            });

            // Go back to the first form when Back button is clicked
            backButton.addEventListener('click', function (e) {
                e.preventDefault();
                partTwo.classList.add('hidden');
                partOne.style.display = 'block';
            });
        });
        // Prevent form submission on Enter keypress
        document.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

    </script>
    <?php
  }

  // function to save end survey data
  public function save_survey_form_data($user_id, $survey_data) {
    global $wpdb;

    // The table name
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Prepare the survey data
    $sanitized_survey_data = [];

    foreach ($survey_data as $key => $value) {
      if( $key == 'registration_number' || $key == 'section_id' || $key == 'course_id' ) continue;
        // Sanitize both the key and the value for security
        $sanitized_key = sanitize_text_field($key);
        $sanitized_value = sanitize_text_field($value);
        $sanitized_survey_data[$sanitized_key] = $sanitized_value;
    }

    // Serialize the survey data before saving it in the DB
    $serialized_survey_data = maybe_serialize($sanitized_survey_data);

    // Update the survey column for this user and course
    $wpdb->update(
        $table_name,
        ['survey' => $serialized_survey_data], // Column to update
        ['user_id' => $user_id, 'course_id' => $this->id], // Where clause to target the correct row
        ['%s'], // Format for the update value (string)
        ['%d', '%d'] // Format for the where clause (integer for user_id and course_id)
    );
  }

  // function to quiz base on
  public function display_quiz_form($quiz_id) {

    if (!isset($this->quiz[$quiz_id])) {
        return;
    }

    $quiz_form = $this->quiz[$quiz_id];
    ?>
    <style>
        /* Full-screen overlay */
        #combined-form-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 1); /* Overlay shadow */
            z-index: 9999;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto; /* Enable scrolling */
            padding: 20px;
        }

        /* Form container */
        #combined-form-container {
            width: 650px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-top: 15vh;
            overflow-y: auto;
        }

        #combined-form-container label{
          width: 100%;
          text-align: left;
        }

        #combined-form-container h2 {
            margin-bottom: 45px;
            margin-top: 25px;
        }

        /* Input and button styling */
        #combined-form-container input[type="text"],
        #combined-form-container input[type="number"],
        #combined-form-container input[type="email"],
        #combined-form-container textarea,
        #combined-form-container select {
            width: 100%; /* Full width input */
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #combined-form-container input[type="submit"],
        #combined-form-container button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            background-color: #0073aa;
            color: white;
            border: none;
            cursor: pointer;
        }

        #combined-form-container input[type="submit"]:hover,
        #combined-form-container button:hover {
            background-color: #005177;
        }

        .hidden {
            display: none;
        }
    </style>

    <div id="combined-form-overlay">
        <div id="combined-form-container">
            <!-- First Part: Sign In/Out Form -->
            <form method="post">
              <div id="part-one">
                <h2>Quiz</h2>
                <label for="registration_email">Registration Email:</label><br>
                <input type="email" id="registration_email" name="registration_email" required><br>
                <input type="hidden" name="course_id" value="<?php echo esc_attr($this->id); ?>">
                <button type="button" id="next-button">Next</button>
              </div>

            <!-- Second Part: Quiz Form (initially hidden) -->
            <div id="part-two" class="hidden">
                <h2><?php echo esc_html($quiz_form['name']); ?></h2>
                <?php
                foreach ($quiz_form['data'] as $question) {
                    $label = esc_html($question['label']);
                    $type = esc_attr($question['type']);
                    $options = isset($question['options']) ? $question['options'] : [];

                    echo "<label for='{$label}'>{$label}</label><br>";

                    switch ($type) {
                        case 'select':
                            echo "<select id='{$label}' name='{$label}' required>";
                            foreach ($options as $option) {
                                echo "<option value='{$option}'>{$option}</option>";
                            }
                            echo "</select><br>";
                            break;

                        case 'textarea':
                            echo "<textarea id='{$label}' name='{$label}' rows='4' required></textarea><br>";
                            break;

                        default:
                            echo "<input type='{$type}' id='{$label}' name='{$label}' required><br>";
                            break;
                    }
                }
                ?>
                <button type="button" id="back-button">Back</button>
                <input type="submit" value="Submit">
              </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var nextButton = document.getElementById('next-button');
            var backButton = document.getElementById('back-button');
            var partOne = document.getElementById('part-one');
            var partTwo = document.getElementById('part-two');

            // Show the quiz form when Next button is clicked
            nextButton.addEventListener('click', function (e) {
                e.preventDefault();
                var regNumber = document.getElementById('registration_email').value.trim();
                if (regNumber === '') {
                    alert('Please enter your registration email.');
                    return;
                }
                partOne.style.display = 'none';
                partTwo.classList.remove('hidden');
            });

            // Go back to the first form when Back button is clicked
            backButton.addEventListener('click', function (e) {
                e.preventDefault();
                partTwo.classList.add('hidden');
                partOne.style.display = 'block';
            });
        });

        // Prevent form submission on Enter keypress
        document.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
    </script>

    <?php
  }

  // function to save user respond on quiz
  public function save_quiz_form_data($user_id, $quiz_data, $quiz_id) {
    global $wpdb;

    // The table name
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Prepare the sanitized quiz data
    $sanitized_quiz_data = [];

    // Loop through the POST data (quiz_data) to extract user answers
    foreach ($quiz_data as $key => $value) {
        if( $key == 'registration_number' || $key == 'course_id' ) continue;
        // Sanitize both the question name and the answer for security
        $sanitized_key = sanitize_text_field($key); // Question Name
        $sanitized_value = sanitize_text_field($value); // Answer
        $sanitized_quiz_data[] = ['name' => $sanitized_key, 'answer' => $sanitized_value]; // Store each question-answer pair
    }

    // Retrieve the existing quiz data from the DB for this user and course
    $existing_quiz_data_serialized = $wpdb->get_var($wpdb->prepare(
        "SELECT quiz FROM $table_name WHERE user_id = %d AND course_id = %d",
        $user_id,
        $this->id
    ));

    // Unserialize the existing quiz data if available, otherwise start with an empty array
    $existing_quiz_data = $existing_quiz_data_serialized ? maybe_unserialize($existing_quiz_data_serialized) : [];

    // Assign the sanitized quiz data to the correct quiz ID
    $existing_quiz_data[$quiz_id] = $sanitized_quiz_data;

    // Serialize the entire quiz data array for storage in the database
    $serialized_quiz_data = maybe_serialize($existing_quiz_data);

    // Update the quiz column for this user and course
    $wpdb->update(
        $table_name,
        ['quiz' => $serialized_quiz_data], // Column to update
        ['user_id' => $user_id, 'course_id' => $this->id], // Where clause to target the correct row
        ['%s'], // Format for the update value (string)
        ['%d', '%d'] // Format for the where clause (integer for user_id and course_id)
    );
  }

  public function delete_all_quiz_data() {
        global $wpdb;

        // Table name
        $table_name = $wpdb->prefix . 'hkota_course_enrollment';

        // Update the table to remove quiz data for this course
        $result = $wpdb->update(
            $table_name,
            ['quiz' => NULL],  // Setting the quiz column to NULL
            ['course_id' => $this->id],  // Where clause to target this course's entries
            ['%s'],  // Format for the update value (string for NULL)
            ['%d']   // Format for the where clause (integer for course_id)
        );


        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('failed_update', __('failed to delete quiz data'));
        }
    }

  public function delete_all_survey_data() {
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Update the table to remove quiz data for this course
    $result = $wpdb->update(
        $table_name,
        ['survey' => NULL],  // Setting the quiz column to NULL
        ['course_id' => $this->id],  // Where clause to target this course's entries
        ['%s'],  // Format for the update value (string for NULL)
        ['%d']   // Format for the where clause (integer for course_id)
    );
    if ($result !== false) {
        return true;
    } else {
        return new WP_Error('failed_update', __('failed to delete survey data'));
    }
  }

  // display certificate button conditionally, in my coures tab under my account page
  public function display_certificate_button($user_id) {
    global $wpdb;

    // Fetch the enrollment data for this course and user
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';
    $enrollment_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND course_id = %d", $user_id, $this->id
    ));

    if ($enrollment_data) {
        // Check if the certificate is already issued
        if ($enrollment_data->certificate_status === 'issued') {
            // Fetch from the cpd_records table
            $cpd_table = $wpdb->prefix . 'hkota_cpd_records';
            $cpd_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $cpd_table WHERE user_id = %d AND course_id = %d", $user_id, $this->id
            ));

            if ($cpd_record) {
                // Show the button linking to the existing certificate
                $certificate_url = COURSE_CERTIFICATE_URL . $cpd_record->file;
                echo '<a target="_blank" class="button" href="' . esc_url($certificate_url) . '" class="button">Download Certificate</a>';
            }
        } else {
            // Check attendance status
            $attendance = maybe_unserialize($enrollment_data->attendance);
            if (isset($attendance['attendance_status']) && $attendance['attendance_status'] === 'fully_attended') {
                // Show the button to create a certificate
                $certificate_url = '/certificate/?course_id=' . $this->id;
                echo '<a class="button" target="_blank" href="' . esc_url($certificate_url) . '" class="button">Generate Certificate</a>';
            }
        }
    }
  }

  // create certificate if pupil meet requirement
  public function create_certificate($user_id) {

    global $wpdb;

    if( $this->type != 'training' ){
        return false; // Only training courses can issue certificates
    }

    if( get_post_status($this->id) != 'publish' && get_post_status($this->id) != 'private' ) {
        return false; // Course is not published or private, return false
    }

    // check if user cert already issued then check if user is enrolled and has fully attended the course
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';
    $enrollment_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND course_id = %d", $user_id, $this->id
    ));

    // If enrollment data is missing, return false
    if (!$enrollment_data) {
        return false;
    }

    $cpd_table = $wpdb->prefix . 'hkota_cpd_records';
    $cpd_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $cpd_table WHERE user_id = %d AND course_id = %d", $user_id, $this->id
    ));

    // If a certificate has already been issued, return false
    if ($cpd_record && $enrollment_data->certificate_status == 'issued' && $cpd_record->status == 'issued') {
        return false;
    }

    // Unserialize the attendance data
    $attendance = maybe_unserialize($enrollment_data->attendance);

    // Ensure that the user fully attended the course
    if (isset($attendance['attendance_status']) && $attendance['attendance_status'] === 'fully_attended') {

        // If there's a pending certificate record, reuse the serial number
        if ($cpd_record && $cpd_record->status == 'pending') {
            $serial_no = $cpd_record->serial_no;
        } else {
            // Assign a new serial number
            $serial_no = $this->assign_unique_certificate_serial_number($user_id);
        }

        // If assigning a serial number failed, return false
        if (!$serial_no) {
            return false;
        }

        // Set the date issued to the current time
        $date_issued = current_time('timestamp');

        // Generate the certificate PDF
        ob_start();
        $args = array(
            'user_id' => $user_id,
            'serial_number' => $serial_no,
            'course' => $this,
            'date_issued' => $date_issued
        );
        load_template(dirname(__FILE__) . "/certificate.php", true, $args);
        $html_content = ob_get_clean();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html_content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_output = $dompdf->output();

        // Generate a unique filename for the certificate
        $filename = sanitize_title($this->title) . '-' . time() . '.pdf';
        $filename = wp_unique_filename(COURSE_CERTIFICATE_DIR, $filename);

        // Save the certificate PDF to the server
        if (!file_put_contents(COURSE_CERTIFICATE_DIR . $filename, $pdf_output)) {
            return false; // Return false if file writing fails
        }

        // Update the wp_hkota_course_enrollment table to mark the certificate as issued
        $wpdb->update(
            $table_name,
            ['certificate_status' => 'issued'],
            ['user_id' => $user_id, 'course_id' => $this->id]
        );

        // Determine the organization issuing the certificate
        $organization = ($this->is_co_organized) ? 'HKOTA & ' . $this->co_organizer_title : 'HKOTA';

        // Update the wp_hkota_cpd_records table with certificate details
        $wpdb->update(
            $cpd_table,
            [
                'enrollment_id' => $enrollment_data->ID,
                'status' => 'issued',
                'date_issued' => date('Y-m-d', $date_issued ),
                'organization' => $organization,
                'file' => $filename
            ],
            [
                'course_id' => $this->id,
                'user_id' => $user_id
            ]
        );

        // Redirect the user to the certificate PDF file
        return $filename;

    } else {
        // User did not fully attend, return false
        return false;
    }
  }

  // create cpd record only for co-orginized event.
  public function create_cpd_record($user_id){
    global $wpdb;

    // check if user cert already issued then check if user is enrolled and has fully attended the course
    $enrollment_table = $wpdb->prefix . 'hkota_course_enrollment';
    $enrollment_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $enrollment_table WHERE user_id = %d AND course_id = %d", $user_id, $this->id
    ));

    // If enrollment data is missing, return false
    if (!$enrollment_data) {
        return 'enrollment data is missing';
    }

    $cpd_table = $wpdb->prefix . 'hkota_cpd_records';
    $cpd_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $cpd_table WHERE user_id = %d AND course_id = %d", $user_id, $this->id
    ));

    // If a cpd has already been issued, return false
    if ($cpd_record && $enrollment_data->certificate_status == 'issued' && $cpd_record->status == 'issued') {
        return 'cpd has already been issued';
    }

    // Unserialize the attendance data
    $attendance = maybe_unserialize($enrollment_data->attendance);

    // Ensure that the user fully attended the course
    if (isset($attendance['attendance_status']) && $attendance['attendance_status'] === 'fully_attended') {

      // Set the date issued to the current time
      $date_issued = current_time('timestamp');

      // Update the wp_hkota_course_enrollment table to mark the certificate as issued
      $wpdb->update(
          $enrollment_table,
          ['certificate_status' => 'issued'],
          ['user_id' => $user_id, 'course_id' => $this->id]
      );

      $wpdb->insert(
          $cpd_table,
          [
              'user_id' => $user_id,
              'course_id' => $this->id,
              'code'      => '',
              'serial_no' => '',
              'title' => $this->title,
              'enrollment_id' => $enrollment_data->ID, // Placeholder value
              'status' => 'issued', // Placeholder value
              'cpd_point' => $this->cpd_point,
              'date_issued' => date('Y-m-d', $date_issued ), // Placeholder value
              'organization' => ($this->cpd_issue_org)? $this->cpd_issue_org: '', // Placeholder value
              'file' => ''
          ]
      );

    } else {
        // User did not fully attend, return false
        return false;
    }
  }

  // Assign unique certificate serial number for user, and insert a placeholder row in cpd table
  public function assign_unique_certificate_serial_number($user_id) {
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'hkota_cpd_records';

    // Start a database transaction to avoid race conditions
    $wpdb->query('START TRANSACTION');

    // Find the last serial number assigned for the given course
    $last_serial = $wpdb->get_var($wpdb->prepare(
        "SELECT serial_no FROM $table_name WHERE course_id = %d AND serial_no LIKE %s ORDER BY serial_no DESC LIMIT 1 FOR UPDATE",
        $this->id, $this->cert_serial_prefix . '%'
    ));

    // Extract the last serial number part
    if ($last_serial) {
        // Get the numerical part of the last serial
        preg_match('/(\d+)$/', $last_serial, $matches);
        $last_serial_number = isset($matches[1]) ? intval($matches[1]) : 0;
    } else {
        // If no previous serial found, start from 0
        $last_serial_number = 0;
    }

    // Generate the next serial number
    $new_serial_number = str_pad($last_serial_number + 1, 4, '0', STR_PAD_LEFT);
    $new_serial = $this->cert_serial_prefix . '/' . $new_serial_number;

    // Insert a new row with the serial number to lock it
    $return = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'course_id' => $this->id,
            'code'      => $this->code,
            'serial_no' => $new_serial,
            'title' => $this->title,
            'enrollment_id' => 0, // Placeholder value
            'status' => 'pending', // Placeholder value
            'cpd_point' => $this->cpd_point,
            'date_issued' => 0, // Placeholder value
            'organization' => 0, // Placeholder value
            'file' => '' // Placeholder value
        ]
    );

    // Commit the transaction to lock the assignment
    $wpdb->query('COMMIT');

    if( $return ){
      return $new_serial;
    } else{
      return false;
    }
  }

  // Function to add a user to waiting list
  public function register_to_waiting_list($user_id) {

    $enrollment = create_enrollment();
    if(!$enrollment) return false; //unable to create enrollment object
    $enrollment->set('user_id',$user_id);
    $enrollment->set('course_id',$this->id);
    $enrollment->set('status','waiting_list');
    $enrollment->set('user_id',$user_id);
    $this->new_waiting_list_email($user_id);
    return $enrollment->id;

  }

  public function get_waiting_list() {
    global $wpdb;

    // Define the table name
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Query to get all rows where status is 'waiting_list' for the current course
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE course_id = %d AND status = %s",
        $this->id, 'waiting_list'
    ));

    // Return the results, or false if no waiting list entries were found
    if (!empty($results)) {
        return $results;
    } else {
        return false;
    }
  }

  // Sent email to user in waiting list and invite them to register.
  public function trigger_waiting_list_email() {

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Waiting list update: ' . $this->title . ' is now open to enroll.';
    ob_start();

    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'        => $this,
    );
    load_template( HKOTA_PLUGIN_DIR . '/email/waiting_list_notification.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();

    $results = $this->get_waiting_list();

    if(!$results) return;

    foreach ($results as $result) {
      $user = get_user_by('ID',$result->user_id);
      // Send the email
      $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

      if ( ! $sent ) {
          // Handle email not sent, you could log the error here
          error_log( 'Email failed to send to ' . $to );
      }
    }
  }

  // Sent email to user about his course status is enrolled
  public function trigger_enrolled_email($user_id) {

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] You have enrolled to course: ' . $this->title;
    ob_start();

    $user = get_user_by('ID', $user_id);
    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'        => $this,
      'user'          => $user
    );

    load_template( HKOTA_PLUGIN_DIR . '/email/course-enrollment-enrolled.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();

    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );
    }

  }

  // Sent email to user about his course status is awaiting approval
  public function trigger_awaiting_approval_email($user_id) {

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Pending enrollment approval for course: ' . $this->title;
    ob_start();

    $user = get_user_by('ID', $user_id);
    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'        => $this,
      'user'          => $user
    );

    load_template( HKOTA_PLUGIN_DIR . '/email/course-enrollment-awaiting_approval.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();

    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );
    }
  }

  // Sent email to user about his course status is pending
  public function trigger_pending_email($user_id) {

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Pending enrollment for course: ' . $this->title;
    ob_start();

    $user = get_user_by('ID', $user_id);
    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'        => $this,
      'user'          => $user
    );

    load_template( HKOTA_PLUGIN_DIR . '/email/course-enrollment-pending.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();

    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );
    }
  }

  // Sent email to user about his course status is rejected (status set by corn job)
  public function trigger_rejected_email($user_id) {

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Enrollment rejected for course: ' . $this->title;
    ob_start();

    $user = get_user_by('ID', $user_id);
    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'        => $this,
      'user'          => $user
    );

    load_template( HKOTA_PLUGIN_DIR . '/email/course-enrollment-rejected.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();

    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );

    }
  }

  // Sent email to user about his course status is rejected (status set by admin on beckend)
  public function trigger_admin_rejected_email($user_id) {

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Enrollment rejected for course: ' . $this->title;
    ob_start();

    $user = get_user_by('ID', $user_id);
    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'        => $this,
      'user'          => $user
    );

    load_template( HKOTA_PLUGIN_DIR . '/email/course-enrollment-admin-rejected.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();

    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );

    }
  }

  public function new_waiting_list_email($user_id){

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Waiting list of ' . $this->title . '.';
    $user = get_user_by('ID',$user_id);
    ob_start();

    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'   => $this,
      'user'  => $user
    );
    load_template( HKOTA_PLUGIN_DIR . '/email/new_waiting_list.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();


    // Send the email
    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );
    }

  }

  public function trigger_on_hold_email($user_id){

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Enrollment on hold for course: ' . $this->title . '.';
    $user = get_user_by('ID',$user_id);
    ob_start();

    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'   => $this,
      'user'  => $user
    );
    load_template( HKOTA_PLUGIN_DIR . '/email/course-enrollment-on_hold.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();


    // Send the email
    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );
    }

  }

  public function trigger_issue_certificate_email($user_id){

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = '[HKOTA] Certificate granted for course: ' . $this->title . '.';
    $user = get_user_by('ID',$user_id);
    ob_start();

    // Include the PHP file that contains the HTML email structure
    $args = array(
      'course'   => $this,
      'user'  => $user
    );
    load_template( HKOTA_PLUGIN_DIR . '/email/course-certificate-issued.php' ,true, $args );

    // Get the content from the buffer and clean the buffer
    $html_content = ob_get_clean();


    // Send the email
    $sent = wp_mail( $user->user_email, $subject, $html_content, $headers );

    if ( ! $sent ) {
        // Handle email not sent, you could log the error here
        error_log( 'Email failed to send to ' . $to );
    }

  }

}

































?>
