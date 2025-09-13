jQuery(function ($) {
    // Set your desired character limit
    let characterLimit = 100;
    try {
        if (typeof ptreqAjax !== 'undefined' && ptreqAjax.ptreq_character_limit) {
            characterLimit = ptreqAjax.ptreq_character_limit;
        }
    } catch (error) {
        console.warn("ptreqAjax not found, using default limit:", characterLimit);
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
     * Bind validation to the post title input field.
     *
     * @param {jQuery} titleField - jQuery object for the post title input.
     */
    function ptreq_input_title_field(titleField) {
        titleField.prop('required', true);
        ptreq_checkTitleOnWithoutEditorPost(titleField);

        titleField.on('input', function () {
            ptreq_checkTitleOnWithoutEditorPost(titleField);
        });
        $('#ptreq_title_limit_warning button').on('click', function () {
            $('#ptreq_title_limit_warning').remove();
        });
    }

    /**
     * Quick Edit - Add validation when inline edit is opened.
     */
    $('.row-actions button.editinline').on('click', function () {
        // check on click quick edit
        const intervalId = setInterval(function () {
            let inlineTitle = $('.inline-edit-col input[name="post_title"]');
            if (inlineTitle.length) {
                clearInterval(intervalId);

                inlineTitle.prop('required', true);

                ptreq_checkTitleOnQuickEdit(inlineTitle);
                // check on each title input
                inlineTitle.on('input', function () {
                    var new_inlineTitle = $('.inline-edit-col input[name="post_title"]');
                    ptreq_checkTitleOnQuickEdit(new_inlineTitle);
                });
            }
        }, 100);
    });

    /**
      * Validate title field on post editor screen.
      *
      * @param {jQuery} titleField - Post title input field.
      */
    async function ptreq_checkTitleOnWithoutEditorPost(titleField) {
        const currentLength = await getVisibleTextLengthWithIgnore(titleField.val());
        const publishBtn = $('#publishing-action #publish');
        const warningBox = $('#ptreq_title_limit_warning');

        warningBox.remove();

        if (!currentLength) {
            publishBtn.prop('disabled', true);
            $('#titlewrap').append(ptreq_getWarningHtml('Title is required.'));
        } else if (currentLength > characterLimit) {
            publishBtn.prop('disabled', true);
            $('#titlewrap').append(ptreq_getWarningHtml('Title character limit is ' + characterLimit));
        } else {
            publishBtn.prop('disabled', false);
        }
    }

    /**
    * Validate title field in Quick Edit mode.
    *
    * @param {jQuery} titleField - Quick Edit title input field.
    */
    async function ptreq_checkTitleOnQuickEdit(titleField) {
        const currentLength = await getVisibleTextLengthWithIgnore(titleField.val());
        const saveBtn = $('.submit button.save');
        const wrapper = titleField.closest('.input-text-wrap');

        $('.ptreq-title-check-required').remove();


        if (!currentLength) {
            saveBtn.prop('disabled', true);
            wrapper.append('<span class="ptreq-title-check-required" style="color: #f00;"> Title is required </span>');

        } else if (currentLength > characterLimit) {
            saveBtn.prop('disabled', true);
            wrapper.append('<span class="ptreq-title-check-required" style="color: #f00;"> Title character limit is ' + characterLimit + '</span>');
        } else {
            saveBtn.prop('disabled', false);
        }
    }

    /**
    * Make an AJAX call to get visible title length after ignoring invisible chars.
    *
    * @param {string} title - Title string.
    * @returns {Promise<number>} The visible length of the title.
    */
    async function getVisibleTextLengthWithIgnore(title) {
        try {
            let response = await $.ajax({
                url: ptreqAjax.ajax_url,
                type: "POST",
                data: {
                    action: ptreqAjax.action_name,
                    _nonce: ptreqAjax.nonce,
                    ptrq_title: title
                },
            });
            console.log(response);
            response = JSON.parse(response);
            if (response.status) {
                return response.length;
            } else {
                console.error("Failed to get visible length");
                return 0;
            }
        } catch (error) {
            console.error("AJAX Error:", error.responseText || error);
            return 0;
        }
    }

    /**
    * Generate warning HTML.
    *
    * @param {string} message - Warning message.
    * @returns {string} HTML for warning box.
    */
    function ptreq_getWarningHtml(message) {
        return `
            <div id="ptreq_title_limit_warning" class="notice notice-warning is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>`;
    }


    /**
     * END
     */
});
