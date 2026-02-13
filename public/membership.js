jQuery(document).ready(function(){

  jQuery('.woocommerce-account-my-memberships .membership-actions').remove();

  // Close popup when clicking the close button
  jQuery('.cart-close-popup').on('click', function() {
      jQuery('#error-popup').addClass('hidden');
  });

  jQuery(document).on('focusout', '.register-form input#mobile' , function(){

    var mobile = jQuery(this).val();
    var form = jQuery(this).closest('form');
    var countryCode = form.find('select#country_code').val() || '+852';
    
    var isValid = false;
    var errorMessage = '';
    
    // Validate based on country code
    if (countryCode === '+852') {
      // Hong Kong: 8 digits starting with 5, 6, 7, 8, or 9
      isValid = mobile.match(/^[56789]{1}[0-9]{7}$/);
      errorMessage = 'Please enter a valid Hong Kong mobile number (8 digits starting with 5, 6, 7, 8, or 9).';
    } else if (countryCode === '+853') {
      // Macau: 8 digits starting with 6
      isValid = mobile.match(/^6[0-9]{7}$/);
      errorMessage = 'Please enter a valid Macau mobile number (8 digits starting with 6).';
    } else if (countryCode === '+86') {
      // China: 11 digits starting with 1
      isValid = mobile.match(/^1[0-9]{10}$/);
      errorMessage = 'Please enter a valid China mobile number (11 digits starting with 1).';
    } else if (countryCode === '+44') {
      // UK: 10-11 digits, mobile numbers typically start with 7
      isValid = mobile.match(/^(7[0-9]{9}|07[0-9]{9})$/);
      errorMessage = 'Please enter a valid UK mobile number (10-11 digits, starting with 7 or 07).';
    } else if (countryCode === '+1') {
      // USA/Canada: 10 digits
      isValid = mobile.match(/^[2-9][0-9]{9}$/);
      errorMessage = 'Please enter a valid USA/Canada mobile number (10 digits).';
    } else if (countryCode === '+61') {
      // Australia: 9-10 digits, mobile numbers start with 4
      isValid = mobile.match(/^(4[0-9]{8}|04[0-9]{8})$/);
      errorMessage = 'Please enter a valid Australia mobile number (9-10 digits, starting with 4 or 04).';
    }

    if (!isValid) {
      form.find('button[type="submit"]').addClass("disabled");
      form.find('button[type="submit"]').attr("disabled","disabled");
      form.find('button[type="submit"]').attr("type","submit-disabled");
      showMessage('error', errorMessage);
    } else {
      form.find('button[type="submit-disabled"]').attr("type","submit");
      form.find('button[type="submit"]').removeClass("disabled");
      form.find('button[type="submit"]').removeAttr("disabled");
    }

  });

});


function showMessage(messageType, messageContent) {
    // Remove excess message boxes if more than 5 exist
    if (jQuery('.popup-message').length >= 5) {
        jQuery('.popup-message').first().remove();
    }

    // Create a new message box
    const messageBox = jQuery('<div></div>', {
        class: `popup-message ${messageType}`,
        text: messageContent
    });

    // Append the message box to the body
    jQuery('body').append(messageBox);

    // Adjust the position of all messages on the screen
    adjustMessagePositions();

    // Show the message box
    messageBox.fadeIn(300).css('opacity', '1');

    // showMessage('notice', 'This is a normal notice.');
    // showMessage('warning', 'This is a warning message.');
    // showMessage('error', 'This is an error message.');

    // Hide the message box after 3 seconds
    setTimeout(function () {
        messageBox.fadeOut(300, function () {
            jQuery(this).remove();
            adjustMessagePositions(); // Adjust positions after one is removed
        });
    }, 4500);

}

// Adjust the positions of all messages so they stack on top of each other
function adjustMessagePositions() {
    const messageBoxes = jQuery('.popup-message');
    let topOffset = 50; // Starting top offset

    messageBoxes.each(function (index, messageBox) {
        jQuery(messageBox).css('top', topOffset + 'px');
        topOffset += jQuery(messageBox).outerHeight() + 10; // Add space between each message
    });
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
