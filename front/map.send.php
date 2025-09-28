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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Positions\Position;

Session::checkLoginUser();

$doc = new Document();

if (isset($_GET['docid'])) { // docid for document
   if (!$doc->getFromDB($_GET['docid'])) {
       throw new BadRequestHttpException(__('Unknown file'));
   }

   if (!file_exists(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
       throw new BadRequestHttpException(__('File not found')); // Not found
   }
   if ($doc->fields['sha1sum'] && $doc->fields['sha1sum'] != sha1_file(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
       throw new BadRequestHttpException(__('File is altered (bad checksum)')); // Doc alterated
   } else {
      return $doc->getAsResponse();
   }

} else if (isset($_GET["file"]) && isset($_GET["type"])) {
   Position::sendFile(GLPI_PLUGIN_DOC_DIR . "/positions/" . $_GET["type"] . "/" . $_GET["file"], $_GET["file"], $_GET["type"]);

} else if (isset($_GET["file"])) { // for other file
   $splitter = explode("/", $_GET["file"]);
   if (count($splitter) == 2) {
      if (file_exists(GLPI_DOC_DIR . "/" . $_GET["file"])) {
          return Toolbox::getFileAsResponse(GLPI_DOC_DIR . "/" . $_GET["file"], $splitter[1]);
      } else {
          throw new BadRequestHttpException(__('Unauthorized access to this file'));
      }
   } else {
       throw new BadRequestHttpException(__('Invalid filename'));
   }
}
