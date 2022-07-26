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

include ('../../../inc/includes.php');

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_GET["users_id"])) {

   //si plugin ressource active
   if (Plugin::isPluginActive("resources")) {
      //recherche de la ressource lie a ce user
      $condition = ["items_id" => $_GET["users_id"],
                    "itemtype" => 'User'];
      $dbu = new DbUtils();
      $infos = $dbu->getAllDataFromTable('glpi_plugin_resources_resources_items', $condition);
      if (!empty($infos)) {
         foreach ($infos as $info) {
            $ressource     = new PluginResourcesResource();
            $ressource->getFromDB($info['plugin_resources_resources_id']);

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
   if (!isset($_POST["locations_id"])) {
      $_POST["locations_id"] = $_GET["locations_id"];
   }
   if (!isset($_POST["download"])) {
      $_POST["download"] = $_GET["download"];
   }

   $types = PluginPositionsPosition::getTypes();
   if (!isset($_POST["itemtype"])) {
      $_POST["itemtype"] = $types;
   }

   $locations_id  = $_POST["locations_id"];
   $id            = $_GET["positions_id"];
   $itemtype      = $_POST['itemtype'];
   $menuoff       = 1;
   $download      = $_POST['download'];
}

if (isset($_GET['from_treeview']) && Plugin::isPluginActive("treeview")) {
   Html::header(PluginPositionsPosition::getTypeName(), '', "tools", "pluginpositionsmenu", "positions");
} else {
   //TODO
   //Use modal
   Html::popHeader(PluginPositionsPosition::getTypeName(), $_SERVER['PHP_SELF']);
}

if (isset($locations_id) && !empty($locations_id)) {
   $target        = $_SERVER['PHP_SELF']."?id=".$id;

   $options = ['id'           => $id,
                    'locations_id' => $locations_id,
                    'itemtype'     => $itemtype,
                    'target'       => $target,
                    'menuoff'      => $menuoff,
                    'download'     => $download];

   PluginPositionsPosition::showMap($options);

} else {
   echo "<div class='center'><br><br>";
   echo "<div class='alert alert-important alert-warning d-flex'>";
   echo "<b'>" . __('The selected object is not located on a map', 'positions') . "</b></div>";
}

if (isset($_GET['from_treeview']) && Plugin::isPluginActive("treeview")) {
   Html::footer();
} else {
   Html::popFooter();
}
