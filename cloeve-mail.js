/**
 * Cloeve Mail Free JS
 */


function subscribeEmail() {

    jQuery( ".ctEmailListBuilderForm" ).each(function( index ) {

        // get data
        var currentForm = jQuery( this );
        var formData = currentForm.serializeArray();

        // if data
        if(formData !== undefined && jQuery.isArray(formData) && formData.length >= 2
            && formData[1].value !== undefined && formData[1].value !== ''){

            jQuery.ajax({
                type: 'POST',
                url: '/wp-json/cloeve-tech/cloeve-mail/v1/subscribe_email',
                data: {'source': formData[0].value, 'email': formData[1].value},
                success: function(response) {

                    // reset form
                    currentForm.each(function(){
                        this.reset();
                    });

                    // show message
                    jQuery(".cloeve-success-container").css("margin-top", "10px");
                    jQuery(".cloeve-success-container").css("max-height", "40px");

                    // hide message
                    setTimeout(function(){
                        jQuery(".cloeve-success-container").css("margin-top", "0");
                        jQuery(".cloeve-success-container").css("max-height", "0");
                    }, 5000);

                },
                error: function() {
                    alert("There was an error submitting your email, please try again.");
                }
            });

        }

    });



    return false;
}
