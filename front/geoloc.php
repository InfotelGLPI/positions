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

use GlpiPlugin\Positions\Position;
use GlpiPlugin\Positions\Menu;
use GlpiPlugin\Resources\Resource;

Session::checkRight('plugin_positions', READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_GET["users_id"])) {
   // Where a person sits: only for who may read that user
   $users_id = (int) $_GET["users_id"];
   $user     = new User();
   if (!$user->can($users_id, READ)) {
      throw new \Glpi\Exception\Http\AccessDeniedHttpException();
   }

   // Resources plugin enabled
   if (Plugin::isPluginActive("resources")) {
      // resource linked to the user
      $condition = ["items_id" => $users_id,
                    "itemtype" => User::class];
      $dbu = new DbUtils();
      $infos = $dbu->getAllDataFromTable('glpi_plugin_resources_resources_items', $condition);
      if (!empty($infos)) {
         foreach ($infos as $info) {
            $ressource     = new Resource();
            // ... and its resource
            if (!$ressource->can((int) $info['plugin_resources_resources_id'], READ)) {
               continue;
            }

            $restrict = ["items_id"    => $ressource->fields['id'],
                         "is_deleted"  => 0,
                         "entities_id" => $ressource->fields['entities_id'],
                         "itemtype"    => $ressource->getType()];
            $datas    = $dbu->getAllDataFromTable('glpi_plugin_positions_positions', $restrict);
            if (!empty($datas)) {
               foreach ($datas as $data) {
                  if (isset($data['id'])) {
                     if (isset($ressource->fields['locations_id'])
                                 && ($ressource->fields['locations_id']>0)) {
                        $id            = $data['id'];
                        $locations_id  = $ressource->fields['locations_id'];
                        $itemtype      = 'User';
                        $menuoff       = 1;
                        $download      = 1;
                     }
                  }
               }
            }
         }
      }
   }

} else {
   $types = Position::getTypes();
   if (!isset($_POST["itemtype"])) {
      $_POST["itemtype"] = $types;
   }

   $locations_id  = (int) ($_POST["locations_id"] ?? $_GET["locations_id"] ?? 0);
   $id            = (int) ($_GET["positions_id"] ?? 0);
   $itemtype      = $_POST['itemtype'];
   $menuoff       = 1;
   $download      = (int) ($_POST["download"] ?? $_GET["download"] ?? 0);
}

if (isset($_GET['from_treeview']) && Plugin::isPluginActive("treeview")) {
   Html::header(Position::getTypeName(), '', "tools", Menu::class, "positions");
} else {
   //TODO
   //Use modal
   Html::popHeader(Position::getTypeName(), PLUGIN_POSITIONS_WEBDIR . '/front/geoloc.php');
}

if (isset($locations_id) && !empty($locations_id)) {
   $target        = PLUGIN_POSITIONS_WEBDIR . "/front/geoloc.php?id=" . (int) $id;

   $options = ['id'           => $id,
                    'locations_id' => $locations_id,
                    'itemtype'     => $itemtype,
                    'target'       => $target,
                    'menuoff'      => $menuoff,
                    'download'     => $download];

    Position::showMap($options);

} else {
   echo "<div class='center'><br><br>";
   echo "<div class='alert alert-important alert-warning d-flex'>";
   echo "<b>" . __('The selected object is not located on a map', 'positions') . "</b></div>";
}

if (isset($_GET['from_treeview']) && Plugin::isPluginActive("treeview")) {
   Html::footer();
} else {
   Html::popFooter();
}
