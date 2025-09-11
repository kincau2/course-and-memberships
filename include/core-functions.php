<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\TcpdfFpdi;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use transloadit\Transloadit;



// Shortcode to display the CSV upload form
add_shortcode('debug', 'display_debug_message');

function display_debug_message(){

  $args = array(
        'status' => array('active','expired','paused'),
    );

  $user_memberships = wc_memberships_get_user_memberships(1, $args);
  $membership_plan = wc_memberships_get_membership_plan(129);
  echo "<pre>";
	echo print_r($membership_plan);
	echo "</pre>";

	echo "<pre>";
	echo print_r(get_transient('debug'),1);
	echo "</pre>";
}

add_shortcode('login-button-message', 'login_button_message');

function login_button_message(){
	if( is_user_logged_in() ){
		echo 'My Account';
	}else{
		echo 'Member Login';
	}
}

add_shortcode('redirect', 'redirect');

function redirect( $atts = array() ) {

	$atts = shortcode_atts(array(
			'url' => '',
	), $atts);

	if( !empty($atts['url']) )
	wp_safe_redirect( $atts['url'] );
	exit;

}

// Shortcode to display the CSV upload form
add_shortcode('import_user_membership', 'import_user_membership_form');

function import_user_membership_form(){

	?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-csv/1.0.21/jquery.csv.js" integrity="sha512-2ypsPur7qcA+2JjmmIJR1c4GWFqTLIe1naXXplraMg0aWyTOyAMpOk+QL+ULpzwrO/GdwA3qB3FhVyyiR8gdhw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <div id="msg-hook" style=" display: none; ">
    <div id="error-notice"></div>
    <div id="respond-message"></div>
  </div>
    <form  id="csv-file-upload" class="membership-form" method="post"  enctype="multipart/form-data">
      <input id="upload" type="file" name="upload" accept=".csv" >
      <input class="form-button" type="submit" style=" flex: 0 0 100%; margin-top: 50px;">
    </form>
	<?php
}

// Shortcode to display the search input and table
add_shortcode('hkota_ot_list', 'hkota_ot_list_shortcode');
function hkota_ot_list_shortcode() {
    ob_start();
    ?>
    <div class="hkota-ot-search-wrapper">
        <input type="text" id="hkota-ot-search" placeholder="Search by Registration No, English Name, or Chinese Name" />
    </div>
    <div id="hkota-ot-list">
        <?php echo hkota_ot_render_list(); ?>
    </div>
    <?php
    return ob_get_clean();
}

// Function to render the initial list of data (first 50 entries)
function hkota_ot_render_list($results = null, $page = 1, $total_rows = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hkota_ot_list';
    $limit = 50;
    $offset = ($page - 1) * $limit;

    // Fetch data only if no results are passed (initial load)
    if (is_null($results)) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY ID ASC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
        // Get total rows for initial load if not provided
        $total_rows = $total_rows ?? $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    $total_pages = ceil($total_rows / $limit);

    ob_start();
    if ($results) {
        echo '<table class="hkota-ot-table">';
        echo '<thead><tr><th>Registration No</th><th>English Name</th><th>Chinese Name</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->Registration_no) . '</td>';
            echo '<td>' . esc_html($row->eng_name) . '</td>';
            echo '<td>' . esc_html($row->chi_name) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        // Render pagination
        echo '<div class="hkota-pagination">' . hkota_ot_render_pagination($page, $total_pages) . '</div>';
    } else {
        echo '<p>No records found.</p>';
    }
    return ob_get_clean();
}

// Function to render pagination links with current page indicator
function hkota_ot_render_pagination($current_page, $total_pages) {
    $output = '';
    if ($total_pages > 1) {
        $output .= '<div class="hkota-pagination-links">';
        for ($i = 1; $i <= $total_pages; $i++) {
            // Skip displaying some page numbers to keep the layout readable
            if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)) {
                $class = ($i == $current_page) ? 'hkota-page-link current-page' : 'hkota-page-link';
                $output .= '<a href="#" class="' . $class . '" data-page="' . $i . '">' . $i . '</a>';
            } elseif ($i == $current_page - 3 || $i == $current_page + 3) {
                $output .= '<span>...</span>';
            }
        }
        $output .= '</div>';
    }
    return $output;
}

add_shortcode('course-filter','course_filter');
function course_filter(){

	$parameter = '';
	$selected_date = sanitize_text_field( $_GET['start-date'] );
	if( !empty($selected_date) ){
		$parameter = "start-date='$selected_date' end-date='$selected_date'";
	}
  ?>
      <div id="course-filter">
        <div class="select-container">
          <select id="course-type">
              <option value="">All</option>
              <option value="training">CPD Programme</option>
							<option value="hkota-event">HKOTA Event</option>
							<option value="supporting-event">Supporting Event</option>
							<option value="co-organized-event">Co-organized Event</option>
              <!-- Add other options here -->
          </select>
        </div>
        <div class="select-container">
          <select id="year">
              <option value="">Years</option>
              <?php for ($i = 2015; $i <= date('Y'); $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
          </select>
        </div>
        <div class="select-container">
          <select id="month">
              <option value="">Months</option>
              <?php for ($i = 1; $i <= 12; $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0, 0, 0, $i, 10)); ?></option>
              <?php endfor; ?>
          </select>
        </div>
      </div>

      <div id="course-hook">
        <?php echo do_shortcode("[display_course $parameter]"); ?>
      </div>

  <?php

}

add_shortcode('display_course', 'display_course');
function display_course($atts = array()) {

    // Extract the attributes
    $atts = shortcode_atts(array(
        'type' => '', // course type
        'start-date' => date('Y-m-').'-01', // start date
        'end-date' => date("Y-m-t"), // end date
				'tab-button' => 'disable'
    ), $atts);

    // Build the meta query based on the attributes
    $meta_query = array();

    if (!empty($atts['type'])) {
        $meta_query[] = array(
            'key' => 'course_type',
            'value' => $atts['type'],
            'compare' => '='
        );
    }

    if (!empty($atts['start-date']) && !empty($atts['end-date'])) {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => 'course_start_date',
                'value' => array( $atts['start-date'] , $atts['end-date'] ),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
            array(
                'key' => 'course_end_date',
                'value' => array( $atts['start-date'] , $atts['end-date'] ),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            ),
            array(
                'relation' => 'AND',
                array(
                    'key' => 'course_start_date',
                    'value' => $atts['start-date'],
                    'compare' => '<',
                    'type' => 'DATE',
                ),
                array(
                    'key' => 'course_end_date',
                    'value' => $atts['end-date'],
                    'compare' => '>',
                    'type' => 'DATE'
                ),
            )
        );
    }

    $args = array(
        'post_type'   => 'COURSE',
        'post_status' => 'publish',
		'meta_key'    => 'course_start_date', // Meta key to order by
        'orderby'     => 'meta_value', // Order by the meta value
        'order'       => 'DESC', // Order by ascending date
        'meta_query'  => $meta_query,
    );

		if( $atts['tab-button'] == 'enable' ){
			?>
				<div class="tab-button-wrapper">
					<div data-type="all" class="tab-button active">All</div>
					<div data-type="training" class="tab-button">CPD Programme</div>
					<div data-type="hkota-event" class="tab-button">HKOTA Event</div>
					<div data-type="supporting-event" class="tab-button">Supporting Event</div>
					<div data-type="co-organized-event" class="tab-button">Co-organized Event</div>
				</div>
			<?php
		}

    $query = new WP_Query($args);

    if ($query->have_posts()) {

        ?> <div class="course-catalog"> <?php

        while ($query->have_posts()) {

            $query->the_post();

            // $debug[] = get_the_title();

            if( get_post_meta( get_the_ID(), 'course_is_private', true) == 'true' ) continue;

            $course = new Course(get_the_ID());

            $course_has_event_within_period = false;
            $course_dates = get_post_meta( get_the_ID(), 'course_dates', true); // Get array of course dates
            foreach ($course_dates as $date) {
                    if ( strtotime($date) >= strtotime( $atts['start-date'] ) && strtotime($date) <= strtotime( $atts['end-date'] ) ) {
                        $course_has_event_within_period = true;
                    }
            }
            if( !$course_has_event_within_period ) continue;
            ?>
            <div class="course-wrapper visable <?php echo $course->type ;?>">
                <div class="course-heading">
                    <h1 class="theme-green"><?php echo $course->title; ?></h1>
										<?php if($course->type == 'training' ): ?>
											<span>(Course Code: <?php echo $course->code; ?>)</span>
										<?php endif; ?>
                </div>
                <div class="course-info">
                    <div class="course-pdf">
											<?php if($course->type == 'training' ): ?>
												<?php if( !empty($course->poster) ): ?>
													<a target="_blank" href="<?php echo COURSE_POSTER_URL . $course->poster ;?>">
	                            <img class="pdf-snapshot" src="<?php echo COURSE_FILE_URL . $course->snapshot ;?>" width="400px;">
	                        </a>
												<?php endif; ?>
											<?php else: ?>
												<?php if( !empty($course->external_poster) ): ?>
													<a target="_blank" href="<?php echo COURSE_FILE_URL . $course->external_poster ;?>">
	                            <img class="pdf-snapshot" src="<?php echo COURSE_FILE_URL . $course->snapshot ;?>" width="400px;">
	                        </a>
												<?php endif; ?>
											<?php endif; ?>
                    </div>
                    <div class="course-details">
                      <div class="details-row">
                        <span class="type">Date:</span>
                        <span class="value"><?php echo $course->createDateString(); ?></span>
                      </div>
                      <div class="details-row">
                        <span class="type">Time:</span>
                        <span class="value"><?php echo $course->createTimeString(); ?></span>
                      </div>
                      <div class="details-row">
                        <span class="type">Venue:</span>
                        <span class="value"><?php echo $course->venue ;?></span>
                      </div>
                      <div class="details-row">
                        <span class="type">Target Participants:</span>
                        <span class="value"><?php echo nl2br(esc_html($course->target_participants)) ;?></span>
                      </div>
                      <div class="details-row">
                        <span class="type">Details:</span>
                        <span class="value">
													<?php if($course->type == 'training' ): ?>
														<?php if( !empty($course->poster) ): ?>
															<a target="_blank" href="<?php echo COURSE_POSTER_URL . $course->poster ;?>">Course poster</a>
														<?php endif; ?>
													<?php else: ?>
														<?php if( !empty($course->external_poster) ): ?>
															<a target="_blank" href="<?php echo COURSE_FILE_URL . $course->external_poster ;?>">Event poster</a>
														<?php endif; ?>
													<?php endif; ?>
												</span>
                      </div>
                      <div class="details-row">
                        <span class="type">Open Application:</span>
                        <span class="value">
													<?php
														if( $course->type == 'training' ){
															if( empty( $course->open_application ) ){
						                    echo '';
						                  } else{
						                    echo '18:00 on '.date( "jS F Y", strtotime($course->open_application) ) ;
						                  }
														} else{
															echo 'Please refer to poster information.';
														}
					                ?>
												</span>
                      </div>
											<?php if($course->type == 'training' ): ?>
												<div class="details-row">
	                        <span class="type">Enrollment Status:</span>
	                        <span class="value <?php echo ($course->get_enrollment_status() == "available" )? "theme-green" : "theme-red" ;?>"><?php echo ucfirst(str_replace("_"," ",$course->get_enrollment_status())) ;?></span>
	                      </div>
											<?php endif; ?>
                      <div style="height:45px;"></div>
                      <div class="register">
												<?php if( $course->type == 'training' ): ?>
													<button id="add-to-cart-button" class="<?php echo ($course->get_enrollment_status() == "available" )? "" : "disabled" ;?>"
	                          <?php echo ($course->get_enrollment_status() == "available" )? "" : "disabled" ;?>
	                          data-product-id="<?php echo get_dummy_product_id() ;?>" data-course-id="<?php echo $course->id ;?>">Register</button>
	                          <?php
	                            if( $course->get_enrollment_status() == "full" && $course->is_waiting_list == 'true' ){
	                              ?>
	                                <button id="add-to-waiting-button" data-product-id="<?php echo get_dummy_product_id() ;?>" data-course-id="<?php echo $course->id ;?>">Apply Waiting list</button>
	                              <?php
	                            }
	                          ?>
												<?php else: ?>
													<a target="_blank"
														 href="<?php echo $course->external_link ;?>"
														 class="<?php
																			if( date("Y-m-d") > date($course->start_date) ){
																				echo "disabled";
																			}
																		?>"
																		<?php
																			if( date("Y-m-d") > date($course->start_date) ){
																				 echo "disabled";
																			}
																		?>>Browse</a>
												<?php endif; ?>
                      </div>
                    </div>
                </div>
            </div>
            <?php

        }

        ?> </div> <?php

    } else{

      ?>

        <div class='no-post'>No course found.</div>

      <?php

    }

    wp_reset_postdata(); // Reset post data after the loop
}

