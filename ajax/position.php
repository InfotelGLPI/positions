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

         $input = ['id' => (int) $config['id']];
         // canvasXpress builds its labels and tooltips as HTML without escaping: the texts are
         // stored without markup, the other attributes restricted to their own syntax
         if (isset($config['label'])) {
            $input['name'] = strip_tags((string) $config['label']);
         }
         if (isset($config['tooltip'])) {
            $input['tooltip'] = strip_tags((string) $config['tooltip']);
         }
         if (isset($config['x'])) {
            $input['x_coordinates'] = (int) $config['x'];
         }
         if (isset($config['y'])) {
            $input['y_coordinates'] = (int) $config['y'];
         }
         // Colors: #rgb, #rrggbb, rgb()/rgba() or a color name
         foreach (['color', 'outline'] as $field) {
            if (isset($config[$field])
                && preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9., ]+\)|[a-z]+)$/i', (string) $config[$field])) {
               $input[$field] = (string) $config[$field];
            }
         }
         // Shape and pattern: names of the library (circle, square, solid, ...)
         foreach (['shape', 'pattern'] as $field) {
            if (isset($config[$field]) && preg_match('/^[a-z0-9_-]{1,50}$/i', (string) $config[$field])) {
               $input[$field] = (string) $config[$field];
            }
         }
         foreach (['width', 'height', 'outlineWidth', 'rotate'] as $field) {
            if (isset($config[$field])) {
               $input[$field] = (int) $config[$field];
            }
         }
         foreach (['hideLabel', 'hideTooltip'] as $field) {
            if (isset($config[$field])) {
               $input[$field] = $config[$field] ? 1 : 0;
            }
         }
         if (isset($config['labelSize'])) {
            $input['labelSize'] = (float) $config['labelSize'];
         }

         $positions->update($input);
      }
      break;
}
