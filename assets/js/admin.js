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

    function initLibraryFilters() {
        var $cards = $('.sp-template-card[data-snippet="true"]');

        if (!$cards.length) {
            return;
        }

        var $searchInput = $('.sp-library-search');
        var $filters = $('.sp-library-filter');
        var $emptyState = $('.sp-empty-panel--library');
        var activeFilter = 'all';
        var searchTerm = '';

        function matchesFilter($card) {
            if (!activeFilter || activeFilter === 'all') {
                return true;
            }

            if (activeFilter === 'available') {
                return ($card.data('status') === 'disabled');
            }

            if (activeFilter.indexOf('type-') === 0) {
                return ($card.data('type') || '') === activeFilter.slice(5);
            }

            if (activeFilter.indexOf('category-') === 0) {
                return ($card.data('category') || '') === activeFilter.slice(9);
            }

            if (activeFilter.indexOf('tag-') === 0) {
                var tagList = ($card.data('tags') || '').toString();
                if (!tagList) {
                    return false;
                }

                var tags = tagList.split(',');
                return tags.indexOf(activeFilter.slice(4)) !== -1;
            }

            return true;
        }

        function matchesSearch($card) {
            if (!searchTerm) {
                return true;
            }

            var haystack = ($card.data('search') || '').toString();
            return haystack.indexOf(searchTerm) !== -1;
        }

        function applyFilters() {
            var visibleCount = 0;

            $cards.each(function () {
                var $card = $(this);
                var isVisible = matchesFilter($card) && matchesSearch($card);
                $card.toggle(isVisible);

                if (isVisible) {
                    visibleCount++;
                }
            });

            if ($emptyState.length) {
                if (visibleCount === 0) {
                    $emptyState.addClass('is-visible');
                } else {
                    $emptyState.removeClass('is-visible');
                }
            }
        }

        $searchInput.on('input', function () {
            searchTerm = $.trim($(this).val()).toLowerCase();
            applyFilters();
        });

        $filters.on('click', function (event) {
            event.preventDefault();

            var $link = $(this);
            activeFilter = $link.data('filter') || 'all';

            $filters.removeClass('is-active');
            $link.addClass('is-active');

            applyFilters();
        });

        var $initialFilter = $filters.filter('.is-active').first();
        if ($initialFilter.length) {
            activeFilter = $initialFilter.data('filter') || 'all';
        }

        if ($searchInput.length) {
            searchTerm = $.trim($searchInput.val()).toLowerCase();
        }

        applyFilters();
    }

    $(initLibraryFilters);
})(window, document, jQuery);

