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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownValue.php")) {
   include ('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

// Security
if (!TableExists($_GET['table'])) {
   exit();
}

$item = new $_GET['itemtype']();

if (isset($_GET["entity_restrict"])
    && !is_numeric($_GET["entity_restrict"])
    && !is_array($_GET["entity_restrict"])) {

   $_GET["entity_restrict"] = Toolbox::decodeArrayFromInput($_GET["entity_restrict"]);
}

// Make a select box with preselected values
if (!isset($_GET["limit"])) {
   $_GET["limit"] = $CFG_GLPI["dropdown_chars_limit"];
}

$NBMAX = $CFG_GLPI["dropdown_max"];
$LIMIT = "LIMIT 0,$NBMAX";
if ($_GET['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT = "";
}

$where =" WHERE `".$_GET['table']."`.`id` NOT IN ('".$_GET['value2']."'";

$used = array();

if ($_GET["name"] != "type") {
   $datas = getAllDatasFromTable("glpi_plugin_positions_positions", "`itemtype` = '".$_GET['itemtype']."'");
} else {
   $datas = getAllDatasFromTable("glpi_plugin_positions_imageitems", "`itemtype` = '".$_GET['itemtype']."'");
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
   $where .= ",'".implode("','",$used)."'";
}

$where .= ")";

$multi = false;

if (isset($_GET["name"]) && $_GET["name"] != "type") {
   if ($_GET['locations_id'] != -1) {
      $where .= " AND `locations_id` = '".$_GET['locations_id']."'";
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
         $where .= getEntitiesRestrictRequest("AND", $_GET['table'], "entities_id",
                                              $_GET["entity_restrict"], $multi);

         if (is_array($_GET["entity_restrict"]) && count($_GET["entity_restrict"])>1) {
            $multi = true;
         }

      } else {
         $where .= getEntitiesRestrictRequest("AND", $_GET['table'], '', '', $multi);

         if (count($_SESSION['glpiactiveentities'])>1) {
            $multi = true;
         }
      }
   }
}
   
$field = "name";

if ($_GET['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
   $where .= " AND $field ".Search::makeTextSearch($_GET['searchText']);
}

$query = "SELECT *
          FROM `".$_GET['table']."`
          $where ";
if ($multi) {
   $query .= " ORDER BY `entities_id`, $field
              $LIMIT";
} else {
   $query .= " ORDER BY $field
              $LIMIT";
}

$result = $DB->query($query);

$out = array();

$number = $DB->numrows($result);
if ($number != 0 && $_GET["locations_id"]== -1) {
   array_push($out, array('id'   => $_GET['itemtype'].";-1",
                          'text' => __('All types', 'positions')));
}
$output = Dropdown::getDropdownName($_GET['table'],$_GET['value2']);
if (!empty($output)&&$output!="&nbsp;") {
   array_push($out, array('id'   => $_GET['value2'],
                          'text' => $output));
}



if ($number) {
   while ($data =$DB->fetch_array($result)) {
      $output = $data[$field];
      $ID = $data['id'];
      
      if (empty($output)) {
         $output = "($ID)";
      }

      array_push($out, array('id'   => $_GET['itemtype'].";".$ID,
                             'text' => $output));
   }
}

$ret['results'] = $out;
$ret['count']   = count($out);

echo json_encode($ret);

?>