add_shortcode('single_display_course_date','single_display_course_date');

function single_display_course_date(){

    $course = new Course(get_the_ID());

    ?>
    <div class="course-wrapper visable <?php echo $course->type ;?>">
        <div class="course-heading">
            <h1 class="theme-green"><?php echo $course->title; ?></h1>
                <?php if($course->type == 'training' ): ?>
                    <span>(Course Code: <?php echo $course->code; ?>)</span>
                <?php endif; ?>
        </div>
        <div class="course-info">
            <div class="course-pdf">
                                    <?php if($course->type == 'training' ): ?>
                                        <?php if( !empty($course->poster) ): ?>
                                            <a target="_blank" href="<?php echo COURSE_POSTER_URL . $course->poster ;?>">
                        <img class="pdf-snapshot" src="<?php echo COURSE_FILE_URL . $course->snapshot ;?>" width="400px;">
                    </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if( !empty($course->external_poster) ): ?>
                                            <a target="_blank" href="<?php echo COURSE_FILE_URL . $course->external_poster ;?>">
                        <img class="pdf-snapshot" src="<?php echo COURSE_FILE_URL . $course->snapshot ;?>" width="400px;">
                    </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
            </div>
            <div class="course-details">
              <div class="details-row">
                <span class="type">Date:</span>
                <span class="value"><?php echo $course->createDateString(); ?></span>
              </div>
              <div class="details-row">
                <span class="type">Time:</span>
                <span class="value"><?php echo $course->createTimeString(); ?></span>
              </div>
              <div class="details-row">
                <span class="type">Venue:</span>
                <span class="value"><?php echo $course->venue ;?></span>
              </div>
              <div class="details-row">
                <span class="type">Target Participants:</span>
                <span class="value"><?php echo nl2br(esc_html($course->target_participants)) ;?></span>
              </div>
              <div class="details-row">
                <span class="type">Details:</span>
                <span class="value">
                                            <?php if($course->type == 'training' ): ?>
                                                <?php if( !empty($course->poster) ): ?>
                                                    <a target="_blank" href="<?php echo COURSE_POSTER_URL . $course->poster ;?>">Course poster</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if( !empty($course->external_poster) ): ?>
                                                    <a target="_blank" href="<?php echo COURSE_FILE_URL . $course->external_poster ;?>">Event poster</a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </span>
              </div>
              <div class="details-row">
                <span class="type">Open Application:</span>
                <span class="value">
                                            <?php
                                                if( $course->type == 'training' ){
                                                    if( empty( $course->open_application ) ){
                                    echo '';
                                  } else{
                                    echo '18:00 on '.date( "jS F Y", strtotime($course->open_application) ) ;
                                  }
                                                } else{
                                                    echo 'Please refer to poster information.';
                                                }
                            ?>
                                        </span>
              </div>
                                    <?php if($course->type == 'training' ): ?>
                                        <div class="details-row">
                    <span class="type">Enrollment Status:</span>
                    <span class="value <?php echo ($course->get_enrollment_status() == "available" )? "theme-green" : "theme-red" ;?>"><?php echo ucfirst(str_replace("_"," ",$course->get_enrollment_status())) ;?></span>
                  </div>
                                    <?php endif; ?>
              <div style="height:45px;"></div>
              <div class="register">
                                        <?php if( $course->type == 'training' ): ?>
                                            <button id="add-to-cart-button" class="<?php echo ($course->get_enrollment_status() == "available" )? "" : "disabled" ;?>"
                      <?php echo ($course->get_enrollment_status() == "available" )? "" : "disabled" ;?>
                      data-product-id="<?php echo get_dummy_product_id() ;?>" data-course-id="<?php echo $course->id ;?>">Register</button>
                      <?php
                        if( $course->get_enrollment_status() == "full" && $course->is_waiting_list == 'true' ){
                          ?>
                            <button id="add-to-waiting-button" data-product-id="<?php echo get_dummy_product_id() ;?>" data-course-id="<?php echo $course->id ;?>">Apply Waiting list</button>
                          <?php
                        }
                      ?>
                        <?php else: ?>
                          <a target="_blank"
                            href="<?php echo $course->external_link ;?>"
                            class="<?php
                              if( date("Y-m-d") > date($course->start_date) || empty($course->external_link) ){
                                  echo "disabled";
                              }
                          ?>"
                          <?php
                              if( date("Y-m-d") > date($course->start_date) || empty($course->external_link) ){
                                    echo "disabled";
                              }
                          ?>>Browse</a>
                        <?php endif; ?>
              </div>
            </div>
        </div>
    </div>
    <?php

}

add_shortcode('get_archive_name','get_archive_name');
function get_archive_name(){
  $category = get_queried_object();
  if ($category && is_category() ) {
      echo $category->name;
  }
}

