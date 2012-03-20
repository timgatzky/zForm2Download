<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  	Tim Gatzky 2012 
 * @author     	Tim Gatzky <info@tim-gatzky.de>
 * @package    	zForm2Download
 * @client		Insignum Werbeagentur GbR <www.insignum.de> 
 * @license    	LGPL 
 * @filesource
 */

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['processFormData'][] = array('Form2Download', 'processFormDataHook');
$GLOBALS['TL_HOOKS']['storeFormData'][] = array('Form2Download', 'storeFormDataHook');


if (version_compare(VERSION . '.' . BUILD, '2.11.1', '<'))
{
	$GLOBALS['TL_HOOKS']['getContentElement'][] = array('Form2DownloadFallback', 'modifyContentElements');
}
else
{
	// > 2.11 
	$GLOBALS['TL_HOOKS']['getContentElement'][] = array('Form2Download', 'modifyContentElements');
}

/**
 * Initializiation
 * Needed for storing global data
 */
$GLOBALS['ZFORM2DOWNLOAD'] = array();
$GLOBALS['ZFORM2DOWNLOAD']['articles'] = array();

/**
 * Settings
 */
//$GLOBALS['ZFORM2DOWNLOAD']['hide_class'] = 'invisible';

// send file directely to browser after processing form
// nach dem Senden direkt die Datei an den Browser senden
$GLOBALS['ZFORM2DOWNLOAD']['jumpToFile'] = false; 

// erlaubt es die BasisURL mit einem Anker zum DownloadElement zu erweitern
// extends the baseURL with an anchor tag directly to the download element
$GLOBALS['ZFORM2DOWNLOAD']['setAnchorToFile'] = false; 


?>