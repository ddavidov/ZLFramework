<?php
/**
* @package		ZL Framework
* @author    	ZOOlanders http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

/*
	Class: zlfwHelperEnviroment
		The ZLFW enviroment helper class
*/
class zlfwHelperEnviroment extends AppHelper {

	public $joomla;
	public $params;

	/*
	   Function: Constructor
	*/
	public function __construct($app) {

		// call parent constructor
		parent::__construct($app);

		// set Joomla instance
		$this->joomla = JFactory::getApplication();

		// set params as DATA class
		$this->params = $this->app->data->create(array());
	}

	/*
		Function: get
			returns the current enviroment

		Example enviroments:
			Item View - site.com_zoo.item
			Category View - site.com_zoo.category
			ZOO manager - admin.com_zoo.manager

		Returns:
			string - the enviroment route
	*/
	public function get()
	{
		// init vars
		$enviroment = array();
		$Itemid = $this->app->request->getCmd('Itemid', null);
		$component = $this->app->request->getCmd('option', null);
		$task = $this->app->request->getCmd('task', null);
		$view = $this->app->request->getCmd('view', null);

		// set back or frontend
		$enviroment[] = $this->joomla->isAdmin() ? 'admin' : 'site';

		// set component
		$enviroment[] = $component;

		// if ZOOlanders
		if ($component == 'com_zoolanders' && $view == 'extension') {
			if ($menu = $this->app->menu->getActive())
				$enviroment[] = $menu->params->get('extension')->name;
			unset($view);
		}

		// set controller
		$enviroment[] = $this->app->request->getCmd('controller', null);

		// if ZOO
		if ($component == 'com_zoo'){

			// if zoo item full view
			if ($task == 'item') {
				$enviroment[] = 'item';
				$this->params->set('item_id', $this->app->request->getCmd('item_id'));
				unset($task);
			} else if ($view == 'item') { // if joomla item menu route
				$enviroment[] = 'item';

				if ($menu = $this->app->menu->getActive())
					$this->params->set('item_id', $menu->params->get('item_id'));
				unset($view);
			}

			// if zoo cat
			else if ($task == 'category') {
				$enviroment[] = 'category';
				$this->params->set('category_id', $this->app->request->getCmd('category_id'));
				unset($task);
			} else if ($view == 'category') { // if joomla item menu route
				$enviroment[] = 'category';

				if ($menu = $this->app->menu->getActive())
					$this->params->set('category_id', $menu->params->get('category'));
				unset($view);
			}
		}

		// add task/view to the enviroment
		if (isset($task) && !empty($task)) $enviroment[] = $task;
		else if (isset($view) && !empty($view)) $enviroment[] = $view;

		// clean values
		$enviroment = array_filter($enviroment);

		// return result in point format
		return implode('.', $enviroment);
	}

	/*
		Function: is
			checks if the passed enviroment is the current enviroment

		Params:
			@enviroments	string of enviroments to check separated by space

		Returns:
			boolean
	*/
	public function is($enviroments)
	{
		// multiple enviroments posible
		$enviroments = explode(' ', $enviroments);

		foreach ($enviroments as $env) {
			// if in any enviroment, return true
			if (strpos($this->get(), trim($env)) === 0) {
				return true;
				break;
			}
		}
		
		return false;
	}
}