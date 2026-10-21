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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Positions\Position;

Session::checkRight('plugin_positions', READ);

if (isset($_GET['docid'])) {
    // Map of a location: the document must be readable, and attached to a location the user
    // may read. getAsResponse() checks nothing by itself.
    $doc = new Document();
    if (!$doc->getFromDB((int) $_GET['docid'])) {
        throw new NotFoundHttpException();
    }
    if (!$doc->canViewFile() || !Position::isLocationMap($doc->getID())) {
        throw new AccessDeniedHttpException();
    }
    if (!file_exists(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
        throw new NotFoundHttpException();
    }
    if ($doc->fields['sha1sum'] && $doc->fields['sha1sum'] != sha1_file(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
        throw new BadRequestHttpException(__('File is altered (bad checksum)'));
    }
    return $doc->getAsResponse();
} elseif (isset($_GET["file"])) {
    // Pictures of the equipment types, the only directory of the plugin served here
    return Position::getPictureResponse((string) $_GET["file"]);
}

throw new BadRequestHttpException();
