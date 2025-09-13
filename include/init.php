<?php

include dirname(__FILE__) . '/edit-course-metaboxes.php' ;
include dirname(__FILE__) . '/class-course.php' ;
include dirname(__FILE__) . '/class-enrollment.php' ;
include dirname(__FILE__) . '/class-hkota-membership.php' ;
include dirname(__FILE__) . '/ajax.php' ;
include dirname(__FILE__) . '/enrollment-function.php' ;  
include dirname(__FILE__) . '/membership-function.php' ;
include dirname(__FILE__) . '/core-functions.php' ;
include dirname(__FILE__) . '/my-account.php' ;
include dirname(__FILE__) . '/user-registration.php' ;
include dirname(__FILE__) . '/capability.php' ;

add_action( 'wp_enqueue_scripts', 'hkota_enqueue_plugin_assets', 20 );
add_action( 'admin_enqueue_scripts', 'hkota_enqueue_plugin_assets', 20 );

function hkota_enqueue_plugin_assets() {
	wp_enqueue_script( 'hkota-membership-js',  plugins_url( '/hkota-courses-and-memberships/public/membership.js'));
	wp_enqueue_style( 'hkota-membership-css', plugins_url( '/hkota-courses-and-memberships/public/membership.css'));
}

add_action( 'init', 'init_plugin' );

function init_plugin(){
  create_course_posttype();
  maybe_install_plugin_table();
  maybe_create_course_dummy_product();
	maybe_create_membership_dummy_product();
  maybe_create_course_file_folder();
  maybe_create_pages();
  maybe_create_certificate_htaccess();
	maybe_create_protect_files_htaccess();
	add_action('admin_init', 'add_courses_capabilities');
}

function create_course_posttype() {
    $args = array(
        'labels'              => array(
          'name' => __( 'Courses' , 'hkota'),
          'singular_name' => __( 'Course' , 'hkota'),
          'all_items' => __( 'All Courses', 'hkota' ),
          'view_item' => __( 'View Course', 'hkota' ),
          'add_new_item' => __( 'Add New Course', 'hkota' ),
          'add_new' => __( 'Add New Course', 'hkota' ),
          'edit_item' => __( 'Edit Course', 'hkota' ),
          'update_item' => __( 'Update Course', 'hkota' ),
          'search_items' => __( 'Search Course', 'hkota' ),
          'not_found' => __( 'Not Found', 'hkota' ),
          'not_found_in_trash' => __( 'Not found in Trash', 'hkota' )
        ),
        'rewrite'             => array('slug' => 'courses'),
        'public'              => true,
        'publicly_queryable'  => true,
        'delete_with_user'    => true,
        'show_in_admin_bar'   => false,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'delete_with_user'    => false,
				'capabilities'        => array(
            'edit_post'          => 'read_course',
            'read_post'          => 'read_course',
            'delete_post'        => 'delete_course',
            'edit_posts'         => 'edit_courses',
            'edit_others_posts'  => 'edit_others_courses',
            'publish_posts'      => 'publish_courses',
            'read_private_posts' => 'read_private_courses',
            'create_posts'       => 'edit_courses', // Use the same as `edit_posts` for simplicity
        ),
        'supports'             => array( 'title', 'revisions' ),
        'register_meta_box_cb' => 'add_course_metaboxes',
        'menu_icon'            => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IS0tIFVwbG9hZGVkIHRvOiBTVkcgUmVwbywgd3d3LnN2Z3JlcG8uY29tLCBHZW5lcmF0b3I6IFNWRyBSZXBvIE1peGVyIFRvb2xzIC0tPg0KPHN2ZyB3aWR0aD0iODAwcHgiIGhlaWdodD0iODAwcHgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4NCjxwYXRoIGQ9Ik0xMC4wNjMgMjEuNzkxN0MxMC4zODk0IDIyLjE0ODMgMTEgMjEuOTUzNCAxMSAyMS40Njk5VjRDMTAgMyA4LjkxMjU1IDIuNTcxNTEgNy43ODM2MSAyLjMyMjQ2QzUuNzgzMTEgMS44ODExMyA0LjAwMjQgMS45NzY5MyAyLjkxMjY1IDIuMTE4NzZDMS43MDU0MyAyLjI3NTg3IDEgMy4zNDkzMSAxIDQuNDA1NjFWMTcuNTY2MkMxIDE4Ljk4OTUgMi4xODgzNCAyMC4xMTE1IDMuNTY4MDcgMjAuMDY2QzQuNzEwMTEgMjAuMDI4NCA2LjI5NTIgMjAuMDY4OCA3LjczMTA1IDIwLjQxNThDOC44MjU5NiAyMC42ODAzIDkuNTIyMzcgMjEuMjAwOSAxMC4wNjMgMjEuNzkxN1oiIGZpbGw9IiMwMDAwMDAiLz4NCjxwYXRoIGQ9Ik0xMy45MzcgMjEuNzkxN0MxMy42MTA2IDIyLjE0ODMgMTMgMjEuOTUzNCAxMyAyMS40Njk5VjRDMTQgMyAxNS4wODc0IDIuNTcxNTEgMTYuMjE2NCAyLjMyMjQ2QzE4LjIxNjkgMS44ODExMyAxOS45OTc2IDEuOTc2OTMgMjEuMDg3MyAyLjExODc2QzIyLjI5NDYgMi4yNzU4NyAyMyAzLjM0OTMxIDIzIDQuNDA1NjFWMTcuNTY2MkMyMyAxOC45ODk1IDIxLjgxMTcgMjAuMTExNSAyMC40MzE5IDIwLjA2NkMxOS4yODk5IDIwLjAyODQgMTcuNzA0OCAyMC4wNjg4IDE2LjI2OSAyMC40MTU4QzE1LjE3NCAyMC42ODAzIDE0LjQ3NzYgMjEuMjAwOSAxMy45MzcgMjEuNzkxN1oiIGZpbGw9IiMwMDAwMDAiLz4NCjwvc3ZnPg=='
    );

    register_post_type( 'course', $args );

}

