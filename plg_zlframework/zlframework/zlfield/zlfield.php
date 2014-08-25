<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

/*
   Class: ZlfieldHelper
   	  ZL Field class for all params fields around Joomla!
*/
class ZlfieldHelper extends AppHelper {

	protected $layout;
	protected $path;
	protected $params;
	protected $enviroment;
	protected $enviroment_args;
	protected $config;
	protected $mode;

	public function __construct($default = array())
	{
		parent::__construct($default);

		// get joomla and application table
		$this->joomla   = $this->app->system->application;
		$this->appTable = $this->app->table->application;
		
		// set data shortcut
		$this->data = $this->app->data;

		// set request shortcut
		$this->req = $this->app->request;

		// get the inviroment
		$this->enviroment = strlen($this->req->getString('enviroment')) ? $this->req->getString('enviroment') : $this->getTheEnviroment();

		// if no enviroment for ZL field, cancel
		if (!$this->enviroment) return;

		// decode and set enviroment arguments
		$this->enviroment_args = json_decode($this->req->get('enviroment_args', 'string', ''), true);
		$this->enviroment_args = $this->app->data->create($this->enviroment_args);

		// get task
		$this->task = $this->req->getVar('parent_task') ? $this->req->getVar('parent_task') : $this->req->getVar('task');

		// get application group
		$this->group = $this->req->getString('group');

		// get type
		$cid  = $this->req->get('cid.0', 'string', ''); // edit view
		$type = $cid ? $cid : $this->req->getString('type'); // assign view
		if(!empty($type)){
			$this->type = $type;
			$this->joomla->setUserState('plg_zlfw_zlfieldtype', $type);
		} else {
			// get type from user session
			$this->type = $this->joomla->getUserState('plg_zlfw_zlfieldtype', '');
		}

		// retrieve application object
		if ($this->group) {
			$this->application = $this->app->object->create('Application');
			$this->application->setGroup($this->group);
		} else {
			$this->application = $this->app->zoo->getApplication();
		}

		// get url params
		$this->controller = $this->req->getString('controller');
		$this->option = $this->req->getString('option');
		$this->view   = $this->req->getString('view');

		// set the params mode - edit, config, positions, module, plugin
		$this->mode = $this->req->getString('zlfieldmode');
		if(empty($this->mode)){
			if($this->task == 'assignelements' || $this->task == 'assignsubmission' || $this->enviroment == 'type-positions')
				$this->mode = 'positions';
			else if($this->task == 'editelements' || $this->task == 'addelement')
				$this->mode = 'config';
			else if($this->task == 'edit')
				$this->mode = 'edit';
			else if(($this->option == 'com_modules' || $this->option == 'com_advancedmodules') && $this->view == 'module')
				$this->mode = 'module';
			else if($this->enviroment == 'app-config') {
				$this->mode = 'appconfig';
			}
		}
		
		// get params
		if($this->app->zlfw->environment->is('admin.com_widgetkit'))
			$this->initWidgetkitMode();
		else if($this->mode == 'edit')
			$this->initEditMode();
		else if($this->mode == 'positions')
			$this->initPositionsMode();
		else if($this->mode == 'config')
			$this->initConfigMode();
		else if($this->mode == 'module')
			$this->initModuleMode();
		else if($this->mode == 'appconfig')
			$this->initAppConfigMode();
		else {
			$this->params = $this->data->create(array());
		}

		// set cache var
		$this->cache = $this->data->create(array());
		
		// load assets
		$this->loadAssets();
	}

	protected function initEditMode()
	{
		// get application
		$this->application = $this->app->zoo->getApplication();

		// get item
		$item_id = $this->req->get('cid.0', 'int');
		$item = $item_id ? $this->app->table->item->get($item_id) : null;
		$data = $item ? $item->elements : array();

		// get params
		$this->params = $this->data->create($data);

		// init config
		$this->initConfigMode();
	}
	
