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
        <p>We regret to inform you that your enrollment in the following course has been rejected:</p>
        <p><b><?php echo $course->title ;?></b></p>
        <p>This decision was made because your membership application remains unapproved after
          five days from the submission date. As per our policy, enrollments for members-only
          courses must be confirmed <b>within five days</b> of membership approval.</p>
        <p>If you believe this is an error, or if your membership status has recently been updated,
           please contact us for further assistance.</p>
        <p>Should you wish to reapply for the course, we encourage you to ensure your membership
          application is completed and approved before proceeding.</p>
        <p>If you have any questions or need further assistance, please contact <?php echo $course->contact; ?></p>
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
