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

use Ajax;
use CommonDBTM;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Resources\Resource;
use Html;
use Plugin;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class ImageItem
 */
class ImageItem extends CommonDBTM
{

    static $rightname = "plugin_positions";

    /**
     * The pictures of the equipment types have no entity and apply to every map: viewing them
     * follows the right of the cartography, changing them the one of the configuration
     */
    public static function canCreate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canUpdate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canDelete(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canPurge(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public function canPurgeItem(): bool
    {
        return self::canPurge();
    }

    public function canDeleteItem(): bool
    {
        return self::canDelete();
    }

   /**
    * Dropdown of the item types, then of the items of the chosen type (ajax/dropdownAllItems.php)
    *
    * @param        $myname
    * @param        $types
    * @param int    $value_type
    * @param int    $value
    * @param int    $entity_restrict
    * @param int    $locations_id
    * @param string $action
    *
    * @return string HTML of the widget
    */
    static function getAllItemsDropdown($myname, $types, $value_type = 0, $value = 0, $entity_restrict = -1, $locations_id = -1, $action = 'showItem')
    {
        if ($myname == 'type') {
            $newtypes = array_flip($types);
            unset($newtypes['Location']);
            unset($newtypes['Socket']);
            if (Plugin::isPluginActive("resources")) {
                unset($newtypes[Resource::class]);
            }
            $types = array_flip($newtypes);
        }

        $rand = mt_rand();
        $out  = Dropdown::showItemTypes(
            $myname,
            $types,
            ['emptylabel' => Dropdown::EMPTY_VALUE, 'width' => 150, 'rand' => $rand, 'display' => false]
        );

        $params = ['type'            => '__VALUE__',
                 'value'           => $value,
                 'myname'          => $myname,
                 'action'          => $action,
                 'entity_restrict' => $entity_restrict,
                 'locations_id'    => $locations_id];

        $out .= Ajax::updateItemOnSelectEvent(
            "dropdown_$myname$rand",
            "show_$myname$rand",
            PLUGIN_POSITIONS_WEBDIR . "/ajax/dropdownAllItems.php",
            $params,
            false
        );

        $out .= "<span id='show_$myname$rand'></span>";
        $out .= Html::showToolTip(
            nl2br(__s('Types of materials should be created so that the association can exist', 'positions')),
            ['display' => false]
        );

        if ($value > 0) {
            $params["typetable"] = $value_type;
            $out .= Html::scriptBlock(
                "document.getElementById('dropdown_$myname$rand').value=" . json_encode((string) $value_type) . ";"
            );
            $out .= Ajax::updateItem(
                "show_$myname$rand",
                PLUGIN_POSITIONS_WEBDIR . "/ajax/dropdownAllItems.php",
                $params,
                '',
                false
            );
        }

        return "<div class='d-flex align-items-center gap-2 flex-wrap'>" . $out . "</div>";
    }

   /**
    * @param $values
    */
    function addItemImage($values)
    {
        global $DB;

        if ($values["type"] != '-1') {
            if ($this->getFromDBByCrit(['itemtype' => $values["itemtype"],
             'type'     => $values["type"]])) {
                $this->update(['id'  => $this->fields['id'],
                           'img' => $values["img"]]);
            } else {
                $this->add(['itemtype' => $values["itemtype"],
                        'type'     => $values["type"],
                        'img'      => $values["img"]]);
            }
        } else {
            $type_item = getItemForItemtype($values["itemtype"] . "Type");
            if (!$type_item) {
                return;
            }
            foreach ($DB->request(['SELECT' => ['id'], 'FROM' => $type_item->getTable()]) as $data) {
                $type_table = $data['id'];
                if ($this->getFromDBByCrit(['itemtype' => $values["itemtype"],
                'type'     => $type_table])) {
                    $this->update(['id'  => $this->fields['id'],
                              'img' => $values["img"]]);
                } else {
                    $this->add(['itemtype' => $values["itemtype"],
                           'type'     => $type_table,
                           'img'      => $values["img"]]);
                }
            }
        }
    }

   /**
    * Pictures uploaded in the pics directory of the plugin
    *
    * @return array|null file names, null when the directory does not exist
    **/
    static function getUploadedFiles()
    {
        $dir = GLPI_PLUGIN_DOC_DIR . "/positions/pics";
        if (!is_dir($dir)) {
            return null;
        }

        $uploaded_files = [];
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $uploaded_files[] = $file;
                }
            }
            closedir($handle);
        }
        natcasesort($uploaded_files);

        return array_values($uploaded_files);
    }

    function showConfigForm()
    {
        global $DB;

        // public/positions.js previews the chosen picture in #imageitemPreview
        Html::requireJs('positions');

        $files = self::getUploadedFiles();
        $pictures = [];
        if (!empty($files)) {
            $pictures = [-1 => Dropdown::EMPTY_VALUE] + array_combine($files, $files);
        }

        // Associations already saved, of the types of the plugin only
        $associations = [];
        foreach ($DB->request([
            'FROM'  => $this->getTable(),
            'ORDER' => ['itemtype', 'type'],
        ]) as $ligne) {
            if (!in_array($ligne["itemtype"], Position::getTypes(), true)
                || !($item = getItemForItemtype($ligne["itemtype"]))) {
                continue;
            }
            $type_name = $ligne["type"];
            $typeclass = getItemForItemtype($ligne["itemtype"] . "Type");
            if ($typeclass && $typeclass->getFromDB((int) $ligne["type"]) && isset($typeclass->fields["name"])) {
                $type_name = $typeclass->fields["name"];
            }
            $associations[] = [
                'id'        => (int) $ligne["id"],
                'item_name' => $item->getTypeName(),
                'type_name' => (string) $type_name,
                'img'       => (string) $ligne["img"],
                'img_ext'   => pathinfo((string) $ligne["img"], PATHINFO_EXTENSION),
                'img_url'   => PLUGIN_POSITIONS_WEBDIR . "/front/map.send.php?file=" . rawurlencode((string) $ligne["img"]),
            ];
        }

        TemplateRenderer::getInstance()->display('@positions/imageitem_config.html.twig', [
            'form_url'       => PLUGIN_POSITIONS_WEBDIR . '/front/imageitem.form.php',
            'types_dropdown' => self::getAllItemsDropdown("type", Position::getTypes(), 0, 0, $_SESSION["glpiactive_entity"], -1, 'showType'),
            'pics_exist'     => $files !== null,
            'pictures'       => $pictures,
            'associations'   => $associations,
        ]);
    }


   /**
    * @param $type
    * @param $itemtype
    *
    * @return string
    */
    function displayItemImage($type, $itemtype)
    {

        $image_name = "";
        $restrict   = ["itemtype" => $itemtype];
        $dbu        = new DbUtils();
        $datas      = $dbu->getAllDataFromTable($this->getTable(), $restrict);

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