	protected function initConfigMode()
	{
		$this->config = array();
		if($this->controller == 'manager' && !empty($this->type) && $type = $this->application->getType($this->type))
		{
			// get params from type.config file
			$config = json_decode(file_get_contents($type->getConfigFile()), true);
			$this->config = isset($config['elements']) ? $config['elements'] : $this->config;
		}

		// custom handle for ZOOcart Address type:
		else if($this->controller == 'addresses' && $this->type == 'address')
		{
			$zoocart = JPluginHelper::getPlugin('system','zoocart');
			if(!empty($zoocart) && JPluginHelper::isEnabled('system','zoocart') 
				&& $this->application->getParams()->get('global.zoocart.enable_cart'))
			{
				// register and load the Address Type
				$this->app->loader->register('AddressType', 'plugins:system/zoocart/zoocart/classes/addresstype.php');
				$address = $this->app->object->create('AddressType', array('address', $this->application));

				if (($file = $address->getConfigFile()) && JFile::exists($file)) {
					$config = json_decode(file_get_contents($file), true);
					$this->config = isset($config['elements']) ? $config['elements'] : $this->config;
				}
			}
		}
		
		// wrap the data with data object
		$this->config = $this->data->create($this->config);

		// use as params in config mode
		if($this->mode == 'config') $this->params = $this->config;
	}

	protected function initPositionsMode()
	{
		// init config
		$this->initConfigMode();

		// get layout
		$this->layout = $this->req->getString('layout');

		// get path
		$this->path = $this->task == 'assignelements' ? JPATH_ROOT.'/'.urldecode($this->req->getVar('path')) : '';
		$this->path = $this->task == 'assignsubmission' ? $this->application->getPath().'/templates/'.$this->req->getString('template') : $this->path;

		// custom handle for ZOOcart Address type:
		if($this->controller == 'addresses' && $this->type == 'address')
		{
			$this->path = JPATH_ROOT.'/'.urldecode($this->req->getVar('path'));

			// get params from position.config file
			$renderer = $this->app->renderer->create('address')->addPath($this->path);
			$this->params = $this->data->create($renderer->getConfig('address')->get($this->application->getGroup().'.'.$this->application->id.'.'.$this->type.'.'.$this->layout));

		// default
		} else {

			// get params from position.config file
			$renderer = $this->app->renderer->create('item')->addPath($this->path);
			$this->params = $this->data->create($renderer->getConfig('item')->get($this->group.'.'.$this->type.'.'.$this->layout));
		}

		// submissions workaround
		if($this->task == 'assignsubmission')
		{
			/* rearrange and give the arrays a name in order to work well with getParams() */
			$data = array();
			foreach($this->params as $position) foreach($position as $element){
				$data[$element['element']] = $element;
			}
			$this->params = $this->data->create($data);
		}
	}

	protected function initModuleMode()
	{
		// init vars
		$module_id = $this->enviroment_args->get('id', $this->req->getVar('id'));
		$result = '';

		if ($module_id) {
			// get module params
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select('m.params');
			$query->from('#__modules AS m');
			$query->where('m.id = '.$module_id);

			$db->setQuery($query);
			$result = $db->loadResult();
		}

		// create the necesay array path
		$this->params = $this->data->create( array('jform' => array('params' => json_decode($result, true))) );

		// save enviroment arguments
		$this->enviroment_args = array('id' => $this->req->getVar('id'));
	}

	protected function initWidgetkitMode()
	{
		$this->enviroment = 'widgetkit';

		// init vars
		$widget_id = $this->enviroment_args->get('id', $this->req->getVar('id'));
		$result = '';

		if ($widget_id) {
			// get module params
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select('widget.content');
			$query->from('#__widgetkit_widget AS widget');
			$query->where('widget.id = '.$widget_id);

			$db->setQuery($query);
			$result = $db->loadResult();
		}

		// create the necesay array path
		$this->params = $this->data->create($result);

		// save enviroment arguments
		$this->enviroment_args = array('id' => $this->req->getVar('id'));
	}

	protected function initAppConfigMode()
	{
		// get application
		$this->application = $this->app->zoo->getApplication();

		// set params
		$this->params = $this->application ? $this->application->getParams() : $this->data->create(array());
	}

