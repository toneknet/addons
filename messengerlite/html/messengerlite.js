$(document).ready(function() {
    function checkForm() {
        const isRadioChecked = $('.frm_category:checked').length > 0;
        const isTextInputFilled = $('.frm_subject').val().trim() !== '';
        const isTextareaFilled = $('.frm_message').val().trim() !== '';

        if (isRadioChecked && isTextInputFilled && isTextareaFilled) {
            $('#db_submit').prop('disabled', false);
        } else {
            $('#db_submit').prop('disabled', true);
        }
    }

    // Apply the function to all necessary elements
    $('.frm_category, .frm_subject, .frm_message').on('change keyup', checkForm);

    // Initial check on page load
    checkForm();
    console.log('messengerlite.js loaded');


    // $("form#dbform :input").each(function(){
    //     var input = $(this); // This is the jquery object of the input, do what you will
    //     console.log($(this));
    // });



});
