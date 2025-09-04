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
        <h1>Course Enrollment Status Update</h1>
        <!-- Main Content Section -->
        <p>Dear <?php echo $user->last_name.', '. $user->first_name ;?>,</p>
        <p>Thank you for your application to the <b><?php echo $course->title ;?></b></p>
        <p>Your enrollment status is currently <b>On Hold</b> while we await confirmation
          of your payment. Once your payment is verified, we will proceed with processing
          your application.</p>
        <p>Please refer to your order email for the payment details.</p>
        <p>If you have any questions or require further assistance, feel free to contact <?php echo $course->contact; ?></p>

        <p>Thank you for your participation in this course.</p>

        <p>Sincerely,</p>
        <p>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
