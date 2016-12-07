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

class PluginPositionsPosition extends CommonDBTM {

   public $dohistory = true;
   static $rightname                   = "plugin_positions";
   protected $usenotepad              = true;
   
   static $types = array('Computer',
                          'Monitor',
                          'NetworkEquipment',
                          'Peripheral',
                          'Printer',
                          'Phone',
                          'Location',
                          'Netpoint'
   );

   /**
    * Return the localized name of the current Type
    *
    * @return string
    **/
   public static function getTypeName($nb = 0) {
      return _n('Cartography', 'Cartographies', $nb, 'positions');
   }
   
   
   function getRights($interface='central') {

      $values = parent::getRights();
      
      if ($interface == 'helpdesk') {
         unset($values[CREATE], $values[UPDATE], $values[DELETE], $values[PURGE], $values[READNOTE], $values[UPDATENOTE]);
      }
      return $values;
   }

   //if item deleted
   static function purgePositions($item) {
      $temp = new self();

      $type = get_class($item);
      $temp->deleteByCriteria(array('itemtype' => $type,
                                    'items_id' => $item->getField('id')), 1);
      return true;
   }

   /**
   * For other plugins, add a type to the linkable types
   *
   * @param $type string class name
   **/
   static function registerType($type) {
      if (!in_array($type, self::$types)) {
         self::$types[] = $type;
      }
   }

   /**
   * Type than could be linked to a position
   *
   * @param $all boolean, all type, or only allowed ones
   *
   * @return array of types
   **/
   static function getTypes($all = false) {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }
      }
      return $types;
   }
   
   function checkValues($input){
      $values_to_check = array('width', 'height', 'outlineWidth', 'hideTooltip', 'hideLabel');
      foreach ($values_to_check as $field) {
         if (in_array($field, $input)) {
            $input[$field] = empty($input[$field]) ? 0 : $input[$field];
         }
      }

      if (!isset($input['hideTooltip']) || empty($input['hideTooltip'])) {
         $input['hideTooltip'] = 0;
      }
      if (!isset($input['hideLabel'])|| empty($input['hideLabel'])) {
         $input['hideLabel'] = 0;
      }

      return $input;
   }

   function prepareInputForAdd($input) {
      if (!isset ($input["items_id"]) 
            || !isset($input["itemtype"])) {
         Session::addMessageAfterRedirect(__('No equipment associated', 'positions'), false, ERROR);
         return array();
      }
      
//      if (isset ($input["items_id"]) 
//            && isset($input["itemtype"])) {
//         $restrict = "`items_id` = '" . $input["items_id"] . "'
//                  AND `itemtype` = '" . $input["itemtype"] . "'";
//         if (countElementsInTable("glpi_plugin_positions_positions", $restrict) != 0) {
//            Session::addMessageAfterRedirect(__('This item is already bound to a location', 'positions'), false, ERROR);
//            return array();
//         }
//      }

      if (!isset ($input["name"]) 
            || empty($input["name"])) {
         $item = new $input['itemtype'];
         if ($item->getFromDB($input['items_id'])) {
            $input['name'] = $item->fields["name"];
         }
      }
      
      if (!isset ($input["x_coordinates"]) 
            || empty($input["x_coordinates"]) 
               || !isset ($input["y_coordinates"]) 
                  || empty($input["y_coordinates"])) {
         $input["x_coordinates"] = '-1000';
         $input["y_coordinates"] = '-225';
      }
      
      $input = $this->checkValues($input);
      
      return $input;
   }
   
   function prepareInputForUpdate($input) {
      
      $input = $this->checkValues($input);

      return $input;
   }

   function post_addItem() {
      global $CFG_GLPI;

      if (!isset($this->input["massiveaction"])) {
         
         Html::redirect($CFG_GLPI["root_doc"] .
                 "/plugins/positions/front/coordinates.form.php?id=" . $this->getField('id'));
      }
   }

   function getSearchOptions() {

      $tab = array();

      $tab['common']           = self::getTypeName();

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'x_coordinates';
      $tab[3]['name']          = __('Coordinate x', 'positions');

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'y_coordinates';
      $tab[4]['name']          = __('Coordinate y', 'positions');
      
      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'items_id';
      $tab[5]['name']          = __('Associated element');
      $tab[5]['massiveaction'] = false;
      $tab[5]['nosearch']      = true;

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'date_mod';
      $tab[6]['name']          = __('Last update');
      $tab[6]['massiveaction'] = false;
      $tab[6]['datatype']      = 'datetime';

      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'is_recursive';
      $tab[7]['name']          = __('Child entities');
      $tab[7]['datatype']      = 'bool';

      $tab[30]['table']        = $this->getTable();
      $tab[30]['field']        = 'id';
      $tab[30]['name']         = __('ID');
      $tab[30]['datatype']     = 'number';

      $tab[80]['table']        = 'glpi_entities';
      $tab[80]['field']        = 'completename';
      $tab[80]['name']         = __('Entity');
      $tab[80]['datatype']     = 'dropdown';

      return $tab;
   }

   function defineTabs($options = array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == __CLASS__) {

            return __('See the map', 'positions');

         } else if ($item->getType() == "Location") {

            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(Document::getTypeName(Session::getPluralNumber()),
                                                  Document_Item::countForItem($item));
            }
            return Document::getTypeName(Session::getPluralNumber());

         } else if (in_array($item->getType(), self::getTypes(true))
             && Session::haveRight('plugin_positions', READ)) {

            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()),
                                                  self::countForItem($item));
            }
            return self::getTypeName(Session::getPluralNumber());
         }
      }

      return '';

   }
   
   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_plugin_positions_positions',
                                  "`itemtype`='".$item->getType()."'
                                   AND `items_id` = '".$item->getID()."'");
   }
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      $self = new self();

      if ($item->getType() == __CLASS__) {

         self::showAddPosition($item);

      } else if ($item->getType() == "Location") {

         Document_Item::showForItem($item, $withtemplate);

      } else if (in_array($item->getType(), self::getTypes(true))) {

         $self->showPluginFromItems(get_class($item), $item->getField('id'));

      }
      return true;
   }

