<?php

// @param Class Course object

?>

<style>
/* Overlay */
.pupil-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
}

/* Popup content */
.popup-content {
    background-color: white;
    padding: 20px;
    margin: 50px auto;
    width: 80%;
    max-width: 1024px; /* Set a maximum width */
    max-height: 80%; /* Set a maximum height for the popup */
    position: relative;
    z-index: 1001;
    overflow-y: auto; /* Add vertical scrolling if content exceeds max height */
}

/* Popup close button */
.close-popup {
    cursor: pointer;
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 20px;
}

/* Table inside the popup */
.popup-content table {
    width: 100%;
    border-collapse: collapse;
}

.popup-content table th, .popup-content table td {
    padding: 10px;
    border: 1px solid #ddd;
}

.popup-content table th {
    background-color: #f4f4f4;
}
.popup-content h2{
padding: unset;
margin: 20px 0;
font-size: 20px;
font-weight: 600;
}

/* Admin Enrollment Section */
.admin-enrollment-section {
background: #f9f9f9;
padding: 15px;
border-radius: 5px;
border: 1px solid #ddd;
}

.user-search-container {
margin-bottom: 15px;
}

.user-search-container label {
display: block;
font-weight: bold;
margin-bottom: 5px;
}

#user-search-input {
width: 100%;
max-width: 400px;
padding: 8px;
border: 1px solid #ccc;
border-radius: 3px;
}

.user-suggestions-list {
max-width: 400px;
max-height: 200px;
overflow-y: auto;
border: 1px solid #ccc;
border-top: none;
background: white;
position: relative;
z-index: 1000;
}

.user-suggestion-item {
padding: 10px;
cursor: pointer;
border-bottom: 1px solid #eee;
}

.user-suggestion-item:hover {
background: #f5f5f5;
}

.user-suggestion-item:last-child {
border-bottom: none;
}

.selected-users-container label {
display: block;
font-weight: bold;
margin-bottom: 5px;
}

.selected-users-tags {
min-height: 40px;
border: 1px solid #ccc;
border-radius: 3px;
padding: 5px;
background: white;
}

.user-tag {
display: inline-block;
background: #0073aa;
color: white;
padding: 5px 10px;
margin: 2px;
border-radius: 15px;
font-size: 12px;
}

.user-tag .remove-user {
margin-left: 8px;
cursor: pointer;
font-weight: bold;
}

.user-tag .remove-user:hover {
color: #ff6b6b;
}

.enrollment-actions {
text-align: right;
}

.enrollment-actions .button {
margin-left: 10px;
}
#pupil-details-table {
width: 100%;
border-collapse: collapse;
}

#pupil-details-table th, #pupil-details-table td {
border: 1px solid #ddd;
padding: 8px;
text-align: left;
}

#pupil-details-table th {
background-color: #f2f2f2;
font-weight: bold;
}

.check-column {
width: 30px;
text-align: center;
}

#pupil-bulk-actions {
margin-bottom: 15px;
padding: 10px;
background-color: #f9f9f9;
border: 1px solid #ddd;
border-radius: 3px;
}

#pupil-bulk-actions select {
margin-right: 10px;
}

#bulk-edit-row {
background-color: #fff8dc;
border: 2px solid #ffec8c;
}

#bulk-edit-row td {
padding: 15px;
}

#bulk-edit-row label {
font-weight: 600;
margin-right: 5px;
}

#bulk-edit-row select {
margin-right: 15px;
min-width: 150px;
}

#attendance-details-popup .popup-content {
box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

#attendance-details-popup table {
font-size: 14px;
}

#attendance-details-popup th {
background-color: #f8f9fa;
font-weight: 600;
}

#attendance-details-popup .close-attendance-popup:hover {
color: #000;
}

.tablesorter th {
    cursor: pointer;
}

.tablesorter th.headerSortUp {
    background-color: #f0f0f0;
}

.tablesorter th.headerSortDown {
    background-color: #d0d0d0;
}

.tablesorter tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}
</style>
<script src="/wp-content/plugins/hkota-courses-and-memberships/lib/jquery-tablesorter/jquery.tablesorter.min.js"></script>
<link rel="stylesheet" type="text/css" href="/wp-content/plugins/hkota-courses-and-memberships/lib/jquery-tablesorter/theme.default.min.css">

<div id="pupil-details-popup" class="pupil-popup-overlay" style="display:none;">
    <div class="popup-content">
        <h2>Pupil Details</h2>
        
        <!-- Admin Enrollment Section -->
        <div class="admin-enrollment-section" style="margin-bottom: 20px;">
            <button type="button" class="button button-secondary" id="add-candidate-payment-btn" data-course-id="<?php // echo $course->id; ?>">
                Add Candidate (Payment Required)
            </button>
            <p>If course is free for a user, no payment link will be sent. please make sure course fee is set correctly.</p>
            <!-- User Selection Dropdown -->
            <div id="user-selection-dropdown" style="display:none; margin-top: 15px;">
                <div class="user-search-container">
                    <label for="user-search-input">Search Users:</label>
                    <input type="text" id="user-search-input" placeholder="Type name or email..." style="width: 300px; margin-bottom: 10px;">
                    <div id="user-suggestions" class="user-suggestions-list" style="display:none;"></div>
                </div>
                
                <div class="selected-users-container">
                    <label>Selected Users:</label>
                    <div id="selected-users-tags" class="selected-users-tags"></div>
                </div>
                
                <div class="enrollment-actions" style="margin-top: 15px;">
                    <button type="button" class="button button-primary" id="confirm-enrollment">Create Orders & Send Payment Links</button>
                    <button type="button" class="button" id="cancel-enrollment">Cancel</button>
                </div>
            </div>
        </div>
        
        <table id="pupil-details-table" class="tablesorter" data-course-id="<?php // echo $course->id ?>">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Enrollment Status</th>
                    <th>Attendance Status</th>
                    <th>Certificate Status</th>
                    <th>Submitted Document</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                <!-- Pupils data will be inserted here via AJAX -->
            </tbody>
        </table>
        <span class="close-popup"><i class="fa-solid fa-xmark"></i></button>
    </div>
</div>