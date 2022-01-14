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
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

// Make a select box
if (isset($_POST["type"]) && $_POST['action']) {
   $dbu = new DbUtils();
   if ($_POST['action'] == 'showType') {
      $item = $dbu->getItemForItemtype($_POST['type']."Type");
   } else {
      $item = $dbu->getItemForItemtype($_POST['type']);
   }

   $itemtype = $_POST['type'];
   $table    = $item->getTable();

   if (!empty($table)) {
      // Link to user for search only > normal users
      $rand = mt_rand();

      $params = ['searchText'   => '__VALUE__',
                      'itemtype'     => $itemtype,
                      'table'        => $table,
                      'rand'         => $rand,
                      'emptylabel'   => true,
                      'valuename'    => Dropdown::EMPTY_VALUE,
                      'value2'       => isset($_POST['value']) ? $_POST['value'] : 0,
                      'name'         => $_POST["myname"],
                      'width'        => 200,
                      'locations_id' => $_POST["locations_id"]];

      if (isset($_POST['value'])) {
         $params['value'] = $_POST['value'];
      }
      if (isset($_POST['entity_restrict'])) {
         $params['entity_restrict'] = $_POST['entity_restrict'];
      }

      $field_id = Html::cleanId("dropdown_".$params['name'].$params['rand']);

      echo Html::jsAjaxDropdown($_POST["myname"], $field_id,
                                PLUGIN_POSITIONS_WEBDIR."/ajax/dropdownValue.php",
                                $params);

      if (isset($_POST['value']) && $_POST['value'] > 0) {
         $params['searchText'] = $CFG_GLPI["ajax_wildcard"];
         echo "<script type='text/javascript' >\n";
         echo "document.getElementById('search_$rand').value='".$CFG_GLPI["ajax_wildcard"]."';";
         echo "</script>\n";
         Ajax::updateItem("results_$rand", PLUGIN_POSITIONS_WEBDIR.
                 "/ajax/dropdownValue.php", $params);
      }
   }
}
