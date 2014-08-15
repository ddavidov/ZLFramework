<?php
/**
* @package		ZL Framework
* @author    	ZOOlanders http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

App::getInstance('zoo')->loader->register('zlfwHelperEnvironment', 'helpers:zlfw/environment.php');

// workaround for outdated extensions calling this helper instead of the new one
class zlfwHelperEnviroment extends zlfwHelperEnvironment {}