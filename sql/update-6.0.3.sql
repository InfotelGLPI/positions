ALTER TABLE glpi_plugin_positions_positions CHANGE `x_coordinates` `x_coordinates` int(11) NOT NULL default '0';
ALTER TABLE glpi_plugin_positions_positions CHANGE `y_coordinates` `y_coordinates` int(11) NOT NULL default '0';
UPDATE `glpi_plugin_positions_positions` SET `itemtype` = 'Glpi\Socket' WHERE `itemtype` = 'Netpoint';