add_shortcode('post_filter','post_filter');
function post_filter(){
  $category = get_queried_object();

  if ($category && is_category()) {
      // Get the category slug
      $category_slug = $category->slug;
  }
  ?>
      <div id="post-filter" data-term-slug="<?php echo $category_slug ;?> ">
        <div class="select-container">
          <select id="year">
              <option value="">Years</option>
              <?php for ($i = 2015; $i <= date('Y'); $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
          </select>
        </div>
      </div>

      <div id="post-hook">
        <?php echo do_shortcode("[display_post category=$category_slug]"); ?>
      </div>

  <?php

}

add_shortcode('display_post', 'display_post');
function display_post($atts = array()) {

    // Extract the attributes
    $atts = shortcode_atts(array(
        'category' => '', // course type
        'start-date' => '', // start date
        'end-date' => '', // end date
    ), $atts);

    // Build the meta query based on the attributes
    $date_query = array();

    if (!empty($atts['start-date']) && !empty($atts['end-date'])) {
        $date_query = array(
            array(
                'after'     => $atts['start-date'],
                'before'    => $atts['end-date'],
                'inclusive' => true, // Include the start and end dates
            ),
        );
    }

    $args = array(
        'post_type'     => 'post',
        'post_status'   => 'publish',
        'category_name' =>  $atts['category'],
        'order'         => 'DESC',
        'orderby'       => 'date',
        'date_query'    => $date_query,
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {

        ?> <div class="post-catalog"> <?php

        while ($query->have_posts()) {

            $query->the_post();

            ?>
            <a href="<?php echo get_permalink() ;?>">
              <div class="post-wrapper">
                  <div class="post-data">
                      <span>Date:</span><p><?php echo get_the_date() ;?></p>
                  </div>
                  <div class="post-data">
                      <span>Title:</span><p><?php echo get_the_title() ;?></p>
                  </div>
              </div>
            </a>
            <?php

        }

        ?> </div> <?php

    } else{

      ?>

        <div class='post-catalog'>No record found.</div>

      <?php

    }

    wp_reset_postdata(); // Reset post data after the loop
}

add_shortcode('product_filter','product_filter');
function product_filter(){

  ?>
      <div id="product-filter">
        <div class="select-container">
          <select id="year">
              <option value="">Years</option>
              <?php for ($i = 2015; $i <= date('Y'); $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
          </select>
        </div>
      </div>

      <div id="post-hook">
        <?php echo do_shortcode("[display_product]"); ?>
      </div>

  <?php

}

add_shortcode('display_product', 'display_product');
function display_product($atts = array()) {

    // Extract the attributes
    $atts = shortcode_atts(array(
        'start-date' => '', // start date
        'end-date' => '', // end date
    ), $atts);

    // Build the meta query based on the attributes
    $date_query = array();

    if (!empty($atts['start-date']) && !empty($atts['end-date'])) {
        $date_query = array(
            array(
                'after'     => $atts['start-date'],
                'before'    => $atts['end-date'],
                'inclusive' => true, // Include the start and end dates
            ),
        );
    }

    $args = array(
        'post_type'     => 'product',
        'post_status'   => 'publish',
        'product_cat'   => 'Publication',
        'order'         => 'DESC',
        'orderby'       => 'date',
        'date_query'    => $date_query,
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {

        ?> <div class="post-catalog"> <?php

        while ($query->have_posts()) {

            $query->the_post();

            ?>
              <div class="product-wrapper">
                <div class="product-image">
                  <?php
                      // Get the featured image URL
                      $product_id = get_the_ID();
                      $featured_image_url = get_the_post_thumbnail_url( $product_id , 'post-thumbnail');

                      // Output the featured image and date
                      if ($featured_image_url) {
                          echo '<div class="post-featured-image">';
                          echo '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr(get_the_title()) . '">';
                          echo '</div>';
                      }
                  ?>
                </div>
                <div class="product-data-wrapper">
                  <div class="product-data">
                      <span>Publish in:</span><p><?php echo get_the_date('F Y') ;?></p>
                  </div>
                  <div class="product-data">
                      <span>Title:</span><p><?php echo get_the_title() ;?></p>
                  </div>
                  <div class="product-data">
                      <span>Description:</span><?php echo get_the_excerpt() ;?>
                  </div>
                  <div class="product-data">
                      <span>Price:</span>
                      <div class="product-price-list">
                        <?php
                          $member_price = get_post_meta($product_id, 'member_price', true);
                          $student_price = get_post_meta($product_id, 'student_price', true);
                          if( $member_price ) {
                            echo "<div class='product-price'><div class='type'>Member:</div><div class='sum'>$$member_price</div></div>" ;
                          }
                          if( $student_price ) {
                            echo "<div class='product-price'><div class='type'>Student Member:</div><div class='sum'>$$student_price</div></div>" ;
                          }
                          $product = wc_get_product( $product_id );
                          $price = $product->get_price();
                          echo "<div class='product-price'><div class='type'>Non Member:</div><div class='sum'>$$price</div></div>" ;
                        ?>
                      </div>
                  </div>
                  <div style="height:45px;"></div>
                  <div class="register">
                    <button id="product-add-to-cart" data-product-id="<?php echo $product_id ;?>" >Add to cart</button>
                  </div>
                </div>
              </div>
            <?php

        }

        ?> </div> <?php

    } else{

      ?>

        <div class='post-catalog'>No course found.</div>

      <?php

    }

    wp_reset_postdata(); // Reset post data after the loop
}

add_shortcode('course_calendar','course_calendar');
function course_calendar($atts = array()) {

    // Extract the attributes
    $atts = shortcode_atts(array(
        'display-course' => 'enable', // start date
    ), $atts);
  ?>
  <script>
    jQuery(document).ready(function(){
      let currentDate = new Date();
      let currentMonth = currentDate.getMonth() + 1; // JS months are 0-based
      let currentYear = currentDate.getFullYear();
      // Initial load
      loadCalendar(currentMonth, currentYear);

      // Navigation buttons
      jQuery('#prev-month').on('click', function () {
          if (currentMonth === 1) {
              currentMonth = 12;
              currentYear -= 1;
          } else {
              currentMonth -= 1;
          }
          loadCalendar(currentMonth, currentYear);
      });

      jQuery('#next-month').on('click', function () {
          if (currentMonth === 12) {
              currentMonth = 1;
              currentYear += 1;
          } else {
              currentMonth += 1;
          }
          loadCalendar(currentMonth, currentYear);
      });

    });
  </script>
  <div id="course-calendar-widget">
    <div id="calender-loader" style="display:none;"><i class="fa fa-spinner fa-spin"></i></div>
    <div id="calendar-wrapper">
      <div id="calendar-nav">
        <button id="prev-month"><i class="fa-solid fa-angle-left"></i></button>
        <div id="current-month"></div>
        <button id="next-month"><i class="fa-solid fa-angle-right"></i></button>
      </div>
      <div id="calendar"></div>
			<div id="dot-index">
    		<div><span class="dot training"></span> CPD Programme</div>
				<div><span class="dot hkota-event"></span> HKOTA Event</div>
				<div><span class="dot supporting-event"></span> Supporting Event</div>
				<div><span class="dot co-organized-event"></span> Co-organized Event</div>
			</div>
      <div id="course-details"></div>
    </div>
		<?php
			if( $atts['display-course'] == 'enable' ){
				?>
					<div id="display-course">
			    </div>
				<?php
			}
		?>
  </div>
  <?php
}

add_shortcode('membership_application', 'membership_application');
function membership_application(){
  ?>
  <button class="membership-application-button button">Membership Application</button>
  <?php
}

add_shortcode('membership_application_popup', 'membership_application_popup');
function membership_application_popup(){
  ?>
    <div id="membership-popup">
      <div id="popup-content"></div>
      <span id="close-popup" style="cursor:pointer; font-size:20px; position:absolute; top:10px; right:10px;">&times;</span>
    </div>
    <div id="overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0, 0, 0, 0.5); z-index:9998;"></div>
  <?php
}

add_action('template_redirect', 'process_pupil_attendance');
function process_pupil_attendance() {

  if (is_page('pupil')) {

    //First check if the section is is valid
    if ( !empty( isset( $_GET['section-id'] ) ) && !empty( isset( $_GET['course-id'] ) ) ) {

      global $wpdb;

      $section_id = sanitize_text_field($_GET['section-id']);
      $course_id = sanitize_text_field($_GET['course-id']);

      $course = new Course($course_id);

      if (!$course->check_section_id_match( $section_id )) {
          // If it doesn't match, display an error message
          display_message_overlay('<p>Error: Invalid QR code for this course.</p>');
          return;
      }
    } else {
      display_message_overlay('<p>Error: Invalid QR code for this course.</p>');
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      global $wpdb;
      $registration_email = sanitize_text_field($_POST['registration_email']);

      // Check if registration number matches any user
      $user = get_user_by('email',$_POST['registration_email']);

      if (!$user) {
          display_message_overlay('<p>Registration Email not found. Please try again.</p>');
          return;
      }

      $user_id = $user->id;

      // Check if user is enrolled in the course with status = 'enrolled'
      $is_enrolled = $wpdb->get_var($wpdb->prepare(
          "SELECT user_id FROM {$wpdb->prefix}hkota_course_enrollment WHERE course_id = %d AND user_id = %d AND status = 'enrolled'",
          $course_id,
          $user_id
      ));

      if (!$is_enrolled) {
          display_message_overlay('<p>You are not enrolled in this course or the status is not enrolled.</p>');
          return;
      }

      if( has_user_already_signed_in($user_id, $course_id, $section_id) ){
          display_message_overlay('<p>You have already signed in/out for this section.</p>');
          return;
      }

      if( $course->check_section_is_end_survey($section_id) ){
        $course->save_survey_form_data($user_id, $_POST);
      }

      $attendance_data = get_user_attendance_data($user_id, $course_id);

      // Mark this section as attended
      $attendance_data['attendance_data'][$section_id] = 1;

      // Update attendance status
      $attendance_status = calculate_attendance_status($attendance_data['attendance_data']);

      // Save the updated attendance back to the database
      $wpdb->update(
          "{$wpdb->prefix}hkota_course_enrollment",
          ['attendance' => maybe_serialize([
              'attendance_status' => $attendance_status,
              'attendance_data' => $attendance_data['attendance_data']
          ])],
          ['user_id' => $user_id, 'course_id' => $course_id],
          ['%s'],
          ['%d', '%d']
      );

      display_message_overlay('<p>Thank you! Your attendance has been recorded.</p>',false);

      if( $attendance_status == 'fully_attended' ){
        $result = $course->create_certificate($user_id);
        if($result){
          $course->trigger_issue_certificate_email($user_id);
        }
      }

    } elseif( $course->check_section_is_end_survey($section_id) ){
      $course->display_end_survey($section_id, $course_id);
    } else{
      // Display the form to enter the registration number
      display_registration_form($section_id, $course_id);
    }
  }
}

add_action('template_redirect', 'process_pupil_quiz');
function process_pupil_quiz() {

  if (is_page('quiz')) {

    //First check if the section is is valid
    if ( !empty( isset( $_GET['quiz-id'] ) ) && !empty( isset( $_GET['course-id'] ) ) ) {

      global $wpdb;

      $quiz_id = sanitize_text_field($_GET['quiz-id']);
      $course_id = sanitize_text_field($_GET['course-id']);

      $course = new Course($course_id);

      if (!$course->check_quiz_id_match($quiz_id)) {
          // If it doesn't match, display an error message
          display_message_overlay('<p>Error: Invalid QR code for this course.</p>');
          return;
      }
    } else {
      display_message_overlay('<p>Error: Invalid QR code for this course.</p>');
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

      global $wpdb;
      $registration_email = sanitize_text_field($_POST['registration_email']);

      // Check if user is enrolled in the course with status = 'enrolled'
      $user = get_user_by('email',$_POST['registration_email']);

      if (!$user) {
          display_message_overlay('<p>Registration Email not found. Please try again.</p>');
          return;
      }

      $user_id = $user->id;

      // Check if user has Enrollment the quiz.
      $is_enrolled = $wpdb->get_var($wpdb->prepare(
          "SELECT user_id FROM {$wpdb->prefix}hkota_course_enrollment WHERE course_id = %d AND user_id = %d AND status = 'enrolled'",
          $course_id,
          $user_id
      ));

      if (!$is_enrolled) {
          display_message_overlay('<p>You are not enrolled in this course or the status is not enrolled.</p>');
          return;
      }

      if( has_user_already_complete_quiz($user_id, $course_id, $quiz_id) ){
          display_message_overlay('<p>You have already completed the quiz.</p>');
          return;
      }

      $course->save_quiz_form_data($user_id, $_POST, $quiz_id);

      display_message_overlay('<p>Thank you! Your have completed this quiz.</p>', false);

    } else {
      // Display the form to enter the registration number
      $course->display_quiz_form($quiz_id);
    }
  }
}

function has_user_already_signed_in($user_id, $course_id, $section_id) {
    // Get the current attendance data for the user
    $attendance_data = get_user_attendance_data($user_id, $course_id);

    // Check if the user has already signed in for this section (section_id)
    if (isset($attendance_data['attendance_data'][$section_id]) && $attendance_data['attendance_data'][$section_id] == 1) {
        return true;  // User has already signed in for this section
    }

    return false;  // User has not signed in yet
}

function has_user_already_complete_quiz($user_id, $course_id, $quiz_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'hkota_course_enrollment';

    // Query the 'quiz' column from the table for the specific user and course
    $quiz_data_serialized = $wpdb->get_var($wpdb->prepare(
        "SELECT quiz FROM $table WHERE user_id = %d AND course_id = %d",
        $user_id,
        $course_id
    ));

    // Check if quiz data exists and is an array
    if ($quiz_data_serialized) {
        $quiz_data = maybe_unserialize($quiz_data_serialized);

        // Check if the specific quiz_id exists in the array and has been completed (i.e. true)
        if (is_array($quiz_data) && isset($quiz_data[$quiz_id]) && !empty($quiz_data[$quiz_id])) {
            return true; // Quiz is completed
        }

    }

    return false; // Quiz is not completed or no data exists
}

function get_user_attendance_data($user_id, $course_id) {
    global $wpdb;
    $enrollment_table = $wpdb->prefix . 'hkota_course_enrollment';
    $attendance_serialized = $wpdb->get_var($wpdb->prepare(
        "SELECT attendance FROM $enrollment_table WHERE user_id = %d AND course_id = %d",
        $user_id,
        $course_id
    ));

    if ($attendance_serialized) {
        return maybe_unserialize($attendance_serialized);
    }

    echo "<p>Error: No attendance data found for the user in this course.</p>";
    wp_die(); // Stop further processing
}

function calculate_attendance_status($attendance_data) {
    $all_attended = true;
    $all_not_attended = true;

    // Loop through the attendance_data array to check each value
    foreach ($attendance_data as $section_id => $status) {
        if ($status == 1) {
            // If any value is 1, mark not all are not_attended
            $all_not_attended = false;
        } else {
            // If any value is 0, mark not all are fully attended
            $all_attended = false;
        }
    }

    // Return the appropriate attendance status
    if ($all_attended) {
        return 'fully_attended';
    } elseif ($all_not_attended) {
        return 'not_attended';
    } else {
        return 'partially_attended';
    }
}

function display_registration_form($section_id, $course_id) {
    ?>
    <style>
        label {
          display: inline-block;
          line-height: 2!important;
          text-align: left;
          width: 100%;
        }
        /* Full-screen overlay */
        #registration-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 1);
            z-index: 9999; /* Make sure it's on top of everything */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Form styling */
        #registration-form {
            width: 500px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Input and button styling */
        #registration-form input[type="text"],
        #registration-form input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #registration-form input[type="submit"] {
            background-color: #0073aa;
            color: white;
            border: none;
            cursor: pointer;
        }

        #registration-form input[type="submit"]:hover {
            background-color: #005177;
        }
    </style>

    <div id="registration-overlay">
        <form id="registration-form" method="post" action="">
            <h2>Sign In/Out</h2>
            <label for="registration_email">Registration Email:</label><br>
            <input type="email" id="registration_email" name="registration_email" required><br>
            <input type="hidden" name="section_id" value="<?php echo esc_attr($section_id); ?>">
            <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">
            <input id="submit" type="submit" value="Submit">
        </form>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            var submitButton = document.getElementById('submit');

            // Show the survey form when Next button is clicked
            submitButton.addEventListener('click', function (e) {
                var regEmail = document.getElementById('registration_email').value.trim();
                if (regEmail === '') {
                    showMessage('error','Please enter your registration email.');
                    e.preventDefault();
                }

            });

        });


    </script>

    <?php
}

