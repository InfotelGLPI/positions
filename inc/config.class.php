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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginPositionsConfig
 */
class PluginPositionsConfig extends CommonDBTM {

   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @since version 0.83
    *
    * @param CommonGLPI $item         Item on which the tab need to be displayed
    * @param boolean    $withtemplate is a template object ? (default 0)
    *
    *  @return string tab name
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        return __('Plugin Setup', 'positions');
   }


   static function showConfigForm() {

      $config = new self();
      $config->getFromDB(1);

      echo "<div class='center'>";
      echo "<form method='post' action='" .
           Toolbox::getItemTypeFormURL('PluginPositionsConfig') . "'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>" . __('General setup') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Add sub-places on the map objects', 'positions');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('use_view_all_object', $config->fields['use_view_all_object']);
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Default width of images', 'positions');
      echo "</td>";
      echo "<td>";
      Dropdown::showNumber('default_width', ['min'   => 1,
                                             'max'   => 300,
                                             'value' => $config->fields['default_width']]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Default height of images', 'positions');
      echo "</td>";
      echo "<td>";
      Dropdown::showNumber('default_height', ['min'   => 1,
                                              'max'   => 300,
                                              'value' => $config->fields['default_height']]);
      echo "</td></tr>";

      echo "<tr><th colspan='2'>";
      echo Html::hidden('id', ['value' => 1]);
      echo "<div align='center'>";
      echo Html::submit(_sx('button', 'Post'), ['name' => 'update_config', 'class' => 'btn btn-primary']);
      echo "</div></th></tr>";
      echo "</table></div>";
      Html::closeForm();

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo __('Setup');
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
           __('Configuring the display materials', 'positions') . "</a>";
      echo "</td>";
      echo "</tr>";
      echo "</table></div>";
   }
}
