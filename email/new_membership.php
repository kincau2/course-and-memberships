<?php

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
    exit();
}

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
        <h1>NEW MEMBERSHIP APPLICATION RECEIVED</h1>
        <!-- Main Content Section -->
        <p>Dear <?php echo $user->last_name.', '. $user->first_name ;?>,</p>
        <p>Thank you for submitting your application for official membership with the Hong Kong
          Occupational Therapy Association (HKOTA). We are currently reviewing your application.</p>
        <p>In the meantime, you are welcome to apply for courses exclusive to members.
          Please note that the enrollment status for these courses will remain <b>PENDING</b> until your
          membership is formally approved.</p>
        <p>Once your membership application is approved, you will receive a separate email
          confirming your membership status, along with additional information regarding your
          enrollment in any courses you have applied for.</p>
        <p>If you have any questions or need further assistance, please feel free to contact us at:</p>
        <div class="contact-buttons">
            <a href="mailto:hkotaeomail@gmail.com" class="email-button">Email</a>
            <a href="https://wa.me/85254066878" class="whatsapp-button">Whatsapp</a>
        </div>
        <p>Thank you for your attention, and we appreciate your interest in joining HKOTA.</p>
        <p>Sincerely,<br>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
