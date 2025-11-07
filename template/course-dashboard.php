<?php

// @param Class Course object

if( $course->type == 'training' && !$course->capacity ){
      echo "<p>Current course capacity is zero, dashboard is disabled.</p>";
      return;
}

global $wpdb;
$table = $wpdb->prefix . 'hkota_course_enrollment';

// Query for enrollment data (enrolled, pending, awaiting_approval)
$enrollment_data = $wpdb->get_results($wpdb->prepare(
    "SELECT status, COUNT(*) as count FROM $table WHERE course_id = %d AND status IN ('enrolled', 'pending', 'awaiting_approval','on_hold') GROUP BY status",
    $course->id
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

if( $course->type == 'co-organized-event' && $used_spots == 0 ){
    echo "<p>Current course has no enrollment data, dashboard is disabled.</p>";
    return;
}

switch ( $course->type ) {
    case 'training':
    $available_count = $course->capacity - $used_spots;
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
    $course->id
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
    $course->id
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
