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

if (!isset($_GET["id"])) $_GET["id"] = "";

$info = new PluginPositionsInfo();

if (isset($_POST["add"])) {
   $info->check(-1, CREATE,$_POST);
   $newID = $info->add($_POST);
   
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($info->getFormURL()."?id=".$newID);
   } else {
      Html::back();
   }

} else if (isset($_POST["update"])) {
   $info->check($_POST['id'], UPDATE);
   $info->update($_POST);
   Html::back(); 
   
}else if (isset($_POST["delete"])) {
   $info->check($_POST['id'], DELETE);
   $info->delete($_POST);
   $info->redirectToList();

} else if (isset($_POST["restore"])) {
   $info->check($_POST['id'], PURGE);
   $info->restore($_POST);
   $info->redirectToList();
   
} else if (isset($_POST["purge"])) {
   $info->check($_POST['id'],PURGE);
   $info->delete($_POST,1);
   $info->redirectToList();

} else {
   Html::header(PluginPositionsPosition::getTypeName(),'',"tools","pluginpositionsmenu","info");
   $info->display($_GET);
   Html::footer();
}

?>