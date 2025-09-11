jQuery(document).ready(function (e) {

  jQuery(document).on('click', '.course-file-preview i' , function(e) {

    e.preventDefault();

    jQuery.ajax({
       type : "post",
       url : hkota_backend_ajax.ajaxurl,
       data : {
         action: "delete_course_media",
         post_id: jQuery(this).data("post-id"),
         input_key: jQuery(this).data("input-key")
       },
       success: function(response) {

         console.log(response);

         response = JSON.parse(response)

         if( response['success'] ){

           var icon = jQuery( 'i[data-input-key='+response['input_key']+']' );
           jQuery(icon).parent().parent().remove();

         } else {

           alert('Error: Please refresh this page and try again.');

         }

       }

    });

  });

  jQuery(document).on('click', '.pupil-file-preview i' , function(e) {

    e.preventDefault();

    // Show confirmation dialog
    const confirmDelete = confirm("Are you sure you want to delete this file?");

    if (confirmDelete) {

      jQuery.ajax({
         type : "post",
         url : hkota_backend_ajax.ajaxurl,
         data : {
           action: "delete_pupil_document",
           user_id: jQuery(this).data("user-id"),
           input_key: jQuery(this).data("input-key")
         },
         success: function(response) {
           if( response.success ){
             alert( response.data.message );
             location.reload(); // Refresh the page to see the updates
           } else {
             alert('Error: ' + response.data.message );
           }
         }
      });
    }
  });

  jQuery('#show-pupil-details').on('click', function(e) {

      e.preventDefault();
      var course_id = jQuery(this).data('course-id');
      render_pupil_table(course_id);

  });

  //Close popup only when clicking the close button or outside the content
  jQuery(document).on('click', '.close-popup', function () {
      jQuery('#pupil-details-popup').fadeOut(); // Close popup when clicking on close button
  });

  // Close popup when clicking on the overlay, but not the popup content itself
  jQuery('#pupil-details-popup').on('click', function (e) {
      if (e.target.id === 'pupil-details-popup') {
          jQuery('#pupil-details-popup').fadeOut();
          jQuery('#pupil-edit-table').remove();
          jQuery('#pupil-details-table').fadeIn();
      }
  });

  // Edit button click
  jQuery(document).on('click', '.edit-pupil', function() {

      // Get the current row details
      var user_id = jQuery(this).data('user-id');
      var currentRow = jQuery(this).closest('tr');
      var first_name = currentRow.find('td:nth-child(1)').text();
      var last_name = currentRow.find('td:nth-child(2)').text();
      var email = currentRow.find('td:nth-child(3)').text();
      var enrollment_status = currentRow.find('td:nth-child(4)').data('enrollment-status');
      var attendance_status = currentRow.find('td:nth-child(5)').data('attendance-status');
      var course_id = jQuery('#pupil-details-table').data('course-id');

      // Hide main table and show edit table
      jQuery('#pupil-details-table').fadeOut(function() {
          // Populate the editable table with existing values
          var editTableHtml = `
              <table id="pupil-edit-table">
                  <thead>
                      <tr>
                          <th>First Name</th>
                          <th>Last Name</th>
                          <th>Email</th>
                          <th>Enrollment Status</th>
                          <th>Attendance Status</th>
                          <th>Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr>
                          <td>${first_name}</td>
                          <td>${last_name}</td>
                          <td>${email}</td>
                          <td>
                              <select id="edit-enrollment-status">
                                  <option value="enrolled" ${enrollment_status == 'enrolled' ? 'selected' : ''}>Enrolled</option>
                                  <option value="awaiting_approval" ${enrollment_status == 'awaiting_approval' ? 'selected' : ''}>Awaiting Approval</option>
                                  <option value="pending" ${enrollment_status == 'pending' ? 'selected' : ''}>Pending</option>
                                  <option value="waiting_list" ${enrollment_status == 'waiting_list' ? 'selected' : ''}>Waiting List</option>
                                  <option value="rejected" ${enrollment_status == 'rejected' ? 'selected' : ''}>Rejected</option>
                              </select>
                          </td>
                          <td>
                              <select id="edit-attendance-status">
                                  <option value="not_attended" ${attendance_status == 'not_attended' ? 'selected' : ''}>Not Attended</option>
                                  <option value="fully_attended" ${attendance_status == 'fully_attended' ? 'selected' : ''}>Fully Attended</option>
                                  <option value="partially_attended" ${attendance_status == 'partially_attended' ? 'selected' : ''}>Partially Attended</option>
                              </select>
                          </td>
                          <td>
                              <button id="save-edit" data-user-id="${user_id}" class="button button-primary" type="button" data-course-id=${course_id}>Save</button>
                              <button id="cancel-edit" class="button button-secondary" type="button">Cancel</button>
                          </td>
                      </tr>
                  </tbody>
              </table>`;

          // Append edit table and fade in
          jQuery('.popup-content').append(editTableHtml).fadeIn();
      });
  });

  // Save edited data
  jQuery(document).on('click', '#save-edit', function() {
      var user_id = jQuery(this).data('user-id');
      var enrollment_status = jQuery('#edit-enrollment-status').val();
      var attendance_status = jQuery('#edit-attendance-status').val();
      var course_id = jQuery(this).data('course-id');

      // Send AJAX request to save the data
      jQuery.ajax({
          url: hkota_backend_ajax.ajaxurl,
          type: 'post',
          data: {
              action: 'save_pupil_enrollment_data',
              user_id: user_id,
              enrollment_status: enrollment_status,
              attendance_status: attendance_status,
              course_id: course_id
          },
          success: function(response) {
              if (response.success) {
                  showMessage('notice',response.data);
                  jQuery('#pupil-edit-table').remove();
                  render_pupil_table(course_id);
                  jQuery('#pupil-details-table').fadeIn();
              } else {
                  showMessage('error',response.data);
              }
          },
          error: function() {
              alert('An error occurred.');
          }
      });
  });

  // Cancel button click
  jQuery(document).on('click', '#cancel-edit', function() {
      // Remove edit table and show main table
      jQuery('#pupil-edit-table').remove();
      jQuery('#pupil-details-table').fadeIn();
  });

  // View certificate button click
  jQuery(document).on('click', '.view-certificate', function(e) {
      e.preventDefault();
      var certificateFile = jQuery(this).data('certificate-file');
      if (certificateFile) {
          // Open certificate in new tab - construct URL dynamically
          var baseUrl = window.location.origin;
          var certificateUrl = baseUrl + '/wp-content/uploads/certificate/' + certificateFile;
          window.open(certificateUrl, '_blank');
      }
  });

  // Resend certificate email button click
  jQuery(document).on('click', '.resend-certificate', function(e) {
      e.preventDefault();
      var userId = jQuery(this).data('user-id');
      var courseId = jQuery(this).data('course-id');
      var button = jQuery(this);
      
      // Disable button and show loading state
      button.prop('disabled', true).text('Sending...');
      
      jQuery.ajax({
          url: hkota_backend_ajax.ajaxurl,
          type: 'POST',
          data: {
              action: 'resend_certificate_email',
              user_id: userId,
              course_id: courseId
          },
          success: function(response) {
              if (response.success) {
                  showMessage('notice', 'Certificate email sent successfully!');
              } else {
                  showMessage('error', response.data || 'Failed to send certificate email.');
              }
              // Restore button state
              button.prop('disabled', false).text('Resend');
          },
          error: function() {
              showMessage('error', 'An error occurred while sending the certificate email.');
              // Restore button state
              button.prop('disabled', false).text('Resend');
          }
      });
  });

  // View attendance details button click
  jQuery(document).on('click', '.view-attendance', function(e) {
      e.preventDefault();
      var userId = jQuery(this).data('user-id');
      var courseId = jQuery(this).data('course-id');
      
      jQuery.ajax({
          url: hkota_backend_ajax.ajaxurl,
          type: 'POST',
          data: {
              action: 'fetch_attendance_details',
              user_id: userId,
              course_id: courseId
          },
          success: function(response) {
              if (response.success) {
                  showAttendanceDetailsPopup(response.data);
              } else {
                  showMessage('error', response.data.message || 'Failed to fetch attendance details.');
              }
          },
          error: function() {
              showMessage('error', 'An error occurred while fetching attendance details.');
          }
      });
  });

  jQuery('#download-quiz-button').on('click', function (e) {
       e.preventDefault();

       var courseId = jQuery(this).data('course-id');

       jQuery.ajax({
           url: hkota_backend_ajax.ajaxurl,
           type: 'POST',
           data: {
               action: 'check_quiz_data',
               course_id: courseId
           },
           success: function (response) {
               if (response.success) {
                   window.location.href = response.data.download_url;
               } else {
                   showMessage('error', 'There is no quiz data yet.');
               }
           },
           error: function () {
               showMessage('error', 'An error occurred while checking quiz data.');
           }
       });
   });

  jQuery('#download-survey-button').on('click', function (e) {
    e.preventDefault();

    var courseId = jQuery(this).data('course-id');

    jQuery.ajax({
        url: hkota_backend_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'check_survey_data',
            course_id: courseId
        },
        success: function (response) {
            if (response.success) {
                window.location.href = response.data.download_url;
            } else {
                showMessage('error', 'There is no survey data yet.');
            }
        },
        error: function () {
            showMessage('error', 'An error occurred while checking survey data.');
        }
    });
  });

  jQuery('#download-pupil-button').on('click', function (e) {
    e.preventDefault();

    var courseId = jQuery(this).data('course-id');

    jQuery.ajax({
        url: hkota_backend_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'check_pupil_data',
            course_id: courseId
        },
        success: function (response) {
            if (response.success) {
                window.location.href = response.data.download_url;
            } else {
                showMessage('error', 'There is no pupil data yet.');
            }
        },
        error: function () {
            showMessage('error', 'An error occurred while checking pupil data.');
        }
    });
  });

  jQuery('#download-attendance-button').on('click', function (e) {
    e.preventDefault();

    var courseId = jQuery(this).data('course-id');

    jQuery.ajax({
        url: hkota_backend_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'check_attendance_data',
            course_id: courseId
        },
        success: function (response) {
            if (response.success) {
                window.location.href = response.data.download_url;
            } else {
                showMessage('error', 'There is no attendance data yet.');
            }
        },
        error: function () {
            showMessage('error', 'An error occurred while checking attendance data.');
        }
    });
  });

  // Bulk actions functionality
  jQuery(document).on('change', '#cb-select-all', function() {
      var isChecked = jQuery(this).is(':checked');
      jQuery('input[name="pupil_ids[]"]').prop('checked', isChecked);
  });

  jQuery(document).on('change', 'input[name="pupil_ids[]"]', function() {
      var totalCheckboxes = jQuery('input[name="pupil_ids[]"]').length;
      var checkedCheckboxes = jQuery('input[name="pupil_ids[]"]:checked').length;
      
      if (checkedCheckboxes === totalCheckboxes) {
          jQuery('#cb-select-all').prop('checked', true);
      } else {
          jQuery('#cb-select-all').prop('checked', false);
      }
  });

  jQuery(document).on('click', '#bulk-apply-btn', function() {
      var selectedAction = jQuery('#bulk-action-selector').val();
      var selectedPupils = jQuery('input[name="pupil_ids[]"]:checked');
      
      if (!selectedAction) {
          showMessage('error', 'Please select a bulk action.');
          return;
      }
      
      if (selectedPupils.length === 0) {
          showMessage('error', 'Please select at least one pupil.');
          return;
      }
      
      if (selectedAction === 'edit') {
          showBulkEditRow();
      }
  });

  jQuery(document).on('click', '#bulk-edit-apply', function() {
      var enrollmentStatus = jQuery('#bulk-enrollment-status').val();
      var attendanceStatus = jQuery('#bulk-attendance-status').val();
      var selectedPupils = [];
      
      jQuery('input[name="pupil_ids[]"]:checked').each(function() {
          selectedPupils.push(jQuery(this).val());
      });
      
      if (!enrollmentStatus && !attendanceStatus) {
          showMessage('error', 'Please select at least one field to update.');
          return;
      }
      
      var confirmMessage = 'Are you sure you want to bulk update ' + selectedPupils.length + ' pupil(s)?';
      if (confirm(confirmMessage)) {
          performBulkEdit(selectedPupils, enrollmentStatus, attendanceStatus);
      }
  });

  jQuery(document).on('click', '#bulk-edit-cancel', function() {
      jQuery('#bulk-edit-row').remove();
  });

  // Function to add new tenure set
  jQuery(document).on('click', '.add-tenure-set', function() {
      // Clone the first input group and modify it
      var newSet = `
          <div class="options_group tenures-custom-field">
              <p class="form-field post_name_field">
                <label for="post_name">Tenures:</label>
                <input type="number" name="tenures[years][]" value="" placeholder="Years">
                <input type="number" name="tenures[new_fee][]" value="" placeholder="New membership fee">
                <input type="number" name="tenures[renew_fee][]" value="" placeholder="Renewal fee">
                <button type="button" class="remove-tenure-set button button-secondary">-</button>
              </p>
          </div>
      `;

      // Append the new input set
      jQuery('#tenures-wrapper').append(newSet);
  });

  // Function to remove a tenure set
  jQuery(document).on('click', '.remove-tenure-set', function() {
      jQuery(this).closest('.options_group').remove(); // Remove the closest input group
  });

  jQuery(document).on('click', '#edit-member-info', function () {
       // Create the popup box
       const popup = `
           <div id="member-edit-popup" class="popup-overlay">
               <div class="popup-content">
                   <h2>Edit Member Info</h2>
                   <form id="edit-member-form">
                       <p>
                         <label>Membership Number:</label>
                         <input type="text" name="member_number" value="${jQuery('.member-data-row .title:contains("Member number")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Title:</label>
                         <input type="text" name="member_title" value="${jQuery('.member-data-row .title:contains("Title")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Chinese Name:</label>
                         <input type="text" name="member_full_name_zh" value="${jQuery('.member-data-row .title:contains("Chinese name")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Last Name:</label>
                         <input type="text" name="member_last_name_eng" value="${jQuery('.member-data-row .title:contains("Last Name")').next('.value').text()}">
                       </p>
                       <p>
                         <label>First Name:</label>
                         <input type="text" name="member_first_name_eng" value="${jQuery('.member-data-row .title:contains("First Name")').next('.value').text()}">
                       </p>
                       <p>
                         <label>HKID:</label>
                         <input type="text" name="member_hkid" value="${jQuery('.member-data-row .title:contains("HKID")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Nature of Work:</label>
                         <input type="text" name="member_field" value="${jQuery('.member-data-row .title:contains("Nature of Work")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Name of Working Place:</label>
                         <input type="text" name="member_working_place" value="${jQuery('.member-data-row .title:contains("Name of Working Place")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Mailing address:</label>
                         <input type="text" name="member_mailing_address" value="${jQuery('.member-data-row .title:contains("Mailing address")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Mobile:</label>
                         <input type="text" name="member_mobile" value="${jQuery('.member-data-row .title:contains("Mobile")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Email:</label>
                         <input type="email" name="user_email" value="${jQuery('.member-data-row .title:contains("Email")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Basic Qualification:</label>
                         <input type="text" name="member_basic_qualification" value="${jQuery('.member-data-row .title:contains("Basic Qualification")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Year of Graduation:</label>
                         <input type="number" name="member_basic_qualification_year" value="${jQuery('.member-data-row .title:contains("Year of Graduation")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Name of Academic Institution:</label>
                         <input type="text" name="member_basic_qualification_institution" value="${jQuery('.member-data-row .title:contains("Name of Basic Academic Institution")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Highest Academic Qualification:</label>
                         <input type="text" name="member_highest_qualification" value="${jQuery('.member-data-row .title:contains("Highest Academic Qualification")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Year Obtained:</label>
                         <input type="text" name="member_highest_qualification_year" value="${jQuery('.member-data-row .title:contains("Year Obtained")').next('.value').text()}">
                       </p>
                       <p>
                         <label>Name of Academic Institution:</label>
                         <input type="text" name="member_highest_qualification_institution" value="${jQuery('.member-data-row .title:contains("Name of Highest Academic Institution")').next('.value').text()}">
                       </p>
                       <p>
                       </p>
                       <p>
                         <label>OT Graduation Certificate / OT Practicing Certificate:</label>
                         <input type="file" name="member_certificate">
                       </p>
                       <p>
                         <label style=" height: 39px; ">OT Student ID Card:</label>
                         <input type="file" name="member_student_id">
                       </p>
                       <button type="submit" class="button button-primary">Save Changes</button>
                       <button type="button" id="close-popup" class="button">Cancel</button>
                   </form>
               </div>
           </div>
       `;

       // Append the popup to the body
       jQuery('body').append(popup);

       // Close popup on cancel
       jQuery('#close-popup').on('click', function () {
           jQuery('#member-edit-popup').remove();
       });

       // Submit the form via AJAX
       jQuery('#edit-member-form').on('submit', function (e) {
           e.preventDefault();
           var formElement = jQuery(this)[0]; // Get the raw form DOM element
           var formData = new FormData(formElement);
           // Add the AJAX action to the form data
           formData.append('action', 'save_member_info');
           formData.append('user_id', jQuery('#edit-member-info').data('user-id'));

           jQuery.ajax({
               url: hkota_backend_ajax.ajaxurl,
               type: 'POST',
               data: formData,
               contentType: false,
               processData: false,
               success: function (response) {
                   if (response.success) {
                       alert('Member details updated successfully!');
                       location.reload(); // Refresh the page to see the updates
                   } else {
                       alert(response.data.message || 'An error occurred.');
                   }
               },
               error: function () {
                   alert('An error occurred while saving the details.');
               },
           });
       });
   });

  // jQuery(document).on('click', "#upload-pupil-data", function(e) {
  //
  //     e.preventDefault();
  //
  //     jQuery("#msg-hook").show();
  //     jQuery("#msg-hook #error-notice").html("");
  //     jQuery("#msg-hook #respond-message").html("");
  //
  //     if( jQuery('#pupil-upload-csv').val().toLowerCase().lastIndexOf(".csv") == -1 ) {
  //        alert("Please upload a file with .csv extension.");
  //        return false;
  //     }
  //     var course_id = jQuery(this).data('course-id');
  //     var form_data;
  //     var csvData = null;
  //     var uploadFile = jQuery('#pupil-upload-csv')[0].files[0];
  //     var reader = new FileReader();
  //     reader.readAsText(uploadFile);
  //     reader.onload = function(event){
  //       var csv = event.target.result;
  //       var csvData = jQuery.csv.toArrays(csv);
  //       csvData.shift();
  //       var currentRow = 1;
  //       var progress = 0;
  //       csvData.forEach( (row) => {
  //         jQuery.ajax({
  //            type : "post",
  //            url : hkota_backend_ajax.ajaxurl,
  //            data: {
  //              action : 'import_pupil_data',
  //              course_id : course_id,
  //              row : JSON.stringify(row)
  //            },
  //            success: function(response) {
  //              if (response.success) {
  //                progress++;
  //                jQuery("#msg-hook #respond-message").html("Total " + progress + "/" + csvData.length + " enrollments imported successfully.");
  //              } else {
  //                jQuery("#msg-hook #error-notice").prepend( "<p><i class='fa-solid fa-circle-exclamation'></i> " + response.data.message + "</p>" );
  //              }
  //         }});
  //         currentRow++;
  //       });
  //
  //     }
  //
  //  });

  jQuery(document).on('click', "#upload-pupil-data", function(e) {
      e.preventDefault();

      jQuery("#msg-hook").show();
      jQuery("#msg-hook #error-notice").html("");
      jQuery("#msg-hook #respond-message").html("");

      if (jQuery('#pupil-upload-csv').val().toLowerCase().lastIndexOf(".csv") === -1) {
          alert("Please upload a file with .csv extension.");
          return false;
      }

      var course_id = jQuery(this).data('course-id');
      var uploadFile = jQuery('#pupil-upload-csv')[0].files[0];
      var reader = new FileReader();

      reader.readAsText(uploadFile);
      reader.onload = function(event) {
          var csv = event.target.result;
          var csvData = jQuery.csv.toArrays(csv);
          csvData.shift(); // Remove header row

          var progress = 0;

          function processRow(index) {
              if (index >= csvData.length) {
                  // All rows processed
                  jQuery("#msg-hook #respond-message").html("All enrollments have been processed successfully.");
                  return;
              }

              var row = csvData[index];
              jQuery.ajax({
                  type: "post",
                  url: hkota_backend_ajax.ajaxurl,
                  data: {
                      action: 'import_pupil_data',
                      course_id: course_id,
                      row: JSON.stringify(row)
                  },
                  success: function(response) {
                      if (response.success) {
                          progress++;
                          jQuery("#msg-hook #respond-message").html("Total " + progress + "/" + csvData.length + " enrollments imported successfully.");
                      } else {
                          jQuery("#msg-hook #error-notice").prepend("<p><i class='fa-solid fa-circle-exclamation'></i> " + response.data.message + "</p>");
                      }
                      // Process the next row
                      processRow(index + 1);
                  },
                  error: function() {
                      jQuery("#msg-hook #error-notice").prepend("<p><i class='fa-solid fa-circle-exclamation'></i> An error occurred while processing row " + (index + 1) + ".</p>");
                      // Continue to the next row even if there's an error
                      processRow(index + 1);
                  }
              });
          }

          // Start processing the first row
          processRow(0);
      };
  });

  jQuery(document).on('click', "#admin-upload-pupil-data", function(e) {
      e.preventDefault();

      jQuery("#admin-msg-hook").show();
      jQuery("#admin-msg-hook #admin-error-notice").html("");
      jQuery("#admin-msg-hook #admin-respond-message").html("");

      if (jQuery('#admin-pupil-upload-csv').val().toLowerCase().lastIndexOf(".csv") === -1) {
          alert("Please upload a file with .csv extension.");
          return false;
      }

      var course_id = jQuery(this).data('course-id');
      var uploadFile = jQuery('#admin-pupil-upload-csv')[0].files[0];
      var reader = new FileReader();

      reader.readAsText(uploadFile);
      reader.onload = function(event) {
          var csv = event.target.result;
          var csvData = jQuery.csv.toArrays(csv);
          csvData.shift(); // Remove header row

          var progress = 0;

          function processRow(index) {
              if (index >= csvData.length) {
                  // All rows processed
                  jQuery("#admin-msg-hook #admin-respond-message").html("All enrollments have been processed successfully.");
                  return;
              }

              var row = csvData[index];
              jQuery.ajax({
                  type: "post",
                  url: hkota_backend_ajax.ajaxurl,
                  data: {
                      action: 'admin_import_pupil_data',
                      course_id: course_id,
                      row: JSON.stringify(row)
                  },
                  success: function(response) {
                      if (response.success) {
                          progress++;
                          jQuery("#admin-msg-hook #admin-respond-message").html("Total " + progress + "/" + csvData.length + " enrollments imported successfully.");
                      } else {
                          jQuery("#admin-msg-hook #admin-error-notice").prepend("<p><i class='fa-solid fa-circle-exclamation'></i> " + response.data.message + "</p>");
                      }
                      // Process the next row
                      processRow(index + 1);
                  },
                  error: function() {
                      jQuery("#admin-msg-hook #admin-error-notice").prepend("<p><i class='fa-solid fa-circle-exclamation'></i> An error occurred while processing row " + (index + 1) + ".</p>");
                      // Continue to the next row even if there's an error
                      processRow(index + 1);
                  }
              });
          }

          // Start processing the first row
          processRow(0);
      };
  });

});

