<?php
/*
 * @version $Id: HEADER 15930 2013-02-07 09:47:55Z tsmr $
 -------------------------------------------------------------------------
 Positions plugin for GLPI
 Copyright (C) 2003-2011 by the Positions Development Team.

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
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Positions. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */
 
class PluginPositionsMenu extends CommonGLPI {
   static $rightname = 'plugin_positions';

   static function getMenuName() {
      return _n('Cartography', 'Cartographies', 2, 'positions');
   }

   static function getMenuContent() {
      global $CFG_GLPI;

      $menu                                           = array();
      $menu['title']                                  = self::getMenuName();
      $menu['page']                                   = "/plugins/positions/front/map.form.php";
      $menu['links']['search']                        = PluginPositionsPosition::getSearchURL(false);
      
      
      $menu['options']['positions']['links']['search'] = PluginPositionsPosition::getSearchURL(false);
      $menu['options']['positions']['links']['config'] = '/plugins/positions/front/config.form.php';
      
      $menu['options']['positions']['links']["<img  src='".$CFG_GLPI["root_doc"].
         "/pics/menu_showall.png' title='".__('Map view', 'positions').
         "' alt='".__('Map view', 'positions')."'>"] = '/plugins/positions/front/map.form.php';
      
      //$menu['options']['config']['links']['search'] = PluginPositionsPosition::getSearchURL(false);
      $menu['options']['config']['links']['config'] = '/plugins/positions/front/config.form.php';
      
      
      $menu['options']['info']['links']['search'] = '/plugins/positions/front/info.php';
      
      $menu['options']['info']['links']["<img  src='".$CFG_GLPI["root_doc"].
      "/pics/menu_showall.png' title='".__('Map view', 'positions').
      "' alt='".__('Map view', 'positions')."'>"] = '/plugins/positions/front/map.form.php';
      
      $menu['options']['info']['links']['config'] = '/plugins/positions/front/config.form.php';
         
      if (PluginPositionsPosition::canCreate()) {
         $menu['links']['add']                        = PluginPositionsPosition::getFormURL(false);
         $menu['options']['positions']['links']['add'] = PluginPositionsPosition::getFormURL(false);
         $menu['options']['info']['links']['add'] = '/plugins/positions/front/info.form.php';
      }

      return $menu;
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