//Use in quiz and survey so display message
function display_message_overlay($message , $error = true) {
  ?>
  <style>
      /* Full-screen overlay */
      #message-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(255, 255, 255, 1); /* White background */
          z-index: 9999; /* Ensure it's on top of everything */
          display: flex;
          align-items: center;
          justify-content: center;
      }

      /* Message box styling */
      #message-box {
          width: 350px;
          background-color: white;
          padding: 20px;
          border-radius: 10px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
          text-align: center;
      }

      /* Button styling */
      #message-box button {
          margin-top: 20px;
          padding: 10px 20px;
          background-color: #0073aa;
          color: white;
          border: none;
          border-radius: 5px;
          cursor: pointer;
      }

      #message-box button:hover {
          background-color: #005177;
      }

      #message-box button a{
        color: #FFF!important;
      }

  </style>

  <div id="message-overlay">
      <div id="message-box">
          <?php echo wp_kses_post($message); ?>
          <?php
            if($error){
              echo "<button><a href=". $_SERVER['REQUEST_URI'] .">Retry</button>";
            } else{
              echo "<button><a href=". home_url() .">Return to Home</button>";
            }

          ?>

      </div>
  </div>
  <?php
}

// Function to generate course poster base on $_GET['course_id']
add_action('admin_post_generate_poster', 'generate_poster');
function generate_poster(){

    $course_id = sanitize_text_field($_GET['course_id']);

    if( empty($course_id) ) {
      echo "Error: Course ID not specified.";
      exit;
    }

		$options = new Options();

		$tmp = sys_get_temp_dir();

		$options->set('isHtml5ParserEnabled', true);

		$options->set('isRemoteEnabled', true);
		
		// Enable Unicode support for Chinese characters
		$options->set('defaultFont', 'DejaVu Sans');
		$options->set('defaultMediaType', 'print');
		$options->set('isFontSubsettingEnabled', true);

		$options->set('fontDir',  $tmp);

		$options->set('fontCache',  $tmp);

		$options->set('tempDir',  $tmp);

		$options->set('chroot',  $tmp );

		$dompdf = new Dompdf($options);

		include dirname(__FILE__) . "/poster.php";

    $html_content = ob_get_clean();

		$dompdf->loadHtml($html_content);

		$dompdf->setPaper('A4', 'portrait');

		$dompdf->render();

    $pdf_output = $dompdf->output();

    $upload_dir = wp_upload_dir();

    $course_poster_dir = $upload_dir['basedir'].'/course-poster';

    $course = get_post( $course_id );

    $ori_file = get_post_meta($course_id,'course_poster',true);

    if( !empty($ori_file) ){
      wp_delete_file( $course_poster_dir . "/" . $ori_file );
    }

    $filename = $course->post_title . ".pdf";

    $filename = wp_unique_filename( $course_poster_dir, $filename );

    $appendix = get_post_meta($course_id,'course_appendix',true);

    $is_appendix = get_post_meta( $course_id, 'course_is_appendix', true );

    // file_put_contents($course_poster_dir . "/" .$filename, $pdf_output);

    if( $is_appendix && !empty($appendix) ){

      //save merged file
      file_put_contents($course_poster_dir . "/" . "tem_" . $filename, $pdf_output);

      $pdf = new \Jurosh\PDFMerge\PDFMerger;

      $pdf->addPDF($course_poster_dir . "/" . "tem_" . $filename , 'all', 'vertical')
          ->addPDF( $upload_dir['basedir'] . '/course-files/' . $appendix , 'all');

      $pdf->merge('file', $course_poster_dir . "/" . "tem__" . $filename );

      wp_delete_file( $course_poster_dir . "/" . "tem_" . $filename );

    } else {

      file_put_contents($course_poster_dir . "/" . "tem__" . $filename, $pdf_output);

    }

    // Path to the existing PDF file
    $pdfFilePath = $course_poster_dir . "/" . "tem__" . $filename;

    // Create a new TCPDF object
    $pdf = new TcpdfFpdi();

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);

    // Import each page from the existing PDF and add the footer
    $pageCount = $pdf->setSourceFile($pdfFilePath);

    for ($i = 1; $i <= $pageCount; $i++) {
        // Import the current page
        $templateId = $pdf->importPage($i);

        // Get the size of the imported page (if needed)
        $size = $pdf->getTemplateSize($templateId);

        // Create a new page (same size as the imported one)
        if ($size['width'] > $size['height']) {
            // Landscape page
            $pdf->AddPage('L', [$size['width'], $size['height']]);
        } else {
            // Portrait page
            $pdf->AddPage('P', [$size['width'], $size['height']]);
        }

        // Use the imported page as a template
        $pdf->useTemplate($templateId);

        // Add the footer with the page number
        $pdf->SetY(-15); // Position at 15 mm from the bottom
        $pdf->SetFont('helvetica', 'I', 8); // Use italic font, size 8
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->Cell(0, 8, 'Page ' . $i . ' of ' . $pageCount, 0, 0, 'R'); // Centered page number
    }

    // Output the modified PDF with page numbers
    $outputFilePath = $course_poster_dir . "/" . $filename;
    $pdf->Output($outputFilePath, 'F'); // Save the output file to the server

    wp_delete_file( $course_poster_dir . "/" . "tem__" . $filename );

    update_post_meta( $course_id, 'course_poster' , $filename);

    $snapshot_dir = $upload_dir['basedir'].'/course-files';

    generate_poster_snapshot( $outputFilePath , $snapshot_dir, $filename, $course_id );

    wp_safe_redirect( home_url() . '/wp-content/uploads/course-poster/' . $filename );

	exit;

}

