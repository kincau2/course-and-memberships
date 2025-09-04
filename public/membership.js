jQuery(document).ready(function(){

  jQuery('.woocommerce-account-my-memberships .membership-actions').remove();

  // Close popup when clicking the close button
  jQuery('.cart-close-popup').on('click', function() {
      jQuery('#error-popup').addClass('hidden');
  });

  jQuery(document).on('focusout', '.register-form input#mobile' , function(){

    var mobile = jQuery(this).val();

    var form = jQuery(this).closest('form');

    if( !mobile.match(/[356789]{1}[0-9]{7}/) ){
      form.find('button[type="submit"]').addClass("disabled");
      form.find('button[type="submit"]').attr("disabled","disabled");
      form.find('button[type="submit"]').attr("type","submit-disabled");
      showMessage('error','Please enter a valid Hong Kong mobile number.');
    } else{
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
