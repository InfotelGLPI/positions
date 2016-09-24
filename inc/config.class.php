<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 positions plugin for GLPI
 Copyright (C) 2009-2016 by the positions Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginPositionsConfig extends CommonDBTM {
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
        return __('Plugin Setup', 'positions');
   }

   
   
 static function showForm(){
      global $CFG_GLPI;
      
      $config = new self();
      $config->getFromDB(1);

      echo "<div class='center'>";
       echo "<form method='post' action='".
         Toolbox::getItemTypeFormURL('PluginPositionsConfig')."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>".__('General setup')."</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Add sub-places on the map objects', 'positions');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('use_view_all_object', $config->fields['use_view_all_object']);
      echo "</td></tr>";
      echo "<tr><th colspan='2'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<div align='center'>";
      echo "<input type='submit' name='update_config' value=\""._x('button', 'Post')."\" class='submit' >";
      echo "</div></th></tr>";
      echo "</table></div>";
      Html::closeForm();
      
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      _e('Setup');
      echo "</th>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>";
      echo "<a href='./imageitem.form.php'>" .
      __('Association : picture / types of equipment', 'positions') . "</a>";
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>";
      echo "<a href='./info.php'>" .
      __('Configuring the display materials', 'positions'). "</a>";
      echo "</td>";
      echo "</tr>";
      echo "</table></div>";
   }
}

?>