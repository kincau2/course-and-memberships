<?php

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
    exit();
}


$user_id = $args['user_id'];
$course = $args['course'];
$serial_number = $args['serial_number'];
$date_issued = date( "jS F, Y", $args['date_issued'] + 28800 );
$is_preview = false;
if( isset($args['preview']) ){
  $is_preview = $args['preview'];
}

$image_path = HKOTA_PLUGIN_DIR . '/asset/cert-outline.png';
if (file_exists($image_path)) {
    $image_data = file_get_contents($image_path);
    $file_info = getimagesize($image_path);
    $mime_type = $file_info['mime'];
    $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
}

?>

<html>
  <head>
    <style>

    @page {
      size: 29.7cm 21cm ;
      margin: 0;
      padding: :0;
    }
    body { margin: 0px; }
    html { margin: 0px}

    .e-cert-background{
      position: fixed;
      top:0px;
      left: :0px;
      height: 100%;
      width: 100%;

    }

    .content-wrapper{
      position: relative;
      text-align: center;
      margin:100px;
      font-size: 18px;
      line-height: 35px;
      font-weight: 400;
      display: flex;
      align-content: center;
      align-items: center;
      justify-content: center;
      height: 100%;

    }

    .logo-wrapper{
      width: 100%;
      <?php echo ($course->is_co_organized)? 'padding: 23px 0 17px 0;' : 'padding: 10px 0 30px 0;'  ;?>
    }

    .logo-wrapper img{
      width: auto;
      max-height: 135px;
      display: inline-block;
      margin: 0 20px;
      vertical-align: middle;

    }

    .Title{
      font-size: 30px;
      font-weight: 900;
      margin: 0 0 10px 0;
    }

    .attendee-title{
      font-size: 35px;
      width: 400px;
      margin: 15px auto 5px auto;
      border-bottom: 1px solid #000;
    }

    .event-title{
      font-weight: 600!important;
      font-size: 20px!important;
    }

    .event-title-wrapper{
        margin-left: auto;
        margin-right: auto;
        width: 750px;
    }

    .footer{
      position: absolute;
      bottom: 160px;
      margin: 0 10px 0 10px;
      width: 100%;
    }

    .event-organizer{
      text-align: left!important;
      display: inline-block;
      width: 36%;
      line-height: 18px!important;
      font-size:14px;
    }

    .signature-warpper{
      text-align: center!important;
      display: inline-block;
      width: fit-content;
      padding: 0 0.5%;
      width: 29%;
    }

    .e-cert-number{
      text-align: right!important;
      position: absolute;
      top: -10px;
      right: 10px;
      width: 400px;
      line-height: 25px!important;
    }

    .signature{
      height: 50px;
      width: auto;
    }

    .underline{
      border-bottom: 1px solid #000;
    }

    .signee-title{
      line-height: 18px!important;
  font-size:14px;
    }


    </style>
  </head>
  <body>
    <img height="100%" width="100%" style="position:fixed;top:0px;left:0px" src="<?php echo $base64;?>" />
    <div class="e-cert-background">
      <div class="content-wrapper">
        <div class="logo-wrapper">
          <img height="150px" width="150px"
             src="<?php
             $image_path = HKOTA_PLUGIN_DIR . '/asset/hkota_logo.png';
             if (file_exists($image_path)) {
                 $image_data = file_get_contents($image_path);
                 $file_info = getimagesize($image_path);
                 $mime_type = $file_info['mime'];
                 $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
             }
             echo $base64;
             ?>" />
          <?php
            if($course->is_co_organized):
              $image_path = COURSE_FILE_DIR . $course->co_organizer_logo ;
              if (file_exists($image_path)) {
                  $image_data = file_get_contents($image_path);
                  $file_info = getimagesize($image_path);
                  $mime_type = $file_info['mime'];
                  $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
              }
              ?>
              <img height="150px" width="150px"
                 src="<?php echo $base64 ?>" />
              <?php
            endif;
          ?>
        </div>
        <div class="e-cert-number">Serial no.
          <?php echo $serial_number; ?>
          <br>Issued on:
          <?php echo $date_issued ?>
        </div>
        <div class="Title"><?php echo $course->cert_heading ;?></div>
        <div>This is to certify that</div>
        <div class="attendee-title">
          <?php
            if($is_preview){
              echo 'Chan, Tai Man Peter' ;
            } else{
              $user = get_user_by('ID',$user_id);
              echo $user->last_name . ', ' . $user->first_name ;
            }
          ?>
        </div>
        <div>had attended</div>
        <div class="event-title-wrapper">the
          <span class="event-title">
            <?php
              if( empty($course->cert_title) ){
                echo $course->title;
              } else{
                echo nl2br( esc_html( $course->cert_title ) );
              }
            ?>
          </span>
        </div>
        <div class="event-code">
          (course code: <?php echo $course->code ;?>) on
          <?php
            echo $course->createDateString();
          ?>
        </div>
        <div > CPD: <?php echo $course->cpd_point ;?> </div>
        <div class="footer">
          <div class="event-organizer">
            <b>Organized by:</b> <br>
            Hong Kong Occupational Therapy Association Limited
            <?php
              if( $course->is_co_organized ){
                echo '<br><b>Co-Organized by: </b><br>';
                echo $course->co_organizer_title;
              }
            ?>
          </div>
          <div class="signature-warpper" <?php echo ($course->is_second_signee)? '' : 'style="visibility:hidden;"' ;?>>
            <?php
              $image_path = COURSE_FILE_DIR . $course->cert_signature_2 ;
              if (file_exists($image_path)) {
                  $image_data = file_get_contents($image_path);
                  $file_info = getimagesize($image_path);
                  $mime_type = $file_info['mime'];
                  $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
              }
             ?>
            <img class="signature" style="height:70px;" src="<?php echo $base64 ;?>" />
            <div class="underline"></div>
            <div class="signee-title"><?php echo nl2br(esc_html($course->cert_signee_2)) ;?></div>
          </div>
          <div class="signature-warpper">
            <?php
              $image_path = COURSE_FILE_DIR . $course->cert_signature_1 ;
               if (file_exists($image_path)) {
                   $image_data = file_get_contents($image_path);
                   $file_info = getimagesize($image_path);
                   $mime_type = $file_info['mime'];
                   $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
               }
            ?>
            <img class="signature" style="height:70px;" src="<?php echo $base64 ;?>" />
            <div class="underline"></div>
            <div class="signee-title"><?php echo nl2br(esc_html($course->cert_signee_1)) ;?></div>
          </div>
      </div>
      </div>
    </div>
</body>
</html>






































<?php ?>
