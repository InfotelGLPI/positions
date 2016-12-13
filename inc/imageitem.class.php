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

class PluginPositionsImageItem extends CommonDBTM {
   
   static $rightname    = "plugin_positions";
   
   static function showAllItems($myname,$value_type=0,$value=0,$entity_restrict=-1,$types, $locations_id=-1, $action='showItem') {
      global $DB,$CFG_GLPI;

      $rand=mt_rand();
      $plugin = new Plugin();
      echo "<table border='0'><tr><td>\n";

      if ($myname == 'type') {
         $newtypes = array_flip($types);
         unset($newtypes['Location']);
         unset($newtypes['Netpoint']);
         if ($plugin->isActivated("resources")) {
            unset($newtypes['PluginResourcesResource']);
         }
         $types = array_flip($newtypes);
      }

      $rand = Dropdown::showItemTypes($myname, $types,
                                      array('emptylabel' => Dropdown::EMPTY_VALUE, 'width' => 150));

      $params = array('type'            => '__VALUE__',
                      'value'           => $value,
                      'myname'          => $myname,
                      'action'          => $action,
                      'entity_restrict' => $entity_restrict,
                      'locations_id'    => $locations_id);

      Ajax::updateItemOnSelectEvent("dropdown_$myname$rand", "show_$myname$rand",$CFG_GLPI["root_doc"].
                                    "/plugins/positions/ajax/dropdownAllItems.php",$params);

      echo "</td><td>\n"	;
      echo "<span id='show_$myname$rand'>&nbsp;</span>\n";
      Html::showToolTip(nl2br(__('Types of materials should be created so that the association can exist', 'positions')));
      echo "</td></tr></table>\n";

      if ($value>0) {
         echo "<script type='text/javascript' >\n";
         echo "document.getElementById('item_type$rand').value='".$value_type."';";
         echo "</script>\n";

         $params["typetable"]=$value_type;
         Ajax::updateItem("show_$myname$rand",$CFG_GLPI["root_doc"].
                           "/plugins/positions/ajax/dropdownAllItems.php",$params);

      }

      return $rand;
   }
   
