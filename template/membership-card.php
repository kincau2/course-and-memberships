<?php

// If this file is called directly, abort.
if (!defined("ABSPATH")) {
    exit();
}


$title = $args['title'];
$plan = $args['plan'];
$membership_number = $args['membership_number'];

?>

<html>
  <head>
    <style>

    @page {
      size: 8.5cm 5.5cm ;
      margin: 0;
      padding: :0;
    }
    body { margin: 0px; }
    html { margin: 0px}

    .e-cert-background{
      position: fixed;
      height: 187px!important;
      width: 303px!important;
      top: 0px;
      left:0px;
      border: 10px solid #008080;
      margin: 0px;
    }

    .content-wrapper{
      position: relative;
      margin:10px;
      font-size: 18px;
      font-weight: 400;
      width: 100%
      height: 100%;
      overflow: hidden;
      font-family: Calibri;
    }

    .logo-wrapper{
      width: 100%;
    }

    .logo-wrapper img{
      display: inline-block;
      height:50px;
      width: auto;
    }

    .plan{
      text-align: right;
      color: #008080;
      margin-top: 10px;
    }
    .title{
    }
    .membership-number{
      font-size: 12px;
    }

    </style>
  </head>
  <body>
    <?php
      $image_path = HKOTA_PLUGIN_DIR . '/asset/card-background.jpg';
      if (file_exists($image_path)) {
          $image_data = file_get_contents($image_path);
          $file_info = getimagesize($image_path);
          $mime_type = $file_info['mime'];
          $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
      }
    ?>
    <img height="100%" width="100%" style="position:fixed;top:0px;left:0px" src="<?php echo $base64;?>" />
    <div class="e-cert-background">
      <div class="e-cert-deco"></div>
      <div class="content-wrapper">
        <div class="logo-wrapper">
          <?php
            $image_path = HKOTA_PLUGIN_DIR . '/asset/logo-wz-text.png';
            if (file_exists($image_path)) {
                $image_data = file_get_contents($image_path);
                $file_info = getimagesize($image_path);
                $mime_type = $file_info['mime'];
                $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
            }
          ?>
          <img src="<?php echo $base64 ;?>" />
        </div>
        <div class="plan"><?php echo $plan ;?></div>
        <div class="title"><?php echo $title ;?></div>
        <div style="height:5px;"></div>
        <?php
          $pos = strpos( $membership_number , '~');
          $prefix = substr( $membership_number, 0 , $pos + 1 );
          $suffix = substr( $membership_number, $pos + 1 );
          $valid = explode("-",$suffix);
        ?>
        <div class="membership-number">
          <div ><span >Membership Number: </span><?php echo $prefix.$suffix ;?> </div>
          <div><span style="padding-right:33px;">Valid through</span>
            <?php
              echo ': 1 May 20'. $valid[0] . ' - 30 Apr 20' . $valid[1];
            ?>
          </div>
        </div>
      </div>
      </div>
    </div>
</body>
</html>






































<?php ?>
