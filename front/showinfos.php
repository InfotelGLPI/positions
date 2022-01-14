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

include('../../../inc/includes.php');

if (!isset($_GET["file"])) {
   $_GET["file"] = "";
   $image        = $_GET['img'];
} else {
   $image = $_GET['file'];
}

$items_id = $_GET['items_id'];
$name     = $_GET['name'];
$itemtype = $_GET['itemtype'];
$idpos    = $_GET['id'];

$pos = new PluginPositionsPosition();

if ($itemtype == 'Location') {
   PluginPositionsPosition::showGeolocLocation($items_id);
} else {

   $detail   = new PluginPositionsInfo();
   $restrict = "`is_active` = 1 ";
   $pos->getFromDB($idpos);
   $dbu = new DbUtils();

   $restrict = ["is_active"  => 1,
                "is_deleted" => 0] + $dbu->getEntitiesRestrictCriteria("glpi_plugin_positions_infos",
                                                                       '', '', $pos->maybeRecursive());
   $infos    = $dbu->getAllDataFromTable('glpi_plugin_positions_infos', $restrict);

   $item = new $itemtype();
   $item->getFromDB($items_id);

   PluginPositionsPosition::showOverlay($image, $item, $infos);
}
