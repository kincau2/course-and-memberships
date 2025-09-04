<?php

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
    exit();
}

$course  = $args['course'];
$user = $args['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include dirname(__FILE__) . '/email_styles.php'; ?> <!-- Include the style file here -->
</head>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/logo-wz-text.png' ) ?>" alt="HKOTA logo">
        </div>
        <h1>COURSE ENROLLMENT STATUS UPDATE</h1>
        <!-- Main Content Section -->
        <p>Dear <?php echo $user->last_name.', '. $user->first_name ;?>,</p>
        <p>Thank you for your application to <b><?php echo $course->title ;?></b></p>
        <p>We are now reviewing your uploaded certificate, as this course requires the submission of
          a certificate for completion of a prerequisite course, your enrollment status is currently
           set to <b>Awaiting Approval</b>.</p>
        <p>No further action is required at this stage. Our team will process your application as
          soon as possible. You will be notified accordingly once we have finished the process.</p>
        <p>You enrollment status will be updated to confirmed afterward.</p>
        <p>If you have any questions or need further assistance, please contact <?php echo $course->contact; ?></p>
        <p>Thank you for your patience and understanding.</p>

        <p>Sincerely,</p>
        <p>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
