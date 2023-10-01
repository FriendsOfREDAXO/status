jQuery(document).ready(function($) {
    const $dirSizeElements = $('.dir-size');

    $dirSizeElements.each(function() {
        const $element = $(this);

        $.ajax({
            url: rex.status_ajax_url,
            data: {
                path: $element.data('path')
            },
            success: function(data) {
                $element.html(data['mb']);
            },
            error: function(error) {
                $element.parents('tr').remove();
                console.error(error);
            }
        });
    });
});