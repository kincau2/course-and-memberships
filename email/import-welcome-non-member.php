<?php

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
    exit();
}

$user = $args['user'];
$reset_url = $args['reset_url'];
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
        <h1>Welcome to Hong Kong Occupational Therapy Association</h1>
        <!-- Main Content Section -->
        <p>As per you recent course application, we have created an account for you on our website.</p>

        <p>Your Login ID will be: <?php echo $user->user_email ;?></p>

        <p>To access your account, please click the link below to set your password:</p>

        <p><a class="ical" href="<?php echo $reset_url ;?>">Reset Password</a></p>

        <p><b>Important: First Login Instructions</b></p>

        <p>Upon your first login, you will be prompted to enter your last name and first name
          exactly as they appear on your official personal identification document. This information
          is crucial, as it will be used for issuing certifications for any future courses you may
          complete with HKOTA. Please double-check your entries, as you will not be able to edit
          this information in the system later.</p>

        <p>Thank you for your cooperation and understanding as we transition to this improved system.
          We look forward to continuing to support your professional growth and development.</p>

        <p>Should you have any questions or need assistance, please feel free to contact us at</p>

        <div class="contact-buttons">
            <a href="mailto:hkotaeomail@gmail.com" class="email-button">Email</a>
            <a href="https://wa.me/85254066878" class="whatsapp-button">Whatsapp</a>
        </div>

        <p>Thank you for your attention.</p>
        <p>Sincerely,<br>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
