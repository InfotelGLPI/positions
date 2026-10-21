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

use GlpiPlugin\Positions\Config;
use GlpiPlugin\Positions\Position;

global $CFG_GLPI, $DB;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Only caller: the add-coordinates and equipment type pictures forms
Session::checkRight('plugin_positions', UPDATE);

if (empty($_GET) && !empty($_POST)) {
    $_GET = $_POST;
}

// Item types of the plugin only, or their "Type" dropdown for the pictures form; the table is
// derived from the item type, never taken from the request
$name     = (string) ($_GET['name'] ?? '');
$is_type  = ($name === 'type');
$itemtype = (string) ($_GET['itemtype'] ?? '');
if (!in_array($itemtype, Position::getTypes(), true)) {
    throw new \Glpi\Exception\Http\BadRequestHttpException();
}
$item = getItemForItemtype($is_type ? $itemtype . 'Type' : $itemtype);
if (!($item instanceof CommonDBTM)) {
    throw new \Glpi\Exception\Http\BadRequestHttpException();
}
// The names listed are the ones of the item type: its own read right is required too
if (!$item::canView()) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}
$table = $item->getTable();

$value2       = (int) ($_GET['value2'] ?? 0);
$locations_id = (int) ($_GET['locations_id'] ?? -1);
$search_text  = (string) ($_GET['searchText'] ?? '');

$entity_restrict = $_GET['entity_restrict'] ?? -1;
if (!is_numeric($entity_restrict) && !is_array($entity_restrict)) {
    $entity_restrict = Toolbox::decodeArrayFromInput($entity_restrict);
}
// Posted by the client: narrowed to the active entities of the user, never widened
if (!(is_numeric($entity_restrict) && $entity_restrict < 0)) {
    $entity_restrict = Session::getMatchingActiveEntities($entity_restrict);
    if ($entity_restrict === [] || $entity_restrict === '') {
        // None of them is active: matches nothing (-1 would mean "all the active ones")
        $entity_restrict = [-2];
    }
}

// Items already placed on a map, or types already having a picture
$used = [$value2];
if (!$is_type) {
    foreach ((new Position())->find(['itemtype' => $itemtype]) as $data) {
        $used[] = (int) $data['items_id'];
    }
} else {
    foreach (getAllDataFromTable('glpi_plugin_positions_imageitems', ['itemtype' => $itemtype]) as $data) {
        $used[] = (int) $data['type'];
    }
}

$where = ['NOT' => ["$table.id" => $used]];
$multi = false;

$config = new Config();
$config->getFromDB(1);
$view_all = !$is_type && $config->fields['use_view_all_object'];

if (!$is_type) {
    if (!$config->fields['use_view_all_object']) {
        if ($locations_id != -1) {
            $where["$table.locations_id"] = $locations_id;
        }
    } else {
        $where["$table.locations_id"] = array_values(getSonsOf('glpi_locations', $locations_id));
    }
    if ($item->maybeDeleted()) {
        $where["$table.is_deleted"] = 0;
    }
    if ($item->maybeTemplate()) {
        $where["$table.is_template"] = 0;
    }

    if ($item->isEntityAssign()) {
        $multi = $item->maybeRecursive();
        if (!(is_numeric($entity_restrict) && $entity_restrict < 0)) {
            $where += getEntitiesRestrictCriteria($table, 'entities_id', $entity_restrict, $multi);
            if (is_array($entity_restrict) && count($entity_restrict) > 1) {
                $multi = true;
            }
        } else {
            $where += getEntitiesRestrictCriteria($table, '', '', $multi);
            if (count($_SESSION['glpiactiveentities']) > 1) {
                $multi = true;
            }
        }
    }
}

$field = 'name';
if ($search_text !== '__VALUE__' && $search_text !== $CFG_GLPI["ajax_wildcard"] && $search_text !== '') {
    $where["$table.$field"] = ['LIKE', Search::makeTextSearchValue($search_text)];
}

$criteria = [
    'FROM'  => $table,
    'WHERE' => $where,
];
if ($view_all) {
    $criteria['ORDER'] = "$table.locations_id";
} else {
    $criteria['ORDER'] = $multi ? ["$table.entities_id", "$table.$field"] : ["$table.$field"];
    if ($search_text !== $CFG_GLPI["ajax_wildcard"]) {
        $criteria['LIMIT'] = (int) $CFG_GLPI["dropdown_max"];
    }
}

$iterator = $DB->request($criteria);

$out = [];

$number = count($iterator);
if ($number != 0 && $locations_id == -1) {
    $out[] = ['id' => $itemtype . ";-1", 'text' => __('All types', 'positions')];
}
if ($value2 > 0) {
    $output = Dropdown::getDropdownName($table, $value2);
    if (!empty($output) && $output != "&nbsp;") {
        $out[] = ['id' => $value2, 'text' => $output];
    }
}

if ($number) {
    if ($view_all) {
        $current_location = null;
        $children         = [];
        foreach ($iterator as $data) {
            if ($current_location === null || $current_location->fields['id'] != $data['locations_id']) {
                if ($current_location !== null) {
                    $out[] = ['text' => $current_location->fields['completename'], 'children' => $children];
                }
                $children         = [];
                $current_location = new Location();
                $current_location->getFromDB($data['locations_id']);
            }
            $output = $data[$field];
            $ID     = $data['id'];
            if (empty($output)) {
                $output = "($ID)";
            }
            $children[] = ['id' => $itemtype . ";" . $ID, 'text' => $output, 'level' => '1'];
        }
        $out[] = ['text' => $current_location->fields['completename'] ?? '', 'children' => $children];
    } else {
        foreach ($iterator as $data) {
            $output = $data[$field];
            $ID     = $data['id'];
            if (empty($output)) {
                $output = "($ID)";
            }
            $out[] = ['id' => $itemtype . ";" . $ID, 'text' => $output];
        }
    }
}

$ret['results'] = $out;
$ret['count']   = count($out);

echo json_encode($ret);
