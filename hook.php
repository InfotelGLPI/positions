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

function plugin_positions_install() {
   global $DB,$CFG_GLPI;

   include_once (PLUGIN_POSITIONS_DIR."/inc/profile.class.php");

   if (!$DB->tableExists("glpi_plugin_positions_positions")) {
      $DB->runFile(PLUGIN_POSITIONS_DIR."/sql/empty-6.0.0.sql");
   }

   //v1.0.0 to V2.0.0
   if ($DB->tableExists("glpi_plugin_positions_positions_items")
           && !$DB->fieldExists("glpi_plugin_positions_positions_items", "items_id")) {

      $query = "ALTER TABLE `glpi_plugin_positions_positions` 
                ADD `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)';";
      $DB->query($query);

      $query = "ALTER TABLE `glpi_plugin_positions_positions` 
                ADD `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file';";
      $DB->query($query);

      $query_ = "SELECT *
                 FROM `glpi_plugin_positions_positions_items` ";
      $result_ = $DB->query($query_);
      if ($DB->numrows($result_) > 0) {

         while ($data = $DB->fetchArray($result_)) {
            $query = "UPDATE `glpi_plugin_positions_positions`
                      SET `items_id` = '".$data["items_id"]."',
                          `itemtype` = '".$data["itemtype"]."'
                      WHERE `id` = '".$data["id"]."';";
            $DB->query($query);
         }
      }

      $query = "DROP TABLE `glpi_plugin_positions_positions_items`;";
      $DB->query($query);
   }
   //v1.0.0 to V2.0.0
   if (!$DB->tableExists("glpi_plugin_positions_infos")) {

      $query = "CREATE TABLE `glpi_plugin_positions_infos` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) collate utf8_unicode_ci default NULL,
                  `entities_id` int(11) NOT NULL default '0',
                  `is_recursive` tinyint(1) NOT NULL default '0',
                  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
                  `fields` text collate utf8_unicode_ci,
                  `comment` text collate utf8_unicode_ci,
                  `notepad` longtext collate utf8_unicode_ci,
                  `date_mod` datetime NULL default NULL,
                  `is_active` tinyint(1) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL default '0',
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $result = $DB->query($query);
   }

   //to V3.0.0
   if ($DB->tableExists("glpi_plugin_positions_positions")
           && $DB->fieldExists("glpi_plugin_positions_positions", "documents_id")) {

      $query = "ALTER TABLE `glpi_plugin_positions_positions` DROP `documents_id`;";
      $DB->query($query);
   }

   // Update to 4.0.1
   if (!$DB->fieldExists("glpi_plugin_positions_positions", "width") && !$DB->fieldExists("glpi_plugin_positions_positions", "height")) {
      $DB->runFile(PLUGIN_POSITIONS_DIR."/sql/update-4.0.1.sql");
   }

   //to v4.2.2
   if (!$DB->tableExists("glpi_plugin_positions_configs")) {
      //add table config
      $query = "CREATE TABLE `glpi_plugin_positions_configs` (
               `id` int(11) NOT NULL auto_increment,
               `use_view_all_object` tinyint(1) NOT NULL default '0',
               PRIMARY KEY  (`id`)
            )ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query);

      $query = "INSERT INTO `glpi_plugin_positions_configs` (`id`,`use_view_all_object`) VALUES ('1', '0');";
      $DB->query($query);

      //add fields locations_id
      $query = "ALTER TABLE `glpi_plugin_positions_positions` 
                ADD `locations_id` int(11) NOT NULL default '0' COMMENT 'RELATION to table glpi_locations';";
      $DB->query($query);

      include(PLUGIN_POSITIONS_DIR . "/sql/update_421_422.php");
      update421to422();
   }

   // Update to 4.4.0
   if (!$DB->fieldExists("glpi_plugin_positions_configs", "default_width")
      && !$DB->fieldExists("glpi_plugin_positions_configs", "default_height")) {
      $DB->runFile(PLUGIN_POSITIONS_DIR."/sql/update-4.4.0.sql");
   }

   //Update to 4.5.1 change size
   $listFields = $DB->listFields('glpi_plugin_positions_positions');
   if(isset($listFields['labelSize']) && $listFields['labelSize']['Type'] == "int(11)") {
      $DB->runFile(PLUGIN_POSITIONS_DIR."/sql/update-4.5.1.sql");
   }

   if ($DB->tableExists("glpi_plugin_positions_profiles")) {

      $notepad_tables = ['glpi_plugin_positions_positions'];

      foreach ($notepad_tables as $t) {
         // Migrate data
         if ($DB->fieldExists($t, 'notepad')) {
            $query = "SELECT id, notepad
                      FROM `$t`
                      WHERE notepad IS NOT NULL
                            AND notepad <>'';";
            foreach ($DB->request($query) as $data) {
               $iq = "INSERT INTO `glpi_notepads`
                             (`itemtype`, `items_id`, `content`, `date`, `date_mod`)
                      VALUES ('PluginPositionsPosition', '".$data['id']."',
                              '".addslashes($data['notepad'])."', NOW(), NOW())";
               $DB->queryOrDie($iq, "0.85 migrate notepad data");
            }
            $query = "ALTER TABLE `glpi_plugin_positions_positions` DROP COLUMN `notepad`;";
            $DB->query($query);
         }
      }
   }

   $rep_files_positions = GLPI_PLUGIN_DOC_DIR."/positions";
   if (!is_dir($rep_files_positions)) {
      mkdir($rep_files_positions);
   }

   $rep_files_positions_pics = GLPI_PLUGIN_DOC_DIR."/positions/pics";
   if (!is_dir($rep_files_positions_pics)) {
      mkdir($rep_files_positions_pics);
   }

   PluginPositionsProfile::initProfile();
   PluginPositionsProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
   $migration = new Migration("4.2.2");
   $migration->dropTable('glpi_plugin_positions_profiles');

   return true;
}

