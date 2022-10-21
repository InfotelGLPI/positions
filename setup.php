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

// Init the hooks of the plugins -Needed

define('PLUGIN_POSITIONS_VERSION', '6.0.2');

if (!defined("PLUGIN_POSITIONS_DIR")) {
   define("PLUGIN_POSITIONS_DIR", Plugin::getPhpDir("positions"));
   define("PLUGIN_POSITIONS_NOTFULL_DIR", Plugin::getPhpDir("positions",false));
   define("PLUGIN_POSITIONS_WEBDIR", Plugin::getWebDir("positions"));
   define("PLUGIN_POSITIONS_NOTFULL_WEBDIR", Plugin::getWebDir("positions",false));
}

function plugin_init_positions() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['positions'] = true;
   $PLUGIN_HOOKS['change_profile']['positions'] = ['PluginPositionsProfile', 'initProfile'];

   if (Session::getLoginUserID()) {

      Plugin::registerClass('PluginPositionsProfile',
                            ['addtabon' => 'Profile']);

      if (Session::haveRight("plugin_positions", UPDATE)) {
         $PLUGIN_HOOKS['use_massive_action']['positions'] = 1;
         $PLUGIN_HOOKS['config_page']['positions']        = 'front/config.form.php';
      }

      if (Session::haveRight("plugin_positions", READ)) {
         $PLUGIN_HOOKS['helpdesk_menu_entry']['positions'] = PLUGIN_POSITIONS_NOTFULL_DIR.'/front/map.form.php';
         $PLUGIN_HOOKS['helpdesk_menu_entry_icon']['positions'] = PluginPositionsPosition::getIcon();
         $PLUGIN_HOOKS['menu_toadd']['positions']          = ['tools' => 'PluginPositionsMenu'];
      }

      // Add specific files to add to the header : javascript or css
      $PLUGIN_HOOKS['add_javascript']['positions'] = [
         //file upload
//         "lib/plupload/plupload.full.js",
         "lib/extjs/adapter/ext/ext-base.js",
         "lib/extjs/ext-all.js",
//         "upload.js",
         "positions.js",
         "geoloc.js",
         "lib/canvas/canvasXpress.min.js",
         "lib/canvas/ext-canvasXpress.js",
         "lib/canvas/color-field.js",
      ];
      $PLUGIN_HOOKS["javascript"]['positions']     = [
         PLUGIN_POSITIONS_NOTFULL_DIR."/positions.js",
      //         PLUGIN_POSITIONS_NOTFULL_DIR."/geoloc.js",
         PLUGIN_POSITIONS_NOTFULL_DIR."/lib/Jcrop/jquery.Jcrop.js",
      ];
      //css
      $PLUGIN_HOOKS['add_css']['positions'] = ["positions.css",
                                                    "lib/canvas/color-field.css",
                                                    "lib/extjs/resources/css/ext-all.css",
                                                    //"lib/Jcrop/jquery.Jcrop.min.css",
      ];

      if (class_exists('PluginTreeviewConfig')) {
         $PLUGIN_HOOKS['treeview_params']['positions'] = ['PluginPositionsPosition', 'showPositionTreeview'];
      }
   }
   // End init, when all types are registered
   $PLUGIN_HOOKS['post_init']['positions'] = 'plugin_positions_postinit';
}

// Get the name and the version of the plugin - Needed
function plugin_version_positions() {

   return [
      'name'           => _n('Cartography', 'Cartographies', 1, 'positions'),
      'version'        => PLUGIN_POSITIONS_VERSION,
      'license'        => 'GPLv2+',
      'author'         => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>",
      'homepage'       => 'https://github.com/InfotelGLPI/positions',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0',
            'max' => '11.0',
            'dev' => false
         ]
      ]
   ];

}