// Function to export inactive member csv file
add_action('admin_post_inactive_member_csv', 'inactive_member_csv');
function inactive_member_csv(){

	global $wpdb;

	$users_table = $wpdb->prefix . 'users';
	$users_meta_table = $wpdb->prefix . 'usermeta';

	// Fetch enrolled pupils' survey data for this course
	$non_activate_user = $wpdb->get_results(
			$wpdb->prepare(
					"SELECT u.user_email
					 FROM $users_table u
					 INNER JOIN $users_meta_table um ON u.ID = um.user_id
					 WHERE um.meta_key = 'member_imported_user' AND um.meta_value = 'yes';"
			)
	);



	if (empty($non_activate_user)) {
			wp_die('No data found.');
	}

	// Prepare data for CSV
	$csv_data = [];

	foreach ($non_activate_user as $row) {

			$user = get_user_by('email', $row->user_email );
			$reset_key = get_password_reset_key( $user );
			$reset_url = wc_get_endpoint_url( 'lost-password', '', get_permalink( wc_get_page_id( 'myaccount' ) ) );
			$reset_url = add_query_arg( [
							'key'   => $reset_key,
							'login' => rawurlencode( $user->user_login )
			], $reset_url );

			// Prepare a row with user info and survey answers
			$data_row = [
					'email'  => $row->user_email,
					'link' => $reset_url,
			];

			$csv_data[] = $data_row;
	}

	// Start CSV export
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="Survey data ' . $course->title . '.csv"');
	header('Pragma: no-cache');
	header('Expires: 0');

	$csv_file = fopen('php://output', 'w');

	// CSV Headers
	$headers = ['Email', 'Reset URL'];
	fputcsv($csv_file, $headers);

	// Output the data rows
	foreach ($csv_data as $data_row) {
			fputcsv($csv_file, $data_row);
	}

	fclose($csv_file);
	exit;

}

// Function to get a jpg snapshot of the firstpage of course poster
function generate_poster_snapshot($pdf_file_path, $output_directory, $filename, $course_id ) {
    // Check if the Imagick extension is loaded
    if (!extension_loaded('imagick')) {
        die('Imagick extension is not installed');
    }

    // Check if the PDF file exists
    if (!file_exists($pdf_file_path)) {
        die('PDF file does not exist.');
    }

    // Create an Imagick object
    $imagick = new Imagick();

    // Read the first page of the PDF (page index 0)

    // Set the resolution (this affects the quality of the output image)
    $imagick->setResolution(150, 150);

    // Read the PDF file
    $imagick->readImage($pdf_file_path . '[0]'); // '[0]' means the first page only

    // Set the background color to white
    $imagick->setImageBackgroundColor('white');

    // Flatten the image layers (removes transparency)
    $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

    // Convert the PDF to a JPG
    $imagick->setImageFormat('jpg');

    // Set compression quality (0 to 100)
    $imagick->setImageCompressionQuality(90);

    // Generate a unique filename for the JPG file
    $filename = str_replace('.pdf' , '.jpg', $filename);

    $filename = wp_unique_filename( $output_directory, $filename );

    $output_path = rtrim($output_directory, '/') . '/' . $filename;

    // Save the JPG file to the server
    if( $imagick->writeImage($output_path) ){
      $old_snapshot = get_post_meta($course_id,'course_snapshot',true);
      if(!empty($old_snapshot)){
        wp_delete_file( $output_directory . "/" . $old_snapshot );
      }
      update_post_meta($course_id,'course_snapshot',$filename);
      // Clear Imagick object
      $imagick->clear();
      $imagick->destroy();
      return true;
    } else {
      return false;
    }

}

// Function to generate course poster base on $_GET['course_id']
add_action('admin_post_preview_certificate', 'preview_certificate');
function preview_certificate(){

  $course_id = sanitize_text_field($_GET['course_id']);

  $course = new Course($course_id);

  $serial_no = $course->cert_serial_prefix . '/0001';

  if( empty($course_id) ) {
    echo "Error: Course ID not specified.";
    exit;
  }

  $options = new Options();
  $options->set('isHtml5ParserEnabled', true);
  $options->set('isRemoteEnabled', true);
  $dompdf = new Dompdf($options);
  $dompdf->setPaper('A4', 'portrait');

  ob_start();

  $args = array(
    'user_id'       => 0,
    'serial_number' => $serial_no,
    'course'        => $course,
    'date_issued'   => time(),
    'preview'       => true
  );

  load_template( dirname(__FILE__) . "/certificate.php" ,true, $args );
  // include dirname(__FILE__) . "/certificate.php";
  $html_content = ob_get_clean();

  $dompdf->loadHtml($html_content);

  $dompdf->setPaper('A4', 'portrait');

  $dompdf->render();

  $dompdf->stream("certificate-preview", array("Attachment" => 0));

  exit;

}

// Function to Download qr code image in a zip file
add_action('admin_post_download_qrcode', 'download_qrcode');
function download_qrcode(){

  $course_id = $_GET['course_id'];

  $course = new Course($course_id);

  if( empty($course_id) ) {
    echo "Error: Course ID not specified.";
    exit;
  }

  $qr_codes = $course->qr_code;

  $upload_dir = wp_upload_dir();

  if ( empty( $upload_dir['basedir'] ) ) {
    echo 'wp_upload_dir() return empty value.';
    exit;
  }

  foreach ($qr_codes as $qr_code) {
    $filesToZip[] = $upload_dir['basedir'].'/course-qr-code/' . $qr_code['filename'];
  }

  // Specify the path for the zip file
  $zipFilePath = $upload_dir['basedir'].'/course-qr-code/' . $course->title . 'qr-code.zip';  // Temporary zip file path

  // Create new zip object
  $zip = new ZipArchive;

  // Open the zip file for writing
  if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // Iterate through the files and add each one to the zip
    foreach ($filesToZip as $filePath) {
        if (file_exists($filePath)) {
            $zip->addFile($filePath, basename($filePath)); // Add the file to the zip archive
        } else {
            echo 'File does not exist: ' . $filePath . '<br>';
            exit;
        }
    }

    // Close the zip file
    $zip->close();

    // Set headers to force the browser to download the zip file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . basename($zipFilePath));
    header('Content-Length: ' . filesize($zipFilePath));

    // Flush the output buffer
    flush();

    // Read and output the zip file
    readfile($zipFilePath);

    // Delete the zip file from the server after download
    unlink($zipFilePath);

    echo "<script>window.close();</script>";
  }
}

