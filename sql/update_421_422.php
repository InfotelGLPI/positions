<?php

/*
  -------------------------------------------------------------------------
  Positions plugin for GLPI
  Copyright (C) 2003-2012 by the Positions Development Team.

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
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Positions. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

/**
 * Update from 4.2.1 to 4.2.2
 *
 * @return bool for success (will die for most error)
 * */
function update421to422() {
   global $DB;
   $migration = new Migration('422');

   $query = "SELECT * FROM `glpi_plugin_positions_positions`";
   $result_query = $DB->query($query);
   while ($data = $DB->fetchArray($result_query)) {
      $dbu = new DbUtils();
      if (!($itemclass = $dbu->getAllDataFromTable($dbu->getTableForItemType($data['itemtype']),
                                                   ["id" => $data['items_id']]))) {
         $query = "DELETE FROM `glpi_plugin_positions_positions` WHERE `items_id` =" . $data['items_id'] . " AND `itemtype` = '" . $data['itemtype'] . "'";
         $DB->queryOrDie($query);
         continue;
      }
      $itemclass = reset($itemclass);
      $query = "UPDATE `glpi_plugin_positions_positions` SET `locations_id` = " . $itemclass['locations_id'] . " WHERE `items_id` =" . $data['items_id'] . " AND `itemtype` = '" . $data['itemtype'] . "'";
      $DB->queryOrDie($query, "ADD fields locations_ids for glpi_plugin_positions_positions");
   }

   $migration->executeMigration();
   return true;
}

