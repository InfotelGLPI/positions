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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$pos = new PluginPositionsPosition();

if (isset($_POST["add"])) {
   $test = explode(";", $_POST['items_id']);
   if (isset($test[0]) && isset($test[1]) && !empty($test[1])) {
      $_POST['items_id'] = $test[1];
      $_POST['itemtype'] = $test[0];
      $pos->check(-1, CREATE, $_POST);
      $pos->add($_POST);
   } else {
      $pos->check(-1, CREATE, $_POST);
      $pos->add($_POST);
   }
   Html::back();

} else if (isset($_POST["additem"])) {
   $pos->check(-1, UPDATE, $_POST);
   $pos->add($_POST);
   Html::back();

} else if (isset($_POST["update"])) {

   if (isset($_POST["multi"])) {
      $data = explode(",", $_POST["multi"]);

      for ($i = 0; $i < count($data); $i = $i + 3) {
         if (isset($data[$i + 1]) && isset($data[$i + 2])) {

            $input = ['id'            => $data[$i],
                      'x_coordinates' => $data[$i + 1],
                      'y_coordinates' => $data[$i + 2],
                      'locations_id'  => $_POST["locations_id"]];

            if ($input['id'] > 0) {
//               $pos->check($input['id'], UPDATE);
               $pos->update($input);
            }
         }
      }

      if (isset($_POST["referrer"]) && $_POST["referrer"] > 0) {
         Html::back();
      } else {
         Html::redirect(PLUGIN_POSITIONS_WEBDIR .
                        "/front/map.php?locations_id=" . $_POST["locations_id"]);
      }

   } else {
      if ($_POST['id'] > 0) {
//         $pos->check($_POST['id'], UPDATE);
         $pos->update($_POST);
      }
      if (isset($_POST["referrer"]) && $_POST["referrer"] > 0) {
         Html::back();
      } else {
         Html::redirect(PLUGIN_POSITIONS_WEBDIR .
                        "/front/position.form.php?id=" . $_POST['id']);
      }
   }

} else if (isset($_POST["delete"])) {
   $pos->check($_POST['id'], DELETE);
   $pos->delete($_POST);
   $pos->redirectToList();

} else if (isset($_POST["restore"])) {
   $pos->check($_POST['id'], PURGE);
   $pos->restore($_POST);
   $pos->redirectToList();

} else if (isset($_POST["purge"])) {
   $pos->check($_POST['id'], PURGE);
   $pos->delete($_POST, 1);
   $pos->redirectToList();

   //from items
} else if (isset($_POST["delete_item"])) {
   $pos->check($_POST['id'], UPDATE);
   $pos->delete($_POST, 1);
   Html::back();

   //from coordinates ou map
} else if (isset($_POST["deletepos"])) {
   $pos->check($_POST['id'], UPDATE);
   $pos->delete($_POST, 1);
   Html::redirect(PLUGIN_POSITIONS_WEBDIR .
                  "/front/map.php?locations_id=" . $_POST["locations_id"]);

} else if (isset($_POST["addLocation"])) {
   $pos->checkGlobal(READ);
   Html::header(PluginPositionsPosition::getTypeName(), '', "tools", "pluginpositionsmenu", "positions");
   $map     = PluginPositionsPosition::getDocument($_POST["locations_id"]);
   $options = ["document_id"  => $map,
               "locations_id" => $_POST["locations_id"]];
   PluginPositionsPosition::showMapCreateLocation($options);
   Html::footer();

} else {
   $pos->checkGlobal(READ);
   Html::header(PluginPositionsPosition::getTypeName(), '', "tools", "pluginpositionsmenu", "positions");
   $pos->display($_GET);
   Html::footer();
}