// Register the admin action hook for exporting quiz data
add_action('admin_post_download_quiz_answers', 'download_quiz_answers');
function download_quiz_answers() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    // Get course ID from the query string
    if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
        wp_die('Course ID is missing.');
    }

    global $wpdb;
    $course_id = intval($_GET['course_id']);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Fetch enrolled users and their quiz data
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, quiz FROM $table_name WHERE course_id = %d AND status = 'enrolled'",
        $course_id
    ), ARRAY_A);

    if (!$results || empty($results)) {
        wp_die('No quiz data found.');
    }

    // Load the Course class
    $course = new Course($course_id);

    // Store CSV files paths for zipping later
    $csv_files = [];

    // Loop through each quiz in the $course->quiz array
    foreach ($course->quiz as $quiz_id => $quiz_details) {
        $quiz_name = sanitize_text_field($quiz_details['name']);
        $csv_filename = 'quiz_' . sanitize_file_name($quiz_name) . '.csv';
        $csv_filepath = sys_get_temp_dir() . '/' . $csv_filename;
        $csv_files[] = $csv_filepath;

        // Open file for writing
        $output = fopen($csv_filepath, 'w');

        // Prepare CSV header: User Info + Quiz Questions
        $header = ['Last Name', 'First Name', 'Email'];
        foreach ($quiz_details['data'] as $question) {
            $header[] = $question['label'];  // Add the question label as the header column
        }
        fputcsv($output, $header);

        // Loop through each enrolled user and their quiz data
        foreach ($results as $result) {
            $user_id = $result['user_id'];
            $user = get_userdata($user_id);
            $quiz_data = maybe_unserialize($result['quiz']);

            $row = [
                $user->last_name,
                $user->first_name,
                $user->user_email
            ];

            // Check if the quiz ID exists in this user's quiz data
            if (isset($quiz_data[$quiz_id]) && !empty($quiz_data[$quiz_id]) ) {

              // Loop through the questions in this quiz and get the corresponding answers
              foreach ($quiz_details['data'] as $key => $question) {
                  $answer = isset($quiz_data[$quiz_id][$key]) ? $quiz_data[$quiz_id][$key]['answer'] : '';
                  $row[] = $answer;  // Add the answer to the row
              }
              // Write this user's answers to the CSV

            }
            fputcsv($output, $row);
        }

        // Close the CSV file
        fclose($output);
    }

    // Zip the CSV files if more than one exists
    if (count($csv_files) > 1) {
        $zip_filename = 'quiz_answers_' . $course_id . '.zip';
        $zip_filepath = sys_get_temp_dir() . '/' . $zip_filename;

        $zip = new ZipArchive();
        if ($zip->open($zip_filepath, ZipArchive::CREATE) === TRUE) {
            foreach ($csv_files as $csv_file) {
                $zip->addFile($csv_file, basename($csv_file));
            }
            $zip->close();

            // Serve the ZIP file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="Quiz data ' . $course->title .'.zip"');
            header('Content-Length: ' . filesize($zip_filepath));
            readfile($zip_filepath);

            // Clean up temp files
            unlink($zip_filepath);
            foreach ($csv_files as $csv_file) {
                unlink($csv_file);
            }

            exit;
        } else {
            wp_die('Could not create ZIP file.');
        }
    } else {
        // If only one CSV, serve it directly
        $single_csv = reset($csv_files);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Quiz data ' . $course->title .'.csv"');
        readfile($single_csv);

        // Clean up temp file
        unlink($single_csv);
        exit;
    }
}

// Register the admin action hook for exporting survey data
add_action('admin_post_export_survey_data', 'export_survey_data');
function export_survey_data() {
    global $wpdb;

    // Verify if the course ID is set in the query string
    if (!isset($_GET['course_id'])) {
        wp_die('Course ID is required.');
    }

    $course_id = intval($_GET['course_id']);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Fetch enrolled pupils' survey data for this course
    $enrollment_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, survey FROM $table_name WHERE course_id = %d AND status = 'enrolled'  AND survey IS NOT NULL ",
            $course_id
        )
    );

    if (empty($enrollment_data)) {
        wp_die('No survey data found.');
    }

    // Retrieve survey questions (this depends on how you store the $survey data, e.g., in the Course class)
    // You may replace the following with how you retrieve the survey questions for the course
    $course = new Course($course_id);
    $survey_questions = $course->survey;

    // Prepare data for CSV
    $csv_data = [];

    foreach ($enrollment_data as $row) {
        $user = get_userdata($row->user_id);
        $survey_responses = maybe_unserialize($row->survey);

        // Prepare a row with user info and survey answers
        $data_row = [
            'last_name'  => $user->last_name,
            'first_name' => $user->first_name,
        ];

        // Add survey answers
        foreach ($survey_responses as $question => $answer ) {
            $data_row[] = isset($answer) ? $answer : 'N/A';
        }

        $csv_data[] = $data_row;
    }

    // Start CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Survey data ' . $course->title . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $csv_file = fopen('php://output', 'w');

    // CSV Headers
    $headers = ['Last Name', 'First Name', 'Email'];
    foreach ($survey_questions as $question) {
        $headers[] = $question['label'];
    }
    fputcsv($csv_file, $headers);

    // Output the data rows
    foreach ($csv_data as $data_row) {
        fputcsv($csv_file, $data_row);
    }

    fclose($csv_file);
    exit;
}

// Register the admin action hook for exporting pupil data
add_action('admin_post_export_pupil_data', 'export_pupil_data');
function export_pupil_data() {
    global $wpdb;

    // Verify if the course ID is set in the query string
    if (!isset($_GET['course_id'])) {
        wp_die('Course ID is required.');
    }

    $course_id = intval($_GET['course_id']);
		$course = new Course($course_id);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Fetch enrolled pupils' survey data for this course
    $enrollment_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE course_id = %d",
            $course_id
        )
    );

    if (empty($enrollment_data)) {
        wp_die('No pupil data found.');
    }

    // Prepare data for CSV
    $csv_data = [];

    foreach ($enrollment_data as $row) {
        $user = get_userdata($row->user_id);
				$attendance_data = maybe_unserialize($row->attendance);
				$table_name = $wpdb->prefix . 'hkota_cpd_records';
				if( $row->certificate_status == 'issued' ){
					$certificate_number = $wpdb->get_var(
			        $wpdb->prepare(
			            "SELECT serial_no FROM $table_name WHERE course_id = %d AND user_id = %d",
			            $course_id, $row->user_id
			        )
			    );
				}
				$mobile = empty( get_user_meta( $row->user_id , 'member_mobile' , true ) ) ? get_user_meta( $row->user_id , 'mobile' , true ) : get_user_meta( $row->user_id , 'member_mobile' , true ) ;
        // Prepare a row with user info and survey answers

				//Handle OT Years data
				if( !empty( get_user_meta( $user->id , 'ot_reg_date' , true ) ) ){
					$date1 = new DateTime(get_user_meta( $user->id , 'ot_reg_date' , true ));
					$date2 = new DateTime("now");
					$interval = $date1->diff($date2);
					$ot_years = $interval->y;
				} else {
					$ot_years = '';
				}

				//Handle Membership status data
				$is_member = 'non-member';
				if ( function_exists( 'wc_memberships_get_user_memberships' ) ){
	        $memberships = wc_memberships_get_user_memberships($user->id);
	        if( !empty($memberships) ){
						foreach ($memberships as $membership ) {
		          // can be: wcm-active, wcm-cancelled, wcm-complimentary, wcm-delayed, wcm-expired, wcm-paused, wcm-pending
		          $status = get_post_status($membership->id);
		          if( $status == 'wcm-active' ){
		            $is_member = 'member';
		          }
							if( $status == 'wcm-paused' ){
		            $is_member = 'member(pending)';
		          }
		        }
					}
	      }

				if( $is_member == 'member' ){
					$member_number = get_user_meta( $user->id , 'member_number' , true );
				} else{
					$member_number = '';
				}


        $data_row = [
            'name_english' 			 => $user->last_name . ' ' . $user->first_name,
            'name_chinese'			 => get_user_meta( $user->id , 'member_full_name_zh' , true ),
						'occupation'				 =>	get_user_meta( $user->id , 'member_field' , true ),
						'ot_years'					 => $ot_years ,
						'member_status'      => $is_member,
						'member_number' 		 => $member_number,
						'OT_number'					 => get_user_meta( $user->id , 'ot_reg_number' , true ),
						'working_place'      => get_user_meta( $user->id , 'member_working_place' , true ),
						'mobile'		 				 => $mobile,
            'email'      				 => $user->user_email,
						'fee'								 => $row->amount,
						'Payment_reference'  => $row->payment_method,
						'enrollment_status'  => $row->status,
						'attendance_status'  => $attendance_data['attendance_status'] ? $attendance_data['attendance_status'] : 'N/A',
						'certificate_status' => $row->certificate_status ? $row->certificate_status : 'N/A' ,
						'certificate_number' => $certificate_number ? $certificate_number : 'N/A',
        ];
				$certificate_number = '';
        $csv_data[] = $data_row;
    }

    // Start CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Pupil data of ' . $course->title . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $csv_file = fopen('php://output', 'w');

    // CSV Headers
    $headers = ['Name (English)', 'Name (Chinese)', 'Occupation', 'OT Years', 'Membership status', 'Membership number', 'OT number', 'Work organisation', 'Mobile', 'Email', 'Course fee', 'Payment reference', 'Enrollment Status', 'Attendance Status', 'Certificate Status', 'Certificate number' ];
    fputcsv($csv_file, $headers);

    // Output the data rows
    foreach ($csv_data as $data_row) {
        fputcsv($csv_file, $data_row);
    }

    fclose($csv_file);
    exit;
}

