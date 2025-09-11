<?php

if (!defined("ABSPATH")) {
  exit();
}

$course_id = $_GET['course_id'];
$course = new Course( $course_id );

// Load Chinese font for Traditional Chinese character support
$chinese_font_config = include(HKOTA_PLUGIN_DIR . '/asset/fonts/noto-sans-tc-base64.php');

?>

    <html>
      <head>
        <style>

        </style>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.cdnfonts.com/css/roman-new-times" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Noto+Sans+TC:wght@100..900&display=swap" rel="stylesheet">

        <style>

          <?php 
          // Add Chinese font support
          if ($chinese_font_config['type'] === 'web_font') {
              echo $chinese_font_config['css'];
          } elseif ($chinese_font_config['type'] === 'base64' && !empty($chinese_font_config['data'])) {
              echo '@font-face {
                  font-family: "NotoSansTC";
                  src: url(data:font/truetype;charset=utf-8;base64,' . $chinese_font_config['data'] . ') format("truetype");
                  font-weight: normal;
                  font-style: normal;
              }';
          }
          ?>

          @page{
            /* size: 21cm 29.7cm; */
            margin: 0;
            padding: :0;
          }
          body{
            font-family: "Noto Sans TC", 'NotoSansTC', "DM Serif Display", serif;
            font-optical-sizing: auto;
            background: :#FFF;
            border:unset!important;
          }
          .noto-sans-tc {
            font-family: "Noto Sans TC", 'NotoSansTC', serif!important;
            font-style: normal;
            vertical-align: top;
          }
          html{
            margin: 1cm;
          }
          .page-wrapper{
            width: : 678px;
            height: 1000px;
          }
          .text{
            vertical-align: middle;
            word-wrap: break-word;
            width: 100%
          }
          h1{
            font-size: 22px!important;
            margin: 0 0 10px 0!important;
          }
          h4{
            font-size: 16px!important;
            margin: 2px 0 0 0!important;
          }
          .text-center{
            text-align: center!important;
          }
          .green{
            color: #53a27f!important;
          }
          .italic{
            font-style: italic!important;
          }
          .purple{
            color: #6d2669!important;
          }
          .inline{
            display: inline-block;
            vertical-align: top;
          }
          .spacer{
            height: 20px;
          }
          .red{
            color:red;
          }
          .dark-green{
            color: #4fad5b;
          }

          .heading-wrapper{
            margin-bottom: 40px;
          }

          .logo-left{
            display:inline-block;
            width:100px;
            padding: 0 10px 0 0;
            vertical-align: top;
          }
          .logo-right{
            display:inline-block;
            width:100px;
            padding: 0 0 10px 0;
            vertical-align: top;
          }
          .course-title{
            display:inline-block;
            <?php echo ($course->is_co_organized)? "max-width: 438px;" : "max-width: 550px;" ;?>
            <?php echo ($course->is_co_organized)? "padding: 20px 10px 0 10px;" : "padding: 20px 0 0 10px;" ;?>
            vertical-align: top;
            line-height: 18px;
          }
          .info-wrapper{
            page-break-inside: avoid !important;
          }
          .info-wrapper span,.info-wrapper p{
            line-height: 18px!important;
          }
          .info-wrapper .inline:first-child{
            min-width: 80px!important;
          }
          .info-wrapper .inline:last-child{
            max-width: 590px!important;
          }
          .info-type{
            font-size: 18px!important;
            margin-right: 5px;
            width: 100px!important;
            font-weight: 600!important;
            margin-top: 50px!important;
          }
          .info-type-small{
            font-size: 16.5px!important;
            margin-right: 5px;
            width: 100px!important;
            font-weight: 600!important;
            margin-top: 5px!important;
          }
          .info-data{
            font-size: 16.5px!important;
            word-wrap: break-word;
            font-weight: 500!important;
            margin-bottom: 0px!important;
            margin-top: 0px!important;
          }
          table {
            border-collapse: collapse!important;
            width: 100%!important;
          }
          table, tr, td, th {
            border: 0.7px solid;
            font-size: 16.5px!important;
          }
          table.fee{
            font-weight: 500!important;
            line-height: 25px!important;
          }
          table.rundown{
            line-height: 22px!important;
          }

          table.fee td:first-child, th:first-child{
            padding-left: 10px;
          }
          table.rundown td{
            padding-left: 5px;
          }

          .alert-box{
            background: #dedddd;
            border:0.7px solid #000;
            width: 100%;
            height: fit-content;
            padding: 10px;
            page-break-inside: avoid;
          }
          .alert-box p{
            font-size: 20px;
            margin:unset!important;
            vertical-align: middle;
            line-height: 25px;
            font-weight: 500;
          }



        </style>
      </head>
      <body>
        <!-- <div class="page-wrapper"> -->
          <div class="heading-wrapper">
            <?php

            $image_path = HKOTA_PLUGIN_DIR . '/asset/hkota_logo.png';
            if (file_exists($image_path)) {
                $image_data = file_get_contents($image_path);
                $file_info = getimagesize($image_path);
                $mime_type = $file_info['mime'];
                $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
            }

            ?>
            <img class="logo-left" src="<?php echo $base64; ?>" width="100px">
            <div class="course-title text-center">
              <h1 class="text-center mb-10"><?php echo $course->title ;?></h1>
              <h4 class="text-center green">(Course Code:<?php echo $course->code ;?>)</h4>
              <h4 class="text-center green italic">Organized by: Hong Kong Occupational Therapy Association Ltd</h4>
              <?php
                if($course->is_co_organized){
                  ?>
                    <h4 class="text-center green italic">Co-organized by: <?php echo $course->co_organizer_title ;?></h4>
                  <?php
                }
              ?>
            </div>
            <?php
              if($course->is_co_organized){
                $image_path = COURSE_FILE_DIR . $course->co_organizer_logo ;
                if (file_exists($image_path)) {
                    $image_data = file_get_contents($image_path);
                    $file_info = getimagesize($image_path);
                    $mime_type = $file_info['mime'];
                    $base64 = 'data:'.$mime_type.';base64,' . base64_encode($image_data);
                }
                ?>
                  <img class="logo-right" src="<?php echo $base64 ;?>" width="100px">
                <?php
              }

              $dates = $course->createDateString();
              $time = $course->createTimeString();

            ?>
          </div>
          <div class="info-wrapper">
            <div class="inline"><span class="info-type purple">Date: </span></div>
            <div class="inline"><span class="info-data "><?php echo $dates ;?></span></div>
          </div>
          <div class="info-wrapper">
            <div class="inline"><span class="info-type purple">Time: </span></div>
            <div class="inline"><span class="info-data"><?php echo $time ;?></span></div>
          </div>
          <div class="info-wrapper">
            <div class="inline"><span class="info-type purple">Venue: </span></div>
            <div class="inline noto-sans-tc"><span class="info-data"><?php echo $course->venue ;?></span></div>
          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <span class="info-type purple">Target Participants: </span><br>
            <p class="info-data"><?php echo nl2br(esc_html($course->target_participants)) ;?></p>
          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <span class="info-type purple">Capacity: </span><br>
            <p class="info-data">
              <?php
                echo $course->capacity;
                if( !empty($course->capacity_remarks) ){
                  echo " (" . nl2br(esc_html($course->capacity_remarks)) . ")" ;
                }
              ?>
            </p>
          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <span class="info-type purple">Speaker: </span><br>
            <p class="info-data"><?php echo $course->speaker ;?> </p>
          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <span class="info-type purple">Course Fee: </span><br>
            <?php

            if( $course->is_early_bird ):

              ?>
                <table class="early-table fee" style="line-height: 18px!important;">
                  <tr>
                    <th style="width:140px"></th>
                    <th style="width:220px" class="text-center">Early bird
                      <br>(before
                      <?php
                        if( empty( $course->early_bird_enddate ) ){
                          echo '';
                        } else{
                          echo date( "jS F Y", strtotime($course->early_bird_enddate) ) ;
                        }
                      ?>)
                    </th>
                    <th style="width:220px" class="text-center">Regular</th>
                  </tr>
                  <tr>
                    <td >HKOTA members</td>
                    <td class="text-center"><?php echo "HKD " . $course->fee_member_earlybird ;?></td>
                    <td class="text-center"><?php echo "HKD " . $course->fee_member ;?></td>
                  </tr>
                  <?php if( !$course->is_member_only ): ?>
                    <tr>
                      <td >Non-HKOTA members</td>
                      <td class="text-center"><?php echo "HKD " . $course->fee_non_member_earlybird ;?></td>
                      <td class="text-center"><?php echo "HKD " . $course->fee_non_member ;?></td>
                    </tr>
                  <?php endif; ?>
                </table>
              <?php


            elseif( empty($course->fee_member) && empty($course->fee_non_member) ):
              ?>
                <p class="info-data"><?php echo "Free of charge." ;?> </p>
              <?php

            else:
              ?>
                <table class="regular-table fee">
                  <tr>
                    <td style="width:50%">HKOTA members</td>
                    <td style="width:50%" class="text-center"><?php echo "HKD " . $course->fee_member ;?></td>
                  </tr>
                  <?php if( !$course->is_member_only ): ?>
                    <tr>
                      <td>Non-HKOTA members</td>
                      <td class="text-center"><?php echo "HKD " . $course->fee_non_member ;?></td>
                    </tr>
                  <?php endif; ?>

                </table>
              <?php

            endif;

            ?>

          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <span class="info-type-small purple">For enquiries and registration, please contact: </span><br>
            <p class="info-data"><?php echo $course->contact ;?> </p>
          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <div class="inline"><span class="info-type-small purple">Deadline for application: </span></div>
            <div class="inline">
              <span class="info-data red">
                <?php
                  if( empty( $course->deadline ) ){
                    echo '';
                  } else{
                    echo date( "jS F Y", strtotime($course->deadline) ) ;
                  }
                ?>
              </span>
            </div>
          </div>
          <div class="info-wrapper">
            <div class="inline"><span class="info-type-small purple">Enrollment confirmation date: </span></div>
            <div class="inline">
              <span class="info-data">
                <?php
                  if( empty( $course->confirmation_date ) ){
                    echo '';
                  } else{
                    echo date( "jS F Y", strtotime($course->confirmation_date) ) ;
                  }
                ?>
              </span>
            </div>
          </div>
          <div class="spacer"></div>
          <div class="alert-box">
            <p class="text-center">
              Deadline of application
              <?php
                if( empty( $course->deadline ) ){
                  echo '';
                } else{
                  echo date( "jS F Y", strtotime($course->deadline) ) ;
                }
              ?>
              <?php echo $course->is_early_bird? "<br>(" . date( "jS F Y", strtotime($course->early_bird_enddate) ). " for early bird)" : "" ;?>
            </p>
            <p class="dark-green text-center">HKOTA CPD: <?php echo $course->cpd_point ;?> points</p>
          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <span class="info-type-small purple">Remarks: </span><br>
            <p class="info-data"><?php echo $course->remarks ;?> </p>
          </div>
          <div class="spacer"></div>
          <div class="info-wrapper">
            <span class="info-type-small purple">Programme Rundown: </span><br>
            <div class="spacer"></div>
            <?php echo $course->generateRundownTable() ;?>
          </div>










        </div>




        <!-- </div> -->
  		</body>
    </html>






















<?php
