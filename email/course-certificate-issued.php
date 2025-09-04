<?php

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
    exit();
}

$course  = $args['course'];
$user = $args['user'];
$args = [
  'user_id'   => $user->ID,
  'course_id' => $course->id,
];
$cpd_record = get_user_cpd_record_by('course_id', $args );

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include dirname(__FILE__) . '/email_styles.php'; ?> <!-- Include the style file here -->
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/logo-wz-text.png' ) ?>" alt="HKOTA logo">
        </div>
        <h1>YOUR CERTIFICATE HAS BEEN GRANTED</h1>
        <!-- Main Content Section -->
        <p>Dear <?php echo $user->last_name.', '. $user->first_name ;?>,</p>
        <p>We are pleased to inform you that your certificate for the
          <b><?php echo $course->title ;?></b> has been successfully issued.</p>

        <p>You can download your certificate using the link below:</p>
        <p><a class="ical" href="<?php echo COURSE_CERTIFICATE_URL .$cpd_record->file ;?>">View</a></p>
        <p>Additionally, your updated CPD record is available for your review:</p>
        <a style="color:#008080;" href="<?php echo home_url('/my-account/cpd') ;?>">View CPD Record</a>
        <p>If you have any questions or need further assistance, please feel free to contact <?php echo $course->contact; ?></p>
        <p>Thank you for your participation, and we look forward to seeing you again in future courses.</p>
        <p>Sincerely,<br>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