	/*
		Function: render - Returns the result from _parseJSON wrapped with main html dom
	*/
	public function render($parseJSONargs, $toggle=false, $open_btn_txt='', $ajaxargs=array(), $class='', $ajaxLoading=false)
	{
		// init vars
		$html = array();
		$ajaxargs = !empty($ajaxargs) ? json_encode($ajaxargs) : false;
		$class = $class ? ' '.$class : '';

		$parsedFields = $parseJSONargs ? call_user_func_array(array($this, "parseJSON"), $parseJSONargs) : '';

		$html[] = '<div class="zlfield zlfield-main placeholder'.$class.'"'.($ajaxargs ? " data-ajaxargs='{$ajaxargs}'" : '').'>';

		if($ajaxLoading)
		{
			$html[] = '<div class="load-field-btn">';
				$html[] = '<span>'.strtolower(JText::sprintf('PLG_ZLFRAMEWORK_EDIT_THIS_PARAMS', $open_btn_txt)).'</span>';
			$html[] = '</div>';
		}
		else if(!$toggle)
		{
			$html[] = $parsedFields;
		} 
		else 
		{
			$hidden = $toggle == 'starthidden' ? true : false;
			$html[] = '<div class="zl-toggle '.($hidden ? '' : 'open').'" data-layout="params">';
				$html[] = '<span class="btn-close">- '.JText::_('PLG_ZLFRAMEWORK_TOGGLE').'</span>';
				$html[] = '<div class="btn-open"><span class="">'.strtolower(JText::sprintf('PLG_ZLFRAMEWORK_EDIT_THIS_PARAMS', $open_btn_txt)).'</span></div>';
			$html[] = '</div>';
			$html[] = '<div class="zl-toggle-content"'.($hidden ? ' style=" display: none;"' : '').'>'.$parsedFields.'</div>';
		}

		$html[] = '</div>';

		return implode("\n", $html);
	}
	
	/*
		Function: parseJSON - Returns result html string from fields declared in json string/arrat format
		Params: 
			$json String		- path to json file or a json formated string
			$ctrl String 		- control
			$psv Array			- All Parent Fields Values
			$pid String			- Parent Field ID
			$arguments Array	- additional arguments the fields could need -> $ajaxargs var will be passed trough ajax call
	*/
	public function parseJSON($json, $ctrl, $psv=array(), $pid='', $returnArray=false, $arguments=array())
	{
		// extract the arguments
		extract($arguments, EXTR_OVERWRITE);

		// load element - $element is extracted from $arguments
		if($this->mode == 'positions' || $this->mode == 'config' && $this->req->getVar('ajaxcall')){
			if(!isset($element) || !is_object($element)){
				$element = $this->app->element->create($element_type, $this->application);
				$element->identifier = $element_id;
				$element->config = $this->app->data->create($this->config->get($element_id));
			}
		}

		// update config if adding new element as it's values can be dynamic
		if($this->mode == 'config' && $this->task == 'addelement'){
			$this->config->set($element->identifier, (array)$element->config);
		}

		// save element object to reuse
		if(isset($element) && is_object($element)){
			$this->element = $element;
		}

		/* update params if provided */
		if(isset($addparams)){
			$this->params = $this->data->create( $addparams );
		}

		// convert to array
		settype($json, 'array');
		
		// if paths provided retrieve json and convert to array
		if (isset($json['paths'])){
			foreach (array_map('trim', explode(',', $json['paths'])) as $pt) if ($path = $this->app->path->path($pt)) // php files only
			{
				if(is_file($path)){
					/* IMPORTANT - this vars are necesary for include function */
					$subloaded = true; // important to let know it's subloaded
					$psv = $this->data->create($psv);
					$json = json_decode(include($path), true);
					break;
				}
			}
		}
		else if (!isset($json['fields'])) // is raw json string then
		{
			$json = json_decode($json[0], true);
		}

		// let's be sure is well formated
		$json = isset($json['fields']) ? $json : array('fields' => $json);

		// process fields if any
		if (isset($json['fields']))
		{			
			$ctrl = $ctrl.(isset($json['control']) ? "[".$json['control']."]" : ''); // ctrl could grow on each iterate
			
			// iterate fields
			$result = $this->_parseFields($json['fields'], $ctrl, $psv, $pid, false, $arguments);

			return $returnArray ? $result : implode("\n", $result);
		} 
		else if($json && false)
		{
			JFactory::getApplication()->enqueueMessage( JText::_('JSON string with bad format or file not found - ') . implode(' | ', $json) );
		}

		return null;
	}
	