//   function cleanDBonPurge() { 
//      // If a position item is deleted from the canvas : remove the picture linked
//      $doc_item = new Document_Item(); 
//      $doc_found = $doc_item->find("`items_id` = '".$this->fields['items_id']."' AND `itemtype` = '".$this->fields['itemtype']."'", '`date_mod` DESC', '1');
//      $doc_item->delete(array('id' => key($doc_found)), 1);
//   }
   
   /**
   * Return the SQL command to retrieve linked object
   *
   * @return a SQL command which return a set of (itemtype, items_id)
   */
   function getSelectLinkedItem() {
      return "SELECT `itemtype`, `items_id`
           FROM `glpi_plugin_positions_positions`
           WHERE `id`='" . $this->fields['id'] . "'";
   }

   /**
   * Affiche la carte en png pour la couper selon les différentes pièces
   *
   */
   static function  showMapCreateLocation($options = array()) {
      global $DB, $CFG_GLPI;
      
      $document_id         = $options["document_id"];
      $locations_idParent  = $options["locations_id"];
      $test = '';

      if (!Session::haveRight('plugin_positions', READ)) return false;

      $alert = __('Select an area and click on OK', 'positions');
      echo "<script type='text/javascript'>
         jQuery(function($){
         $('#target').Jcrop({
            onChange:   showCoords,
            onSelect:   showCoords,
            onRelease:  clearCoords
            });
         });
      </script>";

      echo "<script type='text/javascript'>
         function showCoords(c) {
         $('#x1').val(c.x);
         $('#y1').val(c.y);
         $('#x2').val(c.x2);
         $('#y2').val(c.y2);
         $('#w').val(c.w);
         $('#h').val(c.h);
         $('#document_id').val($document_id);
      };
         function clearCoords(){
         $('coords input').val('');
      };

         function checkCoords()
      {
         if (parseInt(jQuery('#w').val())>0) return true;
         if ('#name'=='') return true;
         alert('$alert');
         return false;
      }

         function showlist(divName,etat)
      {
         if(divName == 'existLocation'){
            document.getElementById(divName).style.visibility=etat;
            document.getElementById('newLocation').style.visibility='hidden';
            $('#test').val('existLocation');
         }
         else if(divName == 'newLocation'){
            document.getElementById(divName).style.visibility=etat;
            document.getElementById('existLocation').style.visibility='hidden';
            $('#test').val('newLocation');
            $('#locations_idParent').val($locations_idParent);
         }
      }
      </script>";

      $Doc = new Document();
      if ($Doc->getFromDB($document_id)) {
      
         $entities_id = $Doc->fields["entities_id"];
         $path = GLPI_DOC_DIR . "/" . $Doc->fields["filepath"];
         $img = $CFG_GLPI['root_doc'] . "/plugins/positions/front/map.send.php?docid=" . $document_id;
         $dim = getimagesize($path);
         $extension = pathinfo($path, PATHINFO_EXTENSION);

         if ($extension == 'PNG' 
               || $extension == 'JPEG' 
                  || $extension == 'JPG' 
                     || $extension == 'GIF') {
           
            echo"<form action=\"crop.form.php\" method=\"post\" onsubmit=\"return checkCoords();\" >";

            //Liste des lieux existants
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>";
            _e('Creation of sub-areas on the map', 'positions');
            echo "</th></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<input type=\"radio\" name=\"choice\" value=\"existLocation\" 
                     onclick=\"showlist('existLocation','visible')\">";
            _e('Choose an existing location', 'positions');
            echo "</td>";

            echo "<td id=\"existLocation\" class='left' style=\"visibility:hidden\">";
            $query = "SELECT `id`,`name`
                            FROM `glpi_locations`
                            WHERE `locations_id` = '" . $locations_idParent . "'
                            AND `name` NOT IN
                                ( SELECT `name` FROM `glpi_documents`
                                 )
                                ";
            $result = $DB->query($query);
            $number = $DB->numrows($result);

            $locations = array();
            while ($data = $DB->fetch_assoc($result)) {

                $locations[] = $data['id'];
            }
            $condition = "";
            if (!empty($locations)) {

               $condition = "`id` IN (" . implode(',', $locations) . ")";

               Dropdown::show('Location', array('value' => $locations_idParent,
                    'entity' => $_SESSION["glpiactive_entity"],
                    'condition' => $condition));
            } else {
               _e('No sub-area found', 'positions');
            }
            echo "</td>";
            echo "</tr>";
               
            //Ajout d'un nouveau lieu
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<input type=\"radio\" checked name=\"choice\" value=\"newLocation\" 
                     onclick=\"showlist('newLocation','visible')\">";
            _e('Create a new location', 'positions');
            echo "</td>";

            echo "<td id=\"newLocation\" class='left' style=\"visibility:hidden\">";
            echo __('Name')." : ";
            echo "<input type=\"text\" size=\"50\" id=\"name\" name=\"name\" />";
            echo "</td>";
            echo "</tr>";

            //Validation
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan=2>";
            echo "<input type=\"hidden\" id=\"x1\" name=\"x1\" />";
            echo "<input type=\"hidden\" id=\"y1\" name=\"y1\" />";
            echo "<input type=\"hidden\" id=\"x2\" name=\"x2\" />";
            echo "<input type=\"hidden\" id=\"y2\" name=\"y2\" />";
            echo "<input type=\"hidden\" id=\"w\" name=\"w\" />";
            echo "<input type=\"hidden\" id=\"h\" name=\"h\" />";
            echo "<input type=\"hidden\" id=\"extension\" name=\"extension\" value=\"$extension\" />  ";
            echo "<input type=\"hidden\" id=\"test\" name=\"test\" value=\"$test\" />";
            echo "<input type=\"hidden\" id=\"document_id\" name=\"document_id\" />";
            echo "<input type=\"hidden\" id=\"locations_idParent\" name=\"locations_idParent\" value=\"$locations_idParent\" />";
            echo "<input type=\"hidden\" id=\"entities_id\" name=\"entities_id\" value=\"$entities_id\" />";
            
            //Case à cocher pour continuer l'ajout
            echo "<input type=\"checkbox\" id=\"continueAdd\" name=\"continueAdd\">&nbsp;";
            _e('Add and continue', 'positions');
            echo "&nbsp;&nbsp;<input type=\"submit\" name=\"valid\" value='"._sx('button','Add')."' class='submit'>";
            echo "</td>";
            echo "</tr>";

            echo "</table>";

            echo "<table class='tab_cadre'>";
            echo "<tr class='tab_bg_1'><th>";
            echo $Doc->fields["name"];
            echo "<div id=\"imageCrop\"> ";
            echo "<img src='" . $img . "' id=\"target\" alt=\"$document_id\" width=\"$dim[0]\" height=\"$dim[1]\" />";
            echo "</div> ";
            echo "</th></tr>";
            echo "</table>";
            
            echo "<script type='text/javascript'>";
            echo "showlist('newLocation','visible');";
            echo "</script>";

            Html::closeForm();
         }
      } else {

         Html::redirect($CFG_GLPI["root_doc"] .
             "/plugins/positions/front/map.php?locations_id=" . $locations_idParent);
      }
   }
   
   static function cropPicture($input) {

      $x1            = $input['x1'];
      $y1            = $input['y1'];
      $x2            = $input['x2'];
      $y2            = $input['y2'];
      $width         = $input['w'];
      $height        = $input['h'];
      
      if ($input['test'] == "newLocation") {
      
         $name       = $input['name'];
         
      } else if ($input['test'] == "existLocation") {
         $loc = new Location();
         $loc->getFromDB($input['locations_id']);
         $name = $loc->fields['name'];
      }

      $document_id   = $input['document_id'];
      $extension     = $input['extension'];

      if (isset($input['locations_id']) ){
         $locations_id  = $input['locations_id'];
      } else {
         $locations_id  = $input['locations_idParent'];
      }
      
      $locations_idParent  = $input['locations_idParent'];
      $entities_id  = $input['entities_id'];
      $test  = $input['test'];

      $Doc = new Document();
      $Doc->getFromDB($document_id)  ;
      $path = GLPI_DOC_DIR."/".$Doc->fields["filepath"];

      if($extension=='PNG') {
      
         $srcImg  = imagecreatefrompng($path);
         $newImg  = imagecreatetruecolor($width, $height);

         imagecopyresampled($newImg, $srcImg, 0, 0, $x1, $y1, $width, $height, $width, $height);

         imagepng($newImg, GLPI_DOC_DIR."/_uploads/".$document_id.$name.'.png');
         
      } else if($extension=='JPG' || $extension=='JPEG') {
      
         $srcImg  = imagecreatefromjpeg($path);
         $newImg  = imagecreatetruecolor($width, $height);

         imagecopyresampled($newImg, $srcImg, 0, 0, $x1, $y1, $width, $height, $width, $height);

         imagejpeg($newImg, GLPI_DOC_DIR."/_uploads/".$document_id.$name.'.jpg');
         
      } else if($extension=='GIF') {
      
         $srcImg  = imagecreatefromgif($path);
         $newImg  = imagecreatetruecolor($width, $height);

         imagecopyresampled($newImg, $srcImg, 0, 0, $x1, $y1, $width, $height, $width, $height);

         imagegif($newImg, GLPI_DOC_DIR."/_uploads/".$document_id.$name.'.gif');
      }
      
      //on test si l'utilisateur à cocher la case pour continuer l'ajout de sous lieu
      //Si oui : on redirige vers la carte PNG
      $checked = 'off';
      if(!empty($input['continueAdd'])) {
         $checked = $input['continueAdd'];
      }

      //Si non : on envoie l'utilisateur vers la carte pour ajouter du matériel
      $options = array("document_id"        => $document_id,
                        "name"               => $name,
                        "locations_idParent" => $locations_idParent,
                        "locations_id"       => $locations_id,
                        "entities_id"        => $entities_id,
                        "test"               => $test,
                        "extension"          => strtolower($extension),
                        "checked"            => $checked);
      
      return $options;
   }

    /**
     * Résultat de la création du sous-lieu + ajout dans la base de données
     */
   static function showFormResCreateLocation($opt) {
      global $CFG_GLPI, $DB;

      $filename = $opt["document_id"] . $opt["name"] . "." . $opt["extension"];
      $filepath = "/_uploads/" . $filename;

      $id = 0;
      if ($opt["test"] == "existLocation") {
         $query = "SELECT `id`,`name`
                  FROM `glpi_locations`
                  WHERE `id` = '" . $opt["locations_id"] . "'";

         $result = $DB->query($query);

         while ($data = $DB->fetch_assoc($result)) {
            $name = $data['name'];
            $id = $data['id'];
         }
      } else if ($opt["test"] == "newLocation") {
         $locations_id = $opt["locations_idParent"];
      }

      $params = array(
         "name"         => $opt["name"],
         "document_id"  => $opt["document_id"],
         "filepath"     => $filepath,
         "filename"     => $filename,
         "entities_id"  => $opt["entities_id"],
         "locations_id" => $opt["locations_id"],
         "id"           => $id,
         "itemtype"     => "Location",
      );

      //FONCTION QUI PERMET D'AJOUTER LE LIEU DANS LA BASE DE DONNEES
      $dropdown = new Location();

      //AJOUT DU LIEU
      if ($opt["test"] == 'newLocation') {
         if ($newID = $dropdown->add($params)) {
         } else {
            $locations_found = $dropdown->find("`name` = '".$params['name']."' AND `entities_id` = '".$params['entities_id']."' AND `locations_id` = '".$params['locations_id']."'", '', '1');
            $newID = key($locations_found);
         }
         $opt["locations_id"] = $newID;
      }

      if ($opt["locations_id"] != $opt["locations_idParent"]) {
         //AJOUT DU DOC ASSOCIE AU LIEU
         $doc = new Document();
         $documentitem = new Document_Item();
         $self = new self();

         $input = array();
         $input["entities_id"] = $opt["entities_id"];
         $input["name"] = $opt["name"];
         $input["upload_file"] = $filename;
         //$input["documentcategories_id"]=$options["rubrique"];
         //$input["mime"]="text/html";
         $input["date_mod"] = date("Y-m-d H:i:s");
         $input["users_id"] = Session::getLoginUserID();

         $newdoc = $doc->add($input);
         
         // Add new location
         if ($opt["test"] == 'newLocation') {
            // We check if the element already exists
            $restrict = "`items_id` = '".$newID."' AND `itemtype` = 'Location'";
            if (countElementsInTable("glpi_plugin_positions_positions", $restrict) != 0) {
               Session::addMessageAfterRedirect(__('This item is already bound to a location', 'positions'), false, ERROR);
               Html::redirect($CFG_GLPI["root_doc"] .
                    "/plugins/positions/front/map.php?locations_id=" . $opt["locations_idParent"]);
               
            // If not we can add its position and picture
            } else {
               $documentitem->add(array('documents_id' => $newdoc,
                                          'itemtype' => 'Location',
                                          'items_id' => $newID,
                                          'entities_id' => $opt["entities_id"]));
               $param = array(
                   "items_id" => $newID,
                   "entities_id" => $opt["entities_id"],
                   "locations_id" => $opt["locations_idParent"],
                   "itemtype" => "Location",
                   "x_coordinates" => -800,
                   "y_coordinates" => -150
               );

               $self->add($param);

               if ($opt["checked"] == 'on') {
                  self::showMapCreateLocation($opt);
               }
               else if ($opt["checked"] == 'off') {
                   Html::redirect($CFG_GLPI["root_doc"] .
                       "/plugins/positions/front/map.php?locations_id=" . $opt["locations_id"]);
               }
            }
            
         // Add existing location
         } else if ($opt["test"] == 'existLocation') {
         
            $documentitem->add(array('documents_id' => $newdoc,
                'itemtype' => 'Location',
                'items_id' => $id));

            $param = array(
                "items_id" => $id,
                "entities_id" => $opt["entities_id"],
                "locations_id" => $opt["locations_idParent"],
                "itemtype" => "Location",
                "x_coordinates" => -800,
                "y_coordinates" => -150
            );
            $self->add($param);

            if ($opt["checked"] == 'on') {
               self::showMapCreateLocation($opt);
            }
            else if ($opt["checked"] == 'off') {
                Html::redirect($CFG_GLPI["root_doc"] .
                    "/plugins/positions/front/map.php?locations_id=" . $opt["locations_id"]);
            }
         }
      } else {
         Session::addMessageAfterRedirect(__('This item is already bound to a location', 'positions'), false, ERROR);
         Html::redirect($CFG_GLPI["root_doc"] .
                    "/plugins/positions/front/map.php?locations_id=" . $opt["locations_id"]);
      }
   }

   function showForm($ID, $options = array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('Name') . ": </td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td>" . __('Coordinate x', 'positions') . ": </td>";
      echo "<td>";
      if ($ID > 0)
         Html::autocompletionTextField($this, "x_coordinates", array('size' => "10"));
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('Associated element') . ": </td>";
      echo "<td>";

      if ($ID < 0 
         || (!$this->fields['itemtype'] 
            && !$this->fields['items_id'])) {
         $types = self::getTypes();

         Dropdown::showAllItems("items_id", 0, 0, (
         $this->fields['is_recursive'] ? -1 :
         $this->fields['entities_id']), self::getTypes());
      } else {
         if ($this->fields['itemtype'] 
            && $this->fields['items_id']) {
            if (class_exists($this->fields['itemtype'])) {
               $item = new $this->fields['itemtype']();
               $item->getFromDB($this->fields['items_id']);
               echo $item->getTypeName() . " - ";
               echo $item->getLink() . " - ";
               echo Dropdown::getDropdownName("glpi_locations", $item->fields['locations_id']);
            }
         }
      }
      echo "</td>";

      echo "<td>" . __('Coordinate y', 'positions') . ": </td>";
      echo "<td>";
      if ($ID > 0) {
         Html::autocompletionTextField($this, "y_coordinates", array('size' => "10"));
      }
      echo "</td>";

      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   static function showAddPosition($item) {

      if ($item->getField('id') > 0
         && Session::haveRight('plugin_positions', READ)) {
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' class='center'>";
         echo "<a href='./coordinates.form.php?id=" . $item->getField('id') . "'>" .
            __('Change the coordinates', 'positions'). "</a>";
         echo "</td>";
         echo "</tr>";
         echo "</table></div>";
      }
   }

   static function getDocument($locations_id) {
      global $DB;

      $documents_id = 0;
      $query = "SELECT `documents_id`
             FROM `glpi_documents_items`
             WHERE `items_id` = '" . $locations_id . "'
                   AND `itemtype` = 'Location' ";

      $result = $DB->query($query);
      while ($data = $DB->fetch_assoc($result)) {
         $documents_id = $data['documents_id'];
      }
      return $documents_id;
   }


   static function getItems($locations_id) {

      $items = array();
      foreach (self::getTypes() as $key => $item) {
         $table = getTableForItemType($item);
         $itemclass = new $item();
         $restrict = "`is_template` = '0' AND `is_deleted` = '0'";
         $restrict .= " AND `locations_id` = '" . $locations_id . "'";
         $restrict .= getEntitiesRestrictRequest(" AND ", $table, '', '',
             $itemclass->maybeRecursive());
         $datas = getAllDatasFromTable($table, $restrict);
         if (!empty($datas)) {
            foreach ($datas as $data) {
               $items[$item][] = $data["id"];
            }
         }
      }
      return $items;
   }

   static function getMapItems($locations_id) {

      $itemsMap = array();
      $restrict = getEntitiesRestrictRequest(" ", "glpi_plugin_positions_positions", '', '',
         true);
      $datas = getAllDatasFromTable("glpi_plugin_positions_positions", $restrict);
      if (!empty($datas)) {
         foreach ($datas as $data) {
            $itemsMap[$data['itemtype']][] = $data;
         }
      }
      return $itemsMap;
   }

   /**
   * @static function showMap : affiche tous les éléments de la carte (menus, onglets...)
   * @param $options
   */
   static function showMap($options) {
      global $CFG_GLPI;

      if (!$options['locations_id']) {
         $self = new self();
         $self->getFromDB($options["id"]);
         if (isset($self->fields["itemtype"])) {
            $options['locations_id'] = $self->fields['locations_id'];
         }
      }
      
      if ($options['locations_id']) {

         $documents_id = self::getDocument($options['locations_id']);

         $Doc = new Document();
         if (isset($documents_id) && $Doc->getFromDB($documents_id)) {

            $params = array("locations_id" => $options['locations_id'],
                            "id"           => $options['id'],
                            "itemtype"     => $options['itemtype'],
                            "target"       => $options['target'],
            );

            $params['docid'] = $documents_id;
            $path = GLPI_DOC_DIR . "/" . $Doc->fields["filepath"];

            if ($handle = fopen($path, "r")) {
               $infos_image = @getImageSize($path);
               $params["largeur"] = $infos_image[0];
               $params["hauteur"] = $infos_image[1];

               $params["download"] = 1;
               if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
                  $params["download"] = 0;
               }

               echo "<div class='center'><table class='plugin_positions_tab_cadre_fixe'>";
               echo "<tr class='tab_bg_2' valign='top'>";

               $items = self::getMapItems($params['locations_id']);

               if (!isset($options['menuoff'])) {
                  echo "<td>";
                  self::showLocationForm($params["locations_id"], "100%");
                  echo "</td>";
               }
               if (Session::haveRight('plugin_positions', UPDATE)
                     && !isset($options['menuoff'])) {
                  echo "<td>";
                  self::showAddFromPlugin($params['locations_id']);
                  echo "</td>";
                  echo "<td>";
                  self::selectDisplay($params, $items);
                  echo "</td>";
               }
               echo "</tr>";
               echo "</table></div>";

               if (isset($options['menuoff'])) $params["menuoff"] = $options['menuoff'];

               if (Session::haveRight('plugin_positions', UPDATE)
                     && !isset($options['menuoff'])) {
                  echo "<form method='post' name='pointform' id='pointform' action=\"" .
                  $CFG_GLPI["root_doc"] . "/plugins/positions/front/position.form.php\">";

                  echo "<div class='center'>";
                  echo "<table class='plugin_positions_tab_cadre_fixe' width='30%'>";

                  if ($options['id']) {
                     echo "<tr class='tab_bg_2'>";
                     $form = Toolbox::getItemTypeFormURL($self->getType());
                     echo "<td colspan='4' class='center'>" .
                     $self->getLink();
                     echo "</td></tr>";
                  }
                  echo "<tr class='tab_bg_2'>";
                  echo "<td colspan='2' class='center'>";
                  echo "<input type='submit' name='update' value=\"" .
                  __s('Change the coordinates', 'positions') . "\" class='submit'>";
                  echo "</td>";
                  //création d'un nouveau bouton pour la création de nouvelles pièces
                  echo "<td colspan='2' class='center'>";
                  echo "<input type='submit' name='addLocation' value=\"" .
                  __s('Add a sub-area', 'positions') . "\" class='submit'>";
                  echo "</td>";
                  
                  echo "<input type='hidden' name='locations_id' value='" .
                  $options['locations_id'] . "'>";
                  echo "<input type='hidden' name='id' value='" . $options['id'] . "'>";
                  echo "<input type='hidden' name ='x_coordinates'>";
                  echo "<input type='hidden' name ='y_coordinates'>";
                  echo "<input type='hidden' name ='multi'>";
                  echo "<input type='hidden' name ='referrer' value='" . $options['id'] . "'>";
                  echo "</tr>";
                  echo "</table></div>";
               }

               echo "<div class='center'><table class='tab_cadre_fixe'>";

               echo "<tr class='tab_bg_1'><th>";
               echo $Doc->fields["name"];
               echo "</th></tr>";

               echo "<tr class='tab_bg_1'><td>";

               self::displayMap($items, $params);
               echo "</td></tr>";
               echo "</table>";

               if (Session::haveRight('plugin_positions', UPDATE) 
               && (!isset($options['menuoff']))) {
                  Html::closeForm();
               }
            } else {
               echo "<div class='center'>";
               _e('No location has a configured map', 'positions');
               echo "</div>";
            }
         } else {
            echo "<div class='center'>";
            echo __('The object location does not match a map', 'positions') . "<br><br>";
            Html::displayBackLink();
            echo "</div>";
         }
      } else {
         echo "<div class='center'>";
         _e('No location selected', 'positions');
         echo "</div>";
      }
   }

   /**
   * @static function showLocationForm : affiche le formulaire contenant la liste des lieux
   * @param $locations_id : id du lieu
   */
   static function showLocationForm($locations_id, $width = "30%", $display = false) {
      global $CFG_GLPI, $DB;

      $locations = array();
      
      $target = $CFG_GLPI["root_doc"] .
      "/plugins/positions/front/map.php";
      echo "<form method='post' id='locationform' action='$target'>";
      if ($display) {
         echo "<table>";
         echo "<tr><td class='center'>";
      } else {
         echo "<table class='tab_cadrehov'>";
         echo "<tr class='tab_bg_2'><td class='center'>";
      }

      $query = "SELECT `items_id`
               FROM `glpi_documents_items`
               WHERE `itemtype` = 'Location' ";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      while ($data = $DB->fetch_assoc($result)) {
         $locations[] = $data['items_id'];
      }
      $condition = "";
      if (!empty($locations)) {
         $condition = "`glpi_locations`.`id` IN (" . implode(',', $locations) . ")";

         Dropdown::show('Location', array('value'     => $locations_id,
                                          'entity'    => $_SESSION["glpiactiveentities"],
                                          'condition' => $condition,
                                          'width' => $width));
         echo "</td></tr>";
         if ($display) {
            echo "<tr>";
         } else {
            echo "<tr class='tab_bg_1'>";
         }
         echo "<td class='center'>";
         echo "<input type='submit' name='export' value=\"" .
         __s('See the map', 'positions') . "\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
      } else {
         _e('No location has a configured map', 'positions');
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
      }
   }

   static function showAddFromPlugin($locations_id) {
      global $CFG_GLPI;

      if (!Session::haveRight('plugin_positions', UPDATE)) return false;

      echo "<div align='center'>";
      echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] .
      "/plugins/positions/front/position.form.php\" name='addfromplugin' id='addfromplugin'>";
      
      $entity = $_SESSION["glpiactive_entity"];
      $loc = new Location();
      if ($loc->getFromDB($locations_id)) {
         $entity = $loc->fields["entities_id"];
      }
      
      echo "<table class='tab_cadre' width='30%'>";
      echo "<tr><th colspan='3'>" . __('Create coordinates', 'positions'). " :</th></tr>";

      echo "<tr class='tab_bg_1'><td>";
      echo _n('Associated item', 'Associated items', 2) . "</td>";
      echo "<td>";
      PluginPositionsImageItem::showAllItems("items_id", 0, 0, $entity, self::getTypes(), $locations_id);
      echo "</td>";

      echo "<td>";
      echo "<input type='hidden' name='locations_id' value='" . $locations_id . "'>";
      echo "<input type='hidden' name='entities_id' value='$entity'>";
      echo "<input type='submit' name='add' value=\"" . _sx('button','Add') .
      "\" class='submit'>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }

   static function selectDisplay($params, $items) {
      global $CFG_GLPI;

      $colspan = count($items);
      if (!empty($items)) {
         $colspan = count($items);
         $colspan = ($colspan * 2) + 1;
         echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action='" . $params["target"] . "'>";
         echo "<table class='tab_cadre' cellpadding='5'>";
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='" . $colspan . "'>";
         echo __('Limited view', 'positions') . " : ";
         echo "</th>";
         echo "</tr>";
         echo "<tr class='tab_bg_1'>";
         foreach ($items as $classe => $ids) {
            if ($classe) {
               if (!class_exists($classe)) {
                  continue;
               }
               $item = new $classe();

               echo "<td>";
               echo "<input type='checkbox' ";
               if (!empty($params["itemtype"])) {
                  if (in_array($classe, $params["itemtype"])) {
                     echo "checked ";
                  }
               }
               echo "name='itemtype[]' value='" . $classe . "'>";
               echo "</td>";
               echo "<td>" . $item->getTypeName() . "</td>";
            }
         }
         echo "<td class='center'>";
         echo "<input type='submit' name='affich' value=\"" . _sx('button', 'Post') .
         "\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
      }
   }

   /**
   * @static function displayMap : permet de récuperer tous les éléments de  la base de données pour pouvoir
   * les afficher sur la carte
   * @param $items
   * @param $params
   */
   static function displayMap($items, $params){
      global $CFG_GLPI, $DB;

      if (isset($params['itemtype'])
              && is_array($params['itemtype'])) {
         foreach ($items as $classe => $ids) {
            if (!in_array($classe, $params['itemtype'])) {
               unset($items[$classe]);
            }
         }
      }

      $plugin = new Plugin();

      $objects = array();
      if (!empty($items)) {
         foreach ($items as $classe => $ids) {
            foreach ($ids as $key => $val) {
               if (($val['locations_id'] == $params['locations_id'])) {
                  $itemclass = new $val['itemtype']();
                  if ($itemclass->getFromDB($val['items_id'])) {
                  
                     $val['picture'] = null;
                     $val['img']     = null;

                     $canvas_id = count($objects);
                     
                     $options = array('canvas_id'     => $canvas_id,
                                      'name'          => self::displayItemTitle($itemclass),
                                      'id'            => $val['id'],
                                      'items_id'      => $val['items_id'],
                                      'itemtype'      => $val['itemtype'],
                                      'width'         => $val['width'],
                                      'height'        => $val['height'],
                                      'x_coordinates' => $val['x_coordinates'],
                                      'y_coordinates' => $val['y_coordinates'],
                                      "hideLabel"     => $val['hideLabel'],
                                      "hideTooltip"   => $val['hideTooltip'],
                                      "tooltip"       => $val['tooltip'],
                                      "color"         => $val['color'],
                                      "rotate"        => $val['rotate'],
                                      "outline"       => $val['outline'],
                                      "outlineWidth"  => $val['outlineWidth'],
                                      "pattern"       => $val['pattern'],
                                      "outlineWidth"  => $val['outlineWidth'],
                                      "labelSize"     => $val['labelSize'],
                                      "shape"         => $val['shape']);


                     if ($plugin->isActivated("resources") && ($val['itemtype'] == 'PluginResourcesResource')) {
                        $val['picture'] = $itemclass->fields['picture'];
                        $options['img'] = $CFG_GLPI['url_base'].'/plugins/resources/pics/nobody.png';
                        if (!($val['picture'] == null)) {
                           $options['img'] = $CFG_GLPI['url_base'].'/plugins/resources/front/picture.send.php?file='.$val['picture'];
                        }
                        $objects[$val['id']] = $options;
                        
                     } else if (($val['itemtype'] == 'Location')) {
                        $options['img'] = $CFG_GLPI['url_base'].'/plugins/positions/pics/door.png';
                        $objects[$val['id']] = $options;
                        
                     } else if (($val['itemtype'] == 'Netpoint')) {
                        $options['img'] = $CFG_GLPI['url_base'].'/plugins/positions/pics/socket.png';
                        $objects[$val['id']] = $options;
                        
                     } else {
                        $itemtype = $val['itemtype']."Type";
                        $typefield = getForeignKeyFieldForTable(getTableForItemType($itemtype));
                        $imgitem = new PluginPositionsImageItem();
                        if (isset($itemclass->fields[$typefield])) {
                           $val['img'] = $imgitem->displayItemImage($itemclass->fields[$typefield], $classe);
                        }
                        $options['img'] = $CFG_GLPI['url_base'].'/plugins/positions/pics/nothing.png';
                        if (!($val['img'] == null)) {
                           $options['img'] = $CFG_GLPI['url_base'].'/plugins/positions/front/map.send.php?type=pics&file='.$val['img'];
                        }

                        $objects[$val['id']] = $options;
                     }
                     $canvas_id++;
                  }
               }
            }
         }
         self::drawCanvas($objects, $params);
         
      } else {
         // Tests sur les droits
         $eventless = false;
         if (!Session::haveRight('plugin_positions', UPDATE) || (isset($params['menuoff']) && $params['menuoff'] == 1)) {
            $eventless = true;
         }

         $objects[] = array('canvas_id'      => '0',
                            'name'           => '',
                            'id'             => '',
                            'items_id'       => '',
                            'itemtype'       => '',
                            'width'          => 0.5,
                            'height'         => 0.5,
                            'x_coordinates'  => -300,
                            'y_coordinates'  => 80,
                            'testEvent'      => $eventless,
                            'hideLabel'      => true,
                            'img'            => $CFG_GLPI['url_base'].'/plugins/positions/pics/nothing.png');
         self::drawCanvas($objects, $params);
      }
   }


    /**
     * @static function drawCanvas : permet de dessiner le canvas avec les éléments récupérés en paramètre
     * @param $items
     * @param $params
     */
    public static function drawCanvas($items, $params) {
        global $CFG_GLPI;

        $nodes = array();
        //tests sur les droits
        $eventless = false;
        if (!Session::haveRight('plugin_positions', UPDATE) || (isset($params['menuoff']) && $params['menuoff'] == 1)) {
            $eventless = true;
        }

        //image de fond
        $input = array();
        $input['id']        = "Fond";
        $input['shape']     = 'image';
        $input['eventless'] = true;
        $input['width']     = $params['largeur'];
        $input['height']    = $params['hauteur'];
        $input['imagePath'] = $CFG_GLPI['url_base'] . '/plugins/positions/front/map.send.php?docid=' . $params['docid'];
        $input['color']     = 'black';
        $input['testEvent'] = $eventless;
        $input['x']         = 250;
        $input['y']         = 250;
        $input['hideLabel'] = true;
        $nodes['nodes'][]   = $input;

        $highlight = array();

        //création des noeuds
        foreach ($items as $node) {
            $node['imagePath'] = $node['img'];
            $node['x']         = $node['x_coordinates'] + 1;
            $node['y']         = $node['y_coordinates'] + 1;
            if(empty($node['hideTooltip'])) unset($node['hideTooltip']);
            if(empty($node['hideLabel']))   unset($node['hideLabel']);
            $node['testEvent'] = $eventless;
            $node['shape']     = 'image';
            $nodes['nodes'][]  = $node;
        }

        if (isset($node['id']) == $params['id'] && isset($node['id']) != '') {
            $highlight[] = $params['id'];
        }

        //configuration du canvas
        $canvas_config = array('graphType'                => 'Network',
                               'backgroundGradient2Color' => 'white', //couleur du fond 1
                               'backgroundGradient1Color' => 'white', //couleur du fond 2
                               'gradient'                 => false, //dégradé
                               'networkFreezeOnLoad'      => true,
                               'nodeFontColor'            => 'rgb(29,34,43)', //couleur des Noeuds
                               'imageDir'                 => $CFG_GLPI['url_base'] . "/plugins/positions/lib/canvas/images/",
                               //'zoom'                   => 1.0, //zoom de depart
                               //'zoomStep'               => 0.5, //coefficient pour le zoom
                               'calculateLayout'          => false,
                               'disableConfigurator'      => true,
                               'showCode'                 => false, //affichage du code
                               'nodeFontSize'             => 6,
                               'fontName'                 => 'Verdana',
                               'resizable'                => false
        );

        //Création du canvas et des évènements
        echo "<script>
         Ext.onReady(function(){
            Ext.QuickTips.init();
               var panel = new Ext.canvasXpress({
               renderTo: 'Carte',
               languages : ".json_encode(self::getJsLanguages())." ,
               frame: false,
               id: 'graph',
               width: 1300,
               height: 550,
               highlightArray: " . json_encode($highlight) . ",
               showExampleData: false,
               imgDir: '../lib/canvas/images/',
               data: " . json_encode($nodes) . ",
               _glpi_csrf_token: " . json_encode(Session::getNewCSRFToken()) . ",
               options:" . json_encode($canvas_config) . ",
               events:{
                  dblclick : function(obj){
                     if (navigator.appName == 'Microsoft Internet Explorer')
                          {
                               var ua = navigator.userAgent;
                               var re  = new RegExp(\"MSIE ([0-9]{1,}[\.0-9]{0,})\");
                               if (re.exec(ua) != null){
                                 rv = parseFloat( RegExp.$1 );
                               }
                               if ( rv == 9.0 ){
                                 var n = obj.nodes[0];
                                 var win = new Ext.Window({
                                    id:n.id,
                                    title:'Informations - '+n.name,
                                    width:'600px',
                                    height:'800px',
                                    modal:false,
                                    layout:'fit',
                                    resizable:false,
                                    autoLoad: function(n){
                                                url: 'showinfos.php?items_id='+n.items_id+'&id='+n.id+'&name='+n.name+
                                            '&img='+n.imagePath+'&itemtype='+n.itemtype}

                                 });
                                 win.show();
                                 if( n.itemtype =='Location'){
                                    win.hide();
                                 }
                               }
                               else{
                                 alert('Download Internet Explorer v9.0');
                               }
                          }
                  },
                  click: function(obj) {
                     var n = obj.nodes[0];
                         var win = new Ext.Window({
                            id:n.id,
                            title:'Informations - '+n.name,
                            width:'600px',
                            height:'800px',
                            modal:false,
                            layout:'fit',
                            resizable:false,
                            autoLoad: {
                            url: 'showinfos.php?items_id='+n.items_id+'&id='+n.id+'&name='+n.name+
                            '&img='+n.imagePath+'&itemtype='+n.itemtype}
                         });
                         win.show();
                         if( n.itemtype =='Location'){
                            win.hide();
                         }
                  }
               }
            });
         });
      </script>";

        echo "<div id='Carte'></div>";

    }

   static function displayItemTitle($itemclass) {

      $text = "";
      if (isset($itemclass->fields["name"])
         && !empty($itemclass->fields["name"])) {

         $plugin = new Plugin();
         if ($plugin->isActivated("resources")
               && $itemclass->getType() == 'PluginResourcesResource') {
            $text .= PluginResourcesResource::getResourceName($itemclass->getID());
         } else {
            $text .= $itemclass->fields["name"];
         }
      }
      if ($_SESSION['glpiactiveprofile']['interface'] != 'central') {
         $text .= PluginPositionsInfo::getCallValue($itemclass, true);
      }
      return $text;
   }

    /*Fonction qui permet de récupérer les informations à afficher dans le popup*/
    static function showOverlay($localID, $srcimg, $itemclass, $infos)
    {
        global $CFG_GLPI;

        $defaultheight = 50;
        $height = 0;
        $addheight = 0;

        if (!empty($infos)) {
            foreach ($infos as $info) {
                if ($itemclass->getType() == $info['itemtype']) {
                    $fields = explode(',', $info['fields']);
                    $nb = 0;
                    for ($i = 0; $i < count($fields); $i++) {
                        if (!empty($itemclass->fields[$fields[$i]])) {
                            $nb++;
                        }
                    }
                    $height = 30 * $nb;
                }
            }
            $height = $defaultheight + $height;
            if ($itemclass->getType() == 'Phone') {
                $height = $height + 80;
            } else if ($itemclass->getType() == 'PluginResourcesResource') {
                $resID = $itemclass->fields['id'];
                $restrict = "`plugin_resources_resources_id` = $resID AND `itemtype` = 'User' ";
                $datas = getAllDatasFromTable('glpi_plugin_resources_resources_items', $restrict);
                if (!empty($datas)) {
                    foreach ($datas as $data) {
                        if (isset($data['items_id']) && ($data['items_id'] > 0)) {
                            $userid = $data['items_id'];
                            $entitiesID = $itemclass->fields['entities_id'];
                            $condition = "`users_id` = '$userid'
                                    AND `is_deleted` = '0' 
                                    AND `is_template` = '0' 
                                    AND `entities_id` = '$entitiesID'
                                    AND `contact_num` != 0 ";
                            if (($number = countElementsInTable("glpi_phones", $condition)) > 1) {
                                $addheight = 30 * $number;
                            }
                            $height = $height + $addheight;
                        }
                    }
                }
            }
        } else {
            $height = $defaultheight + 30;
        }

        if (Session::haveRight('plugin_positions', UPDATE) && $itemclass->canView()) {
            echo "<a class='config' target='_blank' title=\"" . __('Configuring the display materials', 'positions') . "\"
                              href='" . $CFG_GLPI["root_doc"] .
                "/plugins/positions/front/info.php'></a>";
        }
        $width = 450;

        if ($itemclass->getType() != 'PluginResourcesResource'
            && $itemclass->getType() != 'Location'
               && $itemclass->getType() != 'Netpoint') {

            $img = "<img src='" . $CFG_GLPI["root_doc"] . "/plugins/positions/pics/nothing.png' width='30' height='30'>";

            if (!preg_match("/nothing.png/", $srcimg)) {
                $path = GLPI_PLUGIN_DOC_DIR . "/positions/pics/" . $srcimg;
                $sizes = getimagesize($path);
                $largeur = $sizes[0];
                $hauteur = $sizes[1];
                $ext = pathinfo($srcimg, PATHINFO_EXTENSION);
                $img = "<object width='" . $largeur . "' height='" . $hauteur . "' data='" . $CFG_GLPI['root_doc'] . "/plugins/positions/front/map.send.php?file=" . $srcimg . "&type=pics' type='image/$ext'>
             <param name='src' value='" . $CFG_GLPI['root_doc'] .
                    "/plugins/positions/front/map.send.php?file=" . $srcimg . "&type=pics'>
            </object> ";
            }
        } else {
            $plugin = new Plugin();
            if ($plugin->isActivated("resources")
               && $itemclass->getType() == 'PluginResourcesResource') {

                $img = "<img src='" . $CFG_GLPI["root_doc"] . "/plugins/resources/pics/nobody.png' width='90' height='90'>";
                $res = new PluginResourcesResource();
                if ($res->getFromDB($itemclass->fields["id"])) {
                    if (isset($res->fields["picture"])) {
                        $path = GLPI_PLUGIN_DOC_DIR . "/resources/" . $res->fields["picture"];
                        if (file_exists($path)) {
                            $sizes = getimagesize($path);
                            $largeur = $sizes[0];
                            $hauteur = $sizes[1];
                            $ext = pathinfo($srcimg, PATHINFO_EXTENSION);
                            $img = "<object width='" . $largeur . "' height='" . $hauteur . "' data='" . $CFG_GLPI['root_doc'] . "/plugins/resources/front/picture.send.php?file=" . $res->fields["picture"] . "' type='image/$ext'>
                <param name='src' value='" . $CFG_GLPI['root_doc'] .
                                "/plugins/resources/front/picture.send.php?file=" . $res->fields["picture"] . "'>
               </object> ";
                        }
                    }
                }
                $width = $width - 75;
            } //si c'est un lieu
            else {
                $img = '';
            }
        }
        echo "<table><tr><td>";
        echo $img;
        echo "</td><td><div class='details' style='width:480px;'>";
        if (!empty($infos)) {
            foreach ($infos as $info) {
                if ($itemclass->getType() == $info['itemtype']) {
                    PluginPositionsInfo::showFields($info, $itemclass);
                }
            }
        }
        if ($_SESSION['glpiactiveprofile']['interface'] == 'central' || $itemclass->getType() == 'Location') {
            PluginPositionsInfo::getDirectLink($itemclass);
        }

        //end details
        echo "</div>";

        echo "<div class='call' style='width:480px;'>";
        PluginPositionsInfo::getCallValue($itemclass);
        //end call
         echo "</div>";
       echo "</td></tr></table>";
    }

   /**
   * Send a file (not a document) to the navigator
   * See Document->send();
   *
   * @param $file string: storage filename
   * @param $filename string: file title
   *
   * @return nothing
   **/
   static function sendFile($file, $filename, $type) {

      // Test securite : document in DOC_DIR
      $tmpfile = str_replace(GLPI_PLUGIN_DOC_DIR . "/positions/" . $type . "/", "", $file);

      if (strstr($tmpfile, "../") 
            || strstr($tmpfile, "..\\")) {
         Event::log($file, "sendFile", 1, "security",
         $_SESSION["glpiname"] . " try to get a non standard file.");
         die("Security attack !!!");
      }

      if (!file_exists($file)) {
         die("Error file $file does not exist");
      }

      $splitter = explode("/", $file);
      $mime = "application/octet-stream";

      if (preg_match('/\.(....?)$/', $file, $regs)) {
         switch ($regs[1]) {
            case "png" :
               $mime = "image/png";
            break;
            case "jpeg" :
               $mime = "image/jpeg";
            break;

            case "jpg" :
               $mime = "image/jpg";
            break;
            case "gif" :
               $mime = "image/gif";
            break;
         }
      }

      // Now send the file with header() magic
      header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
      header('Pragma: private'); /// IE BUG + SSL
      header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
      header("Content-disposition: filename=\"$filename\"");
      header("Content-type: " . $mime);

      readfile($file) or die ("Error opening file $file");
   }

   //from items
   static function showPluginFromItems($itemtype, $ID, $withtemplate = '') {
      global $DB, $CFG_GLPI;

      $item = new $itemtype();
      $canread = $item->can($ID, READ);
      $canedit = $item->can($ID, UPDATE);

      $self = new self();

      $query = "SELECT `glpi_plugin_positions_positions`.* "
            . "FROM `glpi_plugin_positions_positions` "
            . " LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id` =
                        `glpi_plugin_positions_positions`.`entities_id`) "
            . " WHERE `glpi_plugin_positions_positions`.`items_id` = '" . $ID . "'
                  AND `glpi_plugin_positions_positions`.`itemtype` = '" . $itemtype . "' "
            . getEntitiesRestrictRequest(" AND ", "glpi_plugin_positions_positions", '', '',
                $self->maybeRecursive());
      $query .= " ORDER BY `glpi_plugin_positions_positions`.`name` ";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      
      $colsup = 0;
      if (Session::isMultiEntitiesMode()) {
         $colsup = 1;
      }
      if ($number) {
         echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] .
                "/plugins/positions/front/position.form.php\" name='pointform' id='pointform'>";
         echo "<div align='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='" . (4 + $colsup) . "'>" . __('Associated coordinate', 'positions') . ":</th></tr>";
         echo "<tr><th>" . __('Name') . "</th>";
         if (Session::isMultiEntitiesMode())
            echo "<th>" . __('Entity') . "</th>";
         echo "<th>" . __('Coordinate x', 'positions') . "</th>";
         echo "<th>" . __('Coordinate y', 'positions') . "</th>";
         if (Session::haveRight('plugin_positions', UPDATE))
            echo "<th>&nbsp;</th>";
         echo "</tr>";
         $used = array();

         while ($data = $DB->fetch_array($result)) {
            $positionsID = $data["id"];

            $used[] = $positionsID;
            echo "<tr class='tab_bg_1" . ($data["is_deleted"] == '1' ? "_2" : "") . "'>";

            if ($withtemplate != 3 && $canread
                  && (in_array($data['entities_id'],$_SESSION['glpiactiveentities']) 
                     || $data["is_recursive"])) {
                     
               echo "<td class='center'><a href='" . $CFG_GLPI["root_doc"] .
                     "/plugins/positions/front/position.form.php?id=" . $data["id"] . "'>" . $data["name"];
            
               if (($_SESSION["glpiis_ids_visible"]) || (empty($data["name"]))) echo " (" . $data["id"] . ")";
               echo "</a>";
               echo self::showGeolocLink($itemtype, $ID, $data["id"]);
               echo "</td>";
            } else {
               echo "<td class='center'>" . $data["name"];
               if ($_SESSION["glpiis_ids_visible"]) echo " (" . $data["id"] . ")";
               echo "</td>";
            }
            if (Session::isMultiEntitiesMode()) {
               echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities",
                                                                     $data['entities_id']) . "</td>";
            }
            echo "<td class='center'>" . $data["x_coordinates"] . "</td>";
            echo "<td class='center'>" . $data["y_coordinates"] . "</td>";

            if (Session::haveRight('plugin_positions', UPDATE) 
               && $withtemplate < 2) {
               if ($data["is_deleted"] != 1) {
                  echo "<td class='center tab_bg_2'>";
                  Html::showSimpleForm($CFG_GLPI['root_doc'].'/plugins/positions/front/position.form.php',
                                       'delete_item',
                                       _x('button', 'Delete permanently'),
                                       array('id' => $positionsID));
                  echo "</td>";
               } else {
                  echo "<td class='tab_bg_2 center'></td>";
               }
            }
            echo "</tr>";
         }

         echo "</table></div>";
         Html::closeForm();
      } else {
         self::showAddFromItem($itemtype, $ID);
      }
   }
   
   static function showGeolocInfos($itemtype,$id, $positions_id=0) {
      global $CFG_GLPI;
      
      if ($itemtype != 'User'
            && $itemtype != 'PluginResourcesResource') {
         $item = new $itemtype();
         $item->getFromDB($id);
         
         $restrict = "`items_id` = '".$id."'
                     AND `is_deleted` = '0' 
                     AND `itemtype` = '".$itemtype."'" ;
         $datas = getAllDatasFromTable('glpi_plugin_positions_positions',$restrict);
         if (!empty($datas)) {
            foreach ($datas as $data) {
               $positions_id = $data['id'];
            }
         }
         $documents_id = self::getDocument($item->fields['locations_id']);
         $locations_id = $item->fields['locations_id'];
         
      } else {
         
         //si plugin ressource active
         $plugin = new Plugin();
         if ($plugin->isActivated("resources")) {
            //recherche de la ressource lie a ce user
            
            if ($itemtype != 'PluginResourcesResource') {
               $condition = "`items_id`= '".$id."' AND `itemtype` = 'User'";
            
               $infos = getAllDatasFromTable('glpi_plugin_resources_resources_items',$condition);
               if (!empty($infos)) {
                  foreach ($infos as $info) {
                     $ressource     = new PluginResourcesResource();
                     $ressource->getFromDB($info['plugin_resources_resources_id']);

                     $restrict = "`items_id` = '".$ressource->getID()."'
                                 AND `is_deleted` = '0' 
                                 AND `entities_id` = '".$ressource->fields['entities_id']."'
                                 AND `itemtype` = 'PluginResourcesResource'" ;
                     $datas = getAllDatasFromTable('glpi_plugin_positions_positions',$restrict);
                     if (!empty($datas)) {
                        foreach ($datas as $data) {
                           if (isset($data['id'])) {
                              if (isset($ressource->fields['locations_id']) 
                                          && ($ressource->fields['locations_id']>0)) {
                                 $documents_id = self::getDocument($ressource->fields['locations_id']);
                                 $positions_id = $data['id'];
                                 $locations_id = $ressource->fields['locations_id'];
                              }
                           }
                        }
                     }
                  }
               }
            } else {

               $ressource     = new PluginResourcesResource();
               if($ressource->getFromDB($id)) {
                  $restrict = "`items_id` = '".$ressource->fields['id']."'
                              AND `is_deleted` = '0' 
                              AND `entities_id` = '".$ressource->fields['entities_id']."'
                              AND `itemtype` = '".$ressource->getType()."'" ;
                  $datas = getAllDatasFromTable('glpi_plugin_positions_positions',$restrict);
                  if (!empty($datas)) {
                     foreach ($datas as $data) {
                        if (isset($data['id'])) {
                           if (isset($ressource->fields['locations_id']) 
                                       && ($ressource->fields['locations_id']>0)) {
                              $documents_id = self::getDocument($ressource->fields['locations_id']);
                              $positions_id = $data['id'];
                              $locations_id = $ressource->fields['locations_id'];
                           }
                        }
                     }
                  }
               }
            }
         }
      }
      $out = array();
      $Doc  = new Document();
      if(isset($documents_id) && $Doc->getFromDB($documents_id)) {

         $out["positions_id"] = $positions_id;
         $out["download"]     = 1;
         $out["locations_id"] = $locations_id;
         $out["from_treeview"] = 1;
      }
      
      return $out;
   }
   
   
   static function showGeolocLink($itemtype,$id, $positions_id=0) {
      global $CFG_GLPI;

      if ($itemtype != 'User'
            && $itemtype != 'PluginResourcesResource') {
         $position = new PluginPositionsPosition();
         $position->getFromDBByQuery("WHERE itemtype ='" . $itemtype . "' AND items_id = " . $id);
         $documents_id = self::getDocument($position->fields['locations_id']);
         $locations_id = $position->fields['locations_id'];

      } else {
         
         //si plugin ressource active
         $plugin = new Plugin();
         if ($plugin->isActivated("resources")) {
            //recherche de la ressource lie a ce user
            
            if ($itemtype != 'PluginResourcesResource') {
               $condition = "`items_id`= '".$id."' AND `itemtype` = 'User'";
            
               $infos = getAllDatasFromTable('glpi_plugin_resources_resources_items',$condition);
               if (!empty($infos)) {
                  foreach ($infos as $info) {
                     $ressource     = new PluginResourcesResource();
                     $ressource->getFromDB($info['plugin_resources_resources_id']);

                     $restrict = "`items_id` = '".$ressource->getID()."'
                                 AND `is_deleted` = '0' 
                                 AND `entities_id` = '".$ressource->fields['entities_id']."'
                                 AND `itemtype` = 'PluginResourcesResource'" ;
                     $datas = getAllDatasFromTable('glpi_plugin_positions_positions',$restrict);
                     if (!empty($datas)) {
                        foreach ($datas as $data) {
                           if (isset($data['id'])) {
                              if (isset($ressource->fields['locations_id']) 
                                          && ($ressource->fields['locations_id']>0)) {
                                 $documents_id = self::getDocument($data['locations_id']);
                                 $positions_id = $data['id'];
                                 $locations_id = $data['locations_id'];
                              }
                           }
                        }
                     }
                  }
               }
            } else {

               $ressource     = new PluginResourcesResource();
               if($ressource->getFromDB($id)) {
                  $restrict = "`items_id` = '".$ressource->fields['id']."'
                              AND `is_deleted` = '0' 
                              AND `entities_id` = '".$ressource->fields['entities_id']."'
                              AND `itemtype` = '".$ressource->getType()."'" ;
                  $datas = getAllDatasFromTable('glpi_plugin_positions_positions',$restrict);
                  if (!empty($datas)) {
                     foreach ($datas as $data) {
                        if (isset($data['id'])) {
                           if (isset($data['locations_id']) 
                                       && ($data['locations_id']>0)) {
                              $documents_id = self::getDocument($data['locations_id']);
                              $positions_id = $data['id'];
                              $locations_id = $data['locations_id'];

                           }
                        }
                     }
                  }
               }
            }
         }
      }
      $out = "";
      $Doc  = new Document();
      if(isset($documents_id) && $Doc->getFromDB($documents_id)) {

         $out.="&nbsp;<a href='#' onClick=\"var w = window.open('".$CFG_GLPI['root_doc'].
            "/plugins/positions/front/geoloc.php?positions_id=".
         $positions_id."&amp;download=1&amp;locations_id=".$locations_id.
            "' ,'glpipopup', 
            'height=650, width=1400, top=100, left=100, scrollbars=yes' );
            w.focus();\" ><img src='".$CFG_GLPI["root_doc"].
            "/plugins/positions/pics/sm_globe.png'></a>&nbsp;";
      }
      
      return $out;
   }
   
   static function showGeolocLocation($itemtype, $id, $positions_id = 0) {
      global $CFG_GLPI;

      $documents_id = self::getDocument($id);
      $locations_id = $id;

      $Doc = new Document();
      if (isset($documents_id) 
            && $Doc->getFromDB($documents_id)) {

         $target = $CFG_GLPI["root_doc"]."/plugins/positions/front/geoloc.php?positions_id=" .
         $positions_id . "&amp;download=1&amp;locations_id=" . $locations_id;
         echo "<script type='text/javascript'>
         Position.openWindow('$target');
         </script>";
      }
   }


   static function showAddFromItem($itemtype, $items_id) {
      global $CFG_GLPI;

      if (!Session::haveRight('plugin_positions', READ)) return false;

      $itemclass = new $itemtype();
      $itemclass->getFromDB($items_id);

      echo "<div align='center'>";
      echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] .
      "/plugins/positions/front/position.form.php\" name='pointform' id='pointform'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('Create coordinates', 'positions'). " :</th></tr>";

      echo "<tr class='tab_bg_1'><td>";

      echo __('Name') . "</td>";
      echo "<td>";
      $value = $itemclass->fields["name"];
      
      if (isset($itemclass->fields["contact_num"])
         && !empty($itemclass->fields["contact_num"])) {
         $value = $itemclass->fields["contact_num"];
      }
      $entity = $itemclass->fields["entities_id"];
      Html::autocompletionTextField($itemclass, "name", array('value' => $value,
                                                            'entity' => $entity));
      echo "</td>";
      echo "<td>";
      $location = $itemclass->fields["locations_id"];
      if (isset($location)
         && !empty($location)) {
         
         echo Dropdown::getDropdownName("glpi_locations", $location);
      } else {
         echo "<div class='red'>" . __('No location selected', 'positions') . "</div>";
      }
      echo "</td>";

      if (empty($ID)) {
         if (Session::haveRight('plugin_positions', UPDATE)) {
            echo "<td>";
            echo "<input type='hidden' name='items_id' value='$items_id'>";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
            echo "<input type='hidden' name='entities_id' value='" . $entity . "'>";
            echo "<input type='submit' name='additem' value=\"" . _sx('button','Add') .
            "\" class='submit'>";
            echo "</td>";

         }
      }
      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";

   }

   
   /**
   * Display information on treeview plugin
   *
   * @params itemtype, id, pic, url, name
   *
   * @return params
   **/
   static function showPositionTreeview($params) {
      global $CFG_GLPI;
      
      $out = self::showGeolocInfos($params['itemtype'],$params['id']);
      if (!empty($out["positions_id"])) {
         $params['url'] = $CFG_GLPI['root_doc']."/plugins/positions/front/geoloc.php?positions_id=".
            $out["positions_id"]."&amp;download=".$out["download"].
            "&amp;locations_id=".$out["locations_id"]."&amp;from_treeview=".$out["from_treeview"];
            
         $params['pic'] = "../positions/pics/sm_globe18.png";
      }

      return $params;
   }
   
   /**
   * get all languages for js ext-canvas
   *
   * @return array $languages
   */
   static function getJsLanguages() {
      
      $languages['alertVersion1'] = __('Please download a newer version of canvasXpress at', 'positions');
      $languages['alertVersion2'] = __('You are using an older version that do not support all the functionality of this panel', 'positions');
      $languages['deleteSelectedObjects'] = __('Delete selected objects', 'positions');
      $languages['informationsAbout'] = __('Informations about', 'positions');
      $languages['edit'] = __('Edit');
      $languages['add'] = __('Add');
      $languages['delete1'] = __('Delete the item');
      $languages['hide'] = __('Hide', 'positions');
      $languages['informationsAboutSelectedItems'] = __('informations about selected items', 'positions');
      $languages['allHiddenObjects'] = __('All hidden objects', 'positions');
      $languages['seeAllHiddenObjects'] = __('See all hidden objects', 'positions');
      $languages['cancelModif'] = __('cancel last change', 'positions');
      $languages['replayModif'] = __('Replay the last change', 'positions');
      $languages['saveNetwork'] = __('Save as a new network', 'positions');
      $languages['network'] = __('Network'); 
      $languages['saveNetworkNow'] = __('Save the network now', 'positions'); 
      $languages['simpleSearch'] = __('Simple search', 'positions');
      $languages['importJsonData'] = __('Import JSON data', 'positions');
      $languages['exportJsonData'] = __('Export JSON data', 'positions');
      $languages['importJsonMovie'] = __('Import JSON movie', 'positions');
      $languages['exportJsonMovie'] = __('Export JSON movie', 'positions');
      $languages['forceSaveNetwork'] = __('Force save the entire network', 'positions');
      $languages['advancedTest'] = __('Advanced test', 'positions');
      $languages['refreshGraph'] = __('Refresh the graph', 'positions');
      $languages['seeAsPicture'] = __('See as a picture', 'positions');
      $languages['show'] = __('Show');
      $languages['position'] = __('Position');
      $languages['size'] = __('Size');
      $languages['color'] = _n('Color', 'Colors', 1, 'positions');
      $languages['colors'] = _n('Color', 'Colors', 2, 'positions');
      $languages['scaleFactor'] = __('Scale Factor', 'positions');
      $languages['boxed'] = __('Boxed', 'positions');
      $languages['transpose'] = __('Transpose', 'positions');
      $languages['searchOnObjects'] = __('Search on objects', 'positions');
      $languages['search'] = __('Search');
      $languages['advancedOptions'] = __('Advanced Options', 'positions');
      $languages['searchKey'] = __('Search key', 'positions');
      $languages['acceptedRegex'] = __('Regex accepted', 'positions');
      $languages['oneLineByObject'] = __('One line by object', 'positions');
      $languages['stopHighlighting'] = __('Stop highlighting', 'positions');
      $languages['highlightObject'] = __('Highlight object', 'positions');
      $languages['editSelectedObject'] = __('Edit selected object', 'positions');
      $languages['selectedObject'] = __('Selected object', 'positions');
      $languages['thisObject'] = __('This object', 'positions');
      $languages['selectedObjectsAndAllThe'] = __('Selected objects and all the', 'positions');
      $languages['thisObjectAndAllIts'] = __('This object and all its', 'positions');
      $languages['doYouWantToDelete'] = __('Do you want to delete', 'positions');
      $languages['linksWillBeDeleted'] = __('Links will be deleted', 'positions');
      $languages['tooltip'] = __('Tooltip', 'positions');
      $languages['objectType'] = __('Object type', 'positions');
      $languages['requiredSelection'] = __('Required selection', 'positions');
      $languages['imagePath'] = __('Image path', 'positions');
      $languages['theObjectIsFixed'] = __('The object is fixed', 'positions'); 
      $languages['cancel'] = _x('button', 'Cancel');
      $languages['apply'] = _sx('button', 'Post');
      $languages['objectEditor'] = __('Object editor', 'positions');
      $languages['objectId'] = __('Object id', 'positions');
      $languages['title'] = __('Title');
      $languages['pattern'] = __('Pattern', 'positions');
      $languages['edge'] = __('Edge', 'positions');
      $languages['object'] = _n('Object', 'Objects', 1, 'positions');
      $languages['objects'] = _n('Object', 'Objects', 2, 'positions');
      $languages['general'] = __('General');
      $languages['axes'] = __('Axes', 'positions');
      $languages['labels'] = __('Label');
      $languages['legend'] = __('Legend', 'positions');
      $languages['indicators'] = __('Indicators', 'positions');
      $languages['decorations'] = __('Decorations', 'positions');
      $languages['data'] = __('Data', 'positions');
      $languages['fontWeight'] = __('Font weight', 'positions');
      $languages['properties'] = __('Properties', 'positions');
      $languages['transform'] = __('Transform', 'positions');
      $languages['ticks'] = __('Ticks', 'positions');
      $languages['font'] = __('Font', 'positions');
      $languages['layout'] = __('Layout', 'positions');
      $languages['orientation'] = __('Orientation', 'positions');
      $languages['overlays'] = __('Overlays', 'positions');
      $languages['background'] = __('Background', 'positions');
      $languages['gradient'] = __('Gradient', 'positions');
      $languages['solid'] = __('Solid', 'positions');
      $languages['name'] = __('Name');
      $languages['width'] = __('Width', 'positions');
      $languages['height'] = __('Height', 'positions');
      $languages['text'] = __('Text', 'positions');
      $languages['type'] = __('Type');
      $languages['fieldsearched'] = __('Field searched', 'positions');
      $languages['valuefound'] = __('Value found', 'positions');
      $languages['menu'] = __('Menu', 'positions');
      $languages['information'] = __('Information');
      $languages['subtitle'] = __('Subtitle', 'positions');

      return $languages;
   }
   
}

?>