function add_courses_capabilities() {
    $role = get_role('administrator'); // Change 'editor' to your desired role

    $role->add_cap('edit_course');
    $role->add_cap('read_course');
    $role->add_cap('delete_course');
    $role->add_cap('edit_courses');
    $role->add_cap('edit_others_courses');
    $role->add_cap('publish_courses');
    $role->add_cap('read_private_courses');
}

// Add a course code column to the course listing table
add_filter('manage_course_posts_columns', 'add_course_code_column');
function add_course_code_column($columns) {
		$new_columns = array();
		foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['course_code'] = 'Course code';
        }
    }

    return $new_columns;
}

// Display course code in the new column
add_action('manage_course_posts_custom_column', 'display_course_code_column', 10, 2);
function display_course_code_column($column, $post_id) {
    if ('course_code' === $column) {
        // Get the meta value for the post
        $code = get_post_meta($post_id, 'course_code', true);
        echo $code ? esc_html($code) : "-";
    }
}

function maybe_install_plugin_table() {

  global $wpdb;
  $prefix = $wpdb->prefix;
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  $create_ddl = "CREATE TABLE {$prefix}hkota_options (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      option_name varchar(255) NOT NULL,
      option_value varchar(255) NOT NULL,
      PRIMARY KEY (ID)
  );";

  // If table not exist, creat the hkota_options table
  maybe_create_table( $prefix."hkota_options" , $create_ddl);

  $create_ddl = "CREATE TABLE {$prefix}hkota_course_enrollment (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      user_id varchar(255) NOT NULL,
      course_id varchar(255) NOT NULL,
      status varchar(255) NOT NULL,
      certificate_status varchar(255),
      order_id varchar(255),
      amount varchar(255),
      date_created_gmt varchar(255) NOT NULL,
      payment_method varchar(255),
      attendance longtext,
      survey longtext,
      quiz longtext,
      uploads longtext,
      PRIMARY KEY (ID)
  );";

  // If table not exist, creat the hkota_course_enrollment table
  maybe_create_table( $prefix."hkota_course_enrollment" , $create_ddl);

  $create_ddl = "CREATE TABLE {$prefix}hkota_cpd_records (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      user_id varchar(255) NOT NULL,
      course_id varchar(255),
      code varchar(255),
      serial_no varchar(255),
      title varchar(255) NOT NULL,
      enrollment_id varchar(255),
      status varchar(255) NOT NULL,
      cpd_point varchar(255) NOT NULL,
      date_issued varchar(255) NOT NULL,
      organization varchar(255) NOT NULL,
      file varchar(255),
      PRIMARY KEY (ID),
      UNIQUE (serial_no)
  );";

	// If table not exist, creat the hkota_cpd_records table
  maybe_create_table( $prefix."hkota_cpd_records" , $create_ddl);

  // If table not exist, creat the hkota_course_enrollment table
  maybe_create_table( $prefix."hkota_ot_list" , $create_ddl);

	$create_ddl = "CREATE TABLE {$prefix}hkota_ot_list (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			Registration_no varchar(255) NOT NULL,
      eng_name varchar(255) NOT NULL,
      chi_name varchar(255) NOT NULL,
      PRIMARY KEY (ID)
  );";

  // If table not exist, creat the hkota_course_enrollment table
  maybe_create_table( $prefix."hkota_ot_list" , $create_ddl);

}

