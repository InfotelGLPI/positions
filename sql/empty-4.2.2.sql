DROP TABLE IF EXISTS `glpi_plugin_positions_configs`;
CREATE TABLE `glpi_plugin_positions_configs` (
   `id` int(11) NOT NULL auto_increment,
   `use_view_all_object` tinyint(1) NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_plugin_positions_configs`(`use_view_all_object`) VALUES ('0');

DROP TABLE IF EXISTS `glpi_plugin_positions_positions`;
CREATE TABLE `glpi_plugin_positions_positions` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `is_recursive` tinyint(1) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `locations_id` int(11) NOT NULL default '0' COMMENT 'RELATION to table glpi_locations',
   `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `x_coordinates` int(11) NOT NULL default '0',
   `y_coordinates` int(11) NOT NULL default '0',
   `width` int(11) NOT NULL DEFAULT '25',
   `height` int(11) NOT NULL DEFAULT '30',
   `tooltip` varchar(255) DEFAULT NULL,
   `color` varchar(255) DEFAULT NULL,
   `outline` varchar(255) DEFAULT NULL,
   `outlineWidth` int(11) NOT NULL DEFAULT '1',
   `shape` varchar(255) DEFAULT NULL,
   `hideLabel` tinyint(1) NOT NULL DEFAULT '0',
   `hideTooltip` tinyint(1) NOT NULL DEFAULT '0',
   `rotate` int(11) NOT NULL DEFAULT '0',
   `pattern` varchar(255) DEFAULT NULL,
   `labelSize` int(11) NOT NULL DEFAULT '1',
   `notepad` longtext collate utf8_unicode_ci,
   `date_mod` datetime default NULL,
   `is_deleted` tinyint(1) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `name` (`name`),
   KEY `entities_id` (`entities_id`),
   KEY `date_mod` (`date_mod`),
   KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_positions_imageitems`;
CREATE TABLE `glpi_plugin_positions_imageitems` (
   `id` int(11) NOT NULL auto_increment,
   `type` int(11) NOT NULL default '0',
   `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `img` VARCHAR( 50 ) collate utf8_unicode_ci NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_positions_infos`;
CREATE TABLE `glpi_plugin_positions_infos` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `entities_id` int(11) NOT NULL default '0',
   `is_recursive` tinyint(1) NOT NULL default '0',
   `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   `fields` text collate utf8_unicode_ci NOT NULL,
   `comment` text collate utf8_unicode_ci,
   `notepad` longtext collate utf8_unicode_ci,
   `date_mod` datetime NULL default NULL,
   `is_active` tinyint(1) NOT NULL DEFAULT '0',
   `is_deleted` tinyint(1) NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginPositionsPosition','2','2','0');