function render_pupil_table(course_id){
  // Perform AJAX to fetch the pupil details
  jQuery.ajax({
      url: hkota_backend_ajax.ajaxurl, // The URL to the admin-ajax.php file
      type: 'POST',
      data: {
          action: 'fetch_pupil_details', // The PHP action we'll create
          course_id: course_id
      },
      success: function(response) {
        console.log(response);
          if (response.success) {
              var pupils = response.data.pupil;
              var tableBody = jQuery('#pupil-details-table tbody');
              var tableHead = jQuery('#pupil-details-table thead tr');
              tableBody.empty(); // Clear any existing rows
              
              // Update table header to include checkbox column if not already present
              if (!tableHead.find('th:first').hasClass('check-column')) {
                  tableHead.prepend('<th class="check-column"><input type="checkbox" id="cb-select-all"></th>');
              }
              
              // Ensure proper header structure 
              var expectedHeaders = [
                  '<th class="check-column"><input type="checkbox" id="cb-select-all"></th>',
                  '<th>First Name</th>',
                  '<th>Last Name</th>',
                  '<th>Email</th>',
                  '<th>Enrollment Status</th>',
                  '<th>Attendance Status</th>',
                  '<th>Certificate</th>',
                  '<th>Documents</th>',
                  '<th>Actions</th>'
              ];
              
              if (tableHead.children().length !== expectedHeaders.length) {
                  tableHead.html(expectedHeaders.join(''));
              }

              pupils.forEach(function(pupil) {
                  // Build certificate column content
                  var certificateContent = capitalizeFirstLetter(pupil.certificate_status.replace('_', ' '));
                  
                  // Add view and resend buttons if certificate is issued
                  if (pupil.certificate_status === 'issued' && pupil.certificate_file) {
                      certificateContent += '<br><div style="margin-top: 5px;">';
                      certificateContent += `<button type="button" class="button-link view-certificate" data-certificate-file="${pupil.certificate_file}" style="color: #0073aa; text-decoration: none; border: none; background: none; padding: 0; cursor: pointer;">View</button>`;
                      certificateContent += ' | ';
                      certificateContent += `<button type="button" class="button-link resend-certificate" data-user-id="${pupil.user_id}" data-course-id="${course_id}" style="color: #0073aa; text-decoration: none; border: none; background: none; padding: 0; cursor: pointer;">Resend</button>`;
                      certificateContent += '</div>';
                  }
                  
                  // Build attendance column content
                  var attendanceContent = capitalizeFirstLetter(pupil.attendance_status.replace('_', ' '));
                  attendanceContent += '<br><div style="margin-top: 5px;">';
                  attendanceContent += `<button type="button" class="button-link view-attendance" data-user-id="${pupil.user_id}" data-course-id="${course_id}" style="color: #0073aa; text-decoration: none; border: none; background: none; padding: 0; cursor: pointer;">View Attendance</button>`;
                  attendanceContent += '</div>';
                  
                  var row = `<tr>
                      <td class="check-column"><input type="checkbox" name="pupil_ids[]" value="${pupil.user_id}"></td>
                      <td>${pupil.first_name}</td>
                      <td>${pupil.last_name}</td>
                      <td>${pupil.email}</td>
                      <td data-enrollment-status=${pupil.enrollment_status} >${capitalizeFirstLetter(pupil.enrollment_status.replace('_', ' '))}</td>
                      <td data-attendance-status=${pupil.attendance_status}>${attendanceContent}</td>
                      <td>
                        ${certificateContent}
                      </td>
                      <td>${pupil.uploaded_documents}</td>`;
                  if( response.data.capability ){
                    row += `<td><button type="button" class="button button-secondary edit-pupil" data-user-id=${pupil.user_id}>Edit</button></td></tr>`;
                  } else{
                    row += `<td><button type="button" class="button button-secondary edit-pupil" data-user-id=${pupil.user_id} disabled>Edit</button></td></tr>`;
                  }

                  tableBody.append(row);
              });

              // Add bulk actions interface if not already present
              if (!jQuery('#pupil-bulk-actions').length) {
                  var bulkActionsHtml = `
                      <div id="pupil-bulk-actions" style="margin-bottom: 15px;">
                          <select id="bulk-action-selector">
                              <option value="">Bulk actions</option>
                              <option value="edit">Edit</option>
                          </select>
                          <button type="button" id="bulk-apply-btn" class="button">Apply</button>
                      </div>
                  `;
                  jQuery('#pupil-details-table').before(bulkActionsHtml);
              }

              // Initialize Tablesorter
              jQuery("#pupil-details-table").tablesorter({
                  // Optional configurations can be set here
                  sortList: [[1, 0]],  // Sort by first name column (now column 1 due to checkbox) ascending by default
                  widgets: ["zebra"],  // Adds zebra striping to the rows
                  headers: {
                      // Disable sorting for specific columns
                      0: { sorter: false }, // Checkbox column
                      8: { sorter: false }, // Actions column
                      7: { sorter: false }  // Documents column
                  }
              });

              // Show the popup
              jQuery('#pupil-details-popup').fadeIn();
          } else {
              showMessage('error',response.data); // Show error message if failed
          }
      },
      error: function() {
          showMessage('error','Failed to fetch pupil details.')
      }
  });

}

