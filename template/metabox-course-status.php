<?php

global $post;
$course = new Course($post->ID);
if( $course->type == 'training' || $course->type == 'co-organized-event' ){
    include_once HKOTA_PLUGIN_DIR . '/template/course-dashboard.php';
    echo "<br>";
    include_once HKOTA_PLUGIN_DIR . '/template/pupil-detail-popup.php';
?>
    <button id="show-pupil-details" type="button" class="button button-primary"  data-course-id="<?php echo $post->ID; ?>">Show Pupil Details</button>
    <button id="download-quiz-button" type="button" class="button button-primary" data-course-id="<?php echo $post->ID ;?>" >Download Quiz csv</button>
    <button id="download-survey-button" type="button" class="button button-primary" data-course-id="<?php echo $post->ID ;?>" >Download Survey csv</button>
    <button id="download-pupil-button" type="button" class="button button-primary" data-course-id="<?php echo $post->ID ;?>" >Download Pupil csv</button>
    <button id="download-attendance-button" type="button" class="button button-primary" data-course-id="<?php echo $post->ID ;?>" >Download Attendance csv</button>
<?php
} else {
echo 'Course status not available for this course type.';
}

?>