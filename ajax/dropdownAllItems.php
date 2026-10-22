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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Positions\Position;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkRight('plugin_positions', UPDATE);

// Dropdown of the items of a type (ImageItem::getAllItemsDropdown()): every posted value ends
// in the widget or its script, so it is checked first
if (isset($_POST["type"]) && !empty($_POST['action'])) {
   $itemtype = (string) $_POST['type'];
   if (!in_array($itemtype, Position::getTypes(), true)) {
      throw new BadRequestHttpException();
   }
   // The two forms of the plugin which use this dropdown, and their field
   $action = (string) $_POST['action'];
   $myname = (string) ($_POST["myname"] ?? '');
   if (!in_array($action, ['showType', 'showItem'], true)
       || !in_array($myname, ['type', 'items_id'], true)) {
      throw new BadRequestHttpException();
   }

   $item = getItemForItemtype($action == 'showType' ? $itemtype . "Type" : $itemtype);
   if (!($item instanceof CommonDBTM)) {
      throw new BadRequestHttpException();
   }

   $value        = (int) ($_POST['value'] ?? 0);
   $locations_id = (int) ($_POST["locations_id"] ?? -1);

   $params = ['searchText'   => '__VALUE__',
              'itemtype'     => $itemtype,
              'table'        => $item->getTable(),
              'rand'         => mt_rand(),
              'emptylabel'   => true,
              'valuename'    => Dropdown::EMPTY_VALUE,
              'value2'       => $value,
              'value'        => $value,
              'name'         => $myname,
              'width'        => 200,
              'locations_id' => $locations_id];

   // Entities: integers, narrowed to the active ones by ajax/dropdownValue.php
   if (isset($_POST['entity_restrict'])) {
      $params['entity_restrict'] = is_array($_POST['entity_restrict'])
         ? array_map('intval', $_POST['entity_restrict'])
         : (int) $_POST['entity_restrict'];
   }

   $field_id = Html::cleanId("dropdown_" . $params['name'] . $params['rand']);

   echo Html::jsAjaxDropdown(
      $myname,
      $field_id,
      PLUGIN_POSITIONS_WEBDIR . "/ajax/dropdownValue.php",
      $params
   );
}
