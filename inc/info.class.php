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
 * Class PluginPositionsInfo
 */
class PluginPositionsInfo extends CommonDBTM {

   public    $dohistory  = true;
   static    $rightname  = "plugin_positions";
   protected $usenotepad = true;

   /**
    * Return the localized name of the current Type
    *
    * @return string
    **/
   public static function getTypeName($nb = 0) {
      return _n('Display of equipment', 'Display of equipments', $nb, 'positions');
   }

   /**
    * Provides search options configuration. Do not rely directly
    * on this, @see CommonDBTM::searchOptions instead.
    *
    * @since 9.3
    *
    * This should be overloaded in Class
    *
    * @return array a *not indexed* array of search options
    *
    * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
    **/
   public function rawSearchOptions() {

      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'datatype'           => 'number',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'fields',
         'name'               => __('Displayed fields', 'positions'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => [
            '0'                  => 'itemtype'
         ]
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'itemtype',
         'name'               => __('Equipment', 'positions'),
         'massiveaction'      => false,
         'datatype'           => 'itemtypename',
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'massiveaction'      => false,
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   /**
    * Define tabs to display
    *
    * NB : Only called for existing object
    *
    * @param array $options Options
    *     - withtemplate is a template view ?
    *
    * @return array array containing the tabs
    **/
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {

      if (!$this->canView()) {
         return false;
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "&nbsp;:</td>";
      echo "<td>";
      echo Html::input('name', ['value' => $this->fields['name'], 'size' => 40]);
      echo "</td>";
      echo "<td rowspan='5'>" . __('Comments') . "&nbsp;:</td>";
      echo "<td rowspan='5'>";
      echo Html::textarea([
                             'name'    => 'comment',
                             'value' => $this->fields["comment"],
                             'cols'    => '45',
                             'rows'    => '8',
                             'display' => false,
                          ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Active') . "&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Displayed fields', 'positions') . "&nbsp;:</td>";
      echo "<td>";

      $this->showItemtype($ID, $this->fields['itemtype']);

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Equipment', 'positions') . "&nbsp;:</td>";
      echo "<td>";

      self::selectCriterias($this);

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
      $datestring = __('Last update') . ": ";
      $date       = Html::convDateTime($this->fields["date_mod"]);
      echo $datestring . $date;
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   /**
    * Display a dropdown which contains all the available itemtypes
    *
    * @param ID the field widget item id
    * @param value the selected value
    *
    * @return nothing
    **/
   function showItemtype($ID, $value = 0) {
      global $CFG_GLPI;

      //Criteria already added : only display the selected itemtype
      if ($ID > 0) {
         $item = new $this->fields['itemtype']();
         echo $item->getTypeName();
         echo Html::hidden('itemtype', ['value' => $this->fields['itemtype']]);

      } else {

         $possible_types = PluginPositionsPosition::getTypes();
         $dbu            = new DbUtils();
         $restrict       = ["is_active"  => 1,
                            "is_deleted" => 0]+$dbu->getEntitiesRestrictCriteria("glpi_plugin_positions_infos",
                                                                                 '', '',
                                                                                 $this->maybeRecursive());
         $dbu   = new DbUtils();
         $types = $dbu->getAllDataFromTable('glpi_plugin_positions_infos', $restrict);

         if (!empty($types)) {
            foreach ($types as $type) {
               foreach ($possible_types as $key => $val) {
                  if ($type["itemtype"] == $val) {
                     unset($possible_types[$key]);
                  }
               }
            }
         }
         //Add criteria : display dropdown
         $options[0] = Dropdown::EMPTY_VALUE;
         foreach ($possible_types as $itemtype) {
            if (class_exists($itemtype)) {
               $item = new $itemtype();
               if ($item->can(-1, READ)) {
                  $options[$itemtype] = $item->getTypeName($itemtype);
               }
            }
         }
         asort($options);
         $rand = Dropdown::showFromArray('itemtype', $options);

         $params = ['itemtype' => '__VALUE__',
                         'id'       => $ID];
         Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "span_fields",
                                       PLUGIN_POSITIONS_WEBDIR . "/ajax/dropdownInfoFields.php",
                                       $params);
      }

   }

   /**
    * Display a list of available fields for widget fields
    *
    * @param $widget an instance of CommonDBTM class
    *
    * @return nothing
    **/
   static function selectCriterias(CommonDBTM $config) {
      global $DB;

      echo "<span id='span_fields' name='span_fields'>";

      if (!isset($config->fields['itemtype']) || !$config->fields['itemtype']) {
         echo "</span>";
         return;
      }

      if (!isset($config->fields['entities_id'])) {
         $config->fields['entities_id'] = $_SESSION['glpiactive_entity'];
      }

      $config_fields = explode(',', $config->fields['fields']);
      //Search option for this type
      $item = new $config->fields['itemtype']();

      //Construct list
      echo "<span id='span_fields' name='span_fields'>";
      echo "<select name='_fields[]' multiple size='15' style='width:400px'>";

      $dbu = new DbUtils();
      foreach ($DB->listFields($dbu->getTableForItemType($config->fields['itemtype'])) as $field) {

         $searchOption = $item->getSearchOptionByField('field', $field['Field'],
                                                       $dbu->getTableForItemType($item->getType()));

         if (empty($searchOption)) {
            if ($table = $dbu->getTableNameForForeignKeyField($field['Field'])) {
               $crit = $dbu->getItemForItemtype($dbu->getItemTypeForTable($table));
               if ($crit instanceof CommonTreeDropdown) {
                  $searchOption = $item->getSearchOptionByField('field', 'completename', $table);
               } else {
                  $searchOption = $item->getSearchOptionByField('field', 'name', $table);
               }
            }
         }

         if (!empty($searchOption)
             && !in_array($field['Field'], self::getUnallowedFields($config->fields['itemtype']))) {

            echo "<option value='" . $field['Field'] . "'";
            if (isset($config_fields) && in_array($field['Field'], $config_fields)) {
               echo " selected ";
            }
            echo ">" . $searchOption['name'] . "</option>";
         }
      }

      echo "</select></span>";
   }

   /**
    * Perform checks to be sure that an itemtype and at least a field are selected
    *
    * @param input the values to insert in DB
    *
    * @return input the values to insert, but modified
    **/
   static function checkBeforeInsert($input) {

      if (!$input['itemtype'] || empty($input['_fields'])) {
         Session::addMessageAfterRedirect(__("It's mandatory to select a type and at least one field"),
                                          true, ERROR);
         $input = [];

      } else {
         $input['fields'] = implode(',', $input['_fields']);
         unset($input['_fields']);
      }
      return $input;
   }


   /**
    * Prepare input datas for adding the item
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForAdd($input) {
      return self::checkBeforeInsert($input);
   }


   /**
    * Prepare input datas for updating the item
    *
    * @param array $input data used to update the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForUpdate($input) {

      $input['fields'] = implode(',', $input['_fields']);
      unset($input['_fields']);

      return $input;
   }

   /**
    * @param $itemclass
    *
    * @return array
    */
   static function getUnallowedFields($itemclass) {

      switch ($itemclass) {
         case "Computer" :
            return ['date_mod',
                         'notepad',
                         'os_license_number',
                         'os_licenseid',
                         'autoupdatesystems_id',
                         'manufacturers_id',
                         'is_ocs_import'];
            break;
         case "Printer" :
            return ['is_recursive',
                         'date_mod',
                         'notepad',
                         'have_serial',
                         'have_parallel',
                         'have_usb',
                         'have_wifi',
                         'have_ethernet',
                         'manufacturers_id',
                         'is_global'];
            break;
         case "NetworkEquipment" :
            return ['is_recursive',
                         'date_mod',
                         'notepad',
                         'ram',
                         'networkequipmentfirmwares_id',
                         'manufacturers_id'];
            break;
         case "Monitor" :
            return ['date_mod',
                         'notepad',
                         'size',
                         'have_micro',
                         'have_speaker',
                         'have_subd',
                         'have_bnc',
                         'have_dvi',
                         'have_pivot',
                         'have_hdmi',
                         'have_displayport',
                         'manufacturers_id',
                         'is_global'];
            break;
         case "Peripheral" :
            return ['date_mod',
                         'notepad',
                         'brand',
                         'manufacturers_id',
                         'is_global'];
            break;
         case "Phone" :
            return ['date_mod',
                         'notepad',
                         'brand',
                         'have_headset',
                         'have_hp',
                         'manufacturers_id',
                         'phonepowersupplies_id',
                         'is_global'];
            break;
         case "PluginResourcesResource" :
            return ['alert',
                         'comment',
                         'date_mod',
                         'picture',
                         'is_recursive',
                         'items_id',
                         'is_helpdesk_visible',
                         'notepad',
                         'is_leaving',
                         'date_declaration',
                         'users_id_recipient',
                         'users_id_recipient_leaving',
                         'date_begin',
                         'date_end'];
            break;
         default:
            return ['alert',
                         'date_mod',
                         'picture',
                         'is_recursive',
                         'items_id',
                         'is_helpdesk_visible',
                         'notepad',
                         'is_leaving',
                         'date_declaration',
                         'users_id_recipient',
                         'users_id_recipient_leaving',
                         'date_begin',
                         'date_end'];
      }
   }

   /**
    * @param $item
    * @param $itemclass
    */
   static function showFields($item, $itemclass) {
      global $DB;

      if (isset($item["fields"])
          && !empty($item["fields"])) {

         $input  = explode(',', $item['fields']);
         $target = new $item['itemtype']();
         $dbu    = new DbUtils();
         foreach ($DB->listFields($dbu->getTableForItemType($item['itemtype'])) as $field) {

            if (in_array($field['Field'], $input)) {
               $searchOption = $target->getSearchOptionByField('field', $field['Field'],
                                                               $dbu->getTableForItemType($target->getType()));

               if (empty($searchOption)) {
                  if ($table = $dbu->getTableNameForForeignKeyField($field['Field'])) {
                     $crit = $dbu->getItemForItemtype($dbu->getItemTypeForTable($table));
                     if ($crit instanceof CommonTreeDropdown) {
                        $searchOption = $target->getSearchOptionByField('field', 'completename', $table);
                     } else {
                        if(Plugin::isPluginActive('resources')) {
                           $searchOption = $target->getSearchOptionByField('field', 'name', $table);
                        }
                     }
                  }
               }

               if (!empty($searchOption)
                   && isset($itemclass->fields[$field['Field']])
                   && !empty($itemclass->fields[$field['Field']])
                   && !in_array($field['Field'], self::getUnallowedFields($item['itemtype']))) {

                  self::getFieldsValue($searchOption, $field, $itemclass);
               }
            }
         }
      }
   }

   /**
    * @param $searchOption
    *
    * @return bool|string
    */
   static function getTypeFields($searchOption) {

      $dropdown_tables = ['glpi_entities',
                               'glpi_locations',
                               'glpi_states',
                               'glpi_plugin_resources_contracttypes',
                               'glpi_plugin_resources_resourcestates',
                               'glpi_plugin_resources_departments',
                               'glpi_plugin_resources_leavingreasons',
                               'glpi_groups',
                               'glpi_domains',
                               'glpi_operatingsystems',
                               'glpi_operatingsystemservicepacks',
                               'glpi_operatingsystemversions',
                               'glpi_networks',
                               'glpi_computermodels',
                               'glpi_computertypes',
                               'glpi_monitormodels',
                               'glpi_monitortypes',
                               'glpi_networkequipmentmodels',
                               'glpi_networkequipmenttypes',
                               'glpi_peripheralmodels',
                               'glpi_peripheraltypes',
                               'glpi_phonemodels',
                               'glpi_phonetypes',
                               'glpi_printermodels',
                               'glpi_printertypes'];

      if (in_array($searchOption['table'], $dropdown_tables)) {
         return "dropdown";
      }
      return false;
   }

   /**
    * @param $searchOption
    * @param $field
    * @param $itemclass
    */
   static function getFieldsValue($searchOption, $field, $itemclass) {
      global $CFG_GLPI;

      $display = $itemclass->fields[$field['Field']];
      echo "<h3><span class='title'>" . $searchOption['name'] . " : </span>";

      $type = self::getTypeFields($searchOption);
      if ($type == 'dropdown') {
         echo Dropdown::getDropdownName($searchOption['table'], $display);

      } else if (isset($searchOption['datatype'])
                 && $searchOption['datatype'] == 'decimal') {
         echo Html::formatNumber($display, 2);

      } else if ($searchOption['table'] == 'glpi_users') {
         $dbu = new DbUtils();
         echo $dbu->getUserName($display);

      } else if ($searchOption['field'] == 'contact_num') {

         echo "<img src='" . PLUGIN_POSITIONS_WEBDIR . "/pics/miniphones.png' title='" .
              $display . "'>&nbsp;
         <a href=\"tel:" . $display . "\">" .
              $display . "</a>";

      } else if ($searchOption['field'] == 'number_line') {

         echo "<img src='" . PLUGIN_POSITIONS_WEBDIR . "/pics/miniphones.png' title='" .
              $display . "'>&nbsp;
         <a href=\"tel:" . $display . "\">" .
              $display . "</a>";

      } else {
         echo $display;
      }

      echo "</h3></br>";

   }

   /**
    * @param $itemclass
    */
   static function getDirectLink($itemclass) {
      global $CFG_GLPI;

      if (isset($itemclass->fields["name"])
          && !empty($itemclass->fields["name"])
          && $itemclass->getType() != 'Location') {

         echo "<h3>" . __('Direct link', 'positions') . " : ";

         echo "<a href='" . Toolbox::getItemTypeFormURL($itemclass->getType()) .
              "?id=" . $itemclass->fields["id"] . "' target='_blank'>";
         $title  = $itemclass->fields["name"];
         if (Plugin::isPluginActive("resources")
             && $itemclass->getType() == 'PluginResourcesResource') {
            $title = $itemclass->fields["firstname"] . " " . $itemclass->fields["name"];
         }
         echo $title;
         echo "</a>";

         echo "</h3></br>";

      } else {
         echo "<h3>" . __('Direct link', 'positions') . " : ";

         echo "<a href='" . PLUGIN_POSITIONS_WEBDIR .
              "/front/map.php?locations_id=" . $itemclass->fields["id"] . "'
         target='_blank'>";
         $title = $itemclass->fields["name"];
         echo $title;
         echo "</a>";

         echo "</h3></br>";
      }
   }

   /**
    * @param      $itemclass
    * @param bool $export
    *
    * @return string
    */
   static function getCallValue($itemclass, $export = false) {
      global $CFG_GLPI;

      $display = "";
      switch ($itemclass->getType()) {
         case 'PluginResourcesResource' :
            $resID      = $itemclass->fields['id'];
            $entitiesID = $itemclass->fields['entities_id'];
            $restrict   = ["plugin_resources_resources_id" => $resID,
                           "itemtype" => 'User'];
            $dbu        = new DbUtils();
            $infos      = $dbu->getAllDataFromTable('glpi_plugin_resources_resources_items', $restrict);
            if (!empty($infos)) {
               foreach ($infos as $info) {
                  if (isset($info['items_id']) && ($info['items_id'] > 0)) {
                     $userid    = $info['items_id'];
                     $condition = ["users_id"    => $userid,
                                   "is_deleted"  => 0,
                                   "is_template" => 0,
                                   "entities_id" => $entitiesID,
                                   "NOT"         => ["contact_num" => 0]];

                     $phones = $dbu->getAllDataFromTable('glpi_phones', $condition);
                     if (!empty($phones)) {
                        foreach ($phones as $phone) {
                           $contact_num = $phone['contact_num'];
                           $number_line = $phone['number_line'];

                           $location = Dropdown::getDropdownName("glpi_locations",
                                                                 $phone["locations_id"]);

                           if (!$export) {
                              echo "<h3>";
                           }
                           if (isset($contact_num) && $contact_num != null) {
                              if (!$export) {
                                 $dbu = new DbUtils();
                                 if ($dbu->countElementsInTable("glpi_phones", $condition) > 1) {
                                    echo $location . " : <br>";
                                 }
                                 echo "<span class='title'>" . __('Alternate username number') . " : </span>";
                                 echo "<img src='" . PLUGIN_POSITIONS_WEBDIR . "/pics/miniphones.png' title='" .
                                      $contact_num . "'>&nbsp;
                                 <a href=\"tel:" . $contact_num . "\">" .
                                      $contact_num . "</a>&nbsp;&nbsp;&nbsp;&nbsp;";
                              } else {
                                 $display .= "\n" . __('Alternate username number') . " : " . $contact_num;
                              }
                           }
                           if (isset($number_line) && $number_line != null) {
                              if (!$export) {
                                 echo "<span class='title'>" . _x('quantity', 'Number of lines') . " : </span>";
                                 echo "<img src='" . PLUGIN_POSITIONS_WEBDIR . "/pics/miniphones.png' title='" .
                                      $number_line . "'>&nbsp;
                                 <a href=\"tel:" . $number_line . "\">" .
                                      $number_line . "</a>";
                              } else {
                                 $display .= "\n" . _x('quantity', 'Number of lines') . " :\n" . $number_line;
                              }
                           }
                           if (!$export) {
                              echo "</h3>";
                           }

                           if ($export) {
                              return $display;
                           }
                        }
                     }
                  }
               }
            }
            break;
      }
   }
}
