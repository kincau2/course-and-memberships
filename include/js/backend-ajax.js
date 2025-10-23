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
      var first_name = currentRow.find('td:nth-child(2)').text();
      var last_name = currentRow.find('td:nth-child(3)').text();
      var email = currentRow.find('td:nth-child(4)').text();
      var enrollment_status = currentRow.find('td:nth-child(5)').data('enrollment-status');
      var attendance_status = currentRow.find('td:nth-child(6)').data('attendance-status');
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
                    row += `<td>
                        <button type="button" class="button button-secondary edit-pupil" data-user-id=${pupil.user_id}>Edit</button>`;
                    
                    // Add Accept button for waiting list candidates
                    if (pupil.enrollment_status === 'waiting_list') {
                        row += `<br><button type="button" class="button button-primary accept-waiting-list" data-user-id="${pupil.user_id}" data-user-name="${pupil.first_name} ${pupil.last_name}" data-user-email="${pupil.email}" style="margin-top: 5px;">Accept</button>`;
                    }
                    
                    row += `</td></tr>`;
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
       rundown: JSON.stringify(sections),  // Convert to JSON string
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
    
    // Show progress modal
    showUniversalProgressModal(pupilIds.length, 'Processing Bulk Edit', 'students');
    
    // Initialize tracking variables
    const editResults = [];
    let currentIndex = 0;
    let successCount = 0;
    let errorCount = 0;
    
    // Disable bulk edit button
    jQuery('#bulk-edit-apply').prop('disabled', true).text('Processing...');
    
    // Start processing pupils one by one
    processNextPupil(courseId, pupilIds, enrollmentStatus, attendanceStatus, currentIndex, editResults, successCount, errorCount);
}

function processNextPupil(courseId, pupilIds, enrollmentStatus, attendanceStatus, currentIndex, editResults, successCount, errorCount) {
    if (currentIndex >= pupilIds.length) {
        // All pupils processed, show final results
        completeBulkEditProcess(editResults, successCount, errorCount, pupilIds.length, courseId);
        return;
    }
    
    const currentPupilId = pupilIds[currentIndex];
    const pupilName = `Student ${currentPupilId}`; // We'll get the actual name from response
    
    updateUniversalProgressBar(currentIndex + 1, pupilIds.length, `Processing ${pupilName}...`, 'students');
    
    // Process single pupil
    jQuery.ajax({
        url: hkota_backend_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'bulk_edit_single_pupil',
            pupil_id: currentPupilId,
            course_id: courseId,
            enrollment_status: enrollmentStatus,
            attendance_status: attendanceStatus
        },
        success: function(response) {
            let result;
            if (response.success) {
                result = {
                    user_id: currentPupilId,
                    user_name: response.data.user_name || pupilName,
                    user_email: response.data.user_email || 'N/A',
                    success: true,
                    message: response.data.message || 'Updated successfully'
                };
                successCount++;
            } else {
                result = {
                    user_id: currentPupilId,
                    user_name: response.data.user_name || pupilName,
                    user_email: response.data.user_email || 'N/A',
                    success: false,
                    message: response.data.message || 'Update failed'
                };
                errorCount++;
            }
            
            editResults.push(result);
            
            // Update progress with result
            const statusText = result.success ? 
                `✓ ${result.user_name} - Updated successfully` : 
                `✗ ${result.user_name} - ${result.message}`;
            updateUniversalProgressBar(currentIndex + 1, pupilIds.length, statusText, 'students');
            
            // Small delay to let user see the status, then process next
            setTimeout(function() {
                processNextPupil(courseId, pupilIds, enrollmentStatus, attendanceStatus, currentIndex + 1, editResults, successCount, errorCount);
            }, 500);
        },
        error: function() {
            const result = {
                user_id: currentPupilId,
                user_name: pupilName,
                user_email: 'N/A',
                success: false,
                message: 'AJAX request failed'
            };
            
            editResults.push(result);
            errorCount++;
            
            updateUniversalProgressBar(currentIndex + 1, pupilIds.length, `✗ ${pupilName} - Network error`, 'students');
            
            // Continue to next pupil even if there's an error
            setTimeout(function() {
                processNextPupil(courseId, pupilIds, enrollmentStatus, attendanceStatus, currentIndex + 1, editResults, successCount, errorCount);
            }, 500);
        }
    });
}