function savingRundown(postID,inputKey,sections,relatedness){

  jQuery.ajax({
     type : "post",
     url : hkota_backend_ajax.ajaxurl,
     dataType:"json",
     data : {
       action: "save_rundown",
       post_id: postID,
       input_key: inputKey,
       rundown: sections,
       course_relatedness: relatedness
     },
     success: function(response) {

       // console.log(response);

       if( response['success'] ){

         showMessage(response['message_type'],response['message']);
         jQuery('#qr-code-hook').html("");
         jQuery('#cpd-point-hook').html("<b>CPD point of this rundown: " + response['cpd-point'] + "</b>");
         jQuery('#rundown-warning-hook').html("");

       } else {

         showMessage(response['message_type'],response['message']);

       }

     }

  });

}

function savingForm(postID,inputKey,formData){

  var jsonString = JSON.stringify(formData, function(key, value) {
        // If the value is an empty object or empty array, retain it
        if (Array.isArray(value) && value.length === 0) {
            return value;  // Keep empty arrays
        }
        if (typeof value === 'object' && Object.keys(value).length === 0) {
            return value;  // Keep empty objects
        }
        return value;
  });

  jQuery.ajax({
     type : "post",
     url : hkota_backend_ajax.ajaxurl,
     dataType:"json",
     data : {
       action: "save_form",
       post_id: postID,
       input_key: inputKey,
       formdata: jsonString
     },
     success: function(response) {
         if (response.success) {
             showMessage('notice',response.data.message);
             switch(response.data.type){
               case 'survey':
                  // jQuery('#survey-warning-hook').html("");
                  break;
               case 'quiz':
                  jQuery('#quiz-warning-hook').html("");
                  jQuery('#rundown-warning-hook').html("");
                  jQuery('#qr-code-hook').html("");
                  break;
             }
         } else {
             showMessage('error',response.data.message);
         }
     },
     error: function() {
         showMessage('error','An unexpected error occurred.');
     }

   });

}

