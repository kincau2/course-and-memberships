jQuery(document).ready(function (e) {

    jQuery('#course-filter select').on('change', function() {
          var courseType = jQuery('#course-type').val();
          var year = jQuery('#year').val();
          var month = jQuery('#month').val();

          jQuery.ajax({
              url: hkota_frontend_ajax.ajaxurl,
              type: 'POST',
              data: {
                  action: 'filter_courses',
                  course_type: courseType,
                  year: year,
                  month: month
              },
              success: function(response) {
                  jQuery('#course-hook').html(response); // Replace the content of #course-hook with the filtered courses
              }
          });
    });

    jQuery(document).on('click', '#add-to-cart-button', function (e) {
        e.preventDefault();

        var product_id = jQuery(this).data('product-id'); // Get product ID
        var course_id = jQuery(this).data('course-id'); // Get course ID
        var button = jQuery(this);
        button.append(' <i style="margin-left:5px;" class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        // Check eligibility via AJAX
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl, // Admin-ajax.php file
            type: 'POST',
            data: {
                action         : 'check_course_eligibility', // PHP handler
                course_id      : course_id,
                is_waiting_list: false
            },
            success: function (response) {
                if (response.success) {
                    // If eligibility is passed and course requires document upload
                    if (response.data.requires_upload) {
                        button.find('i.fa-spinner').remove();
                        button.prop('disabled', false);
                        openUploadPopup(product_id, course_id, response.data.required_certs);
                    } else {
                        addToCart(product_id, course_id); // Directly add to cart if no upload required
                    }
                } else {
                    showMessage('error', response.data.message );
                    if(response.data.redirect){
                      button.find('i.fa-spinner').remove();
                      button.prop('disabled', false);
                      window.location = "/login";
                    }
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                }

            },
            error: function () {
                showMessage('error', 'An unexpected error occurred.');
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            }
        });
    });

    jQuery(document).on('click', '#add-to-waiting-button', function (e) {
        e.preventDefault();

        var product_id = jQuery(this).data('product-id'); // Get product ID
        var course_id = jQuery(this).data('course-id'); // Get course ID
        var button = jQuery(this);
        button.append(' <i style="margin-left:5px;" class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        // Check eligibility via AJAX
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl, // Admin-ajax.php file
            type: 'POST',
            data: {
              action         : 'check_course_eligibility', // PHP handler
              course_id      : course_id,
              is_waiting_list: true
            },
            success: function (response) {
                if (response.success) {
                    // If eligibility is passed
                    addToWaitingList(product_id, course_id); // Directly add to cart if no upload required
                } else {
                    showMessage('error', response.data.message );
                    if(response.data.redirect){
                      button.find('i.fa-spinner').remove();
                      button.prop('disabled', false);
                      window.location = "/login";
                    }
                }
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            },
            error: function () {
                showMessage('error', 'An unexpected error occurred.');
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            }
        });
    });

    jQuery('.woocommerce-additional-fields input[type="file"]').on('change', function(e) {

      var elem_id = jQuery(this).attr('id');
      var file_data = jQuery(this).prop('files')[0];
      var maxSize = 5 * 1024 * 1024; // 5MB in bytes
      var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
      var hiddenInputID = '#' + elem_id + '_hidden';

      if ( file_data === undefined ) {
          jQuery(hiddenInputID).val('');
          jQuery.ajax({
              url: hkota_frontend_ajax.ajaxurl,
              type: 'POST',
              data: {
                action         : 'delete_membership_uploaded_file', // PHP handler
                document_type      : elem_id,
              },
              success: function(response) {
                  if (response.success) {
                      showMessage('notice', elem_id + ' deleted.');
                  } else {
                      showMessage('error',response.data.message);
                  }
              },
              error: function() {
                  showMessage('error','unexpected error occurred.');
              }
          });
          return;
      }

      if (file_data.size > maxSize) {
          showMessage('error','The file is too large. Max size is 5MB.');
          jQuery(hiddenInputID).val('');
          return;
      }

      if ( jQuery.inArray(file_data.type, allowedTypes) === -1) {
          showMessage('error','Invalid file type. Only JPG, PNG, and PDF are allowed.');
          jQuery(hiddenInputID).val('');
          return;
      }

      jQuery(hiddenInputID).val('uploaded');

      var form_data = new FormData();
      form_data.append('upload_document', file_data);
      form_data.append('action', 'membership_application_upload_file');
      form_data.append('document_type', elem_id);

      jQuery.ajax({
          url: hkota_frontend_ajax.ajaxurl,
          type: 'POST',
          contentType: false,
          processData: false,
          data: form_data,
          success: function(response) {
              if (response.success) {
                  showMessage('notice', elem_id + ' uploaded.');
              } else {
                  showMessage('error',response.data.message);
              }
          },
          error: function() {
              showMessage('error','File upload failed. Please try again.');
          }
      });
    });

    // Open popup for document upload
    function openUploadPopup(product_id, course_id, certRequirements) {
        // Build HTML for the popup with required certificates dynamically
        var popupHtml = `
            <div id="upload-popup-overlay" class="overlay">
                <div id="upload-popup" class="popup-box">
                    <div class="popup-header">
                        <h3>Upload Required Documents</h3>
                        <span class="close-popup">&times;</span>
                    </div>
                    <div class="enrollment-notice"><b>Important notice:</b> We will need to review your uploaded document before confirming your seat on this course.
                    Please be reminded that if there are faults in the document you provided, we will deduct an admin fee in prior of course fee refund.</div>
                    <div style=" margin-bottom: 20px;">Please upload the certificate base on serial number shown below. </div>
                    <form id="upload-form" enctype="multipart/form-data">
                        <input type="hidden" name="course_id" value="` + course_id + `">
                        <input type="hidden" name="product_id" value="` + product_id + `">
                        `;

        // Dynamically generate file upload fields for each required certification
        certRequirements.forEach(function (cert) {
            popupHtml += `<div class="file-wrapper"><label for="cert_${cert}">${cert}:<span class="theme-red">*</span></label>`;
            popupHtml += `<input type="file" id="cert_${cert}" name="cert_files[cert_${cert}]" accept=".pdf,.jpg,.jpeg,.png" required><br></div>`;
        });

        // Close form
        popupHtml += `<div style="display: flex;flex-direction: row-reverse;justify-content: flex-end;align-items: flex-start;height: 37px;">
                        <label for="unable" style=" margin: 0 0 0 10px; ">I can not provide the certificate document.</label>
                        <input type="checkbox" id="unable" name="unable">
                        <br><br>
                      </div>
                      <p class="text-wrapper" style="display:none;">Please provide the serial number of the matching certificate.</p>`;

        certRequirements.forEach(function (cert) {
            popupHtml += `<div class="text-wrapper" style="display:none;"><label for="text_cert_${cert}">${cert}:<span class="theme-red">*</span></label>`;
            popupHtml += `<input type="text" id="text_cert_${cert}" name="text_cert_${cert}"><br><br></div>`;
        });

        popupHtml += `<br><button type="submit" id="submit-upload" class="submit-button">Submit</button>
                    </form>
                </div>
            </div>`;

        jQuery(document).on('click', '#unable',function(){
          if( jQuery(this).is(':checked') ){
            jQuery('.file-wrapper input[type="file"]').val('');
            jQuery('.file-wrapper input[type="file"]').removeAttr('required');
            jQuery('.file-wrapper').hide();
            jQuery('.text-wrapper').show();
            jQuery('.text-wrapper input[type="text"]').attr('required',true);
          } else{
            jQuery('.text-wrapper input[type="text"]').val('');
            jQuery('.text-wrapper input[type="text"]').removeAttr('required');
            jQuery('.text-wrapper').hide();
            jQuery('.file-wrapper').show();
            jQuery('.file-wrapper input[type="file"]').attr('required',true);
          }

        });

        // Append the popup to the body
        if( jQuery('#upload-popup-overlay').length == 0 ){
          jQuery('body').append(popupHtml);
        }

        // Close the popup when clicking the close button
        jQuery('.close-popup').on('click', function () {
            jQuery('#upload-popup-overlay').remove();
        });

        // Close popup when clicking on the overlay, but not the popup content itself
        jQuery('#upload-popup-overlay').on('click', function (e) {
            if (e.target.id === 'upload-popup-overlay') {
                jQuery('#upload-popup-overlay').remove();
            }
        });

        // Handle form submission and file upload via AJAX
        jQuery('#upload-popup').on('submit', '#upload-form', function (e) {
            e.preventDefault();
            var formData = new FormData(jQuery(this)[0]);
            // Add additional data if needed (like course ID or other metadata)
            formData.append('action', 'handle_file_upload');
            formData.append('course_id', course_id);
            formData.append('product_id', product_id);
            jQuery.ajax({
                url: hkota_frontend_ajax.ajaxurl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    if (response.success) {
                        showMessage('notice', 'Information saved.');
                        jQuery('#upload-popup-overlay').remove();
                        addToCart(product_id, course_id, response.data.document );
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function () {
                    showMessage('error', 'An error occurred while uploading the file.');
                }
            });
        });
    }

    // Function to add product to cart via AJAX
    function addToCart(product_id, course_id, uploads = {}) {
        var button = jQuery('button[data-course-id='+course_id+']');
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'handle_ajax_add_to_cart', // Same function in PHP
                product_id: product_id,
                course_id: course_id,
                uploads: uploads // Send the uploaded files' URLs
            },
            success: function (response) {
                if (response.success) {
                    showMessage('notice', 'Course added to cart successfully.');
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                    window.location.href = '/checkout';
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function () {
                showMessage('error', 'An unexpected error occurred.');
            }
        });
    }

    // Function to add pupil to waiting list via AJAX
    function addToWaitingList(product_id, course_id) {
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'handle_ajax_add_to_waiting_list', // Same function in PHP
                product_id: product_id,
                course_id: course_id,
            },
            success: function (response) {
                if (response.success) {
                    showMessage('notice', 'You have registered to waiting list of this course.');
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function () {
                showMessage('error', 'An unexpected error occurred. Please try again later.');
            }
        });
    }

    // Click on a day to show course details
    jQuery(document).on('click', '.day.event', function () {
        jQuery('#calender-loader').show();
        let selectedDate = jQuery(this).data('date');
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_courses_by_day',
                date: selectedDate
            },
            success: function (response) {
              if(response.success){
                jQuery('#course-details').empty(); // Clear previous details
                if (response.data.course.length) {
                    response.data.course.forEach(function (course) {
                        var courseItem = `<div class="calender-course-wrapper">
                                              <div class="course-date">${changeDateFormat(course.start_date)}</div>
                                              <div class="course-details">
                                                <div class="course-title ${course.type}">${course.title}</div>`;
                        if( course.cpd_point ){
                          courseItem += `<div>CPD Points: ${course.cpd_point}</div>`;
                        }
                        courseItem += `</div></div>`;
                        jQuery('#course-details').append(courseItem);
                    });
                }
              }
              get_course_loop(selectedDate,selectedDate);
            },
            error: function () {
                showMessage('error', 'An unexpected error occurred.');
                jQuery('#calender-loader').hide();
            }

        });
    });

    jQuery(document).on('click', '.day:not(.event)', function () {
        jQuery('#course-details').empty(); // Clear previous details
        jQuery('#display-course .course-catalog').html('<p>No course found.</p>');
    });

    var popupContent = `
      <h2>Select Application Type</h2>
      <div class="membership-button-wrapper">
          <button id="renew-membership-button" class="button">Renew Membership</button>
          <button id="new-membership-button" class="button">New Membership Application</button>
      </div>
      `;

    // Show popup when 'Membership Application' button is clicked
    jQuery('.membership-application-button').on('click', function(e) {
        e.preventDefault();
        jQuery('#membership-popup').show();
        jQuery('#popup-content').html(popupContent);
        jQuery('#overlay').show();
    });

    // Close popup when 'x' is clicked
    jQuery(document).on('click', '#close-popup', function() {
        jQuery('#membership-popup').hide();
        jQuery('#overlay').hide();
    });

    // Close popup when clicking outside of it
    jQuery(document).on('click', '#overlay' , function() {
        jQuery('#membership-popup').hide();
        jQuery('#overlay').hide();
    });

    jQuery(document).on('click', '#cancel-renewal' , function() {
        jQuery('#membership-popup').hide();
        jQuery('#overlay').hide();
    });

    // AJAX request to check for membership renewal
    jQuery(document).on('click', '#renew-membership-button', function(e) {
      e.preventDefault();
      var button = jQuery(this);
      button.append(' <i style="margin-left:5px;" class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
      // Add spinner icon dynamically and Disable the button to prevent multiple clicks

      jQuery.ajax({
          url: hkota_frontend_ajax.ajaxurl,
          type: 'POST',
          data: {
              action: 'check_membership_renewal'
          },
          success: function(response) {
              if (response.success) {
                  // User is eligible to renew, populate the tenure options
                  let tenureOptions = '';

                  // Loop through the tenure data and create option elements
                  jQuery.each(response.data.tenure, function(index, tenure) {
                      tenureOptions += `<option value="${tenure.Years}">${tenure.Years} Year(s)</option>`;
                  });

                  // Replace the button with a new row of buttons and select for tenures
                  jQuery('#popup-content').html(`
                      <h2>Your current membership plan: ${response.data.membership_plan_title}</h2>
                      <div class="renewal">
                          <label for="years">Select years for renewal:</label>
                          <select id="renewal-years">
                              ${tenureOptions}
                          </select>
                      </div>
                      <button id="confirm-renewal" data-user-membership-id="${response.data.user_membership_id}" data-membership-id='${response.data.membership_plan_id}' class="renew-btn">Confirm Renewal</button>
                      <button id="cancel-renewal" class="cancel-btn">Cancel</button>
                  `);
                  //Enable the button again
                  button.find('i.fa-spinner').remove();
                  button.prop('disabled', false);
              } else {
                  // Display error message
                  showMessage('error', response.data.message);
                  if(response.data.redirect){
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                    window.location = "/login";
                  }
                  button.find('i.fa-spinner').remove();
                  button.prop('disabled', false);
              }
          },
          error: function() {
              showMessage('error', 'An error occurred, please try again later.');
              //Enable the button again
              button.find('i.fa-spinner').remove();
              button.prop('disabled', false);
          }
      });

    });

    // Handle renewal confirmation
    jQuery(document).on('click', '#confirm-renewal', function() {

        const years = jQuery('#renewal-years').val();
        const membership_plan_id = jQuery(this).data('membership-id');
        const user_membership_id = jQuery(this).data('user-membership-id');
        const today = new Date();
        const end_year = today.getFullYear() + 1;
        var button = jQuery(this);
        button.append(' <i style="margin-left:5px;" class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        // Send AJAX request to add the product to the cart
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_membership_product_to_cart',
                membership_plan_id: membership_plan_id,
                years: years,
                application_type: 'renew',
                end_year: end_year,
                user_membership_id : user_membership_id
            },
            success: function(response) {
                if (response.success) {
                    showMessage('notice', 'Membership renewal application has been added to your basket. Your will be redirect to application form in 4 seconds.');
                    // Optionally redirect to cart page or update cart display
                    window.location.href = '/checkout';
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                } else {
                    showMessage('error', response.data.message);
                    if(response.data.redirect){
                      button.find('i.fa-spinner').remove();
                      button.prop('disabled', false);
                      window.location = "/login";
                    }
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('error', 'An error occurred, please try again later.');
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            }
        });
    });

    var plans;

    jQuery(document).on('click', '#new-membership-button', function(e) {
        e.preventDefault();

        const today = new Date();
        const currentYear = today.getFullYear();

        // Set the cutoff date as April 30th of the current year
        const april30th = new Date(currentYear, 3, 30); // Month is 0-based, so 3 is April

        // Calculate next year and second next year
        const previousYear = currentYear - 1;
        const nextYear = currentYear + 1;

        // Generate the strings
        const thisPeriod = `${previousYear}-${currentYear}`;
        const nextPeriod = `${currentYear}-${nextYear}`;
        var button = jQuery(this);
        button.append(' <i style="margin-left:5px;" class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        // Send AJAX request to fetch available membership plans
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_membership_plans'
            },
            success: function(response) {
                if (response.success) {
                    plans = response.data;
                    let planOptions = '<option value="">Select a membership plan</option>';

                    // Build the options for the membership plans select element
                    plans.forEach(function(plan) {
                        planOptions += `<option value="${plan.id}">${plan.name}</option>`;
                    });

                    // Display the form for membership plan selection
                    jQuery('#popup-content').html(`
                        <h2>Apply for a New Membership</h2>
                        <div>
                            <label for="membership-plan-select">Choose a Membership Plan:</label>
                            <select id="membership-plan-select">${planOptions}</select>
                            <label for="membership-plan-period">Choose Membership Starting Years:</label>
                            <select id="membership-plan-period">
                            </select>
                        </div>
                        <div id="tenure-selection"></div>
                    `);
                    if( today < april30th ){
                      jQuery('#membership-plan-period').html(`
                        <option value="${currentYear}">May ${thisPeriod}</option>
                        <option value="${nextYear}">Apr ${nextPeriod}</option>
                      `);
                    } else {
                      jQuery('#membership-plan-period').html(`
                        <option value="${nextYear}">${nextPeriod}</option>
                      `);
                    }
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                } else {
                    showMessage('error', response.data.message);
                    if(response.data.redirect){
                      button.find('i.fa-spinner').remove();
                      button.prop('disabled', false);
                      window.location = "/login";
                    }
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('error', 'An error occurred, please try again later.');
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            }
        });
    });

    // Handle membership plan selection and show tenure options
    jQuery(document).on('change', '#membership-plan-select', function() {
        let selectedPlanId = jQuery(this).val();

        if (selectedPlanId) {
            // Find the selected plan from the response data
            let selectedPlan = plans.find(plan => plan.id == selectedPlanId);

            if (selectedPlan) {
                let tenureOptions = '<option value="">Select tenure</option>';

                // Build the tenure options based on the selected membership plan
                selectedPlan.tenures.forEach(function(tenure) {
                    tenureOptions += `<option value="${tenure.Years}">${tenure.Years} Year(s) - New Fee: ${tenure.new}`;
                });

                // Display tenure selection
                jQuery('#tenure-selection').html(`
                    <div>
                        <label for="tenure-select">Choose Tenure:</label>
                        <select id="tenure-select">${tenureOptions}</select>
                    </div>
                    <button data-membership-id="${selectedPlan.id}" id="add-membership-to-cart" class="button">Confirm</button>
                `);
            }
        }
    });

    // Handle renewal confirmation
    jQuery(document).on('click', '#add-membership-to-cart', function() {
        const years = jQuery('#tenure-select').val();
        const end_year = jQuery('#membership-plan-period').val();
        const membership_plan_id = jQuery(this).data('membership-id');
        var button = jQuery(this);
        button.append(' <i style="margin-left:5px;" class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        // Send AJAX request to add the product to the cart
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_membership_product_to_cart',
                membership_plan_id: membership_plan_id,
                years: years,
                end_year: end_year,
                application_type: 'new'
            },
            success: function(response) {
                if (response.success) {
                    showMessage('notice', 'New membership application has been added to your basket. Your will be redirect to application form in 4 seconds.');
                    window.location.href = '/checkout';
                } else {
                    if(response.data.redirect){
                      button.find('i.fa-spinner').remove();
                      button.prop('disabled', false);
                      window.location = "/login";
                    }
                    showMessage('error', response.data.message);
                    button.find('i.fa-spinner').remove();
                    button.prop('disabled', false);
                }
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            },
            error: function() {
                showMessage('error', 'An error occurred, please try again later.');
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            }
        });
    });

    // Handle search input keyup
    jQuery('#hkota-ot-search').on('keyup', function () {
        const query = jQuery(this).val();
        fetchOTList(query, 1);
    });

    // Handle pagination click
    jQuery(document).on('click', '.hkota-page-link', function (e) {
        e.preventDefault();
        const page = jQuery(this).data('page');
        const query = jQuery('#hkota-ot-search').val();
        fetchOTList(query, page);
    });

    // Function to fetch and display the OT list based on query and page
    function fetchOTList(query, page) {
        jQuery.ajax({
            url: hkota_frontend_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'hkota_ot_search',
                query: query,
                page: page
            },
            beforeSend: function () {
                jQuery('#hkota-ot-list').html('<p>Loading...</p>');
            },
            success: function (response) {
                if (response.success) {
                    jQuery('#hkota-ot-list').html(response.data.table);
                } else {
                    jQuery('#hkota-ot-list').html('<p>No results found.</p>');
                }
            },
            error: function () {
                jQuery('#hkota-ot-list').html('<p>An error occurred. Please try again later.</p>');
            }
        });
    }

    jQuery('#post-filter #year').on('change', function() {
       var year = jQuery(this).val();
       var category = jQuery('#post-filter').data('term-slug');
       console.log('fired');
       jQuery.ajax({
           url: hkota_frontend_ajax.ajaxurl, // Use the localized AJAX URL
           type: 'POST',
           data: {
               action: 'filter_posts_by_year', // AJAX action name
               year: year,
               category: category
           },
           success: function(response) {
               jQuery('#post-hook').html(response);
           },
           error: function() {
               showMessage('error', 'An unexpected error occurred.');
           }
       });
   });

    jQuery(document).on('click', '#product-add-to-cart', function(e) {
    e.preventDefault();

    // Get the product ID from the data attribute
    const productId = jQuery(this).data('product-id');
    var button = jQuery(this);
    button.append(' <i style="margin-left:5px;" class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

    jQuery.ajax({
        url: hkota_frontend_ajax.ajaxurl, // Use the localized AJAX URL
        type: 'POST',
        data: {
            action: 'add_to_cart_with_membership_price',
            product_id: productId,
        },
        success: function(response) {
            if (response.success) {
                showMessage('notice', 'Product added to cart successfully. Your will be redirect to checkout in 4 seconds.');
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
                window.location.href = '/checkout';
            } else {
                if(response.data.redirect){
                  button.find('i.fa-spinner').remove();
                  button.prop('disabled', false);
                  window.location = "/login";
                }
                showMessage('error', response.data.message);
                button.find('i.fa-spinner').remove();
                button.prop('disabled', false);
            }
        },
        error: function() {
              showMessage('notice', 'An error occurred. Please try again.');
              button.find('i.fa-spinner').remove();
              button.prop('disabled', false);
        }
    });
});

    jQuery("#csv-file-upload").on('submit',function(e) {

       e.preventDefault();

       jQuery("#msg-hook").show();
       jQuery("#msg-hook #error-notice").html("");
       jQuery("#msg-hook #respond-message").html("");

       if( jQuery('#pupil-upload-csv').val().toLowerCase().lastIndexOf(".csv") == -1 ) {
          alert("Please upload a file with .csv extension.");
          return false;
       }
       var form_data;
       var csvData = null;
       var uploadFile = jQuery('#pupil-upload-csv')[0].files[0];
       var reader = new FileReader();
       reader.readAsText(uploadFile);
       reader.onload = function(event){
         var csv = event.target.result;
         var csvData = jQuery.csv.toArrays(csv);
         csvData.shift();
         var currentRow = 1;
         var progress = 0;
         csvData.forEach( (row) => {
           jQuery.ajax({
              type : "post",
              url : hkota_frontend_ajax.ajaxurl,
              data: {
                action : 'import_user_membership_ajax',
                row : JSON.stringify(row)
              },
              success: function(response) {
                if (response.success) {
                  progress++;
                  jQuery("#msg-hook #respond-message").html("已完成" + progress + "/" + csvData.length + "位用戶導入");
                } else {
                  jQuery("#msg-hook #error-notice").prepend( "<p><i class='fa-solid fa-circle-exclamation'></i> " + response.data.message + "</p>" );
                }
           }});
           currentRow++;
         });

       }

    });

    jQuery('#submit-name-check').on('click', function(e) {
        e.preventDefault();
        var firstName = jQuery('#first_name').val();
        var lastName = jQuery('#last_name').val();

        if (firstName && lastName) {
            jQuery.ajax({
                url: hkota_frontend_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'process_name_check_form',
                    first_name: firstName,
                    last_name: lastName
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('notice', 'Thank you, your information has been saved.');
                        // Optionally redirect to cart page or update cart display
                        window.location.href = '/my-account';
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        } else {
            alert('Please fill in both fields.');
        }
    });

    jQuery(document).on('click','.tab-button',function(){
      jQuery('.tab-button').removeClass('active');
      jQuery(this).addClass('active');
      var type = jQuery(this).data('type');
      jQuery('.course-wrapper:not(' + type + ')' ).removeClass('visable');
      jQuery('.course-wrapper.' + type ).addClass('visable');
      if( type == 'all' ){
        jQuery('.course-wrapper').addClass('visable');
      }
    });

    jQuery(document).on('click','#side-calender .calender-course-wrapper',function(){

      var date = jQuery(this).data('date');

      window.location = "/trainings-and-activities/?start-date=" + date ;

    });

    jQuery(document).on('click','#export-cpd-records',function(){

      jQuery.ajax({
          url: hkota_frontend_ajax.ajaxurl, // Replace with your AJAX endpoint
          type: 'POST',
          data: {
              action: 'fetch_user_cpd_data', // Your AJAX action
          },
          success: function (response) {
              if (response.success) {
                  console.log('fired');
                  // Convert the data to CSV
                  const csvData = convertToCSV(response.data);
                  // Trigger the download
                  downloadCSV(csvData, 'exported_data.csv');
              } else {
                  showMessage('error', response.data );
              }
          },
          error: function () {
              showMessage('error', 'An error occurred. Please try again.');
          }
      });

    });





//End of jquery document ready
});

// Function to convert data to CSV format
function convertToCSV(data) {
    const rows = [];
    const headers = Object.keys(data[0]); // Assuming all rows have the same keys
    rows.push(headers.join(',')); // Add headers

    data.forEach(row => {
        const values = headers.map(header => JSON.stringify(row[header] || '')); // Handle null values
        rows.push(values.join(','));
    });

    return rows.join('\n');
}

// Function to download the CSV
function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function get_course_loop(start_date,end_date){
  jQuery.ajax({
      url: hkota_frontend_ajax.ajaxurl,
      type: 'POST',
      data: {
          action: 'get_course_loop',
          start_date: start_date,
          end_date: end_date
      },
      success: function (response) {
          jQuery('#display-course').empty(); // Clear previous details
          jQuery('#display-course').html(response);
          jQuery('#calender-loader').hide();
      },
      error: function () {
          showMessage('error', 'An unexpected error occurred.');
          jQuery('#calender-loader').hide();
      }

  });
}

function loadCalendar(month, year) {

    jQuery('#calender-loader').show();

    jQuery.ajax({
        url: hkota_frontend_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'fetch_courses_by_month',
            month: month,
            year: year
        },
        success: function (response) {
          console.log(response);
          if(response.success){
            jQuery('#calendar').empty(); // Clear calendar content
            jQuery('#current-month').empty(); // Clear calendar content
            jQuery('#course-details').empty(); // Clear course details

            // Create the days of the week headers
            const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            daysOfWeek.forEach(day => {
                jQuery('#calendar').append(`<div class="day-header">${day}</div>`);
            });

            // Get the first day of the month (0=Sunday, 1=Monday, ..., 6=Saturday)
            let firstDayOfMonth = new Date(year, month - 1, 1).getDay();
            firstDayOfMonth = (firstDayOfMonth === 0) ? 7 : firstDayOfMonth; // Adjust to make Monday = 1, Sunday = 7

            // Calculate days in the month
            let daysInMonth = new Date(year, month, 0).getDate();

            // Add empty divs for the days before the 1st of the month (to align the first day correctly)
            for (let i = 1; i < firstDayOfMonth; i++) {
                jQuery('#calendar').append('<div class="day empty"></div>');
            }

            // Render the days with events marked
            for (let day = 1; day <= daysInMonth; day++) {
                let dayStr = year + '-' + ('0' + month).slice(-2) + '-' + ('0' + day).slice(-2);
                let dayCell = `<div class="day" data-date="${dayStr}">${day}</div>`;

                // Check if there are events for this day
                if (response.data.dates[dayStr]) {
                    dayCell =  `<div class="day event" data-date="${dayStr}">${day}
                                <div class="dot-wrapper">`;
                    response.data.dates[dayStr].forEach((item,i) => {
                      dayCell += `<span class="dot ` + item  + `"></span>`;
                    });
                    dayCell += `</div>`;
                }
                jQuery('#calendar').append(dayCell);
            }

            jQuery('#course-details').empty(); // Clear previous details

            if (response.data.course.length) {
                response.data.course.forEach(function (course) {
                    var courseItem = `<div class="calender-course-wrapper">
                                          <div class="course-date">${changeDateFormat(course.start_date)}</div>
                                          <div class="course-details">
                                            <div class="course-title ${course.type}">${course.title}</div>`;
                    if( course.cpd_point ){
                      courseItem += `<div>CPD Points: ${course.cpd_point}</div>`;
                    }
                    courseItem += `</div></div>`;
                    jQuery('#course-details').append(courseItem);
                });
            }
          }

            jQuery('#current-month').append(displayMonthYear(month, year));
            // First day of the month
            const firstDay = new Date(year, month - 1, 1);
            // Last day of the month
            const lastDay = new Date(year, month, 0);

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            get_course_loop(formatDate(firstDay),formatDate(lastDay));
          }
    });
}

// Function to display month year in a way like "September, 2024"
function displayMonthYear(month, year) {
    // Array of month names
    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    // Adjust month number to match array index (0-based)
    const monthName = monthNames[month - 1];

    // Return formatted string
    return `${monthName}, ${year}`;
}

// Function to diplay date format as 25 Wed
function changeDateFormat(date) {
    // Create a new Date object from the input string (YYYY-MM-DD)
    var parsedDate = new Date(date);

    // Get the day (25 from '2024-09-25')
    var day = parsedDate.getDate();

    // Get the weekday (Wed from '2024-09-25')
    var weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var weekday = weekdays[parsedDate.getDay()]; // getDay returns the day of the week

    // Return the formatted string in span elements
    return `<span>${day}</span><span>${weekday}</span>`;
}






































//
