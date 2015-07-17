<?php

/*
 * @version $Id: HEADER 15930 2013-02-07 09:47:55Z tsmr $
  -------------------------------------------------------------------------
  Positions plugin for GLPI
  Copyright (C) 2003-2011 by the Positions Development Team.

  https://forge.indepnet.net/projects/positions
  -------------------------------------------------------------------------

  LICENSE

  This file is part of Positions.

  Positions is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  Positions is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Positions. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

include ('../../../inc/includes.php');

$img = new PluginPositionsImageItem();

if (isset($_POST["add"]) && isset($_POST['type'])) {
   $test = explode(";", $_POST['type']);
   if (isset($test[0]) && isset($test[1]) && !empty($test[1])) {
      $_POST['type']     = $test[1];
      $_POST['itemtype'] = $test[0];

      if ($img->canCreate()) {
         if (!empty($_POST["img"])) {
            $img->addItemImage($_POST);
         } else {
            Session::addMessageAfterRedirect(__('No picture uploaded', 'positions'), false, ERROR);
         }
      }
   }
   Html::back();
   
} else if (isset($_POST["delete"])) {
   $img->getFromDB($_POST["id"], -1);
   foreach ($_POST["item"] as $key => $val) {
      if ($val == 1) {
         $img->delete(array('id' => $key));
      }
   }
   Html::back();
   
} else {
   Html::header(PluginPositionsPosition::getTypeName(), '', "tools", "pluginpositionsmenu", "config");
   $img->showConfigForm();
   Html::footer();
}
?>