   function getFromDBbyType($itemtype, $type) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '$itemtype'
                      AND `type` = '$type'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
         return false;
      }
      return false;
   }


   function addItemImage($values) {
      global $DB;

      if ($values["type"] != '-1') {
         if ($this->GetfromDBbyType($values["itemtype"],$values["type"])) {
            $this->update(array('id'  => $this->fields['id'],
                                'img' => $values["img"]));
         } else {
            $this->add(array('itemtype' => $values["itemtype"],
                             'type'     => $values["type"],
                             'img'      => $values["img"]));
         }
      } else {
         $query = "SELECT * 
                   FROM `".getTableForItemType($values["itemtype"]."Type")."` ";
         $result = $DB->query($query);
         $number = $DB->numrows($result);
         $i = 0;
         while ($i < $number) {
            $type_table = $DB->result($result, $i, "id");
            if ($this->GetfromDBbyType($values["itemtype"],$type_table)) {
            $this->update(array('id'  => $this->fields['id'],
                                'img' => $values["img"]));
           } else {
             $this->add(array('itemtype' => $values["itemtype"],
                              'type'     => $type_table,
                              'img'      => $values["img"]));
            }
            $i++;
         }
      }
   }

   /**
    * Show dropdown of uploaded files
    *
    * @param $myname dropdown name
   **/
   static function showUploadedFilesDropdown($myname) {
      global $CFG_GLPI;

      if (is_dir(GLPI_PLUGIN_DOC_DIR."/positions/pics")) {
         $uploaded_files = array();

         if ($handle = opendir(GLPI_PLUGIN_DOC_DIR."/positions/pics")) {
            while (false !== ($file = readdir($handle))) {
               if ($file != "." && $file != "..") {
                  $uploaded_files[] = $file;
               }
            }
            closedir($handle);
         }

         if (count($uploaded_files)) {
            $elements = array();
            $elements[-1] = Dropdown::EMPTY_VALUE;
            asort($uploaded_files);
            foreach ($uploaded_files as $key => $val) {
               $elements[$val] = $val;
            }
            Dropdown::showFromArray($myname, $elements);

         } else {
            _e('File not found');
         }

      } else {
         _e("Upload directory doesn't exist");
      }
   }

   function showConfigForm() {
      global $DB,$CFG_GLPI;
      
      echo "<form method='post' action='./imageitem.form.php' name='imageitemform'>";
      
      echo "<table class='tab_cadre_fixe' cellpadding='5'>";
      echo "<tr>";
      echo "<th colspan='5'>";
      echo __('Setup')." : </th>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'><th colspan='5'>";
      _e('Add pictures to use with plugin', 'positions');
      echo "</th></tr>";
      
      echo "<tr class='tab_bg_1'><td colspan='2'>";
      
      echo "<span class='upload' id='container'>";
      
      echo "<img src='../pics/select.png' id='pickfiles' 
            title=\"".__('Select pictures to upload (gif, jpg, png)', 'positions')."\">&nbsp;";
      _e('Select pictures to upload (gif, jpg, png)', 'positions');
      echo "</td><td>";
      echo "<span class='upload' id='filelist'></span>";
      echo "<img src='../pics/upload.png' id='uploadfiles' 
            title=\"".__('upload pictures to the server', 'positions')."\">&nbsp;";
      echo __('Then', 'positions')."&nbsp;";
      _e('upload pictures to the server', 'positions');
      echo "</td><td colspan='2'>";
      
      echo "<a href='".$_SERVER['PHP_SELF']."'><img src='../pics/refresh.png' 
            title=\"".__s('refresh this form','positions')."\"></a>&nbsp;";
      echo __('Then', 'positions')."&nbsp;";
      _e('refresh this form','positions');
      echo "</span>";
      
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_2'><th colspan='5'>";
      _e('Associate images with types of equipment', 'positions');
      echo "</th></tr>";
      
      echo "<tr class='tab_bg_1'><td>";
      $types = PluginPositionsPosition::getTypes();
      self::showAllItems("type",0,0,$_SESSION["glpiactive_entity"],$types,-1, 'showType');
      echo "</td><td>";

      echo "<input type='hidden' name='_glpi_csrf_token' value=''>";
      echo "</td><td>";
      self::showUploadedFilesDropdown("img");
      echo "</td><td>";
      echo "<div id=\"imageitemPreview\"></div>";
      echo "</td><td>";
      echo "<div align='center'><input type='submit' name='add' value=\""._sx('button','Add').
            "\" class='submit' ></div></td></tr>";
      echo "</table>";
      Html::closeForm();

      $query = "SELECT * 
                FROM `".$this->getTable()."` 
                ORDER BY `itemtype`,`type` ASC;";
      $i = 0;
      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         if ($number != 0) {
            echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action='".
                  "./imageitem.form.php'>";
            echo "<div id='liste'>";
            echo "<table class='tab_cadre_fixe' cellpadding='5'>";
            $colspan = 4;
            if ($number > 1) {
               $colspan = 8;
            }
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>";
            _e('List of associations', 'positions');
            echo "</th></tr>";
            echo "<tr>";
            echo "<th class='left'>".__('Equipment', 'positions')."</th>";
            echo "<th class='left'>".__('Equipment type', 'positions')."</th>";
            echo "<th class='left'>".__('Picture', 'positions')."</th><th></th>";
            if ($number > 1) {
               echo "<th class='left'>".__('Equipment', 'positions')."</th>";
               echo "<th class='left'>".__('Equipment type', 'positions')."</th>";
               echo "<th class='left'>".__('Picture', 'positions')."</th><th></th>";
            }
            echo "</tr>";

            while ($ligne = $DB->fetch_assoc($result)) {
               $ID = $ligne["id"];
               if ($i  % 2==0 && $number>1) {
                  echo "<tr class='tab_bg_1'>";
               }
               if ($number == 1) {
                  echo "<tr class='tab_bg_1'>";
               }
               if (!($item = getItemForItemtype($ligne["itemtype"]))) {
                  continue;
               }
               //$item = new $ligne["itemtype"]();
               echo "<td>".$item->getTypeName()."</td>";
               $class = $ligne["itemtype"]."Type";
               $typeclass = new $class();
               $typeclass->getFromDB($ligne["type"]);
               $name = $ligne["type"];
               if (isset($typeclass->fields["name"]))
                  $name = $typeclass->fields["name"];
               echo "<td>".$name."</td>";
               echo "<td>";
               if (!empty($ligne["img"])) {
               $ext = pathinfo($ligne["img"], PATHINFO_EXTENSION);
               echo "<object data='".$CFG_GLPI['root_doc']."/plugins/positions/front/map.send.php?file=".$ligne["img"]."&type=pics' type='image/$ext'>
                      <param name='src' value='".$CFG_GLPI['root_doc'].
                       "/plugins/positions/front/map.send.php?file=".$ligne["img"]."&type=pics'>
                     </object> ";
               } else {
                  _e('No associated picture', 'positions');
               }
               echo "</td>";
               
               echo "<td>";
               echo "<input type='hidden' name='id' value='$ID'>";
               echo "<input type='checkbox' name='item[$ID]' value='1'>";
               echo "</td>";

               $i++;
               if (($i == $number) 
                     && ($number % 2 !=0) 
                        && $number >1) {
                  echo "<td>&nbsp;</td>";
                  echo "<td>&nbsp;</td>";
                  echo "<td>&nbsp;</td>";
                  echo "<td>&nbsp;</td>";
                  echo "</tr>";
               }
            }

            echo "<tr class='tab_bg_1'>";

            if ($number > 1) {
               echo "<td colspan='8' class='center'>";
            } else {
               echo "<td colspan='4' class='center'>";
            }
            echo "<a onclick= \"if (markCheckboxes ('massiveaction_form')) return false;\" 
                  href='#'>".__('Check all')."</a>";
            echo " - <a onclick= \"if ( unMarkCheckboxes ('massiveaction_form') ) return false;\" 
                  href='#'>".__('Uncheck all')."</a> ";
            echo "<input type='submit' name='delete' value=\""._sx('button','Delete permanently').
                     "\" class='submit'>";
            echo "</td></tr>";
            echo "</table>";
            echo "</div>";
            Html::closeForm();
         }
      }
   }


   function displayItemImage($type,$itemtype) {
      
      $image_name = "";
      $restrict = "`itemtype` = '$itemtype'";
      $datas = getAllDatasFromTable($this->getTable(), $restrict);
      
      if (!empty($datas)) {
         foreach ($datas as $data) {
            if ($type == $data["type"]) {
               $image_name = $data["img"];
            }
         }
      }

      return $image_name;
   }

}
?>