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

use GlpiPlugin\Positions\Info;

if (strpos($_SERVER['PHP_SELF'], "dropdownInfoFields.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}
// Only caller: the form of the display configuration of the information pop-up
Session::checkRight('plugin_positions', UPDATE);

$config = new Info();
if ((int) ($_POST['id'] ?? 0) > 0) {
    $config->check((int) $_POST['id'], READ);
} else {
    $config->getEmpty();
    $config->fields['itemtype'] = (string) ($_POST['itemtype'] ?? '');
}

Info::selectCriterias($config);
