/**
 * -------------------------------------------------------------------------
 * positions plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * Map of a location (templates/map.html.twig): the plan of the location as background and the
 * positions of the items as markers, drawn with Leaflet (shipped by GLPI, CRS.Simple: plain
 * pixel coordinates).
 *
 * The data come from the data-positions-map attribute (Position::getMapData()): every text is
 * set through the DOM (textContent), never as HTML.
 *
 * - a click on a marker opens the information window of the item (front/showinfos.php) in a
 *   GLPI modal;
 * - when editable, a marker is dragged to move the item; the coordinates of every marker are
 *   kept in the "multi" field of the pointform form, saved by its "Save the coordinates" button;
 * - when editable, a right click opens a menu: edit the display attributes (saved by
 *   ajax/position.php) or delete the position (front/position.form.php).
 */

/* global _, L, bootstrap, glpi_ajax_dialog, getAjaxCsrfToken */

(function () {
    'use strict';

    // The old canvas (canvasXpress) drew the plan centered on (250, 250) and stored the center of
    // each item: the coordinates keep that reference
    const ORIGIN_X = 250;
    const ORIGIN_Y = 250;

    const csrfToken = function () {
        if (typeof getAjaxCsrfToken === 'function') {
            return getAjaxCsrfToken();
        }
        const meta = document.querySelector('meta[property="glpi:csrf_token"]');
        return meta !== null ? meta.getAttribute('content') : '';
    };

    const post = function (url, data) {
        const body = new URLSearchParams(data);
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': csrfToken(),
            },
            body: body,
        });
    };

    // Leaflet CRS.Simple: lat = -y, lng = x
    const toLatLng = function (x, y) {
        return L.latLng(-y, x);
    };

    const fromLatLng = function (latlng) {
        return {x: Math.round(latlng.lng), y: Math.round(-latlng.lat)};
    };

    // Tooltip of a marker: plain text, never HTML
    const bindTooltip = function (marker, node) {
        marker.unbindTooltip();
        const text = node.tooltip || node.name;
        if (!node.hideTooltip && text) {
            const content = document.createElement('span');
            content.textContent = text;
            marker.bindTooltip(content);
        }
    };

    const buildIcon = function (node) {
        const wrapper = document.createElement('div');
        wrapper.className = 'positions-marker' + (node.highlighted ? ' positions-marker-highlighted' : '');

        const img = document.createElement('img');
        img.src = node.img;
        img.alt = '';
        img.width = node.width;
        img.height = node.height;
        if (node.rotate) {
            img.style.transform = 'rotate(' + Number(node.rotate) + 'deg)';
        }
        if (node.outline && node.outlineWidth) {
            img.style.outline = Number(node.outlineWidth) + 'px solid ' + node.outline;
        }
        wrapper.append(img);

        if (!node.hideLabel && node.name) {
            const label = document.createElement('div');
            label.className = 'positions-marker-label';
            label.textContent = node.name;
            if (node.color) {
                label.style.color = node.color;
            }
            if (node.labelSize) {
                label.style.fontSize = (Number(node.labelSize) * 100) + '%';
            }
            wrapper.append(label);
        }

        return L.divIcon({
            html: wrapper,
            className: 'positions-marker-icon',
            iconSize: [node.width, node.height],
            iconAnchor: [node.width / 2, node.height / 2],
        });
    };

    const init = function (container) {
        let data;
        try {
            data = JSON.parse(container.dataset.positionsMap);
        } catch {
            return;
        }

        const plan = data.plan;
        const map = L.map(container, {
            crs: L.CRS.Simple,
            minZoom: -3,
            maxZoom: 3,
            zoomSnap: 0.25,
            attributionControl: false,
        });
        // The plan, centered on the origin of the old canvas
        const bounds = L.latLngBounds(
            L.latLng(-(ORIGIN_Y + plan.height / 2), ORIGIN_X - plan.width / 2),
            L.latLng(-(ORIGIN_Y - plan.height / 2), ORIGIN_X + plan.width / 2)
        );
        L.imageOverlay(plan.url, bounds).addTo(map);
        map.fitBounds(bounds);

        const form = document.getElementById('pointform');
        const markers = new Map();

        // Coordinates of every marker for the "Save the coordinates" button: id,x,y triples
        const updateMulti = function () {
            if (form === null || form.elements.multi === undefined) {
                return;
            }
            const values = [];
            markers.forEach(function (marker, id) {
                const point = fromLatLng(marker.getLatLng());
                values.push(id, point.x, point.y);
            });
            form.elements.multi.value = values.join(',');
        };

        const openInfo = function (node) {
            if (node.itemtype === 'Location') {
                // A sub-location: its own map
                window.location.href = data.urls.map + '?locations_id=' + encodeURIComponent(node.items_id);
                return;
            }
            glpi_ajax_dialog({
                url: data.urls.info,
                method: 'get',
                // The title is put in the modal as HTML
                title: _.escape(data.labels.information + ' - ' + node.name),
                params: {
                    items_id: node.items_id,
                    id: node.id,
                    itemtype: node.itemtype,
                    img: node.img,
                },
                dialogclass: 'modal-lg',
            });
        };

        const deletePosition = function (node) {
            if (!window.confirm(data.labels.confirmDelete)) {
                return;
            }
            post(data.urls.form, {
                deletepos: 1,
                id: node.id,
                locations_id: data.locations_id,
            }).then(function (response) {
                if (!response.ok) {
                    return;
                }
                const marker = markers.get(node.id);
                if (marker !== undefined) {
                    marker.remove();
                    markers.delete(node.id);
                    updateMulti();
                }
            });
        };

        const editPosition = function (node, marker) {
            const dialog = document.getElementById('positions-edit-dialog');
            if (dialog === null) {
                return;
            }
            const fields = dialog.querySelector('form');
            fields.elements.tooltip.value = node.tooltip || '';
            fields.elements.color.value = /^#[0-9a-f]{6}$/i.test(node.color || '') ? node.color : '#1d222b';
            fields.elements.width.value = node.width;
            fields.elements.height.value = node.height;
            fields.elements.rotate.value = node.rotate || 0;
            fields.elements.labelSize.value = node.labelSize || 1;
            fields.elements.hideLabel.checked = !!node.hideLabel;
            fields.elements.hideTooltip.checked = !!node.hideTooltip;

            fields.onsubmit = function (event) {
                event.preventDefault();
                const config = {
                    id: node.id,
                    tooltip: fields.elements.tooltip.value,
                    color: fields.elements.color.value,
                    width: parseInt(fields.elements.width.value, 10) || node.width,
                    height: parseInt(fields.elements.height.value, 10) || node.height,
                    rotate: parseInt(fields.elements.rotate.value, 10) || 0,
                    labelSize: parseFloat(fields.elements.labelSize.value) || 1,
                    hideLabel: fields.elements.hideLabel.checked ? 1 : 0,
                    hideTooltip: fields.elements.hideTooltip.checked ? 1 : 0,
                };
                post(data.urls.ajax, {action: 'setConfig', config: JSON.stringify(config)})
                    .then(function (response) {
                        if (!response.ok) {
                            return;
                        }
                        Object.assign(node, config);
                        marker.setIcon(buildIcon(node));
                        bindTooltip(marker, node);
                        bootstrap.Modal.getOrCreateInstance(dialog).hide();
                    });
            };
            bootstrap.Modal.getOrCreateInstance(dialog).show();
        };

        const contextMenu = function (node, marker, event) {
            const menu = document.getElementById('positions-context-menu');
            if (menu === null) {
                return;
            }
            menu.replaceChildren();
            const entries = [
                [data.labels.information, function () { openInfo(node); }],
                [data.labels.edit, function () { editPosition(node, marker); }],
                [data.labels.delete, function () { deletePosition(node); }],
            ];
            entries.forEach(function (entry) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'dropdown-item';
                button.textContent = entry[0];
                button.addEventListener('click', function () {
                    menu.classList.remove('show');
                    entry[1]();
                });
                menu.append(button);
            });
            const point = event.containerPoint;
            menu.style.left = point.x + 'px';
            menu.style.top = point.y + 'px';
            menu.classList.add('show');
        };

        data.nodes.forEach(function (node) {
            const marker = L.marker(toLatLng(node.x, node.y), {
                icon: buildIcon(node),
                draggable: data.editable,
                keyboard: false,
            });
            marker.positionNode = node;
            bindTooltip(marker, node);
            marker.on('click', function () {
                openInfo(node);
            });
            if (data.editable) {
                marker.on('dragend', updateMulti);
                marker.on('contextmenu', function (event) {
                    L.DomEvent.preventDefault(event.originalEvent);
                    contextMenu(node, marker, event);
                });
            }
            marker.addTo(map);
            markers.set(node.id, marker);

            if (node.highlighted) {
                map.setView(marker.getLatLng(), Math.max(map.getZoom(), 0));
            }
        });

        map.on('click movestart', function () {
            const menu = document.getElementById('positions-context-menu');
            if (menu !== null) {
                menu.classList.remove('show');
            }
        });

        updateMulti();
    };

    const start = function () {
        document.querySelectorAll('[data-positions-map]').forEach(init);
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
