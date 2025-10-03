/* global jQuery, ajaxurl */
(function (window, document, $) {
    'use strict';

    var settings = window.snippetPressLibrary || {};
    var ajaxUrl = settings.ajaxUrl || window.ajaxurl || '';
    var nonce = settings.nonce || '';

    if (!ajaxUrl || !nonce) {
        return;
    }

    var strings = settings.strings || {};
    var loadingText = strings.loading || 'Creating...';
    var errorText = strings.error || 'Something went wrong. Please try again.';

    function toggleLoading($button, isLoading) {
        if (isLoading) {
            if (!$button.data('originalText')) {
                $button.data('originalText', $button.text());
            }

            $button.text(loadingText);
            $button.prop('disabled', true);
            $button.addClass('sp-is-loading');
            return;
        }

        var original = $button.data('originalText');
        if (original) {
            $button.text(original);
        }

        $button.prop('disabled', false);
        $button.removeClass('sp-is-loading');
    }

    function showError(message) {
        window.alert(message || errorText);
    }

    $(document).on('click', '.sp-template-card .sp-use-snippet', function (event) {
        event.preventDefault();

        var $button = $(this);

        if ($button.hasClass('sp-is-loading')) {
            return;
        }

        var $card = $button.closest('.sp-template-card');

        if (!$card.length) {
            return;
        }

        var slug = $card.data('snippetSlug');

        if (!slug) {
            showError(errorText);
            return;
        }

        toggleLoading($button, true);

        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'sp_use_library_snippet',
                nonce: nonce,
                slug: slug
            }
        }).done(function (response) {
            if (response && response.success && response.data && response.data.redirect) {
                window.location.href = response.data.redirect;
                return;
            }

            toggleLoading($button, false);

            var message = response && response.data && response.data.message ? response.data.message : errorText;
            showError(message);
        }).fail(function () {
            toggleLoading($button, false);
            showError(errorText);
        });
    });
})(window, document, jQuery);