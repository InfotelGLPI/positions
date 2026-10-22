// Creation of a sub-area cropped from the map of a location (templates/crop_form.html.twig).
// Jcrop draws the selection on the map; its coordinates fill the hidden fields of the form.
jQuery(function ($) {
    const form = $('#positions_crop_form');
    if (form.length === 0) {
        return;
    }

    const setCoords = function (c) {
        $('#x1').val(c.x);
        $('#y1').val(c.y);
        $('#x2').val(c.x2);
        $('#y2').val(c.y2);
        $('#w').val(c.w);
        $('#h').val(c.h);
    };
    const clearCoords = function () {
        form.find('.positions-crop-coords').val('');
    };

    $('#target').Jcrop({
        onChange: setCoords,
        onSelect: setCoords,
        onRelease: clearCoords
    });

    // An existing location or a new one: only the field of the chosen option is shown
    const showChoice = function () {
        const choice = form.find('input[name="test"]:checked').val();
        form.find('[data-positions-choice]').each(function () {
            $(this).toggleClass('d-none', $(this).data('positions-choice') !== choice);
        });
    };
    form.on('change', 'input[name="test"]', showChoice);
    showChoice();

    // An area must be selected before the form is sent
    form.on('submit', function (event) {
        if (parseInt($('#w').val(), 10) > 0) {
            return;
        }
        event.preventDefault();
        alert(form.data('empty-area-message'));
    });
});
