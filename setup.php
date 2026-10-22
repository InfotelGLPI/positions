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

use GlpiPlugin\Positions\Menu;
use GlpiPlugin\Positions\Position;
use GlpiPlugin\Positions\Profile;

// Init the hooks of the plugins -Needed
global $CFG_GLPI;

define('PLUGIN_POSITIONS_VERSION', '7.0.4');

if (!defined("PLUGIN_POSITIONS_DIR")) {
    define("PLUGIN_POSITIONS_DIR", Plugin::getPhpDir("positions"));
    // Web paths: GLPI 11 serves every plugin under /plugins/<key>, wherever it is installed
    // (plugins/ or marketplace/), so they never derive from the PHP directory
    define("PLUGIN_POSITIONS_NOTFULL_DIR", '/plugins/positions');
    define("PLUGIN_POSITIONS_WEBDIR", $CFG_GLPI['root_doc'] . PLUGIN_POSITIONS_NOTFULL_DIR);
}

function plugin_init_positions()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['positions'] = true;
    $PLUGIN_HOOKS['change_profile']['positions'] = [Profile::class, 'initProfile'];

    if (Session::getLoginUserID()) {
        Plugin::registerClass(
            Profile::class,
            ['addtabon' => 'Profile']
        );

        if (Session::haveRight("plugin_positions", UPDATE)) {
            $PLUGIN_HOOKS['use_massive_action']['positions'] = 1;
        }
        // The configuration page requires the configuration right: no link to a page refused
        if (Session::haveRight("config", UPDATE)) {
            $PLUGIN_HOOKS['config_page']['positions'] = 'front/config.form.php';
        }

        if (Session::haveRight("plugin_positions", READ)) {
            $PLUGIN_HOOKS['helpdesk_menu_entry']['positions'] = PLUGIN_POSITIONS_NOTFULL_DIR.'/front/map.form.php';
            $PLUGIN_HOOKS['helpdesk_menu_entry_icon']['positions'] = Position::getIcon();
            $PLUGIN_HOOKS['menu_toadd']['positions']          = ['tools' => Menu::class];
        }

        // The scripts of the map and of the crop page are loaded on demand, by Html::requireJs(),
        // on the pages of the plugin only: never on every page of GLPI. The map uses Leaflet and
        // the crop page Cropper.js, both shipped by GLPI
        $PLUGIN_HOOKS['javascript']['positions'] = [
         PLUGIN_POSITIONS_NOTFULL_DIR . "/positions.js",
        ];
        $PLUGIN_HOOKS['javascript']['positions_map'] = [
         PLUGIN_POSITIONS_NOTFULL_DIR . "/positions_map.js",
        ];
        $PLUGIN_HOOKS['javascript']['positions_crop'] = [
         PLUGIN_POSITIONS_NOTFULL_DIR . "/positions_crop.js",
        ];
       //css
        $PLUGIN_HOOKS['add_css']['positions'] = ["positions.css"];

        if (class_exists('PluginTreeviewConfig')) {
            $PLUGIN_HOOKS['treeview_params']['positions'] = [Position::class, 'showPositionTreeview'];
        }
    }
   // End init, when all types are registered
    $PLUGIN_HOOKS['post_init']['positions'] = 'plugin_positions_postinit';
}

// Get the name and the version of the plugin - Needed
function plugin_version_positions()
{

    return [
      'name'           => _n('Cartography', 'Cartographies', 1, 'positions'),
      'version'        => PLUGIN_POSITIONS_VERSION,
      'license'        => 'GPLv2+',
      'author'         => "<a href='https://blogglpi.infotel.com'>Infotel</a>",
      'homepage'       => 'https://github.com/InfotelGLPI/positions',
      'requirements'   => [
         'glpi' => [
            'min' => '11.0',
            'max' => '12.0',
            'dev' => false
         ]
      ]
    ];
}
