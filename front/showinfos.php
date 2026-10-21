<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 positions plugin for GLPI
 Copyright (C) 2009-2022 by the positions Development Team.

 https://github.com/InfotelGLPI/positions
 -------------------------------------------------------------------------

 LICENSE

 This file is part of positions.

 positions is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 positions is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with positions. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Positions\Info;
use GlpiPlugin\Positions\Position;

Session::checkRight('plugin_positions', READ);

$image = (string) ($_GET['file'] ?? $_GET['img'] ?? '');

$items_id = (int) ($_GET['items_id'] ?? 0);
$itemtype = (string) ($_GET['itemtype'] ?? '');
$idpos = (int) ($_GET['id'] ?? 0);
// Positions saved before the namespace keep the short class name of the sockets
if ($itemtype === 'Socket') {
    $itemtype = \Glpi\Socket::class;
}

// Types a position may be linked to only, and an item the user may read
if (!in_array($itemtype, Position::getTypes(), true)) {
    throw new \Glpi\Exception\Http\BadRequestHttpException();
}
$item = getItemForItemtype($itemtype);
if (!$item || !$item->can($items_id, READ)) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}

$pos = new Position();

if ($item instanceof Location) {
    Position::showGeolocLocation($items_id);
} else {
    $pos->getFromDB($idpos);
    $dbu = new DbUtils();

    $restrict = ["is_active" => 1,
            "is_deleted" => 0] + $dbu->getEntitiesRestrictCriteria("glpi_plugin_positions_infos",
            '', '', $pos->maybeRecursive());
    $infos = $dbu->getAllDataFromTable('glpi_plugin_positions_infos', $restrict);

    Position::showOverlay($image, $item, $infos);
}
