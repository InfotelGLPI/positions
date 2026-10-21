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

use GlpiPlugin\Positions\Position;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight('plugin_positions', UPDATE);

switch ($_POST['action'] ?? '') {
   case 'setConfig':
      if (isset($_POST['config']) && !empty($_POST['config'])) {
         $positions = new Position();
         $config = json_decode(stripslashes($_POST['config']), true);
         if (!is_array($config) || (int) ($config['id'] ?? 0) <= 0) {
            throw new \Glpi\Exception\Http\BadRequestHttpException();
         }
         // An existing position the user may update, and only what the canvas edits: its
         // label, coordinates and display attributes, never its item, location or entity
         $positions->check((int) $config['id'], UPDATE);

         // The fields the canvas edits, under their column names; their values are checked by
         // Position::checkValues(), as for the forms
         $input = ['id' => (int) $config['id']];
         $map   = ['label' => 'name', 'x' => 'x_coordinates', 'y' => 'y_coordinates'];
         foreach (['label', 'x', 'y', 'tooltip', 'color', 'outline', 'shape', 'pattern', 'width', 'height',
                   'outlineWidth', 'rotate', 'hideLabel', 'hideTooltip', 'labelSize'] as $field) {
            if (isset($config[$field]) && is_scalar($config[$field])) {
               $input[$map[$field] ?? $field] = $config[$field];
            }
         }

         $positions->update($input);
      }
      break;
}
