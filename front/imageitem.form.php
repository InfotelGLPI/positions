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

use GlpiPlugin\Positions\ImageItem;
use GlpiPlugin\Positions\Position;
use GlpiPlugin\Positions\Menu;

$img = new ImageItem();

if (isset($_POST["add"]) || isset($_POST["delete"])) {
   // The pictures of the equipment types have no entity: they apply to every map, so changing
   // them is a setting of the plugin, under the same right as its configuration
   Session::checkRight('config', UPDATE);
}

if (isset($_POST["add"]) && isset($_POST['type'])) {
   $test = explode(";", $_POST['type']);
   // The item type names the table of its types (addItemImage()): the types of the plugin only
   if (isset($test[0]) && isset($test[1]) && !empty($test[1])
       && in_array($test[0], Position::getTypes(), true)) {
      $_POST['type']     = (int) $test[1];
      $_POST['itemtype'] = $test[0];

      // A picture of the pics directory only, or -1 when none was chosen
      if ($_POST["img"] != -1 && Position::getPicturePath((string) $_POST["img"]) !== null) {
         $img->addItemImage($_POST);
      } else {
         Session::addMessageAfterRedirect(__('No picture uploaded', 'positions'), false, ERROR);
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   foreach ((array) ($_POST["item"] ?? []) as $key => $val) {
      if ($val == 1) {
         $img->delete(['id' => (int) $key]);
      }
   }
   Html::back();

} else {
   $img->checkGlobal(READ);
   Html::header(Position::getTypeName(), '', "tools", Menu::class, "config");
   $img->showConfigForm();
   Html::footer();
}
