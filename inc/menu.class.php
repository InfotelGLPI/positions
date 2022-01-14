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

/**
 * Class PluginPositionsMenu
 */
class PluginPositionsMenu extends CommonGLPI {
   static $rightname = 'plugin_positions';

   /**
    * Get menu name
    *
    * @since version 0.85
    *
    * @return string character menu shortcut key
    **/
   static function getMenuName() {
      return _n('Cartography', 'Cartographies', 2, 'positions');
   }

   /**
    * get menu content
    *
    * @since version 0.85
    *
    * @return array array for menu
    **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu                                           = [];
      $menu['title']                                  = self::getMenuName();
      $menu['page']                                   = PLUGIN_POSITIONS_NOTFULL_WEBDIR."/front/map.form.php";
      $menu['links']['search']                        = PluginPositionsPosition::getSearchURL(false);

      $menu['options']['positions']['links']['search'] = PluginPositionsPosition::getSearchURL(false);
      $menu['options']['positions']['links']['config'] = PLUGIN_POSITIONS_NOTFULL_WEBDIR.'/front/config.form.php';
      $menu['options']['config']['links']['config'] = PLUGIN_POSITIONS_NOTFULL_WEBDIR.'/config.form.php';
      $menu['options']['info']['links']['search'] = PLUGIN_POSITIONS_NOTFULL_WEBDIR.'/front/info.php';
      $menu['options']['info']['links']['config'] = PLUGIN_POSITIONS_NOTFULL_WEBDIR.'/front/config.form.php';

      if (PluginPositionsPosition::canCreate()) {
         $menu['links']['add']                        = PluginPositionsPosition::getFormURL(false);
         $menu['options']['positions']['links']['add'] = PluginPositionsPosition::getFormURL(false);
         $menu['options']['info']['links']['add'] = PLUGIN_POSITIONS_NOTFULL_WEBDIR.'/front/info.form.php';
      }

      $menu['icon'] = self::getIcon();

      return $menu;
   }

   static function getIcon() {
      return "ti ti-map";
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['tools']['types']['PluginPositionsMenu'])) {
         unset($_SESSION['glpimenu']['tools']['types']['PluginPositionsMenu']);
      }
      if (isset($_SESSION['glpimenu']['tools']['content']['pluginpositionsmenu'])) {
         unset($_SESSION['glpimenu']['tools']['content']['pluginpositionsmenu']);
      }
   }
}
