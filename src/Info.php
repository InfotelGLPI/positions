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
use CommonTreeDropdown;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Resources\Resource;
use Html;
use Plugin;
use Session;
use Toolbox;
use Location;
use Phone;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Info
 */
class Info extends CommonDBTM
{

    public $dohistory  = true;
    static $rightname  = "plugin_positions";
    protected $usenotepad = true;

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

   /**
    * Return the localized name of the current Type
    *
    * @return string
    **/
    public static function getTypeName($nb = 0)
    {
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
    public function rawSearchOptions()
    {

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
    function defineTabs($options = [])
    {

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
    function showForm($ID, $options = [])
    {

        if (!$this->canView()) {
            return false;
        }

        $this->initForm($ID, $options);

        TemplateRenderer::getInstance()->display('@positions/info_form.html.twig', [
            'item'             => $this,
            'params'           => $options,
            'itemtype_field'   => $this->getItemtypeField($ID),
            'criterias_field'  => self::getCriteriasField($this),
        ]);

        return true;
    }

   /**
    * The item type of the configuration: its name once saved, else a dropdown of the types
    * without configuration yet, which reloads the list of their fields
    *
    * @param int $ID
    *
    * @return string HTML
    **/
    function getItemtypeField($ID)
    {
       //Criteria already added : only display the selected itemtype
        if ($ID > 0) {
            // Stored by an older version without check: the types of the plugin only
            if (!in_array($this->fields['itemtype'], Position::getTypes(), true)
                || !($item = getItemForItemtype($this->fields['itemtype']))) {
                return '';
            }
            return htmlescape($item->getTypeName())
                . Html::hidden('itemtype', ['value' => $this->fields['itemtype']]);
        }

        $possible_types = Position::getTypes();
        $dbu            = new DbUtils();
        $restrict       = ["is_active"  => 1,
                        "is_deleted" => 0] + $dbu->getEntitiesRestrictCriteria(
                            "glpi_plugin_positions_infos",
                            '',
                            '',
                            $this->maybeRecursive()
                        );
        foreach ($dbu->getAllDataFromTable('glpi_plugin_positions_infos', $restrict) as $type) {
            foreach ($possible_types as $key => $val) {
                if ($type["itemtype"] == $val) {
                    unset($possible_types[$key]);
                }
            }
        }

       //Add criteria : display dropdown
        $options = [0 => Dropdown::EMPTY_VALUE];
        foreach ($possible_types as $itemtype) {
            $item = getItemForItemtype($itemtype);
            if ($item && $item->can(-1, READ)) {
                $options[$itemtype] = $item->getTypeName();
            }
        }
        asort($options);

        $rand = mt_rand();
        $out  = Dropdown::showFromArray('itemtype', $options, ['rand' => $rand, 'display' => false]);
        $out .= Ajax::updateItemOnSelectEvent(
            "dropdown_itemtype$rand",
            "span_fields",
            PLUGIN_POSITIONS_WEBDIR . "/ajax/dropdownInfoFields.php",
            ['itemtype' => '__VALUE__', 'id' => $ID],
            false
        );

        return $out;
    }

   /**
    * List of the fields that may be displayed for the item type of the configuration
    *
    * @param CommonDBTM $config
    *
    * @return string HTML of the multiple select, empty without a valid item type
    **/
    static function getCriteriasField(CommonDBTM $config)
    {
        global $DB;

        // The item type may be posted: only the types of the plugin are instantiated
        if (!isset($config->fields['itemtype']) || !$config->fields['itemtype']
            || !in_array($config->fields['itemtype'], Position::getTypes(), true)
            || !($item = getItemForItemtype($config->fields['itemtype']))) {
            return '';
        }

        $config_fields = explode(',', (string) ($config->fields['fields'] ?? ''));
        $dbu           = new DbUtils();
        $unallowed     = self::getUnallowedFields($config->fields['itemtype']);
        $elements      = [];
        $selected      = [];

        foreach ($DB->listFields($dbu->getTableForItemType($config->fields['itemtype'])) as $field) {
            $searchOption = $item->getSearchOptionByField(
                'field',
                $field['Field'],
                $dbu->getTableForItemType($item->getType())
            );

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

            if (!empty($searchOption) && !in_array($field['Field'], $unallowed)) {
                $elements[$field['Field']] = (string) $searchOption['name'];
                if (in_array($field['Field'], $config_fields)) {
                    $selected[] = $field['Field'];
                }
            }
        }

        return Dropdown::showFromArray('_fields', $elements, [
            'multiple' => true,
            'values'   => $selected,
            'width'    => '100%',
            'display'  => false,
        ]);
    }

   /**
    * Perform checks to be sure that an itemtype and at least a field are selected
    *
    * @param input the values to insert in DB
    *
    * @return input the values to insert, but modified
    **/
    static function checkBeforeInsert($input)
    {

        if (empty($input['itemtype']) || empty($input['_fields']) || !is_array($input['_fields'])) {
            Session::addMessageAfterRedirect(
                __("It's mandatory to select a type and at least one field", 'positions'),
                true,
                ERROR
            );
            return false;
        }
        // The item type is posted and later instantiated: the types of the plugin only
        if (!in_array($input['itemtype'], Position::getTypes(), true)) {
            return false;
        }
        $input['fields'] = implode(',', self::filterFields($input['itemtype'], $input['_fields']));
        unset($input['_fields']);

        return $input;
    }

    /**
     * Posted fields kept among the columns of the table of the item type, the unallowed ones
     * excepted
     *
     * @param array $fields
     *
     * @return list<string>
     */
    static function filterFields(string $itemtype, array $fields): array
    {
        global $DB;

        $columns = array_column($DB->listFields(getTableForItemType($itemtype)), 'Field');
        $allowed = array_diff($columns, (array) self::getUnallowedFields($itemtype));

        return array_values(array_intersect(array_map('strval', $fields), $allowed));
    }


   /**
    * Prepare input datas for adding the item
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    **/
    function prepareInputForAdd($input)
    {
        // Shared with the child entities only by who may reach all of them, as on update:
        // check() only looks at the entity itself
        if (!empty($input['is_recursive'])
            && !Session::haveRecursiveAccessToEntity((int) ($input['entities_id'] ?? -1))) {
            $input['is_recursive'] = 0;
        }

        return self::checkBeforeInsert($input);
    }


   /**
    * Prepare input datas for updating the item
    *
    * @param array $input data used to update the item
    *
    * @return array the modified $input array
    **/
    function prepareInputForUpdate($input)
    {
        // The item type of a configuration is fixed once created
        unset($input['itemtype']);

        // Its entity too: update() does not check the entity posted. It is only shared with the
        // child entities by who may reach all of them
        unset($input['entities_id']);
        if (!empty($input['is_recursive'])
            && !Session::haveRecursiveAccessToEntity((int) $this->fields['entities_id'])) {
            unset($input['is_recursive']);
        }

        if (isset($input['_fields']) && is_array($input['_fields'])) {
            $input['fields'] = implode(',', self::filterFields((string) $this->fields['itemtype'], $input['_fields']));
        }
        unset($input['_fields']);

        return $input;
    }

   /**
    * @param $itemclass
    *
    * @return array
    */
    static function getUnallowedFields($itemclass)
    {

        switch ($itemclass) {
            case "Computer":
                return ['date_mod',
                         'notepad',
                         'os_license_number',
                         'os_licenseid',
                         'autoupdatesystems_id',
                         'manufacturers_id',
                         'is_ocs_import'];
            break;
            case "Printer":
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
            case "NetworkEquipment":
                return ['is_recursive',
                         'date_mod',
                         'notepad',
                         'ram',
                         'networkequipmentfirmwares_id',
                         'manufacturers_id'];
            break;
            case "Monitor":
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
            case "Peripheral":
                return ['date_mod',
                         'notepad',
                         'brand',
                         'manufacturers_id',
                         'is_global'];
            break;
            case "Phone":
                return ['date_mod',
                         'notepad',
                         'brand',
                         'have_headset',
                         'have_hp',
                         'manufacturers_id',
                         'phonepowersupplies_id',
                         'is_global'];
            break;
            case Resource::class:
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
    * Fields of the item shown in the information window of the map, as configured for its type
    *
    * @param array      $item      configuration (glpi_plugin_positions_infos)
    * @param CommonDBTM $itemclass item clicked on the map
    *
    * @return array list of ['label' => string, 'value' => string, 'phone' => bool]
    */
    static function getFields($item, $itemclass)
    {
        global $DB;

        $fields = [];
        if (empty($item["fields"])
          || !in_array($item['itemtype'], Position::getTypes(), true)
          || !($target = getItemForItemtype($item['itemtype']))) {
            return $fields;
        }

        $input = explode(',', $item['fields']);
        $dbu   = new DbUtils();
        foreach ($DB->listFields($dbu->getTableForItemType($item['itemtype'])) as $field) {
            if (!in_array($field['Field'], $input)) {
                continue;
            }
            $searchOption = $target->getSearchOptionByField(
                'field',
                $field['Field'],
                $dbu->getTableForItemType($target->getType())
            );

            if (empty($searchOption)) {
                if ($table = $dbu->getTableNameForForeignKeyField($field['Field'])) {
                    $crit = $dbu->getItemForItemtype($dbu->getItemTypeForTable($table));
                    if ($crit instanceof CommonTreeDropdown) {
                        $searchOption = $target->getSearchOptionByField('field', 'completename', $table);
                    } elseif (Plugin::isPluginActive('resources')) {
                        $searchOption = $target->getSearchOptionByField('field', 'name', $table);
                    }
                }
            }

            if (!empty($searchOption)
             && !empty($itemclass->fields[$field['Field']])
             && !in_array($field['Field'], self::getUnallowedFields($item['itemtype']))) {
                $fields[] = self::getFieldsValue($searchOption, $field, $itemclass);
            }
        }

        return $fields;
    }

   /**
    * @param $searchOption
    *
    * @return bool|string
    */
    static function getTypeFields($searchOption)
    {

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
                               'glpi_printertypes',
                               'glpi_passivedcequipmentmodels',
                               'glpi_passivedcequipmenttypes'];

        if (in_array($searchOption['table'], $dropdown_tables)) {
            return "dropdown";
        }
        return false;
    }

   /**
    * Value of a field of the item, as text: escaped by the template
    *
    * @param $searchOption
    * @param $field
    * @param $itemclass
    *
    * @return array ['label' => string, 'value' => string, 'phone' => bool]
    */
    static function getFieldsValue($searchOption, $field, $itemclass)
    {
        $display = (string) $itemclass->fields[$field['Field']];
        $phone   = false;

        $type = self::getTypeFields($searchOption);
        if ($type == 'dropdown') {
            $display = (string) Dropdown::getDropdownName($searchOption['table'], $display);
        } elseif (isset($searchOption['datatype'])
                 && $searchOption['datatype'] == 'decimal') {
            $display = Html::formatNumber($display, 2);
        } elseif ($searchOption['table'] == 'glpi_users') {
            $dbu     = new DbUtils();
            $display = (string) $dbu->getUserName($display);
        } elseif ($searchOption['field'] == 'contact_num' || $searchOption['field'] == 'number_line') {
            $phone = true;
        }

        return [
            'label' => (string) $searchOption['name'],
            'value' => $display,
            'phone' => $phone,
        ];
    }

   /**
    * Link to the item, or to the map of a location
    *
    * @param $itemclass
    *
    * @return array ['url' => string, 'title' => string]
    */
    static function getDirectLink($itemclass)
    {
        $title = (string) ($itemclass->fields["name"] ?? '');

        if ($title !== '' && $itemclass->getType() != Location::class) {
            if (Plugin::isPluginActive("resources")
             && $itemclass->getType() == Resource::class) {
                $title = $itemclass->fields["firstname"] . " " . $itemclass->fields["name"];
            }
            return [
                'url'   => Toolbox::getItemTypeFormURL($itemclass->getType()) . "?id=" . (int) $itemclass->fields["id"],
                'title' => $title,
            ];
        }

        return [
            'url'   => PLUGIN_POSITIONS_WEBDIR . "/front/map.php?locations_id=" . (int) $itemclass->fields["id"],
            'title' => $title,
        ];
    }

   /**
    * Phones of the user linked to a resource (plugin resources)
    *
    * @param CommonDBTM $itemclass
    *
    * @return array list of ['location' => string, 'contact_num' => ?string, 'number_line' => ?string]
    */
    static function getCallPhones($itemclass)
    {
        $phones_list = [];
        if ($itemclass->getType() != Resource::class) {
            return $phones_list;
        }

        $dbu   = new DbUtils();
        $infos = $dbu->getAllDataFromTable('glpi_plugin_resources_resources_items', [
            "plugin_resources_resources_id" => $itemclass->fields['id'],
            "itemtype"                      => User::class,
        ]);
        foreach ($infos as $info) {
            if (empty($info['items_id'])) {
                continue;
            }
            $condition = ["users_id"    => $info['items_id'],
                 "is_deleted"  => 0,
                 "is_template" => 0,
                 "entities_id" => $itemclass->fields['entities_id'],
                 "NOT"         => ["contact_num" => 0]];

            // Numbers and locations of the phones of the user: only the phones
            // the viewer may read, not on the sole right of the cartography
            $phone_item = new Phone();
            $phones = array_filter(
                $dbu->getAllDataFromTable('glpi_phones', $condition),
                static fn(array $row): bool => $phone_item->can((int) $row['id'], READ),
            );
            foreach ($phones as $phone) {
                $phones_list[] = [
                    'location'    => count($phones) > 1
                        ? (string) Dropdown::getDropdownName("glpi_locations", $phone["locations_id"])
                        : '',
                    'contact_num' => $phone['contact_num'] !== null ? (string) $phone['contact_num'] : null,
                    'number_line' => $phone['number_line'] !== null ? (string) $phone['number_line'] : null,
                ];
            }
        }

        return $phones_list;
    }

   /**
    * Phone numbers of the user linked to a resource, as plain text (tooltip of the map):
    * the first phone, as the text export always did
    *
    * @param $itemclass
    *
    * @return string
    */
    static function getCallValue($itemclass)
    {
        $display = "";
        $phones  = self::getCallPhones($itemclass);
        if (!empty($phones)) {
            $phone = $phones[0];
            if (!empty($phone['contact_num'])) {
                $display .= "\n" . __('Alternate username number') . " : " . $phone['contact_num'];
            }
            if (!empty($phone['number_line'])) {
                $display .= "\n" . _x('quantity', 'Number of lines') . " :\n" . $phone['number_line'];
            }
        }

        return $display;
    }
}