	// $fields, $control, $parentsValue, $parentID
	private function _parseFields($fields, $ctrl, $psv, $pid, $returnArray, $arguments)
	{
		$result = array();
		foreach ($fields as $id => $fld) {
			$fld = $this->data->create($fld);

			// adjust ctrl
			if($adjust = $fld->get('adjust_ctrl')){
				$final_ctrl = preg_replace($adjust['pattern'], $adjust['replacement'], $ctrl);
			} else {
				$final_ctrl = $ctrl;
			}

			// wrapper control if any
			$control = $fld->get('control');
			$final_ctrl = isset($control) && strlen($control) ? $final_ctrl.'['.$control.']' : $final_ctrl;

			$field_type = $fld->get('type', '');
			switch ($field_type)
			{
				case 'separator':
					// set layout
					$layout = $fld->get('layout', 'default');

					// backward compatibility
					if ($fld->get('text')) {
						$layout = $fld->get('big') ? 'section' : 'subsection';
						$fld->set('specific', array('title' => $fld->get('text')));
					}

					// render layout
					$field = $fld;
					if ($layout = $this->getLayout("separator/{$layout}.php")) {
						$result[] = $this->app->zlfw->renderLayout($layout, compact('id', 'field'));
					}
					break;

				case 'wrapper':
				case 'control_wrapper': case 'toggle': case 'fieldset': // backward compatibility

					// get content
					$content = array_filter($this->parseJSON(json_encode(array('fields' => $fld->get('fields'))), $final_ctrl, $psv, $pid, true, $arguments));
					
					// abort if no minimum fields reached
					if (count($content) == 0 || count($content) < $fld->get('min_count', 0)) continue;

					// init vars
					$layout = $fld->get('layout', 'default');
					$content = implode("\n", $content);

					// backward compatibility
					if ($field_type == 'control_wrapper') {
						$result[] = $content;
						continue;
					} else if ($field_type == 'fieldset'){
						$layout = 'fieldset';
					} else if ($field_type == 'toggle'){
						$layout = 'toggle';
					}

					// render layout
					if ($this->renderIf($fld->get('renderif')) // render check
							&& $layout = $this->getLayout("wrapper/{$layout}.php")) {

						$result[] = $this->app->zlfw->renderLayout($layout, compact('id', 'content', 'fld'));
					}
					
					break;
				case 'subfield':
					// get parent fields data
					$psv = $this->data->create($psv);

					// replace path {value} if it's string
					$paths = is_string($psv->get($pid)) ? str_replace('{value}', basename($psv->get($pid), '.php'), $fld->get('path')) : $fld->get('path');

					// replace parent values in paths
					foreach ((array)$psv as $key => $pvalue) {
						$paths = str_replace('{'.$key.'}', basename(@(string)$pvalue, '.php'), $paths);
					}

					// build json paths
					$json = array('paths' => $paths);

					// create possible arguments objects
					if($field_args = $fld->get('arguments')) foreach($field_args as $name => $args){
						$arguments[$name] = $this->app->data->create($args);
					}

					// parse fields
					if($res = $this->parseJSON($json, $final_ctrl, $psv, $pid, false, $arguments)){
						$result[] = $res;
					}

					break;
				default:
					// init vars
					$value = null;

					// check old values
					if($fld->get('check_old_value'))
					{
						// adjust ctrl for old value
						$old_value_ctrl = $final_ctrl;
						if($adjust = $fld->find('check_old_value.adjust_ctrl')) $old_value_ctrl = preg_replace($adjust['pattern'], $adjust['replacement'], $old_value_ctrl);
						// get old value
						$value = $this->getFieldValue($fld->find('check_old_value.id'), null, $old_value_ctrl);
						// translate old value
						if($translations = $fld->find('check_old_value.translate_value')){
							foreach($translations as $key => $trans) if($value == $key){
								if($trans == '_SKIPIT_'){
									$value = null;
									break;
								} else {
									$value = $trans;
									break;
								}
							}
						}
					}

					// get value from config instead
					if($fld->get('data_from_config'))
					{
						$path = preg_replace( // create equivalent path to the config values
							array('/^('.$this->element->identifier.')/', '/(positions\[\S+\])\[(\d+)\]|elements\[[^\]]+\]|\]$/', '/(\]\[|\[|\])/', '/^\./'),
							array('', '', '.', ''),
							$final_ctrl
						);
						$path = "{$this->element->identifier}.{$path}";
						$value = $this->config->find($path.".$id", $value);
					}
					else
					{
						// get value
						$value = strlen($value) ? $value : $this->getFieldValue($id, $fld->get('default'), $final_ctrl, $fld->get('old_id', false));
					}

					// get inital value dinamicly
					if (empty($value) && $fld->get('request_value')) {

						// from url
						if ($fld->find('request_value.from') == 'url') {
							$value = $this->req->get($fld->find('request_value.param'), $fld->find('request_value.type'), $fld->find('request_value.default'));
						}
					}
					
					// set specific
					$specific = $fld->get('specific', array()); /**/ if ($psv) $specific['parents_val'] = $psv;
					

					// prepare row params
					$params = array(
						'field'		=> (array)$fld,
						'type' 		=> $field_type,
						'id'		=> $id,
						'name'		=> $final_ctrl.'['.$id.']',
						'specific'	=> $specific,
						'label'		=> $fld->get('label'),
						'class'		=> $fld->get('class'),
						'dependent' => $fld->get('dependent'),
						'dependents' => $fld->get('dependents'),
						'renderif'	=> $fld->get('renderif'),
						'render'	=> $fld->get('render', 1),
						'layout'	=> $fld->get('layout', 'default'),
						'final_ctrl' => $final_ctrl
					);

					// render individual field row
					$params = $this->app->data->create($params);
					if($field = $this->field($params, $value)) {
						$result[] = $this->row($params, $field);
					}

					// load childs
					if($childs = $fld->find('childs.loadfields'))
					{
						// create parent values
						$pid = $id;

						// add current value to parents array, if empty calculate it
						$psv[$id] = $value ? $value : $this->field($params, $value, true); 

						$p_task = $this->req->getVar('parent_task') ? $this->req->getVar('parent_task') : $this->req->getVar('task'); // parent task necesary if double field load ex: layout / sublayout
						$url = $this->app->link(array('controller' => 'zlframework', 'format' => 'raw', 'type' => $this->type, 'layout' => $this->layout, 'group' => $this->group, 'path' => $this->req->getVar('path'), 'parent_task' => $p_task, 'zlfieldmode' => $this->mode), false);

						// rely options to be used by JS later on
						$json = $fld->find('childs.loadfields.subfield', '') ? array('paths' => $fld->find('childs.loadfields.subfield.path')) : array('fields' => $childs);
						
						$pr_opts = json_encode(array('id' => $id, 'url' => $url, 'psv' => $psv, 'json' => json_encode($json)));
						
						// all options are stored as data on DOM so can be used from JS
						$loaded_fields = $this->parseJSON(array('fields' => $childs), $final_ctrl, $psv, $pid, false, $arguments);
						$result[] = '<div class="placeholder" data-relieson-type="'.$field_type.'"'.($pr_opts ? " data-relieson='{$pr_opts}'" : '').' data-control="'.$final_ctrl.'" >';
						$result[] = $loaded_fields ? '<div class="loaded-fields">'.$loaded_fields.'</div>' : '';
						$result[] = '</div>';
					}
			}
		}
		return $result;
	}
	
