<?php
/**
 * Course Payment Request Email Template
 * 
 * @var Course $course
 * @var WP_User $user  
 * @var WC_Order $order
 * @var string $payment_url
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$course  = $args['course'];
$user = $args['user'];
$order = $args['order'];
$payment_url = $args['payment_url'];    

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Required - Course Registration</title>
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
        <p>You have been registered for the following course by HKOTA. To complete your enrollment, please make payment using the link below:</p>

        <p>Course Details:</p>
        <p><b><?php echo $course->title ;?></b><br>
            <span class="title">Date:</span><span class="value"><?php echo $course->createDateString() ;?></span><br>
            <span class="title">Time:</span><span class="value"><?php echo $course->createTimeString() ;?></span><br>
            <span class="title">Venue:</span><span class="value"><?php echo $course->venue ;?></span>
            <span class="title">CPD Points:</span><span class="value"><?php echo $course->cpd_point ;?></span>
            <span class="title">Fee:</span><span class="value">HK$<?php echo number_format($order->get_total(), 0); ?></span>
        </p>

        <p>Payment Details:</p>
        <p>To complete your registration, please click the button below to make payment:</p>
        <div class="button-container">
            <a href="<?php echo esc_url($payment_url); ?>" class="payment-button">
                Complete Payment - HK$<?php echo number_format($order->get_total(), 0); ?>
            </a>
        </div>
        <p class="payment-note">
            <small>Order #<?php echo $order->get_order_number(); ?> | 
            This payment link is secure and will expire if not used within a reasonable time.</small>
        </p>

        <p>If you have any questions or need further assistance, please feel free to contact <?php echo $course->contact; ?></p>
        <p>Thank you for your participation in this course. We look forward to seeing you there!</p>
        <p>Sincerely,<br>Hong Kong Occupational Therapy Association</p>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Hong Kong Occupational Therapy Association. All rights reserved.</p>
        </div>
    </div>

    <style>
        .payment-button {
            display: inline-block;
            background-color: #008080;
            color: white !important;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
        }
        .payment-button:hover {
            background-color: #0052a3;
            text-decoration: none;
        }
        .button-container {
            text-align: center;
            margin: 20px 0;
        }
        .course-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #0066cc;
        }
    </style>
</body>
</html>
