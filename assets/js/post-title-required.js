jQuery(function ($) {
    // Set your desired character limit
    try {
        var characterLimit = data_obj.ptreq_character_limit;
    } catch (error) {
        var characterLimit = 100;
    }

    /**
     * check post title 
     */
    let titleField = $('input[name="post_title"]');
    if (titleField.length) {
        // For the post without editor
        ptreq_input_title_field(titleField);
    }

    /**
     * 
     * @param {*} titleField 
     */
    function ptreq_input_title_field(titleField) {
        titleField.prop('required', true);
        titleField.on('input', function () {
            var currentLength = titleField.val().length;
            if (currentLength > characterLimit) {
                var trimmedTitle = titleField.val().substring(0, characterLimit);// Trim the title to the character limit
                titleField.val(trimmedTitle);
                $('#title_limit_warning').remove();
                $('#titlewrap').append(
                    '<div id="title_limit_warning" class="notice notice-warning is-dismissible"><p>Title character limit is ' +
                    characterLimit +
                    '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
                );
                $('#title_limit_warning button').on('click', function () {
                    $('#title_limit_warning').remove();
                });
            }
        });
    }

    /**
     * quick edit field
     */
    $('.row-actions button.editinline').on('click', function () {
        $('.requird-identify').remove();
        $('.title-required .title').append('<span class="requird-identify" style="color: #f00;">*</span>');

        // check on click quick edit
        var find_input_interva = setInterval(function () {
            var titleField = $('.inline-edit-col input[name="post_title"]');
            if (titleField.length) {
                clearInterval(find_input_interva);
                titleField.prop('required', true);
                titleField.parent().parent().addClass('title-required');

                if (titleField.val().length === 0) {
                    $('.submit button.save').prop('disabled', true);
                }
            }
        }, 100);

        // check on each title input
        titleField.on('input', function () {
            var new_titleField = $('.inline-edit-col input[name="post_title"]');
            var currentLength = new_titleField.val().length;
            if (currentLength === 0) {
                $('.submit button.save').prop('disabled', true);
                $('.wp-title-check-required').remove();
                $('.title-required .input-text-wrap').append('<span class="wp-title-check-required" style="color: #f00;"> Title is required </span>');
            } else if (currentLength > characterLimit) {
                var trimmedTitle = new_titleField.val().substring(0, characterLimit);
                new_titleField.val(trimmedTitle);
                $('.submit button.save').prop('disabled', true);
                $('.wp-title-check-required').remove();
                $('.title-required .input-text-wrap').append('<span class="wp-title-check-required" style="color: #f00;"> Title character limit is ' + characterLimit + '</span>');
            } else {
                $('.submit button.save').prop('disabled', false);
                $('.wp-title-check-required').remove();
            }
        });
    });

    /**
     * END
     */
});