	/*
		Function: getFieldValue - retrieves the field stored value from the $params
		$params, $fieldID, $fieldControl, $defaultValue
	*/
	public function getFieldValue($id, $default, $ctrl, $old_id=false)
	{
		$path = preg_replace( // create path to the params from control
		array('/(^positions\[|^elements\[|^addons\[|\]$)/', '/(\]\[|\[|\])/'),
		array('', '.'),
		$ctrl);

		$value = null;
		if ($this->enviroment == 'app-config') // if App Config Params
		{
			$path = "global.$path";
			$param = $this->params->get($path);

			if(is_array($param) && isset($param[$id])){
				$value = $param[$id];
			} else {
				$value = $param;
			}
		}
		else if(is_array($id))
		{
			$params = array();
			foreach ((array) $id as $key => $id) {
				$params[$key] = $this->params->find("$path.$id", $default);
			} $value = $params;
		}
		else // default
		{
			// if FIND miss value use GET, if NO apply default
			$value = $this->params->find("$path.$id");
			if(empty($value) && $old_id){
				$value = $this->params->find("$path.$old_id"); // try with old id
			}
		}

		// set default if value empty
		if (!isset($value) && isset($default)) {
			$value = $default;
		}

		// return result
		return $value; 
	}

	/*
		Function: parseArray - returns an json formated string from an array
			The array is the XML data standarized by the type inits
	*/
	function parseArray($master, $isChild=false, $arguments=array())
	{
		$fields = array();
		if(count($master)) foreach($master as $val)
		{
			// init vars
			$name   = $val['name'];
			$attrs  = $val['attributes'];
			$childs = isset($val['childs']) ? $val['childs'] : array();

			if($name == 'loadfield')
			{
				// get field from json
				if($json = $this->app->path->path("zlfield:json/{$attrs['type']}.json.php")){
					// extract the arguments
					extract($arguments, EXTR_OVERWRITE);

					// parse all subfiels and set as params
					$result = $this->parseArray($childs, true, $arguments);
					$params = $this->app->data->create($result);
					
					// remove the {} from json string and proceede
					$fields[] = preg_replace('(^{|}$)', '', include($json));
				} else {
					$fields[] = '"notice":{"type":"info","specific":{"text":"'.JText::_('PLG_ZLFRAMEWORK_ZLFD_FIELD_NOT_FOUND').'"}}';
				}
			}
			else if($isChild)
			{
				$fields = array_merge($fields, array($name => array_merge($attrs, $this->parseArray($childs, true, $arguments))));
			}
			else // setfield
			{
				// get field id and remove from attributes
				$id = $attrs['id'];
				unset($attrs['id']);

				// merge val attributes
				$field = array($id => array_merge($attrs, $this->parseArray($childs, true, $arguments)));

				// remove the {} created by the encode and proceede
				$fields[] = preg_replace('(^{|}$)', '', json_encode($field));
			}
		}
		return $fields;
	}

