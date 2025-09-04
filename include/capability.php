<?php

add_action('admin_footer', 'capability_control_wc_user_membership_edit_page');
function capability_control_wc_user_membership_edit_page() {
    global $post;

    // Check if we're editing a `wc_user_membership` post type
    if (isset($post->post_type) && $post->post_type === 'wc_user_membership') {
			if( !current_user_can('edit_member') ){
        ?>
				<script>

	        jQuery(document).ready(function(){
	          jQuery(`#wc-memberships-user-membership-data input,
                    #wc-memberships-user-membership-data select,
                    #wc-memberships-user-membership-data button`).prop( "disabled", true );
						jQuery(`h2.nav-tab-wrapper.woo-nav-tab-wrapper ,
										.page-title-action,
										#postbox-container-1 ,
										ul.user_membership_actions.submitbox,
										div#wc-memberships-user-membership-notes`).remove();
            jQuery('.row-actions').remove();
            jQuery('#bulk-action-selector-top option[value="delete"]').remove();
						jQuery('h3.membership-plans a').each(function(){
							url = jQuery(this).attr('href');
							if( url.includes("post-new") ){
								jQuery(this).parent().remove();
							}
						});
	        });

	      </script>
				<style>
					div#post-body {
						margin: unset !important;
					}
				</style>
        <?php
			}
    }
}

add_action('admin_footer', 'capability_control_wc_user_course_edit_page');
function capability_control_wc_user_course_edit_page() {
    global $post;

    // Check if we're editing a `wc_user_membership` post type
    if (isset($post->post_type) && $post->post_type === 'course') {
			if( !current_user_can('edit_course') ){
        ?>
        <script>

          jQuery(document).ready(function(){

            jQuery('.page-title-action, #edit-slug-buttons, .fa-solid.fa-circle-xmark').remove();
            jQuery('#submitdiv, #categorydiv, #tagsdiv-post_tag').remove();
            jQuery('#course_details input,#course_details textarea,#course_details select, #course_details button:not(.tab-button)').prop( "disabled", true );
            jQuery('.row-actions, .alignleft.actions.bulkactions').remove();

          });

        </script>
        <?php
			}
    }
}


?>
