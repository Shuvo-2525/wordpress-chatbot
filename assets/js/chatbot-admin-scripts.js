jQuery(document).ready(function ($) {
    'use strict';

    // NEW: Initialize the WP Color Picker
    $('.wph-color-picker').wpColorPicker();

    // This handles the media uploader
    $(document).on('click', '.wph-image-upload-button', function (e) {
        e.preventDefault();

        var button = $(this);
        var inputField = button.prev('input[type="hidden"]');
        var previewImage = $('#' + inputField.attr('id') + '_preview');
        var placeholder = $('#' + inputField.attr('id') + '_placeholder');
        var removeButton = button.next('.wph-image-remove-button');

        var frame = wp.media({
            title: wphChatbotAdmin.uploaderTitle,
            button: {
                text: wphChatbotAdmin.uploaderButton
            },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            
            // Set the hidden input value
            inputField.val(attachment.url);

            // Set the preview image
            previewImage.attr('src', attachment.url).removeClass('hidden');
            placeholder.addClass('hidden');

            // Show the remove button
            removeButton.show();
        });

        frame.open();
    });

    // This handles the remove image button
    $(document).on('click', '.wph-image-remove-button', function (e) {
        e.preventDefault();

        var button = $(this);
        var inputField = button.prevAll('input[type="hidden"]').first();
        var previewImage = $('#' + inputField.attr('id') + '_preview');
        var placeholder = $('#' + inputField.attr('id') + '_placeholder');
        
        // Clear the hidden input
        inputField.val('');

        // Clear the preview image
        previewImage.attr('src', '').addClass('hidden');
        placeholder.removeClass('hidden');

        // Hide the remove button
        button.hide();
    });
});

