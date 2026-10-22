// Creation of a sub-area cropped from the map of a location (templates/crop_form.html.twig).
// Cropper.js (shipped by GLPI) draws the selection on the map; its coordinates, in pixels of the
// original picture, fill the hidden fields of the form.

/* global Cropper */

(function () {
    'use strict';

    const init = function () {
        const form = document.getElementById('positions_crop_form');
        const img = document.getElementById('target');
        if (form === null || img === null) {
            return;
        }

        const setField = function (name, value) {
            form.elements[name].value = value;
        };
        const clearCoords = function () {
            form.querySelectorAll('.positions-crop-coords').forEach(function (field) {
                field.value = '';
            });
        };

        // The map stays still (no move, zoom or rotation): only the selection is drawn
        const cropper = new Cropper(img, {
            template: '<cropper-canvas background>'
                + '<cropper-image></cropper-image>'
                + '<cropper-shade hidden></cropper-shade>'
                + '<cropper-handle action="select" plain></cropper-handle>'
                + '<cropper-selection initial-coverage="0" movable resizable>'
                + '<cropper-grid role="grid" bordered covered></cropper-grid>'
                + '<cropper-handle action="move" theme-color="rgba(255, 255, 255, 0.35)"></cropper-handle>'
                + '<cropper-handle action="n-resize"></cropper-handle>'
                + '<cropper-handle action="e-resize"></cropper-handle>'
                + '<cropper-handle action="s-resize"></cropper-handle>'
                + '<cropper-handle action="w-resize"></cropper-handle>'
                + '<cropper-handle action="ne-resize"></cropper-handle>'
                + '<cropper-handle action="nw-resize"></cropper-handle>'
                + '<cropper-handle action="se-resize"></cropper-handle>'
                + '<cropper-handle action="sw-resize"></cropper-handle>'
                + '</cropper-selection>'
                + '</cropper-canvas>',
        });
        const canvas = cropper.getCropperCanvas();
        const image = cropper.getCropperImage();
        const selection = cropper.getCropperSelection();
        const natural_width = parseInt(img.getAttribute('width'), 10) || 0;
        const natural_height = parseInt(img.getAttribute('height'), 10) || 0;

        // The map is shown at its own size, as on the old crop page
        canvas.style.width = natural_width + 'px';
        canvas.style.height = natural_height + 'px';
        canvas.style.maxWidth = '100%';
        image.$ready(function () {
            image.$center('contain');
        });

        // The selection is given relative to the canvas: back to pixels of the original picture
        selection.addEventListener('change', function (event) {
            const detail = event.detail;
            if (!detail || detail.width <= 0 || detail.height <= 0) {
                clearCoords();
                return;
            }
            const canvas_rect = canvas.getBoundingClientRect();
            const image_rect = image.getBoundingClientRect();
            const scale = natural_width > 0 ? image_rect.width / natural_width : 1;

            const x1 = Math.max(0, Math.round((detail.x - (image_rect.left - canvas_rect.left)) / scale));
            const y1 = Math.max(0, Math.round((detail.y - (image_rect.top - canvas_rect.top)) / scale));
            const w = Math.min(Math.round(detail.width / scale), natural_width - x1);
            const h = Math.min(Math.round(detail.height / scale), natural_height - y1);
            setField('x1', x1);
            setField('y1', y1);
            setField('x2', x1 + w);
            setField('y2', y1 + h);
            setField('w', w);
            setField('h', h);
        });

        // An existing location or a new one: only the field of the chosen option is shown
        const showChoice = function () {
            const checked = form.querySelector('input[name="test"]:checked');
            const choice = checked !== null ? checked.value : '';
            form.querySelectorAll('[data-positions-choice]').forEach(function (block) {
                block.classList.toggle('d-none', block.dataset.positionsChoice !== choice);
            });
        };
        form.querySelectorAll('input[name="test"]').forEach(function (radio) {
            radio.addEventListener('change', showChoice);
        });
        showChoice();

        // An area must be selected before the form is sent
        form.addEventListener('submit', function (event) {
            if (parseInt(form.elements.w.value, 10) > 0) {
                return;
            }
            event.preventDefault();
            window.alert(form.dataset.emptyAreaMessage);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
