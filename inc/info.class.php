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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginPositionsInfo extends CommonDBTM {
   
   public $dohistory=true;
   static $rightname                   = "plugin_positions";
   protected $usenotepad         = true;
   /**
    * Return the localized name of the current Type
    *
    * @return string
    **/
   public static function getTypeName($nb = 0) {
      return _n('Display of equipment', 'Display of equipments', $nb, 'positions');
   }


   function getSearchOptions() {
      $tab = array();
    
      $tab['common']                = self::getTypeName();

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'name';
      $tab[1]['name']               = __('Name');
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['itemlink_type']      = $this->getType();
      $tab[1]['massiveaction']      = false;

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['datatype']           = 'number';
      $tab[2]['massiveaction']      = false;

      $tab[3]['table']              = $this->getTable();
      $tab[3]['field']              = 'fields';
      $tab[3]['name']               = __('Displayed fields', 'positions');
      $tab[3]['massiveaction']      = false;
      $tab[3]['datatype']           = 'specific';
      $tab[3]['additionalfields']   = array('itemtype');

      $tab[4]['table']              = $this->getTable();
      $tab[4]['field']              = 'itemtype';
      $tab[4]['name']               = __('Equipment', 'positions');
      $tab[4]['massiveaction']      = false;
      $tab[4]['datatype']           = 'itemtypename';
      $tab[4]['forcegroupby']       = true;

      $tab[86]['table']             = $this->getTable();
      $tab[86]['field']             = 'is_recursive';
      $tab[86]['name']              = __('Child entities');
      $tab[86]['datatype']          = 'bool';
      
      $tab[14]['table']             = $this->getTable();
      $tab[14]['field']             = 'date_mod';
      $tab[14]['name']              = __('Last update');
      $tab[14]['massiveaction']     = false;
      $tab[14]['datatype']          = 'datetime';
      
      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[30]['table']             = $this->getTable();
      $tab[30]['field']             = 'is_active';
      $tab[30]['name']              = __('Active');
      $tab[30]['datatype']          = 'bool';
      $tab[30]['massiveaction']     = false;

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['datatype']          = 'dropdown';
      
      return $tab;
   }
   
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }
   
   function showForm ($ID, $options=array()) {
      
      if (!$this->canView()) return false;
      
      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='5'>".__('Comments')."&nbsp;:</td>";
      echo "<td rowspan='5'>
         <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'><td>" . __('Active') . "&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".__('Displayed fields', 'positions')."&nbsp;:</td>";
      echo "<td>";
      
      $this->showItemtype($ID, $this->fields['itemtype']);
      
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'><td>".__('Equipment', 'positions')."&nbsp;:</td>";
      echo "<td>";	
      
      self::selectCriterias($this);
      
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
      $datestring = __('Last update').": ";
      $date = Html::convDateTime($this->fields["date_mod"]);
      echo $datestring.$date;
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
   function showItemtype($ID, $value=0) {
      global $CFG_GLPI;

      //Criteria already added : only display the selected itemtype
      if ($ID > 0) {
          $item = new $this->fields['itemtype']();
          echo $item->getTypeName();
          echo "<input type='hidden' name='itemtype' value='".$this->fields['itemtype']."'";

      } else {
      
         $possible_types=PluginPositionsPosition::getTypes();
         
         $restrict = "`is_active` = '1' AND `is_deleted` = '0'";
         $restrict .= getEntitiesRestrictRequest(" AND ","glpi_plugin_positions_infos",'','',
                        $this->maybeRecursive());
         $types = getAllDatasFromTable('glpi_plugin_positions_infos',$restrict);

         if (!empty($types)) {
            foreach ($types as $type) {
               foreach ($possible_types as $key=>$val) {
                  if ($type["itemtype"] == $val)
                     unset($possible_types[$key]);
               }
            }
         }
         //Add criteria : display dropdown
         $options[0] = Dropdown::EMPTY_VALUE;
         foreach ($possible_types as $itemtype) {
            if (class_exists($itemtype)) {
               $item = new $itemtype();
               if ($item->can(-1,READ)) {
                  $options[$itemtype] = $item->getTypeName($itemtype);
               }
            }
         }
         asort($options);
         $rand = Dropdown::showFromArray('itemtype', $options);
         
         $params = array('itemtype' => '__VALUE__',
                         'id'       => $ID);
         Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "span_fields",
                           $CFG_GLPI["root_doc"]."/plugins/positions/ajax/dropdownInfoFields.php",
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

      //Do not add fields in DB with theses types
      $blacklisted_types = array('longtext', 'text');

      echo "<span id='span_fields' name='span_fields'>";

      if (!isset($config->fields['itemtype']) || !$config->fields['itemtype']) {
         echo  "</span>";
         return;
      }

      if (!isset($config->fields['entities_id'])) {
         $config->fields['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      
      $config_fields = explode(',', $config->fields['fields']);
      //Search option for this type
      $target = new $config->fields['itemtype']();
     
      //Construct list
      echo "<span id='span_fields' name='span_fields'>";
      echo "<select name='_fields[]' multiple size='15' style='width:400px'>";

      foreach ($DB->list_fields(getTableForItemType($config->fields['itemtype'])) as $field) {

         $searchOption = $target->getSearchOptionByField('field', $field['Field']);

         if (empty($searchOption)) {
            $table = getTableNameForForeignKeyField($field['Field']);
            if ($table = getTableNameForForeignKeyField($field['Field'])) {
               $crit = getItemForItemtype(getItemTypeForTable($table));
               if ($crit instanceof CommonTreeDropdown) {
                  $searchOption = $target->getSearchOptionByField('field', 'completename', $table);
               } else {
                  $searchOption = $target->getSearchOptionByField('field', 'name', $table);
               }
            }
         }

         if (!empty($searchOption)
             /*&& !in_array($field['Type'],$blacklisted_types)*/
             && !in_array($field['Field'],self::getUnallowedFields($config->fields['itemtype']))) {

            echo "<option value='".$field['Field']."'";
            if (isset($config_fields) && in_array($field['Field'],$config_fields)) {
               echo " selected ";
            }
            echo  ">".$searchOption['name']."</option>";
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
         $input = array();

      } else {
         $input['fields'] = implode(',',$input['_fields']);
         unset($input['_fields']);
      }
      return $input;
   }


   function prepareInputForAdd($input) {
      return self::checkBeforeInsert($input);
   }


   function prepareInputForUpdate($input) {

      $input['fields'] = implode(',',$input['_fields']);
      unset($input['_fields']);

      return $input;
   }
   
   static function getUnallowedFields($itemclass) {
      
      switch ($itemclass) {
         case "Computer" :
            return array('comment',
                         'date_mod', 
                         'notepad',
                         'os_license_number',
                         'os_licenseid',
                         'autoupdatesystems_id',
                         'manufacturers_id',
                         'is_ocs_import');
            break;
         case "Printer" :
            return array('comment',
                         'is_recursive',
                         'date_mod', 
                         'notepad',
                         'have_serial',
                         'have_parallel',
                         'have_usb',
                         'have_wifi',
                         'have_ethernet',
                         'manufacturers_id',
                         'is_global');
            break;
         case "NetworkEquipment" :
            return array('is_recursive',
                         'comment',
                         'date_mod', 
                         'notepad',
                         'ram',
                         'networkequipmentfirmwares_id',
                         'manufacturers_id');
            break;
         case "Monitor" :
            return array('comment',
                         'date_mod', 
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
                         'is_global');
            break;
         case "Peripheral" :
            return array('comment',
                         'date_mod', 
                         'notepad',
                         'brand',
                         'manufacturers_id',
                         'is_global');
            break;
         case "Phone" :
            return array('comment',
                         'date_mod', 
                         'notepad',
                         'brand',
                         'have_headset',
                         'have_hp',
                         'manufacturers_id',
                         'phonepowersupplies_id',
                         'is_global');
            break;
         case "PluginResourcesResource" :
            return array('alert',
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
                         'date_end');
            break;
         default: 
            return array('alert',
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
                         'date_end');
      }
   }
   
   static function showFields($item,$itemclass) {
      global $DB;

      if (isset($item["fields"]) 
         && !empty($item["fields"])) {

         $input = explode(',',$item['fields']);
         $target = new $item['itemtype']();
         foreach ($DB->list_fields(getTableForItemType($item['itemtype'])) as $field) {
            
            if(in_array($field['Field'],$input)) {
               $searchOption = $target->getSearchOptionByField('field', $field['Field']);

               if (empty($searchOption)) {
                  $table = getTableNameForForeignKeyField($field['Field']);
                  if ($table = getTableNameForForeignKeyField($field['Field'])) {
                     $crit = getItemForItemtype(getItemTypeForTable($table));
                     if ($crit instanceof CommonTreeDropdown) {
                        $searchOption = $target->getSearchOptionByField('field', 'completename', $table);
                     } else {
                        $searchOption = $target->getSearchOptionByField('field', 'name', $table);
                     }
                  }
               }
               
               if (!empty($searchOption)
                  && isset($itemclass->fields[$field['Field']])
                   && !empty($itemclass->fields[$field['Field']])
                   && !in_array($field['Field'],self::getUnallowedFields($item['itemtype']))) {

                  self::getFieldsValue($searchOption,$field, $itemclass);
               }
            }
         }
      }
   }
   
   static function getTypeFields($searchOption) {
      
      $dropdown_tables = array('glpi_entities',
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
                               'glpi_printertypes');
      
      if (in_array($searchOption['table'],$dropdown_tables)) {
            return "dropdown";
      }
      return false;
   }
   
   static function getFieldsValue($searchOption,$field,$itemclass) {
      global $CFG_GLPI;
      
      $display = $itemclass->fields[$field['Field']];
      echo "<h3><span class='title'>".$searchOption['name']." : </span>";
      
      $type = self::getTypeFields($searchOption);
      if ($type == 'dropdown') {
         echo Dropdown::getDropdownName($searchOption['table'],$display);
         
      } else if (isset($searchOption['datatype']) 
                  && $searchOption['datatype'] == 'decimal') {
         echo Html::formatNumber($display, 2);
         
      } else if ($searchOption['table'] == 'glpi_users') {
         echo getUserName($display);
         
      } else if ($searchOption['field'] == 'contact_num') {
         
         echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/positions/pics/miniphones.png' title='".
         $display."'>&nbsp;
         <a href=\"tel:".$display."\">".
         $display."</a>";
         
      } else if ($searchOption['field'] == 'number_line') {
         
         echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/positions/pics/miniphones.png' title='".
         $display."'>&nbsp;
         <a href=\"tel:".$display."\">".
         $display."</a>";
         
      } else {
         echo $display;
      }
      
      echo "</h3></br>";

   }
   
   static function getDirectLink($itemclass) {
      global $CFG_GLPI;

      if (isset($itemclass->fields["name"]) 
            && !empty($itemclass->fields["name"]) 
               && $itemclass->getType() != 'Location' ) {
               
         echo "<h3>".__('Direct link', 'positions')." : ";

         echo "<a href='".Toolbox::getItemTypeFormURL($itemclass->getType()).
         "?id=".$itemclass->fields["id"]."' target='_blank'>";
         $title = $itemclass->fields["name"];
         $plugin = new Plugin();
         if ($plugin->isActivated("resources")
            && $itemclass->getType() == 'PluginResourcesResource') {
            $title= $itemclass->fields["firstname"]." ".$itemclass->fields["name"];
         }
         echo $title;
         echo "</a>";
         
         echo "</h3></br>";
         
      } else {
         echo "<h3>".__('Direct link', 'positions')." : ";
         
         echo "<a href='".$CFG_GLPI['root_doc'].
         "/plugins/positions/front/map.php?locations_id=".$itemclass->fields["id"]."'
         target='_blank'>";
         $title = $itemclass->fields["name"];
         echo $title;
         echo "</a>";
         
         echo "</h3></br>";
      }
   }
   
   static function getCallValue($itemclass, $export=false) {
      global $CFG_GLPI;
      
      $plugin = new Plugin();
      $display = "";
      switch ($itemclass->getType()) {
         /*case 'Phone' :
            if (isset($itemclass->fields['contact_num']) 
               && !empty($itemclass->fields['contact_num'])) {
               
               $contact_num= $itemclass->fields["contact_num"];
               
               if (!$export) {
                  echo "<span class='title'>".__('Alternate username number')." : </span>";
                  echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/positions/pics/miniphones.png' title='".
                  $contact_num."'>&nbsp;
                  <a href=\"tel:".$contact_num."\">".
                  $contact_num."</a>&nbsp;&nbsp;&nbsp;&nbsp;";
               } else {
                  $display.="\n".__('Alternate username number')." : ".$contact_num;
               }
            }
            if (isset($itemclass->fields['number_line']) 
               && !empty($itemclass->fields['number_line'])) {
               
               $directline= $itemclass->fields["number_line"];
               
               if (!$export) {
                  echo "<span class='title'>"._x('quantity', 'Number of lines')." : </span>";
                  echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/positions/pics/miniphones.png' title='".
                  $directline."'>&nbsp;
                  <a href=\"tel:".$directline."\">".
                  $directline."</a>";
               } else {
                  $display.="\n"._x('quantity', 'Number of lines')." : ".$directline;
               }
            }
            if ($export) {
               return $display;
            }
            break;*/
         case 'PluginResourcesResource' :
            $resID=$itemclass->fields['id'];
            $entitiesID=$itemclass->fields['entities_id'];
            $restrict = "`plugin_resources_resources_id` = $resID AND `itemtype` = 'User' " ;
            $infos = getAllDatasFromTable('glpi_plugin_resources_resources_items',$restrict);
            if (!empty($infos)) {
               foreach ($infos as $info) {
                  if (isset($info['items_id']) && ($info['items_id']>0)) {
                     $userid=$info['items_id'];
                     $condition = "`users_id` = '$userid '
                                    AND `is_deleted` = '0' 
                                    AND `is_template` = '0' 
                                    AND `entities_id` = '$entitiesID'
                                    AND `contact_num` != 0 ";
                                    
                     $phones = getAllDatasFromTable('glpi_phones',$condition);
                     if (!empty($phones)) {
                        foreach ($phones as $phone) {
                           $contact_num=$phone['contact_num'];
                           $number_line=$phone['number_line'];
                           
                           $location=Dropdown::getDropdownName("glpi_locations",
                                                                     $phone["locations_id"]);
                           
                           if (!$export) {
                              echo "<h3>";
                           }
                           if (isset($contact_num) && $contact_num!=NULL) {
                              if (!$export) {
                                 if (countElementsInTable("glpi_phones",$condition) > 1) {
                                    echo $location." : <br>";
                                 }
                                 echo "<span class='title'>".__('Alternate username number')." : </span>";
                                 echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/positions/pics/miniphones.png' title='".
                                 $contact_num."'>&nbsp;
                                 <a href=\"tel:".$contact_num."\">".
                                 $contact_num."</a>&nbsp;&nbsp;&nbsp;&nbsp;";
                              } else {
                                 $display.="\n".__('Alternate username number')." : ".$contact_num;
                              }
                           }
                           if (isset($number_line) && $number_line!=NULL) {
                              if (!$export) {
                                 echo "<span class='title'>"._x('quantity', 'Number of lines')." : </span>";
                                 echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/positions/pics/miniphones.png' title='".
                                 $number_line."'>&nbsp;
                                 <a href=\"tel:".$number_line."\">".
                                 $number_line."</a>";
                              } else {
                                 $display.="\n"._x('quantity', 'Number of lines')." :\n".$number_line;
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
?>