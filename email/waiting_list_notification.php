<?php

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
    exit();
}

$course = $args['course'];

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
        <p>We are pleased to inform you that a seat has become available for the <strong><?php echo $course->title ?></strong>.</p>
        <p>To secure your place in the course, please visit the course section of our official website using the link below:</p>

        <!-- Invitation Button -->
        <div style="text-align: left;">
            <a href="<?php echo home_url('/trainings-and-activities') ?>" class="button">Course List</a>
        </div>

        <p>Please note that available spots are allocated on a first-come, first-served basis,
          so we encourage you to act quickly.</p>
        <p>If you have any questions or need further assistance, feel free to contact <?php echo $course->contact ?></p>
        <p>We look forward to welcoming you to the course!</p>
        <p>Sincerely,</p>
        <p>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
