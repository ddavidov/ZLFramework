<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// init vars
$placeholder = $fld->find('settings.placeholder');

// initialize some field attributes.
$placeholder = $placeholder ? ' placeholder="'.JText::_($placeholder).'"' : '';

echo '<input type="text" name="' . $name . '" id="' . $fld->get('id') . '"' . ' value="'
	. htmlspecialchars($this->values->get($id), ENT_COMPAT, 'UTF-8') . '"' . $placeholder . '/>';