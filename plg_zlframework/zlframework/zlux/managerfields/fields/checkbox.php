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
$attrs = '';

// init vars
$extra_label = $fld->find('settings.label');
$input_value = $fld->find('settings.value', 1);
$checked = $this->values->get($id) ? ' checked="checked"' : '';

echo '<input type="checkbox" '.$attrs.' name="'.$name.'" id="' . $fld->get('id') . '"'. $checked .' value="'.$input_value.'" />'.($extra_label ? '<span>'.JText::_($extra_label).'</span>' : '');