function completeBulkEditProcess(results, successCount, errorCount, totalProcessed, courseId) {
    // Final update to progress bar
    updateUniversalProgressBar(totalProcessed, totalProcessed, 'All updates completed!', 'students');
    
    // Wait a moment then show results
    setTimeout(function() {
        // Close progress modal
        closeUniversalProgressModal();
        
        // Re-enable bulk edit button and remove bulk edit row
        jQuery('#bulk-edit-apply').prop('disabled', false).text('Update');
        jQuery('#bulk-edit-row').remove();
        
        // Show detailed results
        const consolidatedData = {
            results: results,
            success_count: successCount,
            error_count: errorCount,
            total_processed: totalProcessed
        };
        
        showUniversalResultsModal(consolidatedData, 'Bulk Edit', 'students');
        
        // Refresh the pupil details table
        render_pupil_table(courseId);
    }, 1500);
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

// Universal Modal Functions (Global scope for use across all features)
function showUniversalProgressModal(totalItems, operationType = 'Processing', itemName = 'items') {
    // Remove any existing progress modal
    jQuery('#universal-progress-modal').remove();
    
    const modalTitle = `${operationType} ${itemName}`;
    const initialMessage = `Starting ${operationType.toLowerCase()} process...`;
    
    const modalHtml = `
        <div id="universal-progress-modal" class="popup-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;">
            <div class="popup-content" style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; text-align: center;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa;">${modalTitle}</h2>
                
                <div style="margin-bottom: 20px;">
                    <div style="background: #f1f1f1; border-radius: 10px; height: 20px; overflow: hidden; margin-bottom: 10px;">
                        <div id="progress-bar-fill" style="background: linear-gradient(90deg, #0073aa, #005177); height: 100%; width: 0%; transition: width 0.3s ease; border-radius: 10px;"></div>
                    </div>
                    <div id="progress-text" style="font-weight: bold; color: #333;">Initializing...</div>
                    <div id="progress-counter" style="color: #666; margin-top: 5px;">0 of ${totalItems} ${itemName} processed</div>
                </div>
                
                <div id="progress-status" style="text-align: left; max-height: 150px; overflow-y: auto; background: #f9f9f9; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; border: 1px solid #ddd;">
                    <div style="color: #666;">${initialMessage}</div>
                </div>
            </div>
        </div>
    `;
    
    jQuery('body').append(modalHtml);
}

function updateUniversalProgressBar(current, total, statusMessage, itemName = 'items') {
    const percentage = (current / total) * 100;
    
    jQuery('#progress-bar-fill').css('width', percentage + '%');
    jQuery('#progress-text').text(statusMessage);
    jQuery('#progress-counter').text(`${current} of ${total} ${itemName} processed`);
    
    // Add status message to log
    const timestamp = new Date().toLocaleTimeString();
    jQuery('#progress-status').append(`<div style="margin-bottom: 3px;"><span style="color: #999;">[${timestamp}]</span> ${statusMessage}</div>`);
    
    // Auto-scroll to bottom
    const statusDiv = jQuery('#progress-status')[0];
    if (statusDiv) {
        statusDiv.scrollTop = statusDiv.scrollHeight;
    }
}

function closeUniversalProgressModal() {
    jQuery('#universal-progress-modal').remove();
}

function showUniversalResultsModal(data, operationType = 'Operation', itemName = 'items') {
    // Remove any existing results popup
    jQuery('#universal-results-popup').remove();
    
    const results = data.results || [];
    const successfulResults = results.filter(result => result.success);
    const failedResults = results.filter(result => !result.success);
    
    let popupHtml = `
        <div id="universal-results-popup" class="popup-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
            <div class="popup-content" style="background: white; padding: 20px; border-radius: 5px; max-width: 800px; width: 90%; max-height: 80%; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
                    <h2 style="margin: 0; color: #0073aa;">${operationType} Results</h2>
                    <button class="close-universal-results-popup" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666; font-weight: bold;">&times;</button>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 5px;">
                    <h3 style="margin: 0 0 10px 0; color: #0073aa;">Summary</h3>
                    <p style="margin: 5px 0;"><strong>Total Processed:</strong> ${data.total_processed}</p>
                    <p style="margin: 5px 0; color: #28a745;"><strong>Successful:</strong> ${data.success_count}</p>
                    <p style="margin: 5px 0; color: #dc3545;"><strong>Failed:</strong> ${data.error_count}</p>
                </div>
    `;
    
    // Show successful results
    if (successfulResults.length > 0) {
        popupHtml += `
            <div style="margin-bottom: 20px;">
                <h3 style="color: #28a745; border-bottom: 1px solid #28a745; padding-bottom: 5px;">
                    <i class="fa-solid fa-check-circle"></i> Successful ${operationType} (${successfulResults.length})
                </h3>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background-color: #f8f9fa; position: sticky; top: 0;">
                            <tr>
                                <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: bold;">Name</th>
                                <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: bold;">Email</th>
                                <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: bold;">Details</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        successfulResults.forEach(function(result) {
            popupHtml += `
                <tr>
                    <td style="border: 1px solid #dee2e6; padding: 8px;">${result.user_name || result.name || 'N/A'}</td>
                    <td style="border: 1px solid #dee2e6; padding: 8px;">${result.user_email || result.email || 'N/A'}</td>
                    <td style="border: 1px solid #dee2e6; padding: 8px;">${result.message || result.details || 'Success'}</td>
                </tr>
            `;
        });
        
        popupHtml += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    // Show failed results
    if (failedResults.length > 0) {
        popupHtml += `
            <div style="margin-bottom: 20px;">
                <h3 style="color: #dc3545; border-bottom: 1px solid #dc3545; padding-bottom: 5px;">
                    <i class="fa-solid fa-exclamation-triangle"></i> Failed ${operationType} (${failedResults.length})
                </h3>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background-color: #f8f9fa; position: sticky; top: 0;">
                            <tr>
                                <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: bold;">Name</th>
                                <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: bold;">Email</th>
                                <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: bold;">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        failedResults.forEach(function(result) {
            popupHtml += `
                <tr>
                    <td style="border: 1px solid #dee2e6; padding: 8px;">${result.user_name || result.name || 'N/A'}</td>
                    <td style="border: 1px solid #dee2e6; padding: 8px;">${result.user_email || result.email || 'N/A'}</td>
                    <td style="border: 1px solid #dee2e6; padding: 8px; color: #dc3545;">${result.message || 'Unknown error'}</td>
                </tr>
            `;
        });
        
        popupHtml += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    popupHtml += `
                <div style="text-align: right; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
                    <button id="export-universal-results" class="button button-secondary" type="button" style="margin-right: 10px;">
                        <i class="fa-solid fa-download"></i> Export CSV
                    </button>
                    <button class="close-universal-results-popup button button-primary" type="button">Close</button>
                </div>
            </div>
        </div>
    `;
    
    // Append popup to body
    jQuery('body').append(popupHtml);
    
    // Add event handlers
    jQuery('.close-universal-results-popup').on('click', function() {
        jQuery('#universal-results-popup').remove();
    });
    
    // Export CSV functionality
    jQuery('#export-universal-results').on('click', function() {
        exportUniversalResultsToCSV(data, operationType);
    });
    
    // Close popup when clicking on overlay
    jQuery('#universal-results-popup').on('click', function(e) {
        if (e.target.id === 'universal-results-popup') {
            jQuery('#universal-results-popup').remove();
        }
    });
}

