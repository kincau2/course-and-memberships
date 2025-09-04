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
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/logo-wz-text.png' ) ?>" alt="HKOTA logo">
        </div>

        <!-- Main Content Section -->
        <h1>COURSE WAITING LIST NOTIFICATION</h1>
        <p>Dear <?php echo $user->last_name.', '. $user->first_name; ?>,</p>
        <p>We would like to inform you that you have been placed on the waiting list for the <strong><?php echo $course->title ?></strong>.</p>
        <p>Should seats become available, we will notify you promptly at this email address.
          Please note that any openings will be offered on a first-come, first-served basis.</p>
        <p>If you have any questions or require further assistance, please feel free to contact <?php echo $course->contact; ?></p>
        <p>Thank you for your understanding and patience.</p>
        <p>Sincerely,</p>
        <p>Hong Kong Occupational Therapy Association</p>


        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