	/*
		Function: renderIf 
			Render or not depending if specified extension is instaled and enabled
		Params
			$extensions - array, Ex: [com_widgetkit, 0]
	*/
	public function renderIf($extensions)
	{
		$render = 1;
		if (!empty($extensions)) foreach ($extensions as $ext => $action)
		{
			if ($this->app->zlfw->checkExt($ext)){
				$render = $action;
			} else {
				$render = !$action;
			}
		}
		return $render; // if nothing to check, render as usual
	}
	
	/*
		Function: replaceVars - Returns html string with all variables replaced
	*/
	public function replaceVars($vars, $string)
	{
		$vars = is_string($vars) ? explode(',', trim($vars, ' ')) : $vars;
		
		$pattern = $replace = array(); $i=1;
		foreach((array)$vars as $var){
			$pattern[] = "/%s$i/"; $i++;
			$replace[] = preg_match('/^{/', $var) ? $this->app->zlfw->shortCode($var) : JText::_($var);
		}

		return preg_replace($pattern, $replace, $string);
	}

	/**
	 * getTheEnviroment
	 *
	 * @return @string item-edit, type-config, type-positions
	 *
	 * @since 3.0
	 */
	public function getTheEnviroment()
	{
		$option = $this->req->getVar('option');
		$controller = $this->req->getVar('controller');
		$task = $this->req->getVar('task');
		switch ($task) {
			case 'editelements':
				if ($option == 'com_zoo') return 'type-edit';
				break;

			case 'assignelements':
			case 'assignsubmission':
				if ($option == 'com_zoo') return 'type-positions';
				break;

			case 'edit':
				if ($option == 'com_zoo') return 'item-edit';
				break;

			case 'addelement':
				if ($option == 'com_zoo') return 'type-edit';
				break;

			default:
				if ($option == 'com_advancedmodules' || $option == 'com_modules')
					return 'module';

				else if ($option == 'com_zoo' && $controller == 'configuration')
					return 'app-config';

				else if ($option == 'com_zoo' && $controller == 'new' && $task == 'add' && strlen($this->req->getVar('group')));
					return 'app-config';
		}
	}

