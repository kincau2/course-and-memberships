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
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="<?php echo plugins_url( '/hkota-courses-and-memberships/asset/logo-wz-text.png' ) ?>" alt="HKOTA logo">
        </div>
        <h1>We have renewed your membership</h1>
        <!-- Main Content Section -->
        <p>Dear <?php echo $user->last_name.', '. $user->first_name  ;?>,</p>
        <p>We are pleased to inform you that we have renewed your official membership
          with the Hong Kong Occupational Therapy Association (HKOTA).</p>
        <p>As an official member, you now have full access to the benefits provided by HKOTA,
          including eligibility for exclusive members-only courses and events.
          We will automatically confirm your enrollment status for any courses you previously applied for as a pending applicant.</p>
        <p>If you have any questions regarding your membership, enrollment, or require further
          assistance, please don’t hesitate to contact us via email or WhatsApp:</p>
        <div class="contact-buttons">
            <a href="mailto:hkotaeomail@gmail.com" class="email-button">Email</a>
            <a href="https://wa.me/85254066878" class="whatsapp-button">Whatsapp</a>
        </div>
        <p>Thank you once again for joining the Hong Kong Occupational Therapy Association.
          We look forward to your active participation.</p>
        <p>Sincerely,<br>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
