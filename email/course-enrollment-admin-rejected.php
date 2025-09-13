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
        <p>We regret to inform you that your enrollment in the following course has been <strong>rejected</strong>:</p>
        <p><b><?php echo $course->title ;?></b></p>
        <p>This action was taken because we believe you do not meet the enrollment requirment of this course.</p>
        <p>If you believe this is an error, please contact us for further assistance. Here are the contacts:</p>
        <p><?php echo $course->contact; ?></p>
        <p>Thank you for your understanding.</p>

        <p>Sincerely,</p>
        <p>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
