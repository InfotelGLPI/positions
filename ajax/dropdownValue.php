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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "dropdownValue.php")) {
   include ('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (empty($_GET) && !empty($_POST)) {
   $_GET = $_POST;
}
// Security
if (!$DB->tableExists($_GET['table'])) {
   exit();
}

$item = new $_GET['itemtype']();

if (isset($_GET["entity_restrict"])
    && !is_numeric($_GET["entity_restrict"])
    && !is_array($_GET["entity_restrict"])) {

   $_GET["entity_restrict"] = Toolbox::decodeArrayFromInput($_GET["entity_restrict"]);
}

$NBMAX = $CFG_GLPI["dropdown_max"];
$LIMIT = "LIMIT 0,$NBMAX";
if ($_GET['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT = "";
}

$where =" WHERE `".$_GET['table']."`.`id` NOT IN ('".$_GET['value2']."'";

$used = [];
$dbu = new DbUtils();
if ($_GET["name"] != "type") {
   $datas = $dbu->getAllDataFromTable("glpi_plugin_positions_positions",
                                      ["itemtype" => $_GET['itemtype']]);
} else {
   $datas = $dbu->getAllDataFromTable("glpi_plugin_positions_imageitems",
                                      ["itemtype" => $_GET['itemtype']]);
}
if (!empty($datas)) {
   foreach ($datas as $data) {
      if ($_GET["name"] != "type") {
         $used[]=$data["items_id"];
      } else {
         $used[]=$data["type"];
      }
   }
}

if (count($used)) {
   $where .= ",'".implode("','", $used)."'";
}

$where .= ")";

$multi = false;

 $config = new PluginPositionsConfig();
 $config->getFromDB(1);

if (isset($_GET["name"]) && $_GET["name"] != "type") {
   $dbu = new DbUtils();
   if (!$config->fields['use_view_all_object']) {
      if ($_GET['locations_id'] != -1) {
         $where .= " AND `locations_id` = '" . $_GET['locations_id'] . "'";
      }
   } else {
      $locations = $dbu->getSonsOf('glpi_locations', $_GET['locations_id']);
      $where .= " AND `locations_id` IN (" . implode(',', $locations). ")";
   }
   if ($item->maybeDeleted()) {
      $where .= " AND `is_deleted` = '0' ";
   }
   if ($item->maybeTemplate()) {
      $where .= " AND `is_template` = '0' ";
   }

   if ($item->isEntityAssign()) {
      $multi = $item->maybeRecursive();

      if (isset($_GET["entity_restrict"]) && !($_GET["entity_restrict"]<0)) {
         $where .= $dbu->getEntitiesRestrictRequest("AND", $_GET['table'], "entities_id",
                                              $_GET["entity_restrict"], $multi);

         if (is_array($_GET["entity_restrict"]) && count($_GET["entity_restrict"])>1) {
            $multi = true;
         }

      } else {
         $where .= $dbu->getEntitiesRestrictRequest("AND", $_GET['table'], '', '', $multi);

         if (count($_SESSION['glpiactiveentities'])>1) {
            $multi = true;
         }
      }
   }
}

$field = "name";

if ($_GET['searchText'] != '__VALUE__' && $_GET['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
   $where .= " AND $field ".Search::makeTextSearch($_GET['searchText']);
}

$query = "SELECT *
          FROM `".$_GET['table']."`
          $where ";
if ($config->fields['use_view_all_object'] && $_GET["name"] != "type") {
   $query .= "ORDER BY `locations_id`";
} else {
   if ($multi) {
      $query .= " ORDER BY `entities_id`, $field
                 $LIMIT";
   } else {
      $query .= " ORDER BY $field
                 $LIMIT";
   }
}

$result = $DB->query($query);

$out = [];

$number = $DB->numrows($result);
if ($number != 0 && $_GET["locations_id"]== -1) {
   array_push($out, ['id'   => $_GET['itemtype'].";-1",
                          'text' => __('All types', 'positions')]);
}
$output = Dropdown::getDropdownName($_GET['table'], $_GET['value2']);
if (!empty($output)&&$output!="&nbsp;") {
   array_push($out, ['id'   => $_GET['value2'],
                          'text' => $output]);
}


if ($number) {
   if ($config->fields['use_view_all_object'] && $_GET["name"] != "type") {
      $current_location = '';
      while ($data =$DB->fetchArray($result)) {
         if (empty($current_location)) {
            $children = [];
            $level = 1;
            $current_location = new Location();
            $current_location->getFromDB($data['locations_id']);
         } else if ($current_location->fields['id'] != $data['locations_id']) {
            array_push($out, ['text' => $current_location->fields['completename'], 'children' => $children]);

            $children = [];
            $level = 1;
            $current_location = new Location();
            $current_location->getFromDB($data['locations_id']);
         }
         $output = $data[$field];
         $ID = $data['id'];

         if (empty($output)) {
            $output = "($ID)";
         }
         array_push($children, ['id' => $_GET['itemtype'].";".$ID, 'text' => $output, 'level' => '1']);
         $level++;

      }
      array_push($out, ['text' => $current_location->fields['completename'], 'children' => $children]);

   } else {
      while ($data =$DB->fetchArray($result)) {
         $output = $data[$field];
         $ID = $data['id'];

         if (empty($output)) {
            $output = "($ID)";
         }

         array_push($out, ['id'   => $_GET['itemtype'].";".$ID,
                                'text' => $output]);
      }
   }
}

$ret['results'] = $out;
$ret['count']   = count($out);

echo json_encode($ret);