function generateQRCode(postID){
  jQuery.ajax({
     type : "post",
     url : hkota_backend_ajax.ajaxurl,
     dataType:"json",
     data : {
       action: "generate_qr_code",
       post_id: postID,
     },
     success: function(response) {
       if( response.success ){
         showMessage('notice',"QR code generated.");
         var output = `<div class="flex">`;
         response.data.qrcodes.forEach( ( QRCode ) => {
           output += `<div class="course-file-preview">`;
           output += `<a href="` + QRCode['url'] + `" target="_blank">`;
           output += `<img src="` + QRCode['url'] + `" width='140px' ></a>`;
           output += `</div>`;
         });
         output += `</div><br><a target="_blank" href="/wp-admin/admin-post.php?action=download_qrcode&course_id=` + postID + `"><button type="button" class="button button-primary">Download QR Code</button></a><br><br>`;
         jQuery('#qr-code-hook').html(output);
         jQuery('#rundown-warning-hook').html("<p style='color:red;max-width:500px;'>Alert: You have already generated QR-code for this course, editing rundown after QR code generate will result in earsing all QR codes and pupil sign in / out datas.<br><br>You will need to re-generate QR code manually later.</p>");
         jQuery('#survey-warning-hook').html("<p style='color:red;max-width:500px;'>Alert: You have already generated QR-code for this course, editing survey after QR code generate will result in earsing all pupil sign in / out and survey datas.");
         jQuery('#quiz-warning-hook').html("<p style='color:red;max-width:500px;'>Alert: You have already generated QR-code for this course, editing quiz after QR code generate will result in earsing all QR codes and quiz datas.<br><br>You will need to re-generate QR code manually later.</p>");

       } else {
         showMessage('error',"Errors: " + response.data.message );
       }
     }
  });
}