function plugin_positions_uninstall() {
   global $DB;

   $tables = ["glpi_plugin_positions_positions",
                   "glpi_plugin_positions_positions_items",
                   "glpi_plugin_positions_imageitems",
                   "glpi_plugin_positions_infos",
                   "glpi_plugin_positions_configs"];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $rep_files_positions = GLPI_PLUGIN_DOC_DIR."/positions";

   Toolbox::deleteDir($rep_files_positions);

   $tables_glpi = ["glpi_displaypreferences",
                        "glpi_documents_items",
                        "glpi_savedsearches",
                        "glpi_logs"];

   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` = 'PluginPositionsPosition' ;");
   }

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginPositionsProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }

   PluginPositionsMenu::removeRightsFromSession();
   PluginPositionsProfile::removeRightsFromSession();

   return true;
}


// Define dropdown relations
function plugin_positions_getDatabaseRelations() {

   if (Plugin::isPluginActive("positions")) {
      return ["glpi_entities"=>["glpi_plugin_positions_positions"=>"entities_id"]];
   } //"glpi_locations"=>array("glpi_plugin_positions_positions"=>"locations_id"),

   else {
      return [];
   }
}

////// SEARCH FUNCTIONS ///////() {

function plugin_positions_getAddSearchOptions($itemtype) {

   $sopt = [];

   if (in_array($itemtype, PluginPositionsPosition::getTypes(true))) {
      if (Session::haveRight("plugin_positions", READ)) {
         $sopt[4415]['table']          = 'glpi_plugin_positions_positions';
         $sopt[4415]['field']          = 'name';
         $sopt[4415]['linkfield']      = '';
         $sopt[4415]['name']           = __('Cartography', 'positions')." - ".
                                          __('Name');
         $sopt[4415]['forcegroupby']   = '1';
         $sopt[4415]['datatype']       = 'itemlink';
         $sopt[4415]['itemlink_type']  = 'PluginPositionsPosition';
         $sopt[4415]['massiveaction']  = false;

      }
   }
   return $sopt;
}

function plugin_positions_addLeftJoin($type, $ref_table, $new_table, $linkfield, &$already_link_tables) {

   switch ($new_table) {
      case "glpi_plugin_positions_positions" : // From items
         $out= " LEFT JOIN `glpi_plugin_positions_positions` 
                        ON (`$ref_table`.`id` = 
                              `glpi_plugin_positions_positions`.`items_id` 
                              AND `glpi_plugin_positions_positions`.`itemtype` = '$type') ";
         return $out;
         break;
   }

   return "";
}

function plugin_positions_giveItem($type, $ID, $data, $num) {
   global $DB;

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   switch ($type) {
      case 'PluginPositionsPosition':

         switch ($table.'.'.$field) {
            case "glpi_plugin_positions_positions.items_id" :
               $query_device  = "SELECT DISTINCT `itemtype`
                                 FROM `glpi_plugin_positions_positions`
                                 WHERE `id` = '".$data['id']."'
                                 ORDER BY `itemtype`";
               $result_device = $DB->query($query_device);
               $number_device = $DB->numrows($result_device);
               $y             = 0;
               $out           = '';
               $positions_id  = $data['id'];
               if ($number_device > 0) {
                  $dbu = new DbUtils();
                  for ($i = 0; $i < $number_device; $i++) {
                     $column   = "name";
                     $itemtype = $DB->result($result_device, $i, "itemtype");

                     if (!class_exists($itemtype)) {
                        continue;
                     }
                     $item = new $itemtype();
                     if ($item->canView()) {
                        $table_item = $dbu->getTableForItemType($itemtype);

                        $query = "SELECT `".$table_item."`.*, `glpi_plugin_positions_positions`.`id` AS items_id, `glpi_entities`.`id` AS entity "
                                ."FROM `glpi_plugin_positions_positions`, `".$table_item."` "
                                ."LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id` = `".$table_item."`.`entities_id`) "
                                ."WHERE `".$table_item."`.`id` = `glpi_plugin_positions_positions`.`items_id`
                                  AND `glpi_plugin_positions_positions`.`itemtype` = '$itemtype'
                                  AND `glpi_plugin_positions_positions`.`id` = '".$positions_id."' "
                                .$dbu->getEntitiesRestrictRequest(" AND ", $table_item, '', '', $item->maybeRecursive());

                        if ($item->maybeTemplate()) {
                           $query.=" AND `".$table_item."`.`is_template` = '0'";
                        }
                        $query.=" ORDER BY `glpi_entities`.`completename`, `".$table_item."`.`$column`";

                        if ($result_linked = $DB->query($query)) {
                           if ($DB->numrows($result_linked)) {
                              $item = new $itemtype();
                              while ($val = $DB->fetchAssoc($result_linked)) {
                                 if ($item->getFromDB($val['id'])) {
                                    $out .= $item->getTypeName()." - ".$item->getLink()."<br>";
                                 }
                              }
                           } else {
                              $out.= ' ';
                           }
                        }
                     } else {
                        $out.=' ';
                     }
                  }
               }
               return $out;
               break;
         }
         break;
   }

   if (in_array($type, PluginPositionsPosition::getTypes(true))) {
      switch ($table.'.'.$field) {
         case "glpi_plugin_positions_positions.name" :
            $out = "";
            $pos = new PluginPositionsPosition();
            if (isset($data[$num][0]['id'])
                  && !empty($data[$num][0]['id'])
                  &&  $pos->getFromDB($data[$num][0]['id'])) {
               $out .= $pos->getLink();
               $out .= PluginPositionsPosition::showGeolocLink($type, $data['id'], $data[$num][0]['id']);
               $out .= "<br>";
            }
            return $out;
      }
   }

   return "";
}

function plugin_positions_postinit() {
   global $PLUGIN_HOOKS;

   $plugin = 'positions';
   foreach (['add_css', 'add_javascript'] as $type) {
      if (isset($PLUGIN_HOOKS[$type][$plugin])) {
         foreach ($PLUGIN_HOOKS[$type][$plugin] as $data) {
            if (!empty($PLUGIN_HOOKS[$type])) {
               foreach ($PLUGIN_HOOKS[$type] as $key => $plugins_data) {
                  if (is_array($plugins_data) && $key != $plugin) {
                     foreach ($plugins_data as $key2 => $values) {
                        if ($values == $data) {
                           unset($PLUGIN_HOOKS[$type][$key][$key2]);
                        }
                     }
                  }
               }
            }
         }
      }
   }

   $PLUGIN_HOOKS['item_purge']['positions'] = [];

   foreach (PluginPositionsPosition::getTypes(true) as $type) {
      $PLUGIN_HOOKS['item_purge']['positions'][$type] = ['PluginPositionsPosition','purgePositions'];
      CommonGLPI::registerStandardTab($type, 'PluginPositionsPosition');
   }
}
