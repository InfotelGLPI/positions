// Preview of the picture chosen for an equipment type (ImageItem::showConfigForm()). The element
// is built through the DOM, the file name encoded in the URL: never concatenated into HTML.
// The dropdown is a select2, whose change event is only seen by jQuery listeners.
jQuery(document).on('change', 'form[name="imageitemform"] select[name="img"]', function () {
    const preview = document.getElementById('imageitemPreview');
    if (preview === null) {
        return;
    }
    preview.replaceChildren();

    const filename = this.value;
    if (!filename || filename === '-1') {
        return;
    }
    const url = 'map.send.php?file=' + encodeURIComponent(filename);
    const object = document.createElement('object');
    object.width = 60;
    object.height = 60;
    object.data = url;
    const param = document.createElement('param');
    param.name = 'src';
    param.value = url;
    object.append(param);
    preview.append(object);
});