function getPoster(postID){
  setTimeout(function(){
    jQuery.ajax({
       type : "post",
       url : hkota_backend_ajax.ajaxurl,
       dataType:"json",
       data : {
         action: "get_poster",
         post_id: postID,
       },
       success: function(response) {
         // console.log(response);
         if( response['success'] ){
           showMessage('notice',"poster generated.");
           var output = '<div><div class="course-file-preview">';
              output += '<a href="' + response['url'] + '" target="_blank">';
              output += '<img src="https://' + window.location.hostname + '/wp-content/plugins/hkota-courses-and-memberships/asset/pdf-icon.png" width="50px" ></a>';
              output += '<i data-post-id="' + postID + '" data-input-key="course_poster" class="fa-solid fa-circle-xmark"></i>'
              output += '</div><span>' + response['filename'] + '</span></div><br>';
           jQuery('#poster-hook').html(output);
         } else {
           showMessage('error',"Errors: " + response['message'] );
         }
       }
    });
  },3000);
}

function showBulkEditRow() {
    // Remove existing bulk edit row if present
    jQuery('#bulk-edit-row').remove();
    
    var bulkEditHtml = `
        <tr id="bulk-edit-row" style="background-color: #f9f9f9;">
            <td colspan="9" style="padding: 15px;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div>
                        <label for="bulk-enrollment-status"><strong>Enrollment Status:</strong></label>
                        <select id="bulk-enrollment-status" style="margin-left: 5px;">
                            <option value="">— No Change —</option>
                            <option value="enrolled">Enrolled</option>
                            <option value="awaiting_approval">Awaiting Approval</option>
                            <option value="pending">Pending</option>
                            <option value="waiting_list">Waiting List</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label for="bulk-attendance-status"><strong>Attendance Status:</strong></label>
                        <select id="bulk-attendance-status" style="margin-left: 5px;">
                            <option value="">— No Change —</option>
                            <option value="not_attended">Not Attended</option>
                            <option value="fully_attended">Fully Attended</option>
                            <option value="partially_attended">Partially Attended</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="bulk-edit-apply" class="button button-primary">Update</button>
                        <button type="button" id="bulk-edit-cancel" class="button">Cancel</button>
                    </div>
                </div>
            </td>
        </tr>
    `;
    
    // Insert after table header
    jQuery('#pupil-details-table thead').after(bulkEditHtml);
}

