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
 
include ('../../../inc/includes.php');

if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
   //from central
   Html::header(PluginPositionsPosition::getTypeName(),'', "tools", "pluginpositionsmenu", "positions");
} else {
   //from helpdesk
   Html::helpHeader(PluginPositionsPosition::getTypeName());
}

if (!isset($_POST["locations_id"])) $_POST["locations_id"] = 0;

PluginPositionsPosition::showLocationForm($_POST["locations_id"]);

if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}

?>