function exportUniversalResultsToCSV(data, operationType = 'operation') {
    const results = data.results || [];
    
    // Create CSV header
    const headers = ['Name', 'Email', 'Status', 'Details/Message'];
    
    // Create CSV rows
    const csvRows = [headers];
    
    results.forEach(function(result) {
        const row = [
            result.user_name || result.name || 'N/A',
            result.user_email || result.email || 'N/A',
            result.success ? 'Success' : 'Failed',
            result.message || result.details || ''
        ];
        csvRows.push(row);
    });
    
    // Convert to CSV string
    const csvContent = csvRows.map(row => 
        row.map(field => `"${String(field).replace(/"/g, '""')}"`)
           .join(',')
    ).join('\n');
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        
        // Generate filename with timestamp
        const now = new Date();
        const timestamp = now.getFullYear() + 
                        String(now.getMonth() + 1).padStart(2, '0') + 
                        String(now.getDate()).padStart(2, '0') + '_' +
                        String(now.getHours()).padStart(2, '0') + 
                        String(now.getMinutes()).padStart(2, '0');
        
        link.setAttribute('download', `${operationType.toLowerCase().replace(/\s+/g, '_')}_results_${timestamp}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Admin Enrollment Functionality
jQuery(document).ready(function() {
    let selectedUsers = [];
    let searchTimeout;

    // Show/hide user selection dropdown
    jQuery(document).on('click', '#add-candidate-payment-btn', function() {
        jQuery('#user-selection-dropdown').slideToggle();
        jQuery('#user-search-input').focus();
    });

    // User search functionality
    jQuery(document).on('input', '#user-search-input', function() {
        let searchTerm = jQuery(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length >= 2) {
            searchTimeout = setTimeout(function() {
                searchUsers(searchTerm);
            }, 300);
        } else {
            jQuery('#user-suggestions').hide();
        }
    });

    // Add user from suggestions
    jQuery(document).on('click', '.user-suggestion-item', function() {
        let userData = {
            id: jQuery(this).data('user-id'),
            name: jQuery(this).data('user-name'),
            email: jQuery(this).data('user-email')
        };
        
        addUserToSelection(userData);
        jQuery('#user-search-input').val('');
        jQuery('#user-suggestions').hide();
    });

    // Remove user from selection
    jQuery(document).on('click', '.remove-user', function() {
        let userId = jQuery(this).data('user-id');
        removeUserFromSelection(userId);
    });

    // Confirm enrollment
    jQuery(document).on('click', '#confirm-enrollment', function() {
        if (selectedUsers.length === 0) {
            alert('Please select at least one user.');
            return;
        }
        
        let courseId = jQuery('#pupil-details-table').data('course-id');
        
        if (confirm(`Create pending orders and send payment links to ${selectedUsers.length} user(s)?`)) {
            startBulkEnrollmentProcess(courseId, selectedUsers);
        }
    });

    // Cancel enrollment
    jQuery(document).on('click', '#cancel-enrollment', function() {
        selectedUsers = [];
        updateSelectedUsersDisplay();
        jQuery('#user-selection-dropdown').slideUp();
        jQuery('#user-search-input').val('');
        jQuery('#user-suggestions').hide();
    });

    // Accept waiting list candidate
    jQuery(document).on('click', '.accept-waiting-list', function() {
        let userId = jQuery(this).data('user-id');
        let userName = jQuery(this).data('user-name');
        let userEmail = jQuery(this).data('user-email');
        let courseId = jQuery('#pupil-details-table').data('course-id');
        
        if (confirm(`Accept ${userName} from waiting list and create payment order?\n\nThis will:\n- Change status from "Waiting List" to "On Hold"\n- Create a pending WooCommerce order\n- Send payment request email to ${userEmail}`)) {
            
            // Create user object in same format as bulk enrollment
            let userToAccept = [{
                id: userId,
                name: userName,
                email: userEmail
            }];
            
            // Use the same bulk enrollment process but for single user
            startBulkEnrollmentProcess(courseId, userToAccept, true); // true indicates waiting list acceptance
        }
    });

    function searchUsers(searchTerm) {
        let courseId = jQuery('#pupil-details-table').data('course-id');
        
        jQuery.ajax({
            url: hkota_backend_ajax.ajaxurl,
            method: 'POST',
            data: {
                action: 'search_users_for_course_enrollment',
                search_term: searchTerm,
                course_id: courseId,
                nonce: hkota_backend_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayUserSuggestions(response.data);
                } else {
                    console.error('Search failed:', response.data);
                }
            },
            error: function() {
                console.error('AJAX request failed');
            }
        });
    }

    function displayUserSuggestions(users) {
        let suggestionsHtml = '';
        
        if (users.length === 0) {
            suggestionsHtml = '<div class="user-suggestion-item">No users found</div>';
        } else {
            users.forEach(function(user) {
                // Skip users already selected
                if (!selectedUsers.find(selected => selected.id == user.id)) {
                    suggestionsHtml += `
                        <div class="user-suggestion-item" 
                             data-user-id="${user.id}" 
                             data-user-name="${user.last_name}, ${user.first_name}" 
                             data-user-email="${user.user_email}">
                            <strong>${user.last_name}, ${user.first_name}</strong><br>
                            <small>${user.user_email}</small>
                        </div>
                    `;
                }
            });
        }
        
        jQuery('#user-suggestions').html(suggestionsHtml).show();
    }

    function addUserToSelection(userData) {
        // Check if user is already selected
        if (selectedUsers.find(user => user.id == userData.id)) {
            return;
        }
        
        selectedUsers.push(userData);
        updateSelectedUsersDisplay();
    }

    function removeUserFromSelection(userId) {
        selectedUsers = selectedUsers.filter(user => user.id != userId);
        updateSelectedUsersDisplay();
    }

    function updateSelectedUsersDisplay() {
        let tagsHtml = '';
        
        selectedUsers.forEach(function(user) {
            tagsHtml += `
                <span class="user-tag">
                    ${user.name} (${user.email})
                    <span class="remove-user" data-user-id="${user.id}">&times;</span>
                </span>
            `;
        });
        
        jQuery('#selected-users-tags').html(tagsHtml);
    }

    function startBulkEnrollmentProcess(courseId, users, isWaitingListAcceptance = false) {
        // Determine operation type and labels
        const operationType = isWaitingListAcceptance ? 'Processing Waiting List Acceptance' : 'Processing Enrollments';
        
        // Show progress modal
        showUniversalProgressModal(users.length, operationType, 'users');
        
        // Initialize tracking variables
        const enrollmentResults = [];
        let currentIndex = 0;
        let successCount = 0;
        let errorCount = 0;
        
        // Disable the confirm button (if it exists)
        jQuery('#confirm-enrollment').prop('disabled', true).text('Processing...');
        
        // Start processing users one by one
        processNextUser(courseId, users, currentIndex, enrollmentResults, successCount, errorCount, isWaitingListAcceptance);
    }
    
    function processNextUser(courseId, users, currentIndex, enrollmentResults, successCount, errorCount, isWaitingListAcceptance) {
        if (currentIndex >= users.length) {
            // All users processed, show final results
            completeEnrollmentProcess(enrollmentResults, successCount, errorCount, users.length);
            return;
        }
        
        const currentUser = users[currentIndex];
        updateUniversalProgressBar(currentIndex + 1, users.length, `Processing ${currentUser.name}...`, 'users');
        
        // Process single user
        createOrderForSingleUser(courseId, currentUser, isWaitingListAcceptance, function(result) {
            // Add result to collection
            enrollmentResults.push(result);
            
            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
            
            // Update progress with result
            const actionText = isWaitingListAcceptance ? 'accepted' : 'enrolled';
            const statusText = result.success ? 
                `✓ ${currentUser.name} - ${actionText} successfully` : 
                `✗ ${currentUser.name} - ${result.message}`;
            updateUniversalProgressBar(currentIndex + 1, users.length, statusText, 'users');
            
            // Small delay to let user see the status, then process next
            setTimeout(function() {
                processNextUser(courseId, users, currentIndex + 1, enrollmentResults, successCount, errorCount, isWaitingListAcceptance);
            }, 500);
        });
    }
    
    function createOrderForSingleUser(courseId, user, isWaitingListAcceptance, callback) {
      console.log('Creating order for user:', user, 'Waiting list acceptance:', isWaitingListAcceptance);
      jQuery.ajax({
        url: hkota_backend_ajax.ajaxurl,
        method: 'POST',
        data: {
            action: 'handle_admin_create_order_for_course',
            course_id: courseId,
            user_ids: [user.id], // Single user array
            is_waiting_list_acceptance: isWaitingListAcceptance, // Pass the flag
            nonce: hkota_backend_ajax.nonce
        },
        success: function(response) {
            if (response.success && response.data.results && response.data.results.length > 0) {
                // Extract the single result
                const result = response.data.results[0];
                callback(result);
            } else {
                callback({
                    user_id: user.id,
                    user_name: user.name,
                    user_email: user.email,
                    success: false,
                    message: response.data || 'Unknown error occurred'
                });
            }
        },
        error: function() {
            callback({
                user_id: user.id,
                user_name: user.name,
                user_email: user.email,
                success: false,
                message: 'AJAX request failed'
            });
        }
    });
    }
    
    function completeEnrollmentProcess(results, successCount, errorCount, totalProcessed) {
        // Final update to progress bar
        updateUniversalProgressBar(totalProcessed, totalProcessed, 'All enrollments completed!', 'users');
        
        // Wait a moment then show results
        setTimeout(function() {
            // Close progress modal
            closeUniversalProgressModal();
            
            // Re-enable confirm button
            jQuery('#confirm-enrollment').prop('disabled', false).text('Create Orders & Send Payment Links');
            
            // Show detailed results
            const consolidatedData = {
                results: results,
                success_count: successCount,
                error_count: errorCount,
                total_processed: totalProcessed
            };
            
            showUniversalResultsModal(consolidatedData, 'Enrollment', 'users');
            
            // Reset the form
            selectedUsers = [];
            updateSelectedUsersDisplay();
            jQuery('#user-selection-dropdown').slideUp();
            jQuery('#user-search-input').val('');
            jQuery('#user-suggestions').hide();
            
            // Refresh the pupil details table
            jQuery('#show-pupil-details').trigger('click');
            jQuery('#show-pupil-details').trigger('click'); // Second click to refresh data
        }, 1500);
    }
});
