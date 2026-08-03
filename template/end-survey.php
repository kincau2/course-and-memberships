<?php

// @param Class Course object $this

?>

<style>
/* Full-screen overlay */
#combined-form-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 1); /* Overlay shadow */
    z-index: 9999;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow-y: auto; /* Enable scrolling */
    padding: 20px;
}

/* Form container */
#combined-form-container {
    width: 550px;
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    text-align: center;
    margin-top: 15vh;
    overflow-y: auto;
}

#combined-form-container label{
width: 100%;
text-align: left;
}

#combined-form-container h2 {
    margin-bottom: 45px;
    margin-top: 25px;
}

/* Input and button styling */
#combined-form-container input[type="text"],
#combined-form-container input[type="number"],
#combined-form-container input[type="email"],
#combined-form-container textarea,
#combined-form-container select {
    width: 100%; /* Full width input */
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

#combined-form-container input[type="submit"],
#combined-form-container button {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    background-color: #0073aa;
    color: white;
    border: none;
    cursor: pointer;
}

#combined-form-container input[type="submit"]:hover,
#combined-form-container button:hover {
    background-color: #005177;
}

.hidden {
    display: none;
}

</style>
<div id="combined-form-overlay">
<div id="combined-form-container">
    <!-- First Part: Sign In/Out Form -->
    <form method="post">
        <div id="part-one">
        <h2>Sign out section & End course survey </h2>
        <label for="registration_email">Registration Email:</label><br>
        <input type="email" id="registration_email" name="registration_email" required><br>
        <input type="hidden" name="section_id" value="<?php echo esc_attr($section['id']); ?>">
        <input type="hidden" name="course_id" value="<?php echo esc_attr($this->id); ?>">
        <button type="button" id="next-button">Next</button>
        </div>

    <!-- Second Part: Survey Form (initially hidden) -->
    <div id="part-two" class="hidden">
        <h2>Course Survey</h2>

        <?php
        if (!empty($this->survey)) {
            foreach ($this->survey as $question) {
                $label = esc_html($question['label']);
                $id = esc_attr($question['id']);
                $value = isset($question['value']) ? esc_attr($question['value']) : '';

                echo "<label for='{$id}'>{$label}</label><br>";

                switch ($question['type']) {
                    case 'select':
                        $options = explode(',', $question['options']);
                        echo "<select id='{$id}' name='{$id}' required>";
                        foreach ($options as $option) {
                            $selected = ($option === $value) ? "selected" : "";
                            echo "<option value='{$option}' {$selected}>{$option}</option>";
                        }
                        echo "</select><br>";
                        break;

                    case 'textarea':
                        echo "<textarea id='{$id}' name='{$id}' rows='4' required>{$value}</textarea><br>";
                        break;

                    case 'text':
                    case 'number':
                    case 'email':
                        echo "<input type='{$question['type']}' id='{$id}' name='{$id}' value='{$value}' required><br>";
                        break;
                }
            }
        }
        ?>
        <button type="button" id="back-button">Back</button>
        <input type="submit" value="Submit">
        </div>
    </form>
</div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    var nextButton = document.getElementById('next-button');
    var backButton = document.getElementById('back-button');
    var partOne = document.getElementById('part-one');
    var partTwo = document.getElementById('part-two');

    // Show the survey form when Next button is clicked
    nextButton.addEventListener('click', function (e) {
        e.preventDefault();
        var regEmail = document.getElementById('registration_email').value.trim();
        emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (regEmail === '' || !emailPattern.test(regEmail) ) {
            showMessage('error','Please enter your registration email.');
            return;
        }
        partOne.style.display = 'none';
        partTwo.classList.remove('hidden');
    });

    // Go back to the first form when Back button is clicked
    backButton.addEventListener('click', function (e) {
        e.preventDefault();
        partTwo.classList.add('hidden');
        partOne.style.display = 'block';
    });
});
// Prevent form submission on Enter keypress
document.addEventListener('keypress', function (event) {
    if (event.key === 'Enter') {
        event.preventDefault();
    }
});

</script>