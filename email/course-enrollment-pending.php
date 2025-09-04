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
        <h1>COURSE ENROLLMENT STATUS UPDATE</h1>
        <!-- Main Content Section -->
        <p>Dear <?php echo $user->last_name.', '. $user->first_name ;?>,</p>
        <p>Thank you for your interest in our members-only courses at the Hong Kong Occupational
          Therapy Association (HKOTA). We would like to inform you that your enrollment in the
          following course is currently pending approval, as your membership application is
          still under review:</p>
        <p>Course(s) Applied For:</p>
        <p><b><?php echo $course->title ;?></b></p>
        <p>Once your membership application has been approved, your enrollment status will be
          updated automatically, and you will receive a confirmation email regarding your
          successful enrollment.</p>
        <p>We appreciate your patience during this process and will keep you informed of any
          updates. If you have any questions or need further assistance, please feel free to
          contact <?php echo $course->contact; ?></p>
        <p>Thank you for your understanding.</p>
        <p>Sincerely,<br>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
