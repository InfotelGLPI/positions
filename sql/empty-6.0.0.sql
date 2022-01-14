DROP TABLE IF EXISTS `glpi_plugin_positions_configs`;
CREATE TABLE `glpi_plugin_positions_configs` (
   `id` int unsigned NOT NULL auto_increment,
   `use_view_all_object` tinyint NOT NULL default '0',
   `default_width` int unsigned NOT NULL DEFAULT '25',
   `default_height` int unsigned NOT NULL DEFAULT '30',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_positions_configs`(`use_view_all_object`) VALUES ('0');

DROP TABLE IF EXISTS `glpi_plugin_positions_positions`;
CREATE TABLE `glpi_plugin_positions_positions` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `is_recursive` tinyint NOT NULL default '0',
   `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `locations_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to table glpi_locations',
   `items_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   `itemtype` varchar(100) collate utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `x_coordinates` int unsigned NOT NULL default '0',
   `y_coordinates` int unsigned NOT NULL default '0',
   `width` int unsigned NOT NULL DEFAULT '25',
   `height` int unsigned NOT NULL DEFAULT '30',
   `tooltip` varchar(255) DEFAULT NULL,
   `color` varchar(255) DEFAULT NULL,
   `outline` varchar(255) DEFAULT NULL,
   `outlineWidth` int unsigned NOT NULL DEFAULT '1',
   `shape` varchar(255) DEFAULT NULL,
   `hideLabel` tinyint NOT NULL DEFAULT '0',
   `hideTooltip` tinyint NOT NULL DEFAULT '0',
   `rotate` int unsigned NOT NULL DEFAULT '0',
   `pattern` varchar(255) DEFAULT NULL,
   `labelSize` decimal(11,2) NOT NULL DEFAULT '1',
   `notepad` longtext collate utf8mb4_unicode_ci,
   `date_mod` timestamp NULL DEFAULT NULL,
   `is_deleted` tinyint NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `name` (`name`),
   KEY `entities_id` (`entities_id`),
   KEY `date_mod` (`date_mod`),
   KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_positions_imageitems`;
CREATE TABLE `glpi_plugin_positions_imageitems` (
   `id` int unsigned NOT NULL auto_increment,
   `type` int unsigned NOT NULL default '0',
   `itemtype` varchar(100) collate utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `img` VARCHAR( 50 ) collate utf8mb4_unicode_ci NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_positions_infos`;
CREATE TABLE `glpi_plugin_positions_infos` (
   `id` int unsigned NOT NULL auto_increment,
   `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `entities_id` int unsigned NOT NULL default '0',
   `is_recursive` tinyint NOT NULL default '0',
   `itemtype` varchar(100) collate utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `fields` text collate utf8mb4_unicode_ci NOT NULL,
   `comment` text collate utf8mb4_unicode_ci,
   `notepad` longtext collate utf8mb4_unicode_ci,
   `date_mod` timestamp NULL DEFAULT NULL,
   `is_active` tinyint NOT NULL DEFAULT '0',
   `is_deleted` tinyint NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginPositionsPosition','2','2','0');