function get_dummy_product_id(){

  global $wpdb;

  $prefix = $wpdb->prefix;

  $sql = "SELECT option_value FROM {$prefix}hkota_options
          WHERE
          option_name = %s
          ";

  return $wpdb->get_var( $wpdb->prepare( $sql,  "hkota_course_product_id" ) );

}

function maybe_create_course_dummy_product(){

  global $wpdb;

  $prefix = $wpdb->prefix;

  $dummy_product_id = get_dummy_product_id();

  if( empty($dummy_product_id) or
      !wc_get_product( $dummy_product_id ) or
      get_post_status($dummy_product_id) !== 'publish' ){

    //product is not set

    $product = new WC_Product_Simple();

    $product->set_name( 'HKOTA Course Registration' ); // product title

    $product->set_slug( 'hkota-course-registration' );

    $product->set_regular_price(0);

    $sql = "DELETE FROM {$prefix}hkota_options
            WHERE
            option_name = %s
            ";

    $wpdb->query( $wpdb->prepare($sql, "hkota_course_product_id") );

    $sql = "INSERT INTO {$prefix}hkota_options
            (ID, option_name, option_value)
            VALUES
            (NULL, %s, %d);
            ";

    $product->set_sold_individually( true );

    $product->save();

    $wpdb->query( $wpdb->prepare($sql, "hkota_course_product_id" , $product->get_id() ) );

  } else {

    //product is set, check if it is set correctly

    $product = wc_get_product($dummy_product_id);

    if(!$product->get_virtual()){
      $product->set_virtual(true);
    }

    if( $product->get_regular_price() !== 0 ){
      $product->set_regular_price(0);
    }

    $product->set_sold_individually( true );

    $product->save();

  }

}

function get_membership_dummy_product_id(){

  global $wpdb;

  $prefix = $wpdb->prefix;

  $sql = "SELECT option_value FROM {$prefix}hkota_options
          WHERE
          option_name = %s
          ";

  return $wpdb->get_var( $wpdb->prepare( $sql,  "hkota_membership_product_id" ) );

}

function maybe_create_membership_dummy_product(){

  global $wpdb;

  $prefix = $wpdb->prefix;

  $dummy_product_id = get_membership_dummy_product_id();

  if( empty($dummy_product_id) or
      !wc_get_product( $dummy_product_id ) or
      get_post_status($dummy_product_id) !== 'publish' ){

    //product is not set

    $product = new WC_Product_Simple();

    $product->set_name( 'HKOTA Membership Registration' ); // product title

    $product->set_slug( 'hkota-membership-registration' );

    $product->set_regular_price(0);

    $sql = "DELETE FROM {$prefix}hkota_options
            WHERE
            option_name = %s
            ";

    $wpdb->query( $wpdb->prepare($sql, "hkota_membership_product_id") );

    $sql = "INSERT INTO {$prefix}hkota_options
            ( ID ,  option_name , option_value)
            VALUES
            (NULL, %s, %d);
            ";

    $product->set_sold_individually( true );

    $product->save();

    $wpdb->query( $wpdb->prepare($sql, "hkota_membership_product_id" , $product->get_id() ) );

  } else {

    //product is set, check if it is set correctly

    $product = wc_get_product($dummy_product_id);

    if(!$product->get_virtual()){
      $product->set_virtual(true);
    }

    if( $product->get_regular_price() !== 0 ){
      $product->set_regular_price(0);
    }

    $product->set_sold_individually( true );

    $product->save();

  }

}

