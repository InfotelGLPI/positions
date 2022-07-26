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

if (Plugin::isPluginActive("positions")) {
   $config = new PluginPositionsConfig();

   if (isset($_POST["update_config"])) {
        Session::checkRight("config", UPDATE);
        $config->update($_POST);
        Html::back();

   } else {
      Html::header(PluginPositionsPosition::getTypeName(), '', "tools", "pluginpositionsmenu", "config");
      $config->showConfigForm();
      Html::footer();
   }



} else {
   Html::header(__('Setup'), '', "config", "plugins");
   echo "<div class='alert alert-important alert-warning d-flex'>";
   echo "<b>".__('Please activate the plugin', 'positions')."</b></div>";
   Html::footer();
}