function performBulkEdit(pupilIds, enrollmentStatus, attendanceStatus) {
    var courseId = jQuery('#pupil-details-table').data('course-id');
    
    jQuery.ajax({
        url: hkota_backend_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'bulk_edit_pupils',
            pupil_ids: pupilIds,
            course_id: courseId,
            enrollment_status: enrollmentStatus,
            attendance_status: attendanceStatus
        },
        success: function(response) {
            if (response.success) {
                showMessage('notice', response.data.message || 'Bulk update completed successfully.');
                jQuery('#bulk-edit-row').remove();
                // Refresh the table
                render_pupil_table(courseId);
            } else {
                showMessage('error', response.data.message || 'An error occurred during bulk update.');
            }
        },
        error: function() {
            showMessage('error', 'An error occurred while performing bulk update.');
        }
    });
}

function showAttendanceDetailsPopup(data) {
    // Remove existing attendance popup if present
    jQuery('#attendance-details-popup').remove();
    
    var pupilName = data.user_name || 'Unknown User';
    var courseName = data.course_name || 'Unknown Course';
    var attendanceSections = data.attendance_sections || [];
    
    var popupHtml = `
        <div id="attendance-details-popup" class="popup-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
            <div class="popup-content" style="background: white; padding: 20px; border-radius: 5px; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                    <h2 style="margin: 0;">Attendance Details</h2>
                    <button class="close-attendance-popup" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Student:</strong> ${pupilName}<br>
                    <strong>Course:</strong> ${courseName}
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Overall Attendance Status:</strong> 
                    <span style="padding: 4px 8px; border-radius: 3px; background-color: ${getAttendanceStatusColor(data.overall_status)}; color: white;">
                        ${capitalizeFirstLetter((data.overall_status || 'not_attended').replace('_', ' '))}
                    </span>
                </div>
                
                <h3>QR Code Scan Details:</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Section</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Date & Time</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    if (attendanceSections.length > 0) {
        attendanceSections.forEach(function(section) {
            var statusText = section.attended ? 'Checked' : 'Not Checked';
            var statusColor = section.attended ? '#28a745' : '#dc3545';
            var statusIcon = section.attended ? '✓' : '✗';
            
            popupHtml += `
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">${section.section_name}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${section.date_time}</td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                        <span style="color: ${statusColor}; font-weight: bold;">
                            ${statusIcon} ${statusText}
                        </span>
                    </td>
                </tr>
            `;
        });
    } else {
        popupHtml += `
            <tr>
                <td colspan="3" style="border: 1px solid #ddd; padding: 20px; text-align: center; color: #666;">
                    No QR code sections found for this course.
                </td>
            </tr>
        `;
    }
    
    popupHtml += `
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button class="close-attendance-popup button" type="button">Close</button>
                </div>
            </div>
        </div>
    `;
    
    // Append popup to body
    jQuery('body').append(popupHtml);
    
    // Add event handlers
    jQuery('.close-attendance-popup').on('click', function() {
        jQuery('#attendance-details-popup').remove();
    });
    
    // Close popup when clicking on overlay
    jQuery('#attendance-details-popup').on('click', function(e) {
        if (e.target.id === 'attendance-details-popup') {
            jQuery('#attendance-details-popup').remove();
        }
    });
}

function getAttendanceStatusColor(status) {
    switch(status) {
        case 'fully_attended':
            return '#28a745';
        case 'partially_attended':
            return '#ffc107';
        case 'not_attended':
        default:
            return '#dc3545';
    }
}
