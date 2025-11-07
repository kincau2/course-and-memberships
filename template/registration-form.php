<?php

// @param Class Course object $this

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
<?php
$display_text;
switch ($section['type']) {
    case 'registration':
        $display_text = 'Sign In';
        break;
    case 'end':
        $display_text = 'Sign Out';
        break;
}
?>
<div id="registration-overlay">
<form id="registration-form" method="post" action="">
    <h2><?php echo esc_html($display_text). ' section'; ?></h2>
    <p><?php echo esc_html($section['date']) . ' ' . esc_html($section['startTime']); ?></p>
    <label for="registration_email">Registration Email:</label><br>
    <input type="email" id="registration_email" name="registration_email" required><br>
    <input type="hidden" name="section_id" value="<?php echo esc_attr($section['id']); ?>">
    <input type="hidden" name="course_id" value="<?php echo esc_attr($this->id); ?>">
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