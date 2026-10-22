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

namespace GlpiPlugin\Positions;

use CommonDBTM;
use CommonGLPI;
use Computer;
use DbUtils;
use Document;
use Document_Item;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;
use Glpi\Socket;
use GlpiPlugin\Resources\Resource;
use Html;
use Location;
use Monitor;
use NetworkEquipment;
use PassiveDCEquipment;
use Peripheral;
use Phone;
use Plugin;
use Printer;
use Rack;
use Session;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Position
 */
class Position extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = "plugin_positions";
    protected $usenotepad = true;

    public static $types = [Computer::class,
        Monitor::class,
        NetworkEquipment::class,
        Peripheral::class,
        Printer::class,
        Phone::class,
        Location::class,
        Socket::class,
        Rack::class,
        PassiveDCEquipment::class,
    ];

    /**
     * Return the localized name of the current Type
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Cartography', 'Cartographies', $nb, 'positions');
    }


    /**
     * Get rights for an item _ may be overload by object
     *
     * @param string $interface (defalt 'central')
     *
     * @return array array of rights to display
     **@since 0.85
     *
     */
    /**
     * The rights matrix of the plugin offers no Purge: deleting covers it
     */
    public static function canPurge(): bool
    {
        return Session::haveRight(static::$rightname, DELETE);
    }

    public function canPurgeItem(): bool
    {
        return $this->canDeleteItem();
    }

    public function getRights($interface = 'central')
    {

        $values = parent::getRights();

        if ($interface == 'helpdesk') {
            unset($values[CREATE], $values[UPDATE], $values[DELETE], $values[PURGE], $values[READNOTE], $values[UPDATENOTE]);
        }
        return $values;
    }

    //if item deleted
    public static function purgePositions($item)
    {
        $temp = new self();

        $type = get_class($item);
        $temp->deleteByCriteria(['itemtype' => $type,
            'items_id' => $item->getField('id')], 1);
        return true;
    }

    /**
     * For other plugins, add a type to the linkable types
     *
     * @param $type string class name
     **/
    public static function registerType($type)
    {
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
    public static function getTypes($all = false)
    {
        // Only allowed types
        $types = self::$types;

        if ($all) {
            return $types;
        }

        // The types whose class is missing (a plugin disabled since) are left out
        return array_values(array_filter($types, static fn(string $type): bool => class_exists($type)));
    }

    /**
     * Display attributes of a position, whichever way they are posted (forms or canvas):
     * canvasXpress renders them as HTML without escaping, so the texts lose their markup and
     * the other attributes are kept to their own syntax. A value out of it is dropped.
     */
    public function checkValues($input)
    {
        foreach (['name', 'tooltip'] as $field) {
            if (isset($input[$field])) {
                $input[$field] = strip_tags((string) $input[$field]);
            }
        }
        // Colors: #rgb, #rrggbb, rgb()/rgba() or a color name
        foreach (['color', 'outline'] as $field) {
            if (isset($input[$field])
                && !preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9., ]+\)|[a-z]+)?$/i', (string) $input[$field])) {
                unset($input[$field]);
            }
        }
        // Shape and pattern: names of the library (circle, square, solid, ...)
        foreach (['shape', 'pattern'] as $field) {
            if (isset($input[$field]) && !preg_match('/^[a-z0-9_-]{0,50}$/i', (string) $input[$field])) {
                unset($input[$field]);
            }
        }
        foreach (['width', 'height', 'outlineWidth', 'rotate', 'x_coordinates', 'y_coordinates', 'locations_id'] as $field) {
            if (isset($input[$field])) {
                $input[$field] = (int) $input[$field];
            }
        }
        foreach (['hideTooltip', 'hideLabel'] as $field) {
            if (isset($input[$field])) {
                $input[$field] = empty($input[$field]) ? 0 : 1;
            }
        }
        if (isset($input['labelSize'])) {
            $input['labelSize'] = (float) $input['labelSize'];
        }

        // The map the position is put on must be readable
        if (!empty($input['locations_id'])) {
            $location = new Location();
            if (!$location->can($input['locations_id'], READ)) {
                Session::addMessageAfterRedirect(__('No location selected', 'positions'), false, ERROR);
                return false;
            }
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        if (!isset($input["items_id"])
            || !isset($input["itemtype"])) {
            Session::addMessageAfterRedirect(__('No equipment associated', 'positions'), false, ERROR);
            return [];
        }

        $input['itemtype'] = str_replace('\\\\', '\\', $input['itemtype']);
        // The item type is posted: only the types a position may be linked to are instantiated
        if (!in_array($input['itemtype'], self::getTypes(), true)) {
            return false;
        }
        // The linked item must be within the reach of the user
        $linked = getItemForItemtype($input['itemtype']);
        if (!$linked || !$linked->can((int) $input['items_id'], READ)) {
            Session::addMessageAfterRedirect(__('No equipment associated', 'positions'), false, ERROR);
            return false;
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

        if (!isset($input["name"])
            || empty($input["name"])) {
            $item = new $input['itemtype']();
            if ($item->getFromDB($input['items_id'])) {
                $input['name'] = $item->fields["name"];
            }
        }

        if (!isset($input["x_coordinates"])
            || empty($input["x_coordinates"])
            || !isset($input["y_coordinates"])
            || empty($input["y_coordinates"])) {
            $input["x_coordinates"] = '-1000';
            $input["y_coordinates"] = '-225';
        }

        //default size
        $config = new Config();
        $config->getFromDB(1);

        $input['width'] = $config->fields['default_width'];
        $input['height'] = $config->fields['default_height'];

        $input = $this->checkValues($input);

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        // An item linked by an update gets the same checks as on creation
        if (isset($input['itemtype']) || isset($input['items_id'])) {
            $itemtype = str_replace('\\\\', '\\', (string) ($input['itemtype'] ?? $this->fields['itemtype']));
            $items_id = (int) ($input['items_id'] ?? $this->fields['items_id']);
            $linked   = in_array($itemtype, self::getTypes(), true) ? getItemForItemtype($itemtype) : false;
            if (!$linked || !$linked->can($items_id, READ)) {
                Session::addMessageAfterRedirect(__s('No equipment associated', 'positions'), false, ERROR);
                return false;
            }
            $input['itemtype'] = $itemtype;
            $input['items_id'] = $items_id;
        }

        $input = $this->checkValues($input);

        return $input;
    }

    public function post_addItem()
    {
        global $CFG_GLPI;

        if (!isset($this->input["massiveaction"])) {
            Html::redirect(PLUGIN_POSITIONS_WEBDIR
                . "/front/coordinates.form.php?id=" . $this->getField('id'));
        }
    }

    /**
     * Provides search options configuration. Do not rely directly
     * on this, @return array a *not indexed* array of search options
     *
     * @since 9.3
     *
     * This should be overloaded in Class
     *
     * @see CommonDBTM::searchOptions instead.
     *
     * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
     **/
    public function rawSearchOptions()
    {

        $tab = parent::rawSearchOptions();

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'x_coordinates',
            'name' => __('Coordinate x', 'positions'),
        ];

        $tab[] = [
            'id' => '4',
            'table' => $this->getTable(),
            'field' => 'y_coordinates',
            'name' => __('Coordinate y', 'positions'),
        ];

        $tab[] = [
            'id' => '5',
            'table' => $this->getTable(),
            'field' => 'items_id',
            'name' => __('Associated element', 'positions'),
            'massiveaction' => false,
            'nosearch' => true,
        ];

        $tab[] = [
            'id' => '6',
            'table' => $this->getTable(),
            'field' => 'date_mod',
            'name' => __('Last update'),
            'massiveaction' => false,
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id' => '7',
            'table' => $this->getTable(),
            'field' => 'is_recursive',
            'name' => __('Child entities'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number',
        ];

        $tab[] = [
            'id' => '80',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown',
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-map";
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            if ($item->getType() == __CLASS__) {
                return self::createTabEntry(__('See the map', 'positions'));
            } elseif (in_array($item->getType(), self::getTypes(true))
                && Session::haveRight('plugin_positions', READ)) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    return self::createTabEntry(
                        self::getTypeName(Session::getPluralNumber()),
                        self::countForItem($item)
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()));
            }
        }

        return '';
    }

    public static function countForItem(CommonDBTM $item)
    {
        // The positions the tab lists: of the entities of the user
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            'glpi_plugin_positions_positions',
            ["itemtype" => $item->getType(),
                "items_id" => $item->getID()]
            + getEntitiesRestrictCriteria('glpi_plugin_positions_positions', '', '', true)
        );
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // The tab can be requested directly (ajax/common.tabs.php): the right of the
        // cartography is checked again, not only when its title is shown
        if (!Session::haveRight('plugin_positions', READ)) {
            return false;
        }

        $self = new self();

        if ($item->getType() == __CLASS__) {
            self::showAddPosition($item);
        } elseif (in_array($item->getType(), self::getTypes(true))) {
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
    public function getSelectLinkedItem()
    {
        return "SELECT `itemtype`, `items_id`
           FROM `glpi_plugin_positions_positions`
           WHERE `id`='" . $this->fields['id'] . "'";
    }

    /**
     * Map of a location, to crop it into its sub-areas
     *
     */
    public static function showMapCreateLocation($options = [])
    {
        global $DB;

        $document_id = (int) $options["document_id"];
        $locations_idParent = (int) $options["locations_id"];

        if (!Session::haveRight('plugin_positions', READ)) {
            return false;
        }
        $parent = new Location();
        if (!$parent->can($locations_idParent, READ)) {
            throw new \Glpi\Exception\Http\AccessDeniedHttpException();
        }

        $Doc = new Document();
        if (!$Doc->getFromDB($document_id)) {
            Html::redirect(PLUGIN_POSITIONS_WEBDIR
                . "/front/map.php?locations_id=" . $locations_idParent);
        }

        $path = GLPI_DOC_DIR . "/" . $Doc->fields["filepath"];
        // Upper case, as compared here and in cropPicture(): ".png" maps were refused
        $extension = strtoupper(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['PNG', 'JPEG', 'JPG', 'GIF'], true)) {
            return false;
        }
        $dim = getimagesize($path);

        // Sub-locations of the parent which have no map yet
        $locations = [];
        foreach ($DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_locations',
            'WHERE'  => [
                'locations_id' => $locations_idParent,
                'NOT'          => ['name' => new \Glpi\DBAL\QuerySubQuery([
                    'SELECT' => 'name',
                    'FROM'   => 'glpi_documents',
                ])],
            ],
        ]) as $data) {
            $locations[] = $data['id'];
        }

        // Jcrop draws the selection on the map; public/positions_crop.js fills the hidden fields
        Html::requireJs('positions_crop');

        TemplateRenderer::getInstance()->display('@positions/crop_form.html.twig', [
            'form_url'           => PLUGIN_POSITIONS_WEBDIR . '/front/crop.form.php',
            'document_id'        => $document_id,
            'document_name'      => (string) $Doc->fields["name"],
            'img_url'            => PLUGIN_POSITIONS_WEBDIR . "/front/map.send.php?docid=" . $document_id,
            'width'              => (int) ($dim[0] ?? 0),
            'height'             => (int) ($dim[1] ?? 0),
            'extension'          => $extension,
            'locations_idParent' => $locations_idParent,
            'entities_id'        => (int) $Doc->fields["entities_id"],
            'sub_locations'      => $locations,
            'empty_area_message' => __('Select an area and click on OK', 'positions'),
        ]);
    }

    public static function cropPicture($input)
    {
        $x1 = (int) $input['x1'];
        $y1 = (int) $input['y1'];
        $width = (int) $input['w'];
        $height = (int) $input['h'];
        if ($width <= 0 || $height <= 0) {
            throw new \Glpi\Exception\Http\BadRequestHttpException();
        }

        $test = (string) ($input['test'] ?? '');
        $locations_idParent = (int) ($input['locations_idParent'] ?? 0);
        $entities_id = (int) ($input['entities_id'] ?? -1);
        $document_id = (int) ($input['document_id'] ?? 0);

        // Every id comes from the request: the entity, the source map and the locations must
        // all be within the reach of the user
        $Doc = new Document();
        $parent = new Location();
        if (!Session::haveAccessToEntity($entities_id)
            || !$Doc->can($document_id, READ)
            || !$parent->can($locations_idParent, READ)) {
            throw new \Glpi\Exception\Http\AccessDeniedHttpException();
        }
        // They must also belong together: the source is the map of the parent location, and the
        // sub-area is created in the entity of the parent (or one below it when it is recursive)
        if ($document_id !== (int) self::getDocument($locations_idParent)) {
            throw new \Glpi\Exception\Http\BadRequestHttpException();
        }
        $parent_entity = (int) $parent->fields['entities_id'];
        if ($entities_id !== $parent_entity
            && !($parent->isRecursive() && in_array($entities_id, getSonsOf('glpi_entities', $parent_entity)))) {
            $entities_id = $parent_entity;
        }

        if ($test == "newLocation") {
            $name = (string) $input['name'];
            $locations_id = $locations_idParent;
        } elseif ($test == "existLocation") {
            $loc = new Location();
            $locations_id = (int) $input['locations_id'];
            if (!$loc->can($locations_id, UPDATE)) {
                throw new \Glpi\Exception\Http\AccessDeniedHttpException();
            }
            $name = $loc->fields['name'];
        } else {
            throw new \Glpi\Exception\Http\BadRequestHttpException();
        }

        $extension = strtoupper((string) $input['extension']);
        if (!in_array($extension, ['PNG', 'JPG', 'JPEG', 'GIF'], true)) {
            throw new \Glpi\Exception\Http\BadRequestHttpException();
        }

        $path = GLPI_DOC_DIR . "/" . $Doc->fields["filepath"];

        // The area must lie within the source map: an unbounded size had GD allocate gigabytes
        $sizes = @getimagesize($path);
        if ($sizes === false
            || $x1 < 0 || $y1 < 0
            || $x1 + $width > $sizes[0] || $y1 + $height > $sizes[1]) {
            throw new \Glpi\Exception\Http\BadRequestHttpException();
        }

        // The name becomes part of a file name in the upload directory: keep a bare component
        $file_name = preg_replace('/[^\p{L}\p{N}_ -]/u', '_', basename(str_replace('\\', '/', $name)));
        $tmpfile = GLPI_UPLOAD_DIR . "/" . $document_id . $file_name;
        if ($extension == 'PNG') {
            $srcImg = imagecreatefrompng($path);
            $newImg = imagecreatetruecolor($width, $height);

            imagecopyresampled($newImg, $srcImg, 0, 0, $x1, $y1, $width, $height, $width, $height);

            imagepng($newImg, $tmpfile . '.png');
        } elseif ($extension == 'JPG' || $extension == 'JPEG') {
            $srcImg = imagecreatefromjpeg($path);
            $newImg = imagecreatetruecolor($width, $height);

            imagecopyresampled($newImg, $srcImg, 0, 0, $x1, $y1, $width, $height, $width, $height);

            imagejpeg($newImg, $tmpfile . '.jpg');
        } elseif ($extension == 'GIF') {
            $srcImg = imagecreatefromgif($path);
            $newImg = imagecreatetruecolor($width, $height);

            imagecopyresampled($newImg, $srcImg, 0, 0, $x1, $y1, $width, $height, $width, $height);

            imagegif($newImg, $tmpfile . '.gif');
        }

        // Whether the user checked the box to go on adding sub-areas:
        // if so, back to the map to crop another one;
        $checked = 'off';
        if (!empty($input['continueAdd'])) {
            $checked = $input['continueAdd'];
        }

        // otherwise, to the map to place equipment on it
        $options = ["document_id" => $document_id,
            "name" => $name,
            "file_name" => $file_name,
            "locations_idParent" => $locations_idParent,
            "locations_id" => $locations_id,
            "entities_id" => $entities_id,
            "test" => $test,
            "extension" => strtolower($extension),
            "checked" => $checked];

        return $options;
    }

    /**
     * Creates the sub-area cropped from the map: its location, document and position
     */
    public static function showFormResCreateLocation($opt)
    {
        global $CFG_GLPI, $DB;

        $filename = $opt["document_id"] . $opt["file_name"] . "." . $opt["extension"];
        $filepath = "/_uploads/" . $filename;

        $id = 0;
        if ($opt["test"] == "existLocation") {
            $location = new Location();
            if ($location->getFromDB((int) $opt["locations_id"])) {
                $id = $location->getID();
            }
        } elseif ($opt["test"] == "newLocation") {
            $locations_id = $opt["locations_idParent"];
        }

        $params = [
            "name" => $opt["name"],
            "document_id" => $opt["document_id"],
            "filepath" => $filepath,
            "filename" => $filename,
            "entities_id" => $opt["entities_id"],
            "locations_id" => $opt["locations_id"],
            "id" => $id,
            "itemtype" => Location::class,
        ];

        // The location to create or reuse
        $dropdown = new Location();

        // Location
        if ($opt["test"] == 'newLocation') {
            if (!($newID = $dropdown->add($params))) {
                // The same location exists already (same name, parent and entity): it is
                // reused, the cropped map attached to it, only when the user may change it
                $locations_found = $dropdown->find(
                    ['name' => $params['name'],
                        'entities_id' => $params['entities_id'],
                        'locations_id' => $params['locations_id']],
                    [],
                    '1'
                );
                $newID = (int) key($locations_found);
                if ($newID <= 0 || !$dropdown->can($newID, UPDATE)) {
                    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
                }
            }
            $opt["locations_id"] = $newID;
        }

        if ($opt["locations_id"] != $opt["locations_idParent"]) {
            // Document of the cropped map, linked to the location
            $doc = new Document();
            $documentitem = new Document_Item();
            $self = new self();

            $input = [];
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
                $restrict = ["items_id" => $newID,
                    "itemtype" => Location::class];
                $dbu = new DbUtils();
                if ($dbu->countElementsInTable("glpi_plugin_positions_positions", $restrict) != 0) {
                    Session::addMessageAfterRedirect(__('This item is already bound to a location', 'positions'), false, ERROR);
                    Html::redirect(PLUGIN_POSITIONS_WEBDIR
                        . "/front/map.php?locations_id=" . $opt["locations_idParent"]);

                    // If not we can add its position and picture
                } else {
                    $documentitem->add(['documents_id' => $newdoc,
                        'itemtype' => Location::class,
                        'items_id' => $newID,
                        'entities_id' => $opt["entities_id"]]);
                    $param = [
                        "items_id" => $newID,
                        "entities_id" => $opt["entities_id"],
                        "locations_id" => $opt["locations_idParent"],
                        "itemtype" => Location::class,
                        "x_coordinates" => -800,
                        "y_coordinates" => -150,
                    ];

                    $self->add($param);

                    if ($opt["checked"] == 'on') {
                        self::showMapCreateLocation($opt);
                    } elseif ($opt["checked"] == 'off') {
                        Html::redirect(PLUGIN_POSITIONS_WEBDIR
                            . "/front/map.php?locations_id=" . $opt["locations_id"]);
                    }
                }

                // Add existing location
            } elseif ($opt["test"] == 'existLocation') {
                $documentitem->add(['documents_id' => $newdoc,
                    'itemtype' => Location::class,
                    'items_id' => $id,
                    'entities_id' => $opt["entities_id"]]);

                $param = [
                    "items_id" => $id,
                    "entities_id" => $opt["entities_id"],
                    "locations_id" => $opt["locations_idParent"],
                    "itemtype" => Location::class,
                    "x_coordinates" => -800,
                    "y_coordinates" => -150,
                ];
                $self->add($param);

                if ($opt["checked"] == 'on') {
                    self::showMapCreateLocation($opt);
                } elseif ($opt["checked"] == 'off') {
                    Html::redirect(PLUGIN_POSITIONS_WEBDIR
                        . "/front/map.php?locations_id=" . $opt["locations_id"]);
                }
            }
        } else {
            Session::addMessageAfterRedirect(__('This item is already bound to a location', 'positions'), false, ERROR);
            Html::redirect(PLUGIN_POSITIONS_WEBDIR
                . "/front/map.php?locations_id=" . $opt["locations_id"]);
        }
    }

    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);

        $item_field = '';
        if ($ID < 0
            || (!$this->fields['itemtype']
                && !$this->fields['items_id'])
        ) {
            $item_field = Dropdown::showSelectItemFromItemtypes(['items_id_name' => "items_id",
                'entity_restrict' => ($this->fields['is_recursive'] ? -1 : $this->fields['entities_id']),
                'itemtypes' => self::getTypes(),
                'display' => false,
            ]);
        } elseif ($this->fields['itemtype']
            && $this->fields['items_id']
            && in_array($this->fields['itemtype'], self::getTypes(), true)
            && ($item = getItemForItemtype($this->fields['itemtype']))) {
            $item_field = htmlescape($item->getTypeName()) . " - ";
            // As on the map, the item itself is shown only to who may read it
            if ($item->can((int) $this->fields['items_id'], READ)) {
                $item_field .= $item->getLink() . " - "
                    . htmlescape((string) Dropdown::getDropdownName("glpi_locations", $item->fields['locations_id']));
            } else {
                $item_field .= __s('Restricted item');
            }
        }

        TemplateRenderer::getInstance()->display('@positions/position_form.html.twig', [
            'item'       => $this,
            'params'     => $options,
            'item_field' => $item_field,
            // Coordinates are set on the map, once the position exists
            'show_coordinates' => $ID > 0,
        ]);

        return true;
    }

    public static function showAddPosition($item)
    {
        if ($item->getField('id') > 0
            && Session::haveRight('plugin_positions', READ)) {
            TemplateRenderer::getInstance()->display('@positions/position_map_link.html.twig', [
                'coordinates_url' => PLUGIN_POSITIONS_WEBDIR . '/front/coordinates.form.php?id=' . (int) $item->getField('id'),
            ]);
        }
    }

    public static function getDocument($locations_id)
    {
        global $DB;

        $documents_id = 0;
        foreach ($DB->request([
            'SELECT' => ['documents_id'],
            'FROM'   => 'glpi_documents_items',
            'WHERE'  => [
                'items_id' => (int) $locations_id,
                'itemtype' => Location::class,
            ],
        ]) as $data) {
            $documents_id = $data['documents_id'];
        }
        return $documents_id;
    }


    public static function getItems($locations_id)
    {

        $items = [];
        $dbu = new DbUtils();
        foreach (self::getTypes() as $key => $item) {
            $table = $dbu->getTableForItemType($item);
            $itemclass = new $item();
            $restrict = ["is_template" => 0,
                "is_deleted" => 0,
                "locations_id" => $locations_id] + $dbu->getEntitiesRestrictCriteria(
                    $table,
                    '',
                    '',
                    $itemclass->maybeRecursive()
                );
            $dbu = new DbUtils();
            $datas = $dbu->getAllDataFromTable($table, $restrict);
            if (!empty($datas)) {
                foreach ($datas as $data) {
                    $items[$item][] = $data["id"];
                }
            }
        }
        return $items;
    }

    public static function getMapItems($locations_id)
    {

        $itemsMap = [];
        $dbu = new DbUtils();
        $restrict = ["locations_id" => $locations_id]
            + $dbu->getEntitiesRestrictCriteria("glpi_plugin_positions_positions", '', '', true);
        $datas = $dbu->getAllDataFromTable("glpi_plugin_positions_positions", $restrict);
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $itemsMap[$data['itemtype']][] = $data;
            }
        }
        return $itemsMap;
    }

    /**
     * Map of a location, with its menus and tabs
     * @param $options
     */
    public static function showMap($options)
    {
        // Ext JS and canvasXpress, on the pages of the map only
        Html::requireJs('positions_map');

        $options['id'] = (int) $options['id'];
        // Types displayed: posted as a list, or "0" when none is checked; a plain string made
        // in_array() fail further down. Only the types of the plugin are kept.
        // None checked shows every type, as the string "0" used to
        $options['itemtype'] = array_values(array_intersect(
            is_array($options['itemtype'] ?? null) ? $options['itemtype'] : [],
            self::getTypes(),
        )) ?: self::getTypes();
        if (!$options['locations_id']) {
            // The position comes from the request: its name and link are shown, so it must be
            // readable itself (entity), not only through its location
            $self = new self();
            if ($options['id'] > 0) {
                if (!$self->can($options['id'], READ)) {
                    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
                }
                $options['locations_id'] = $self->fields['locations_id'];
            }
        }

        $options['locations_id'] = (int) $options['locations_id'];
        // The location comes from the request: its map is only shown to who may read it
        $location = new Location();
        if ($options['locations_id'] && !$location->can($options['locations_id'], READ)) {
            throw new \Glpi\Exception\Http\AccessDeniedHttpException();
        }

        if ($options['locations_id']) {
            $documents_id = self::getDocument($options['locations_id']);

            $Doc = new Document();
            if (isset($documents_id) && $Doc->getFromDB($documents_id)) {
                $params = ["locations_id" => $options['locations_id'],
                    "id" => $options['id'],
                    "itemtype" => $options['itemtype'],
                    "target" => $options['target'],
                ];

                $params['docid'] = $documents_id;
                $path = GLPI_DOC_DIR . "/" . $Doc->fields["filepath"];

                if (is_readable($path) && ($infos_image = @getimagesize($path)) !== false) {
                    $params["largeur"] = $infos_image[0];
                    $params["hauteur"] = $infos_image[1];

                    $params["download"] = 1;
                    if (Session::getCurrentInterface() == 'central') {
                        $params["download"] = 0;
                    }

                    $items     = self::getMapItems($params['locations_id']);
                    $with_menu = !isset($options['menuoff']);
                    $canupdate = Session::haveRight('plugin_positions', UPDATE);

                    // Link to the position the map was opened for, when the user may read it
                    $position_link = '';
                    $position      = new self();
                    if ($options['id'] && $position->can($options['id'], READ)) {
                        $position_link = $position->getLink();
                    }

                    if (!$with_menu) {
                        $params["menuoff"] = $options['menuoff'];
                    }

                    // The canvas is drawn by inline scripts: rendered here, put in the template
                    ob_start();
                    self::displayMap($items, $params);
                    $map_html = ob_get_clean();

                    TemplateRenderer::getInstance()->display('@positions/map.html.twig', [
                        'with_menu'        => $with_menu,
                        'canupdate'        => $canupdate,
                        'location_form'    => $with_menu ? self::getLocationForm($params["locations_id"]) : '',
                        'add_form'         => $with_menu && $canupdate ? self::getAddFromPlugin($params['locations_id']) : '',
                        'display_form'     => $with_menu && $canupdate ? self::getSelectDisplay($params, $items) : '',
                        'point_form_url'   => Toolbox::getItemTypeFormURL(self::getType()),
                        'position_link'    => $position_link,
                        'locations_id'     => $options['locations_id'],
                        'id'               => $options['id'],
                        'map_name'         => (string) $Doc->fields["name"],
                        'map_html'         => $map_html,
                    ]);
                } else {
                    self::displayMapMessage(__('No location has a configured map', 'positions'));
                }
            } else {
                self::displayMapMessage(__('The object location does not match a map', 'positions'), true);
            }
        } else {
            self::displayMapMessage(__('No location selected', 'positions'));
        }
    }

    /**
     * Warning shown instead of a map
     *
     * @param string $message
     * @param bool   $back_link
     */
    public static function displayMapMessage(string $message, bool $back_link = false)
    {
        TemplateRenderer::getInstance()->display('@positions/map_message.html.twig', [
            'message'   => $message,
            'back_link' => $back_link,
        ]);
    }

    /**
     * Form listing the locations that have a map
     * @param $locations_id location selected
     */
    public static function showLocationForm($locations_id)
    {
        echo self::getLocationForm($locations_id);
    }

    /**
     * @param int $locations_id location selected
     *
     * @return string HTML of the form listing the locations that have a map
     */
    public static function getLocationForm($locations_id)
    {
        global $DB;

        $locations = [];
        foreach ($DB->request([
            'SELECT' => ['items_id'],
            'FROM'   => 'glpi_documents_items',
            'WHERE'  => ['itemtype' => Location::class],
        ]) as $data) {
            $locations[] = $data['items_id'];
        }

        return TemplateRenderer::getInstance()->render('@positions/map_location_form.html.twig', [
            'form_url'     => PLUGIN_POSITIONS_WEBDIR . "/front/map.php",
            'locations_id' => (int) $locations_id,
            'has_maps'     => !empty($locations),
            'condition'    => ['glpi_locations.id' => $locations ?: [0]],
        ]);
    }

    /**
     * @param int $locations_id
     *
     * @return string HTML of the form adding an item on the map of the location
     */
    public static function getAddFromPlugin($locations_id)
    {
        if (!Session::haveRight('plugin_positions', UPDATE)) {
            return '';
        }

        $entity   = $_SESSION["glpiactive_entity"];
        $entities = $entity;
        $loc = new Location();
        if ($loc->getFromDB($locations_id)) {
            $entity = $loc->fields["entities_id"];
            if ($loc->isRecursive()) {
                $dbu = new DbUtils();
                $entities = $dbu->getSonsOf('glpi_entities', $loc->fields["entities_id"]);
            } else {
                $entities = $loc->fields["entities_id"];
            }
        }

        return TemplateRenderer::getInstance()->render('@positions/map_add_form.html.twig', [
            'form_url'       => PLUGIN_POSITIONS_WEBDIR . "/front/position.form.php",
            'items_dropdown' => ImageItem::getAllItemsDropdown("items_id", self::getTypes(), 0, 0, $entities, $locations_id),
            'locations_id'   => (int) $locations_id,
            'entities_id'    => (int) $entity,
        ]);
    }

    /**
     * @param array $params
     * @param array $items  items on the map, by type
     *
     * @return string HTML of the form restricting the types shown on the map
     */
    public static function getSelectDisplay($params, $items)
    {
        $types = [];
        foreach (array_keys($items) as $classe) {
            // Positions saved before the namespace keep the short class name of the sockets
            $type = $classe === 'Socket' ? Socket::class : $classe;
            if (!$classe || !in_array($type, self::getTypes(), true)
                || !($item = getItemForItemtype($type))) {
                continue;
            }
            $types[] = [
                'class'   => $classe,
                'name'    => $item->getTypeName(),
                'checked' => !empty($params["itemtype"]) && in_array($classe, $params["itemtype"]),
            ];
        }
        if (empty($types)) {
            return '';
        }

        return TemplateRenderer::getInstance()->render('@positions/map_display_form.html.twig', [
            'form_url' => (string) $params["target"],
            'types'    => $types,
        ]);
    }

    /**
     * Items of the location read from the database,
     * to be drawn on the map
     * @param $items
     * @param $params
     */
    public static function displayMap($items, $params)
    {
        global $CFG_GLPI;

        if (isset($params['itemtype'])
            && is_array($params['itemtype'])) {
            foreach ($items as $classe => $ids) {
                if (!in_array($classe, $params['itemtype'])) {
                    unset($items[$classe]);
                }
            }
        }

        $objects = [];
        if (!empty($items)) {
            foreach ($items as $classe => $ids) {
                foreach ($ids as $key => $val) {
                    if (($val['locations_id'] == $params['locations_id'])) {
                        if ($val['itemtype'] == "Socket") {
                            $itemclass = new Socket();
                        } else {
                            $itemclass = new $val['itemtype']();
                        }

                        // Only the items the user may read on their own (e.g. the Computer
                        // right), not on the sole right of the cartography
                        if ($itemclass->getFromDB($val['items_id'])
                            && $itemclass->can($val['items_id'], READ)) {
                            $val['picture'] = null;
                            $val['img'] = null;

                            $canvas_id = count($objects);

                            // canvasXpress renders the label and the tooltip as HTML: escaped,
                            // values saved by older versions included
                            $options = ['canvas_id' => $canvas_id,
                                'name' => htmlescape((string) self::displayItemTitle($itemclass)),
                                'id' => $val['id'],
                                'items_id' => $val['items_id'],
                                'itemtype' => $val['itemtype'],
                                'width' => $val['width'],
                                'height' => $val['height'],
                                'x_coordinates' => $val['x_coordinates'],
                                'y_coordinates' => $val['y_coordinates'],
                                "hideLabel" => $val['hideLabel'],
                                "hideTooltip" => $val['hideTooltip'],
                                "tooltip" => htmlescape((string) $val['tooltip']),
                                "color" => $val['color'],
                                "rotate" => $val['rotate'],
                                "outline" => $val['outline'],
                                "outlineWidth" => $val['outlineWidth'],
                                "pattern" => $val['pattern'],
                                "outlineWidth" => $val['outlineWidth'],
                                "labelSize" => $val['labelSize'],
                                "shape" => $val['shape']];

                            if (Plugin::isPluginActive("resources") && ($val['itemtype'] == Resource::class)) {
                                $val['picture'] = $itemclass->fields['picture'];
                                $options['img'] = $CFG_GLPI['url_base'] . PLUGIN_RESOURCES_WEBDIR . '/pics/nobody.png';
                                if (!($val['picture'] == null)) {
                                    $options['img'] = $CFG_GLPI['url_base'] . PLUGIN_RESOURCES_WEBDIR . '/front/picture.send.php?file=' . rawurlencode(basename((string) $val['picture']));
                                }
                                $objects[$val['id']] = $options;
                            } elseif (($val['itemtype'] == Location::class)) {
                                $options['img'] = $CFG_GLPI['url_base'] . PLUGIN_POSITIONS_NOTFULL_DIR . '/pics/door.png';
                                $objects[$val['id']] = $options;
                            } elseif (($val['itemtype'] == Socket::class)) {
                                $options['img'] = $CFG_GLPI['url_base'] . PLUGIN_POSITIONS_NOTFULL_DIR . '/pics/socket.png';
                                $objects[$val['id']] = $options;
                            } else {
                                $itemtype = $val['itemtype'] . "Type";
                                $dbu = new DbUtils();
                                $typefield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($itemtype));
                                $imgitem = new ImageItem();
                                if (isset($itemclass->fields[$typefield])) {
                                    $val['img'] = $imgitem->displayItemImage($itemclass->fields[$typefield], $classe);
                                }
                                $options['img'] = $CFG_GLPI['url_base'] . PLUGIN_POSITIONS_NOTFULL_DIR . '/pics/nothing.png';
                                // canvasXpress puts the URL in HTML: a picture of the pics
                                // directory only, its name encoded
                                if (!empty($val['img']) && self::getPicturePath((string) $val['img']) !== null) {
                                    $options['img'] = $CFG_GLPI['url_base'] . PLUGIN_POSITIONS_NOTFULL_DIR
                                        . '/front/map.send.php?type=pics&file=' . rawurlencode((string) $val['img']);
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

            $objects[] = ['canvas_id' => '0',
                'name' => '',
                'id' => '',
                'items_id' => '',
                'itemtype' => '',
                'width' => 0.5,
                'height' => 0.5,
                'x_coordinates' => -300,
                'y_coordinates' => 80,
                'testEvent' => $eventless,
                'hideLabel' => true,
                'img' => $CFG_GLPI['url_base'] . PLUGIN_POSITIONS_NOTFULL_DIR . '/pics/nothing.png'];
            self::drawCanvas($objects, $params);
        }
    }


    /**
     * Draws the canvas of the map with the given items
     * @param $items
     * @param $params
     */
    public static function drawCanvas($items, $params)
    {
        global $CFG_GLPI;

        $nodes = [];
        // Rights
        $eventless = false;
        if (!Session::haveRight('plugin_positions', UPDATE) || (isset($params['menuoff']) && $params['menuoff'] == 1)) {
            $eventless = true;
        }

        // Background picture
        $input = [];
        $input['id'] = "Fond";
        $input['shape'] = 'image';
        $input['eventless'] = true;
        $input['width'] = $params['largeur'];
        $input['height'] = $params['hauteur'];
        $input['imagePath'] = $CFG_GLPI['url_base'] . PLUGIN_POSITIONS_NOTFULL_DIR . '/front/map.send.php?docid=' . $params['docid'];
        $input['color'] = 'black';
        $input['testEvent'] = $eventless;
        $input['x'] = 250;
        $input['y'] = 250;
        $input['hideLabel'] = true;
        $nodes['nodes'][] = $input;

        $highlight = [];

        // Nodes
        foreach ($items as $node) {
            $node['imagePath'] = $node['img'];
            $node['x'] = $node['x_coordinates'] + 0;
            $node['y'] = $node['y_coordinates'] + 0;
            if (empty($node['hideTooltip'])) {
                unset($node['hideTooltip']);
            }
            if (empty($node['hideLabel'])) {
                unset($node['hideLabel']);
            }
            $node['testEvent'] = $eventless;
            $node['shape'] = 'image';
            $nodes['nodes'][] = $node;

            // The position the map was opened for
            if (!empty($params['id']) && isset($node['id']) && (int) $node['id'] === (int) $params['id']) {
                $highlight[] = (int) $params['id'];
            }
        }

        // Canvas settings
        $canvas_config = ['graphType' => 'Network',
            'backgroundGradient2Color' => 'white', // background color 1
            'backgroundGradient1Color' => 'white', // background color 2
            'gradient' => false, // gradient
            'networkFreezeOnLoad' => true,
            'nodeFontColor' => 'rgb(29,34,43)', // color of the nodes
            'imageDir' => $CFG_GLPI['url_base'] . PLUGIN_POSITIONS_NOTFULL_DIR . "/lib/canvas/images/",
            //'zoom'                   => 1.0, // initial zoom
            //'zoomStep'               => 0.5, // zoom step
            'calculateLayout' => false,
            'disableConfigurator' => true,
            'showCode' => false, // display of the code
            'nodeFontSize' => 6,
            'fontName' => 'Verdana',
            'resizable' => false,
        ];

        // Canvas and its events
        echo "<script>
         $(document).ready(function(){
            Ext.QuickTips.init();
               var panel = new Ext.canvasXpress({
               renderTo: 'Carte',
               languages : " . json_encode(self::getJsLanguages()) . " ,
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
                                    title:'Informations - '+Ext.util.Format.htmlEncode(n.name),
                                    width:'600px',
                                    height:'800px',
                                    modal:false,
                                    layout:'fit',
                                    resizable:false,
                                    autoLoad: function(n){
                                                url: 'showinfos.php?items_id='+encodeURIComponent(n.items_id)+'&id='+encodeURIComponent(n.id)+
                                            '&img='+encodeURIComponent(n.imagePath)+'&itemtype='+encodeURIComponent(n.itemtype)}

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
                            title:'Informations - '+Ext.util.Format.htmlEncode(n.name),
                            width:'600px',
                            height:'800px',
                            modal:false,
                            layout:'fit',
                            resizable:false,
                            autoLoad: {
                            url: 'showinfos.php?items_id='+encodeURIComponent(n.items_id)+'&id='+encodeURIComponent(n.id)+
                            '&img='+encodeURIComponent(n.imagePath)+'&itemtype='+encodeURIComponent(n.itemtype)}
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

    public static function displayItemTitle($itemclass)
    {

        $text = "";
        if (isset($itemclass->fields["name"])
            && !empty($itemclass->fields["name"])) {
            if (Plugin::isPluginActive("resources")
                && $itemclass->getType() == Resource::class) {
                $text .= Resource::getResourceName($itemclass->getID());
            } else {
                $text .= $itemclass->fields["name"];
            }
        }
        if (Session::getCurrentInterface() != 'central') {
            $text .= Info::getCallValue($itemclass);
        }
        return $text;
    }

    /** Information shown in the pop-up of an item */
    public static function showOverlay($srcimg, $itemclass, $infos)
    {
        // Picture of the item: of its type (pics directory), of the resource, none for a location
        $picture = null;
        if ($itemclass->getType() != Resource::class
            && $itemclass->getType() != Location::class
            && $itemclass->getType() != Socket::class) {
            $picture = ['url' => PLUGIN_POSITIONS_WEBDIR . '/pics/nothing.png', 'width' => 30, 'height' => 30, 'mime' => null];

            // The picture comes from the request, as a file name or the URL of the canvas
            // (map.send.php?type=pics&file=<name>): only a picture of the pics directory is shown
            $name  = (string) $srcimg;
            $query = (string) parse_url($name, PHP_URL_QUERY);
            if ($query !== '') {
                parse_str($query, $params);
                $name = (string) ($params['file'] ?? '');
            }
            $path = self::getPicturePath($name);
            if ($path !== null && ($sizes = getimagesize($path)) !== false) {
                $picture = [
                    'url'    => PLUGIN_POSITIONS_WEBDIR . '/front/map.send.php?file=' . rawurlencode($name),
                    'width'  => (int) $sizes[0],
                    'height' => (int) $sizes[1],
                    'mime'   => 'image/' . pathinfo($path, PATHINFO_EXTENSION),
                ];
            }
        } elseif (Plugin::isPluginActive("resources")
            && $itemclass->getType() == Resource::class) {
            $picture = ['url' => PLUGIN_RESOURCES_WEBDIR . '/pics/nobody.png', 'width' => 90, 'height' => 90, 'mime' => null];
            $res = new Resource();
            if ($res->getFromDB($itemclass->fields["id"]) && !empty($res->fields["picture"])) {
                // The picture of the resource, never the request: its file name is a bare
                // component, its type the one of the real image
                $name = basename((string) $res->fields["picture"]);
                $path = GLPI_PLUGIN_DOC_DIR . "/resources/" . $name;
                if (is_file($path) && ($sizes = @getimagesize($path)) !== false) {
                    $picture = [
                        'url'    => PLUGIN_RESOURCES_WEBDIR . '/front/picture.send.php?file=' . rawurlencode($name),
                        'width'  => (int) $sizes[0],
                        'height' => (int) $sizes[1],
                        'mime'   => (string) $sizes['mime'],
                    ];
                }
            }
        }

        // Fields configured for the type of the item
        $fields = [];
        foreach ($infos ?: [] as $info) {
            if ($itemclass->getType() == $info['itemtype']) {
                $fields = array_merge($fields, Info::getFields($info, $itemclass));
            }
        }

        TemplateRenderer::getInstance()->display('@positions/overlay.html.twig', [
            'picture'     => $picture,
            'fields'      => $fields,
            'direct_link' => Session::getCurrentInterface() == 'central' || $itemclass->getType() == Location::class
                ? Info::getDirectLink($itemclass)
                : null,
            'phones'      => Info::getCallPhones($itemclass),
            'config_url'  => Session::haveRight('plugin_positions', UPDATE) && $itemclass->canView()
                ? PLUGIN_POSITIONS_WEBDIR . '/front/info.php'
                : null,
            'phone_icon'  => PLUGIN_POSITIONS_WEBDIR . '/pics/miniphones.png',
        ]);
    }

    /** Directory of the pictures of the equipment types (front/map.send.php?file=) */
    public static function getPicturesDir(): string
    {
        return GLPI_PLUGIN_DOC_DIR . '/positions/pics';
    }

    /**
     * Path of a picture of the pics directory, null unless the name is a bare image file name
     * that resolves inside that directory
     */
    public static function getPicturePath(string $name): ?string
    {
        if ($name === '' || basename($name) !== $name || !preg_match('/\.(png|jpe?g|gif)$/i', $name)) {
            return null;
        }
        $dir  = realpath(self::getPicturesDir());
        $path = realpath(self::getPicturesDir() . '/' . $name);
        if ($dir === false || $path === false || !str_starts_with($path, $dir . DIRECTORY_SEPARATOR) || !is_file($path)) {
            return null;
        }

        return $path;
    }

    /**
     * A picture of the pics directory, the only files of the plugin served by map.send.php:
     * the name is reduced to a bare file name, the final path kept inside the directory
     */
    public static function getPictureResponse(string $name): \Symfony\Component\HttpFoundation\Response
    {
        $path = self::getPicturePath($name);
        if ($path === null) {
            throw new \Glpi\Exception\Http\NotFoundHttpException();
        }

        return Toolbox::getFileAsResponse($path, basename($path));
    }

    /**
     * Whether the document is the map of a location the user may read
     */
    public static function isLocationMap(int $documents_id): bool
    {
        $location = new Location();
        foreach ((new Document_Item())->find(['documents_id' => $documents_id, 'itemtype' => Location::class]) as $link) {
            if ($location->can((int) $link['items_id'], READ)) {
                return true;
            }
        }

        return false;
    }

    //from items
    public static function showPluginFromItems($itemtype, $ID, $withtemplate = '')
    {
        global $DB;

        $item = getItemForItemtype($itemtype);
        if (!$item) {
            return;
        }
        $canread = $item->can($ID, READ);

        $self = new self();

        $iterator = $DB->request([
            'SELECT' => ['glpi_plugin_positions_positions.*'],
            'FROM'   => 'glpi_plugin_positions_positions',
            'WHERE'  => [
                'glpi_plugin_positions_positions.items_id' => (int) $ID,
                'glpi_plugin_positions_positions.itemtype' => $itemtype,
            ] + getEntitiesRestrictCriteria('glpi_plugin_positions_positions', '', '', $self->maybeRecursive()),
            'ORDER'  => 'glpi_plugin_positions_positions.name',
        ]);

        if (!count($iterator)) {
            self::showAddFromItem($itemtype, $ID);
            return;
        }

        $candelete = Session::haveRight('plugin_positions', DELETE) && $withtemplate < 2;
        $rows      = [];
        foreach ($iterator as $data) {
            $name = (string) $data["name"];
            if ($_SESSION["glpiis_ids_visible"] || $name === '') {
                $name .= " (" . (int) $data["id"] . ")";
            }
            $linked = $withtemplate != 3 && $canread
                && (in_array($data['entities_id'], $_SESSION['glpiactiveentities']) || $data["is_recursive"]);

            $rows[] = [
                'name'         => $name,
                'url'          => $linked ? PLUGIN_POSITIONS_WEBDIR . "/front/position.form.php?id=" . (int) $data["id"] : null,
                // Link opening the map in a window, already escaped by showGeolocLink()
                'geoloc_link'  => $linked ? self::showGeolocLink($itemtype, $ID, $data["id"]) : '',
                'entity'       => Dropdown::getDropdownName("glpi_entities", $data['entities_id']),
                'x'            => $data["x_coordinates"],
                'y'            => $data["y_coordinates"],
                'is_deleted'   => $data["is_deleted"] == 1,
                'delete_form'  => $candelete && $data["is_deleted"] != 1
                    ? Html::getSimpleForm(
                        PLUGIN_POSITIONS_WEBDIR . '/front/position.form.php',
                        'delete_item',
                        _x('button', 'Delete permanently'),
                        ['id' => (int) $data["id"]],
                        'ti-trash',
                        "class='btn btn-sm btn-ghost-danger' title='" . _sx('button', 'Delete permanently') . "'"
                    )
                    : '',
            ];
        }

        TemplateRenderer::getInstance()->display('@positions/item_positions.html.twig', [
            'rows'         => $rows,
            'multi_entity' => Session::isMultiEntitiesMode(),
            'candelete'    => $candelete,
        ]);
    }

    public static function showAddFromItem($itemtype, $items_id)
    {
        if (!Session::haveRight('plugin_positions', READ)) {
            return false;
        }

        $itemclass = getItemForItemtype($itemtype);
        if (!$itemclass || !$itemclass->getFromDB($items_id)) {
            return false;
        }

        $name = $itemclass->fields["name"] ?? '';
        if (!empty($itemclass->fields["contact_num"])) {
            $name = $itemclass->fields["contact_num"];
        }
        $locations_id = $itemclass->fields["locations_id"] ?? 0;

        TemplateRenderer::getInstance()->display('@positions/item_add_position.html.twig', [
            'form_url'      => PLUGIN_POSITIONS_WEBDIR . '/front/position.form.php',
            'name'          => $name,
            'locations_id'  => $locations_id,
            'location_name' => $locations_id ? Dropdown::getDropdownName("glpi_locations", $locations_id) : '',
            'items_id'      => (int) $items_id,
            'itemtype'      => $itemtype,
            'entities_id'   => $itemclass->fields["entities_id"] ?? 0,
            'canupdate'     => Session::haveRight('plugin_positions', UPDATE),
        ]);
    }

    public static function showGeolocInfos($itemtype, $id, $positions_id = 0)
    {

        $dbu = new DbUtils();

        if ($itemtype != User::class
            && $itemtype != Resource::class) {
            $item = new $itemtype();
            $item->getFromDB($id);

            $restrict = ["items_id" => $id,
                "is_deleted" => 0,
                "itemtype" => $itemtype];
            $datas = $dbu->getAllDataFromTable('glpi_plugin_positions_positions', $restrict);
            if (!empty($datas)) {
                foreach ($datas as $data) {
                    $positions_id = $data['id'];
                }
            }
            $documents_id = self::getDocument($item->fields['locations_id']);
            $locations_id = $item->fields['locations_id'];
        } else {
            if (Plugin::isPluginActive("resources")) {
                // resource linked to the user

                if ($itemtype != Resource::class) {
                    $condition = ["items_id" => $id,
                        "itemtype" => User::class];

                    $infos = $dbu->getAllDataFromTable('glpi_plugin_resources_resources_items', $condition);
                    if (!empty($infos)) {
                        foreach ($infos as $info) {
                            $ressource = new Resource();
                            $ressource->getFromDB($info['plugin_resources_resources_id']);

                            $restrict = ["items_id" => $ressource->getID(),
                                "is_deleted" => 0,
                                "entities_id" => $ressource->fields['entities_id'],
                                "itemtype" => Resource::class];
                            $datas = $dbu->getAllDataFromTable('glpi_plugin_positions_positions', $restrict);
                            if (!empty($datas)) {
                                foreach ($datas as $data) {
                                    if (isset($data['id'])) {
                                        if (isset($ressource->fields['locations_id'])
                                            && ($ressource->fields['locations_id'] > 0)) {
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
                    $ressource = new Resource();
                    if ($ressource->getFromDB($id)) {
                        $restrict = ["items_id" => $ressource->fields['id'],
                            "is_deleted" => 0,
                            "entities_id" => $ressource->fields['entities_id'],
                            "itemtype" => $ressource->getType()];
                        $datas = $dbu->getAllDataFromTable('glpi_plugin_positions_positions', $restrict);
                        if (!empty($datas)) {
                            foreach ($datas as $data) {
                                if (isset($data['id'])) {
                                    if (isset($ressource->fields['locations_id'])
                                        && ($ressource->fields['locations_id'] > 0)) {
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
        $out = [];
        $Doc = new Document();
        if (isset($documents_id) && $Doc->getFromDB($documents_id)) {
            $out["positions_id"] = $positions_id;
            $out["download"] = 1;
            $out["locations_id"] = $locations_id;
            $out["from_treeview"] = 1;
        }

        return $out;
    }


    public static function showGeolocLink($itemtype, $id, $positions_id = 0)
    {
        global $CFG_GLPI;

        $dbu = new DbUtils();
        if ($itemtype != User::class
            && $itemtype != Resource::class) {
            $position = new Position();
            $position->getFromDBByCrit(['itemtype' => $itemtype,
                'items_id' => $id]);
            $documents_id = self::getDocument($position->fields['locations_id']);
            $locations_id = $position->fields['locations_id'];
        } else {
            // Resources plugin enabled
            if (Plugin::isPluginActive("resources")) {
                // resource linked to the user

                if ($itemtype != Resource::class) {
                    $condition = ["items_id" => $id,
                        "itemtype" => User::class];

                    $infos = $dbu->getAllDataFromTable('glpi_plugin_resources_resources_items', $condition);
                    if (!empty($infos)) {
                        foreach ($infos as $info) {
                            $ressource = new Resource();
                            $ressource->getFromDB($info['plugin_resources_resources_id']);

                            $restrict = ["items_id" => $ressource->getID(),
                                "is_deleted" => 0,
                                "entities_id" => $ressource->fields['entities_id'],
                                "itemtype" => Resource::class];
                            $datas = $dbu->getAllDataFromTable('glpi_plugin_positions_positions', $restrict);
                            if (!empty($datas)) {
                                foreach ($datas as $data) {
                                    if (isset($data['id'])) {
                                        if (isset($ressource->fields['locations_id'])
                                            && ($ressource->fields['locations_id'] > 0)) {
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
                    $ressource = new Resource();
                    if ($ressource->getFromDB($id)) {
                        $restrict = ["items_id" => $ressource->fields['id'],
                            "is_deleted" => 0,
                            "entities_id" => $ressource->fields['entities_id'],
                            "itemtype" => $ressource->getType()];
                        $datas = $dbu->getAllDataFromTable('glpi_plugin_positions_positions', $restrict);
                        if (!empty($datas)) {
                            foreach ($datas as $data) {
                                if (isset($data['id'])) {
                                    if (isset($data['locations_id'])
                                        && ($data['locations_id'] > 0)) {
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
        $Doc = new Document();
        if (isset($documents_id) && $Doc->getFromDB($documents_id)) {
            $out .= "&nbsp;<a href='#' onClick=\"var w = window.open('" . PLUGIN_POSITIONS_WEBDIR
                . "/front/geoloc.php?positions_id="
                . $positions_id . "&amp;download=1&amp;locations_id=" . $locations_id
                . "' ,'glpipopup',
            'height=650, width=1400, top=100, left=100, scrollbars=yes' );
            w.focus();\" ><i class='fa-1x fas fa-map-location-dot'></i></a>&nbsp;";
        }

        return $out;
    }

    public static function showGeolocLocation($id, $positions_id = 0)
    {
        global $CFG_GLPI;

        // Fragment loaded by the info window of the map (front/showinfos.php): Ext JS and
        // geoloc.js (Position.openWindow()) are already on the page
        $locations_id = (int) $id;
        $documents_id = self::getDocument($locations_id);

        $Doc = new Document();
        if (isset($documents_id)
            && $Doc->getFromDB($documents_id)) {
            $target = PLUGIN_POSITIONS_WEBDIR . "/front/geoloc.php?positions_id="
                . (int) $positions_id . "&download=1&locations_id=" . $locations_id;
            echo "<script type='text/javascript'>
         Position.openWindow(" . json_encode($target) . ");
         </script>";
        }
    }


    /**
     * Display information on treeview plugin
     *
     * @params itemtype, id, pic, url, name
     *
     * @return params
     **/
    public static function showPositionTreeview($params)
    {
        global $CFG_GLPI;

        $out = self::showGeolocInfos($params['itemtype'], $params['id']);
        if (!empty($out["positions_id"])) {
            $params['url'] = PLUGIN_POSITIONS_WEBDIR . "/front/geoloc.php?positions_id="
                . $out["positions_id"] . "&amp;download=" . $out["download"]
                . "&amp;locations_id=" . $out["locations_id"] . "&amp;from_treeview=" . $out["from_treeview"];

            $params['pic'] = "../positions/pics/sm_globe18.png";
        }

        return $params;
    }

    /**
     * get all languages for js ext-canvas
     *
     * @return array $languages
     */
    public static function getJsLanguages()
    {

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