// Register the admin action hook for exporting attendance data
add_action('admin_post_export_attendance_data', 'export_attendance_data');
function export_attendance_data() {
    global $wpdb;

    // Security check - ensure user can edit courses
    if (!current_user_can('edit_course')) {
        wp_die('Insufficient permissions.');
    }

    // Verify if the course ID is set in the query string
    if (!isset($_GET['course_id'])) {
        wp_die('Course ID is required.');
    }

    $course_id = intval($_GET['course_id']);
    $course = new Course($course_id);
    $table_name = $wpdb->prefix . 'hkota_course_enrollment';

    // Get QR codes (attendance sections) for this course
    $qr_codes = $course->qr_code;
    
    if (empty($qr_codes)) {
        wp_die('No QR codes found for this course.');
    }

    // Fetch enrolled pupils' attendance data for this course
    $enrollment_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, attendance FROM $table_name WHERE course_id = %d",
            $course_id
        )
    );

    if (empty($enrollment_data)) {
        wp_die('No enrollment data found.');
    }

    // Prepare CSV headers
    $headers = ['ID', 'Name', 'Email'];
    
    // Add QR code sections as headers using the same format as QR code labels
    foreach ($qr_codes as $qr_code) {
        if ($qr_code['type'] == 'registration' || $qr_code['type'] == 'end' || $qr_code['type'] == 'end_survey') {
            $header = $qr_code['type'] . ' @ ' . $qr_code['time'] . '/' . $qr_code['date'];
            $headers[] = $header;
        }
    }

    // Prepare data for CSV
    $csv_data = [];

    foreach ($enrollment_data as $row) {
        $user = get_userdata($row->user_id);
        if (!$user) continue;

        // Start with basic user info
        $data_row = [
            $row->user_id,
            $user->last_name . ', ' . $user->first_name,
            $user->user_email
        ];

        // Parse attendance data
        $attendance_data = maybe_unserialize($row->attendance);
        
        // Add attendance status for each QR code section
        foreach ($qr_codes as $qr_code) {
            if ($qr_code['type'] == 'registration' || $qr_code['type'] == 'end' || $qr_code['type'] == 'end_survey') {
                $section_id = $qr_code['id'];
                
                // Check if user attended this section
                $attended = '';
                if (isset($attendance_data['attendance_data'][$section_id]) && $attendance_data['attendance_data'][$section_id] == 1) {
                    $attended = 'checked';
                }
                
                $data_row[] = $attended;
            }
        }

        $csv_data[] = $data_row;
    }

    // Start CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Attendance data of ' . $course->title . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $csv_file = fopen('php://output', 'w');

    // Output CSV headers
    fputcsv($csv_file, $headers);

    // Output the data rows
    foreach ($csv_data as $data_row) {
        fputcsv($csv_file, $data_row);
    }

    fclose($csv_file);
    exit;
}

// Create Certificate
add_action('template_redirect', 'handle_certificate_generation');
function handle_certificate_generation() {
    if (is_page('certificate') && isset($_GET['course_id'])) {
        $course_id = intval($_GET['course_id']);
        $user_id = get_current_user_id();  // Get the logged-in user

        // Validate the input and ensure the user is enrolled in this course
        $course = new Course($course_id);
        $course->create_certificate($user_id);
    }
}

// Protect the certificate file access
add_action('init', 'protect_certificate_access');
function protect_certificate_access() {

  // Get the current URL
  $current_url = $_SERVER['REQUEST_URI'];

  // Check if the URL contains the certificate path. If not, proceed normally
  if (strpos($current_url, 'wp-content/uploads/certificate/') == false) return;

  // Check if the user is logged in
  if (!is_user_logged_in()) {
      ?>
        <div class="error-overlay" id="errorOverlay">
          <div class="error-box" id="errorBox">
              <h2>You must log in to view this file.</h2>
              <a href="<?php echo home_url('/my-account'); ?>" class="button">Log In</a>
          </div>
        </div>
      <?php
      exit;
  }

  // Get the current user ID
  $current_user_id = get_current_user_id();

  // Extract the file name from the URL
  $file_name = basename($current_url);

  // Query the database to get the CPD record that matches the file name
  global $wpdb;
  $table_name = $wpdb->prefix . 'hkota_cpd_records';
  $cpd_record = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE file = %s",
      $file_name
  ));

  // Check if the CPD record exists and if the current user is the owner
  if ( $cpd_record && ( $cpd_record->user_id == $current_user_id || current_user_can('administrator' ) ) ) {
      // The user is authorized to view the certificate, proceed normally
      $file_path = WP_CONTENT_DIR . '/uploads/certificate/' . $file_name;
      if (file_exists($file_path)) {
        // Clear any previous output to prevent corruption
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        
        readfile($file_path);
        exit; // Critical: Stop execution to prevent additional output
    } else {
      ?>
        <div class="error-overlay" id="errorOverlay">
          <div class="error-box" id="errorBox">
              <h2>File not found..</h2>
          </div>
        </div>
      <?php
        echo "<div class='error-box'> <a href='" . home_url() . "'>Return to Home</a></div>";
    }

  } else {
    ?>
    <div class="error-overlay" id="errorOverlay">
      <div class="error-box" id="errorBox">
          <h2>You are not authorized to access this certificate.</h2>
      </div>
    </div>
    <?php
      exit;
  }
  ?>
  <style>
    /* Full-screen overlay to dim the background */
    .error-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5); /* Overlay background */
      z-index: 999;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Centered error box */
    .error-box {
      background-color: white;
      padding: 20px;
      border: 2px solid red;
      border-radius: 10px;
      text-align: center;
      width: 300px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Error message and login button */
    .error-box h2 {
      color: red;
      font-size: 18px;
      margin-bottom: 20px;
    }

    .error-box .button {
      padding: 10px 20px;
      background-color: #0073aa;
      color: white;
      text-decoration: none;
      border-radius: 5px;
    }

    .error-box .button:hover {
      background-color: #005177;
    }
  </style>
  <?php

}

// Protect the pupil uploaded file
add_action('init', 'protect_pupil_file_access');
function protect_pupil_file_access() {

    // Get the current URL
    $current_url = $_SERVER['REQUEST_URI'];

    // Check if the URL contains the certificate path. If not, proceed normally
    if (strpos($current_url, 'wp-content/uploads/pupil-uploaded-files/') === false) {
        return;
    }

    ?>
    <style>
        /* Full-screen overlay to dim the background */
        .error-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Overlay background */
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Centered error box */
        .error-box {
            background-color: white;
            padding: 20px;
            border: 2px solid red;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Error message and login button */
        .error-box h2 {
            color: red;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .error-box .button {
            padding: 10px 20px;
            background-color: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .error-box .button:hover {
            background-color: #005177;
        }
    </style>
    <?php

    // Check if the user is logged in
    if (!is_user_logged_in()) {
        ?>
        <div class="error-overlay" id="errorOverlay">
            <div class="error-box" id="errorBox">
                <h2>You must log in to view this file.</h2>
                <a href="<?php echo home_url('/my-account'); ?>" class="button">Log In</a>
            </div>
        </div>
        <?php
        exit;
    }

    // Get the current user ID
    $current_user_id = get_current_user_id();

    // Extract the file name from the URL
    $file_name = basename($current_url);

    // Check if the user is authorized (admin)
    if (current_user_can('administrator')) {

        // Path to the file
        $file_path = WP_CONTENT_DIR . '/uploads/pupil-uploaded-files/' . $file_name;
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION)); // Get file extension

        // Check if the file exists
        if (file_exists($file_path)) {
            // Start output buffering to avoid issues with headers
            ob_clean();
            flush();

            // Determine the correct content type header based on the file extension
            switch ($file_extension) {
                case 'pdf':
                    header('Content-Type: application/pdf');
                    break;
                case 'jpg':
                case 'jpeg':
                    header('Content-Type: image/jpeg');
                    break;
                case 'png':
                    header('Content-Type: image/png');
                    break;
                default:
                    ?>
                    <div class="error-overlay" id="errorOverlay">
                        <div class="error-box" id="errorBox">
                            <h2>Unsupported file type.</h2>
                        </div>
                    </div>
                    <?php
                    exit;
            }

            // Set the headers for file display
            header('Content-Disposition: inline; filename="' . $file_name . '"');
            header('Content-Length: ' . filesize($file_path));

            // Output the file content
            readfile($file_path);

            // End output buffering
            ob_end_flush();
            exit;

        } else {
            // File not found
            ?>
            <div class="error-overlay" id="errorOverlay">
                <div class="error-box" id="errorBox">
                    <h2>File not found.</h2>
                </div>
            </div>
            <?php
        }

    } else {
        // User not authorized
        ?>
        <div class="error-overlay" id="errorOverlay">
            <div class="error-box" id="errorBox">
                <h2>You are not authorized to access this file.</h2>
            </div>
        </div>
        <?php
        exit;
    }
}

// Add custom
add_action('woocommerce_product_options_pricing', 'add_custom_general_fields');
function add_custom_general_fields() {

  global $post;

  $member_price = get_post_meta($post->ID, 'member_price', true);
  $student_price = get_post_meta($post->ID, 'student_price', true);
  $remarks = get_post_meta($post->ID, 'remarks', true);

  woocommerce_wp_text_input(
      array(
          'id'          => 'member_price',
          'label'       => __('Member price ($)', 'woocommerce'),
          'type'        => 'number', // Limit input to numbers only
          'value'       => $member_price // Display the saved value
      )
  );

  woocommerce_wp_text_input(
      array(
          'id'          => 'student_price',
          'label'       => __('Student price ($)', 'woocommerce'),
          'type'        => 'number', // Limit input to numbers only
          'value'       => $student_price // Display the saved value
      )
  );

  woocommerce_wp_text_input(
      array(
          'id'          => 'remarks',
          'label'       => __('Remarks', 'woocommerce'),
          'value'       => $remarks // Display the saved value
      )
  );

}

// Save the custom field value when saving the product
add_action('woocommerce_process_product_meta', 'save_custom_general_fields');
function save_custom_general_fields($post_id) {
    $custom_field_value = isset($_POST['member_price']) ? sanitize_text_field($_POST['member_price']) : '';
    update_post_meta($post_id, 'member_price', $custom_field_value);
    $custom_field_value = isset($_POST['student_price']) ? sanitize_text_field($_POST['student_price']) : '';
    update_post_meta($post_id, 'student_price', $custom_field_value);
    $custom_field_value = isset($_POST['remarks']) ? sanitize_text_field($_POST['remarks']) : '';
    update_post_meta($post_id, 'remarks', $custom_field_value);
}

// Add custom message to my account dashboard
add_action('woocommerce_account_dashboard', 'custom_dashboard_message');
function custom_dashboard_message() {
    echo '<p class="welcome-message">For HKOTA Official Membership applications <a href='. home_url('/member-registration/') .'>Click here</a></p>';
    echo '<p class="welcome-message">For HKOTA Course application <a href='. home_url('/trainings-and-activities') .'>Click here</a></p>';
}

// Add a custom column to the orders table in WooCommerce.
add_filter('manage_woocommerce_page_wc-orders_columns', 'add_order_items_column', 20, 1);
function add_order_items_column($columns) {
    $new_columns = array();

    // Loop through existing columns to insert our custom column after "order".
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;

        // Insert "Order Items" column right after the "order" column.
        if ('order_number' === $key) {
            $new_columns['order_items'] = __('Order Items', 'woocommerce');
        }
    }

    return $new_columns;
}