	/*
		Function: loadAssets - Load the necesary assets
	*/
	protected $loadedAssets = false;
	public function loadAssets()
	{
		if (!$this->loadedAssets) {
			// init vars
			$url = $this->app->link(array('controller' => 'zlframework', 'format' => 'raw', 'type' => $this->type), false);
			$enviroment_args = json_encode($this->enviroment_args);

			// load zlfield assets
			$this->app->document->addStylesheet('zlfield:zlfield.css');
			$this->app->document->addStylesheet('zlfield:layouts/field/style.css');
			$this->app->document->addStylesheet('zlfield:layouts/separator/style.css');
			$this->app->document->addStylesheet('zlfield:layouts/wrapper/style.css');
			$this->app->document->addScript('zlfield:zlfield.js');

			if ($this->enviroment == 'module') {
				$this->app->document->addScript('libraries:jquery/jquery-ui.custom.min.js');
				$this->app->document->addStylesheet('libraries:jquery/jquery-ui.custom.css');
				$this->app->document->addScript('libraries:jquery/plugins/timepicker/timepicker.js');
				$this->app->document->addStylesheet('libraries:jquery/plugins/timepicker/timepicker.css');
			}

			// workaround for jQuery 1.9 transition
			$this->app->document->addScript('zlfw:assets/js/jquery.plugins/jquery.migrate.min.js');

			// load libraries
			$this->app->zlfw->zlux->loadMainAssets();
			$this->app->zlfw->loadLibrary('qtip');
			$this->app->document->addStylesheet('zlfw:assets/libraries/zlux/zlux.css');

			// init scripts
			$javascript = "jQuery(function($){ $('body').ZLfield({ url: '{$url}', type: '{$this->type}', enviroment: '{$this->enviroment}', enviroment_args: '{$enviroment_args}' }); });";
			$this->app->document->addScriptDeclaration($javascript);

			// don't load them twice
			$this->loadedAssets = true;
		}
	}

	/*
		Function: getLayout
			Get element layout path and use override if exists.

		Returns:
			String - Layout path
	*/
	public function getLayout($layout = null)
	{
		// find layout
		return $this->app->path->path("zlfield:layouts/{$layout}");
	}

	/*
		Function: field - Returns field html string
	*/
	public function field($params, $value, $getCurrentValue=false)
	{
		$type	= $params->get('type');

		if ($type && $params->get('render') && $this->renderIf($params->get('renderif')))
		{
			$id 		= $params->get('id');
			$name 		= $params->get('final_ctrl').'['.$id.']';
			$specific 	= $this->app->data->create((array)$params->get('specific'));
			$attrs		= '';

			// render field
			$field = $this->app->zlfieldhtml->_('zlf.'.$type.'Field', $id, $name, $value, $specific, $attrs, $getCurrentValue);

			if (!empty($field)) return $field;
		}

		return null;
	}

	/*
		Function: row - Returns row html string
	*/
	public function row($params, $field)
	{
		$layout = $params->get('layout', 'default');

		// render layout
		if ($layout = $this->getLayout("field/{$layout}.php")) {
			return $this->app->zlfw->renderLayout($layout, compact('params', 'field'));
		} else {
			return $field;
		}

		return null;
	}

	/*
		Function: elementsList - Returns element list
	*/
	protected $_elements_list = array();
	public function elementsList($groups_filter = array(), $elements_filter = array(), $filter_types = array())
	{
		$groups_filter 		= array_filter((array)($groups_filter));
		$elements_filter 	= array_filter((array)($elements_filter));
		$filter_types 		= array_filter((array)($filter_types));

		$hash = md5(serialize( array($groups_filter, $elements_filter, $filter_types) ));
		if (!array_key_exists($hash, $this->_elements_list))
		{
			// get apps
			$apps = $this->app->table->application->all(array('order' => 'name'));
			
			// prepare types and filter app group
			$types = array();
			foreach ($apps as $app){
				if (empty($groups_filter) || in_array($app->getGroup(), $groups_filter) || in_array($app->id, $groups_filter)) {
					$types = array_merge($types, $app->getTypes());
				}
			}
			
			// filter types
			if (count($filter_types) && !empty($filter_types[0])){
				$filtered_types = array();
				foreach ($types as $type){
					if (in_array($type->id, $filter_types)){
						$filtered_types[] = $type;
					}
				}
				$types = $filtered_types;
			}
			
			// get all elements
			$elements = array();
			foreach($types as $type){
				$elements = array_merge( $elements, $type->getElements() );
			}
			
			// create options
			$options = array();			
			foreach ($elements as $element) {
				// include only desired element type
				if (empty($elements_filter) || in_array($element->getElementType(), $elements_filter)) {
					$options[$element->getType()->name.' > '.$element->config->get('name')] = $element->identifier;
				}
			}

			$this->_elements_list[$hash] = $options;
		}

		// return elements array
		return @$this->_elements_list[$hash];
	}
}