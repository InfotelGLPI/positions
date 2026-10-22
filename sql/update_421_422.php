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

   // Values read from the database: bound by the query builder, never concatenated. An item type
   // which is no longer a class of GLPI (plugin removed, malformed value) is left as is.
   foreach ($DB->request(['FROM' => 'glpi_plugin_positions_positions']) as $data) {
      $item = getItemForItemtype((string) $data['itemtype']);
      if (!($item instanceof CommonDBTM)) {
         continue;
      }
      $where = ['items_id' => (int) $data['items_id'], 'itemtype' => (string) $data['itemtype']];
      if (!$item->getFromDB((int) $data['items_id'])) {
         $DB->delete('glpi_plugin_positions_positions', $where);
         continue;
      }
      $DB->update(
         'glpi_plugin_positions_positions',
         ['locations_id' => (int) ($item->fields['locations_id'] ?? 0)],
         $where
      );
   }

   $migration->executeMigration();
   return true;
}

