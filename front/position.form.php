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

use GlpiPlugin\Positions\Menu;
use GlpiPlugin\Positions\Position;

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$pos = new Position();

if (isset($_POST["add"]) || isset($_POST["additem"])) {
   // The fields the creation forms post; the display attributes are checked by
   // Position::checkValues()
   $input = array_intersect_key($_POST, array_flip([
      'name', 'items_id', 'itemtype', 'entities_id', 'is_recursive', 'locations_id',
      'x_coordinates', 'y_coordinates',
   ]));
   $test = explode(";", (string) ($input['items_id'] ?? ''));
   if (isset($test[0]) && isset($test[1]) && !empty($test[1])) {
      $input['items_id'] = (int) $test[1];
      $input['itemtype'] = $test[0];
   }
   $pos->check(-1, CREATE, $input);
   $pos->add($input);
   Html::back();

} else if (isset($_POST["update"])) {

   if (isset($_POST["multi"])) {
      // Coordinates moved on the map: id,x,y triples, all on the location of the map
      $locations_id = (int) $_POST["locations_id"];
      $location     = new Location();
      if (!$location->can($locations_id, READ)) {
         throw new \Glpi\Exception\Http\AccessDeniedHttpException();
      }
      $data = explode(",", $_POST["multi"]);

      for ($i = 0; $i < count($data); $i = $i + 3) {
         if (isset($data[$i + 1]) && isset($data[$i + 2])) {

            $input = ['id'            => (int) $data[$i],
                      'x_coordinates' => (int) $data[$i + 1],
                      'y_coordinates' => (int) $data[$i + 2],
                      'locations_id'  => $locations_id];

            if ($input['id'] > 0) {
               $pos->check($input['id'], UPDATE);
               $pos->update($input);
            }
         }
      }

      if (isset($_POST["referrer"]) && $_POST["referrer"] > 0) {
         Html::back();
      } else {
         Html::redirect(PLUGIN_POSITIONS_WEBDIR .
                        "/front/map.php?locations_id=" . $locations_id);
      }

   } else {
      $id = (int) ($_POST['id'] ?? 0);
      if ($id > 0) {
         $pos->check($id, UPDATE);
         // The fields of the position form only: its item, location and entity stay
         $input = ['id' => $id];
         if (isset($_POST['name'])) {
            $input['name'] = (string) $_POST['name'];
         }
         foreach (['x_coordinates', 'y_coordinates'] as $field) {
            if (isset($_POST[$field])) {
               $input[$field] = (int) $_POST[$field];
            }
         }
         $test = explode(";", (string) ($_POST['items_id'] ?? ''));
         if (!$pos->fields['itemtype'] && !$pos->fields['items_id']
             && isset($test[1]) && in_array($test[0], Position::getTypes(), true)) {
            // Item chosen on a position created without one
            $input['itemtype'] = $test[0];
            $input['items_id'] = (int) $test[1];
         }
         $pos->update($input);
      }
      if (isset($_POST["referrer"]) && $_POST["referrer"] > 0) {
         Html::back();
      } else {
         Html::redirect(PLUGIN_POSITIONS_WEBDIR .
                        "/front/position.form.php?id=" . $id);
      }
   }

} else if (isset($_POST["delete"])) {
   $pos->check($_POST['id'], DELETE);
   $pos->delete($_POST);
   $pos->redirectToList();

} else if (isset($_POST["restore"])) {
   $pos->check((int) $_POST['id'], DELETE);
   $pos->restore($_POST);
   $pos->redirectToList();

} else if (isset($_POST["purge"])) {
   $pos->check((int) $_POST['id'], DELETE);
   $pos->delete($_POST, 1);
   $pos->redirectToList();

   //from items
} else if (isset($_POST["delete_item"])) {
   // A removal: the Delete right of the profile, the only one it offers (no Purge in its matrix)
   $pos->check((int) $_POST['id'], DELETE);
   $pos->delete(['id' => (int) $_POST['id']], 1);
   Html::back();

   //from coordinates ou map
} else if (isset($_POST["deletepos"])) {
   $pos->check((int) $_POST['id'], DELETE);
   $pos->delete(['id' => (int) $_POST['id']], 1);
   Html::redirect(PLUGIN_POSITIONS_WEBDIR .
                  "/front/map.php?locations_id=" . (int) $_POST["locations_id"]);

} else if (isset($_POST["addLocation"])) {
   $pos->checkGlobal(READ);
   Html::header(Position::getTypeName(), '', "tools", Menu::class, "positions");
   $map     = Position::getDocument((int) $_POST["locations_id"]);
   $options = ["document_id"  => $map,
               "locations_id" => (int) $_POST["locations_id"]];
    Position::showMapCreateLocation($options);
   Html::footer();

} else {
   $pos->checkGlobal(READ);
   Html::header(Position::getTypeName(), '', "tools", Menu::class, "positions");
   $pos->display($_GET);
   Html::footer();
}