// Display the order items in the custom column in WooCommerce orders table.
add_action('manage_woocommerce_page_wc-orders_custom_column', 'show_order_items_column_content', 10, 2);
function show_order_items_column_content($column, $post_id) {
    if ($column === 'order_items') {
        // Get the order object using the post ID.
        $order = wc_get_order($post_id);

        if ($order) {
            $items = $order->get_items();
            $item_names = [];

            // Loop through the order items to get product names.
            foreach ($items as $item) {
                $item_names[] = $item->get_name(); // Get the name of each item.
            }

            // Display the product names as a comma-separated list.
            if (!empty($item_names)) {
                echo implode(', ', $item_names);
            } else {
                echo __('No items found', 'woocommerce');
            }
        }
    }
}

add_action('wc_memberships_before_my_memberships','display_membership_profile');
function display_membership_profile(){

  $user_id = get_current_user_id();
  $args = array(
			'status' => array('active', 'expired','paused')
	);
  $user_memberships = wc_memberships_get_user_memberships($user_id, $args);

  if (!empty($user_memberships)) {

    // Current date and the renewal time window
    $timezone = new DateTimeZone('Asia/Hong_Kong');
    $current_time = new DateTime('now',$timezone);
    $current_year = date('Y');
    $renewal_start = new DateTime("$current_year-04-01",$timezone);
    $renewal_end = new DateTime("$current_year-07-31 23:59:59", $timezone);

    // Check the latest memberships and check if they are renewable
    $membership = $user_memberships[0];
    $expiry_date = get_post_meta($membership->id, '_end_date', true);
    $expiry_year = date('Y', strtotime($expiry_date));

    $status = '';

    if( date('Y', strtotime($expiry_date)) == $current_year ){
      if ($current_time < $renewal_start) {
          $status = 'active';
      } elseif ($current_time > $renewal_end) {
          $status = 'expired';
      } else {
        $status = 'expiring';
      }

    } else {
      if( $membership->status == 'wcm-active' ){
        $status = 'active';
      }
    }

    if( $status == 'active' || $status == 'expiring' || $status == 'expired' ){
      ?>
        <div class="member-profile">
          <div class="profile-heading">Member Profile</div>
          <div class="profile-data">
            <span class="title">Member Type:</span>
            <span class="value">
              <?php echo $membership->plan->name ;?>
            </span>
          </div>
          <div class="profile-data">
            <span class="title">Full membership No:</span>
            <span class="value">
              <?php echo get_user_meta($user_id,'member_number',true); ?>
            </span>
          </div>
          <div class="profile-data">
            <span class="title">Status:</span>
            <span class="value">
              <?php
                echo ucfirst( $status ) ;
                switch( $status ){
                  case 'active':
                    echo "<a href='".home_url('/membership-card/')."'>Download e-membership card</a>";
                    break;
                  default:
                    echo "<a href='".home_url('/member-registration/')."'>Renew membership</a>";
                    break;
                }
              ?>
            </span>
          </div>
        </div>
      <?php
    } else {
      echo "You have no active membership.";
    }
  }

  ?>
    <div class="profile-heading">Membership History</div>
  <?php

}

add_action('template_redirect', 'display_membership_card');
function display_membership_card() {
    if ( is_page('membership-card') ) {
        $user_id = get_current_user_id();
        $args = array(
            'status' => array('active')
        );
        $user_memberships = wc_memberships_get_user_memberships($user_id, $args);

        // Check if the user is logged in
        if (!is_user_logged_in()) {
          echo "You must log in to view this file. <a href='".home_url("/login")."'>Login</a>";
          exit;
        }

        if( empty($user_memberships) ) {
          echo "Sorry, you are not an active member. <a href='".home_url()."'>Return to home</a>";
          exit;
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');

        ob_start();

        $args = array(
          'title'             => get_user_meta( $user_id, 'member_last_name_eng' , true).' '.get_user_meta( $user_id, 'member_first_name_eng' , true),
          'plan'              => ucwords( $user_memberships[0]->plan->name ),
          'membership_number' => get_user_meta( $user_id, 'member_number', true)
        );

        load_template( dirname(__FILE__) . "/membership-card.php" ,true, $args );

        $html_content = ob_get_clean();

        $dompdf->loadHtml($html_content);

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        $dompdf->stream("certificate-preview", array("Attachment" => 0));

        exit;



    }
}

add_action('login_redirect', 'login_redirect_import_check', 10);
function login_redirect_import_check() {

    $redirect = home_url('/?import-check=true');

		return $redirect;

}

add_action('template_redirect', 'display_name_form_for_import_user');
function display_name_form_for_import_user() {
    if (isset($_GET['import-check']) && is_user_logged_in() && !current_user_can('administrator')) {
        if (get_user_meta(get_current_user_id(), 'member_imported_user', true) == 'yes') {
            ?>
            <style>
                /* Simple CSS to center the form */
                #name-check-form-container {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.6);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }
                #name-check-form {
                    background: #fff;
                    padding: 20px;
                    width: 90%;
                    max-width: 400px;
                    border-radius: 8px;
                    display: flex;
                    flex-direction: column;
                }
                .warning-message {
                    color: #ff0000;
                    margin-bottom: 10px;
                    font-size: 14px
                }
                label{
                    display: inline-block;
                    width: 100px;
                }
                input {
                    height: 34px;
                    border: unset;
                    background: #c7c7c735;
                    font-size: 16px;
                    padding: 5px;
                }

                button#submit-name-check {
                    background: #008080;
                    border: unset;
                    color: #FFF;
                    padding: 10px;
                }
            </style>

            <div id="name-check-form-container">
                <form id="name-check-form">
                    <div class="warning-message">
                        Please provide your genuine first and last names based on your personal identification document.
                        This information is crucial, as it will be used for issuing certifications for any future courses
                        you may complete with HKOTA. Please double-check your entries, as you will not be able to edit this
                        information in the system later.
                    </div>
                    <label for="last_name">Surname:</label>
                    <input type="text" name="last_name" id="last_name" required><br><br>

                    <label for="first_name">Given Name:</label>
                    <input type="text" name="first_name" id="first_name" required><br><br>

                    <button type="button" id="submit-name-check">Submit</button>
                </form>
            </div>
            <?php

        }
    }
}

add_action( 'login_enqueue_scripts', 'my_login_logo' );
function my_login_logo() {
	?>
    <style type="text/css">
        #login h1 a {
            background-image: url(<?php echo plugins_url( '/hkota-courses-and-memberships/asset/logo-wz-text.png' )?> );
						height: 65px;
						width: 320px;
						background-size: 320px 65px;
						background-repeat: no-repeat;
	        	padding-bottom: 30px;
        }
    </style>
	<?php
}