function maybe_create_course_file_folder(){
  $upload_dir = wp_upload_dir();
  if ( !empty( $upload_dir['basedir'] ) ) {
      wp_mkdir_p($upload_dir['basedir'].'/course-files');
      wp_mkdir_p($upload_dir['basedir'].'/course-poster');
      wp_mkdir_p($upload_dir['basedir'].'/course-qr-code');
      wp_mkdir_p($upload_dir['basedir'].'/pupil-uploaded-files');
      wp_mkdir_p($upload_dir['basedir'].'/certificate');
  }
}

function maybe_create_pages() {
    // Define the page data
    $pages_to_create = [
        [
            'slug' => 'pupil',
            'title' => 'PUPIL (DO NOT DELETE)'
        ],
        [
            'slug' => 'quiz',
            'title' => 'QUIZ (DO NOT DELETE)'
        ],
        [
            'slug' => 'certificate',
            'title' => 'CERTIFICATE (DO NOT DELETE)'
        ],
        [
            'slug' => 'membership-card',
            'title' => 'MEMBERSHIP CARD (DO NOT DELETE)'
        ]
    ];

    // Loop through each page to check if it exists
    foreach ($pages_to_create as $page) {
        // Check if the page with the given slug exists
        $existing_page = get_page_by_path($page['slug']);

        // If the page does not exist, create it
        if (!$existing_page) {
            $new_page = [
                'post_title'    => $page['title'],
                'post_name'     => $page['slug'],
                'post_content'  => '', // You can add content if needed
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1 // The user ID of the page author, can be changed
            ];

            // Insert the page into the database
            $new_page_id = wp_insert_post($new_page);

        }
    }
}

//Create a .htaccess file in put it into /certificate to set up wordpress environment.
function maybe_create_certificate_htaccess() {

    // Define the .htaccess file path
    $htaccess_file = COURSE_CERTIFICATE_DIR . '.htaccess';

    // Check if the .htaccess file already exists
    if (!file_exists($htaccess_file)) {

        // Create the .htaccess content
        $htaccess_content = <<<HTACCESS
        <FilesMatch "\.(pdf)$">
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} -f
            RewriteCond %{QUERY_STRING} !nocache
            RewriteCond %{REQUEST_URI} ^/wp-content/uploads/certificate/
            RewriteRule (.*) /wp-load.php [L]
        </FilesMatch>
        HTACCESS;

        // Write the .htaccess file to the directory
        file_put_contents($htaccess_file, $htaccess_content);

        // Optional: Set file permissions
        chmod($htaccess_file, 0644);
    }
}

function maybe_create_protect_files_htaccess() {
    // Define the file paths
		$htaccess_content = "Options -Indexes\n";
    $htaccess_path = COURSE_FILE_DIR . '.htaccess';
    if ( ! file_exists( $htaccess_path ) ) {
        file_put_contents( $htaccess_path, $htaccess_content );
    }
		$htaccess_path = COURSE_POSTER_DIR . '.htaccess';
    if ( ! file_exists( $htaccess_path ) ) {
        file_put_contents( $htaccess_path, $htaccess_content );
    }
		$htaccess_path = COURSE_QR_CODE_DIR . '.htaccess';
    if ( ! file_exists( $htaccess_path ) ) {
        file_put_contents( $htaccess_path, $htaccess_content );
    }
		$htaccess_path = COURSE_PUPIL_FILE_DIR . '.htaccess';
    if ( ! file_exists( $htaccess_path ) ) {
			// Create the .htaccess content
				$htaccess_content = <<<HTACCESS
				<FilesMatch ".*">
						RewriteEngine On
						RewriteCond %{REQUEST_FILENAME} -f
						RewriteCond %{QUERY_STRING} !nocache
						RewriteCond %{REQUEST_URI} ^/wp-content/uploads/pupil-uploaded-files/
						RewriteRule (.*) /wp-load.php [L]
				</FilesMatch>
				HTACCESS;
        file_put_contents( $htaccess_path, $htaccess_content );
    }

}