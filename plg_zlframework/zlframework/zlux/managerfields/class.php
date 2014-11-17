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
   Class: zluxFields
*/
class zluxFields extends zlux
{
	/**
	 * Class constructor
	 *
	 * @param string $app App instance.
	 */
	public function __construct($app){
		parent::__construct($app);

		// register paths
		$this->app->path->register(dirname(__FILE__), 'zluxFields');
		if ($path = $this->app->path->path('zluxFields:fields')) {
			$this->app->path->register($path, 'zluxFields.fields');
		}
	}

	/**
	 * Creates a ZLUX Fields engine instance
	 *
	 * @param string $type ZLUX Fields type
	 * @param array $args Additional arguments for the constructor
	 * @return zluxFields
	 */
	public function create($type = '', $args = array()) {

		// load renderer class
		$class = $type ? 'zluxFieldsEngine'.ucfirst($type) : 'zluxFieldsEngine';
		if ($type) {
			$this->app->loader->register($class, 'zluxFields:engine/'.strtolower($type).'.php');
		}

		// prepend app
		array_unshift($args, $this->app);

		return $this->app->object->create($class, $args);
	}
}

/*
   Class: zluxFieldsEngine
*/
abstract class zluxFieldsEngine
{
	/**
	 * Reference to the global App class
	 *
	 * @var App
	 */
	public $app;

	/**
	 * Reference to the request Helper
	 *
	 * @var RequestHelper
	 */
	public $request;

	/**
	 * Reference to the field values
	 *
	 * @var array
	 */
	public $values;

	/**
	 * Reference to the field control
	 *
	 * @var string
	 */
	public $control;

	/**
	 * Class constructor
	 *
	 * @param string $app App instance.
	 */
	public function __construct($app)
	{
		// init vars
		$this->app		= $app;
		$this->request	= $app->request;
	}

	/**
	 * Set the fields values
	 *
	 * @param array $values Field values
	 */
	public function setValues($values)
	{
		$this->values = $this->app->data->create($values);
	}

	/**
	 * Set the fields control
	 *
	 * @param array $ctrl Field control
	 */
	public function setControl($ctrl)
	{
		$this->control = $ctrl;
	}

	/**
	 * Renders the fields
	 *
	 * @param mixed $fields The fields in XML, JSON, path to JSON file or Array format
	 *
	 * @return string The HTML fields string
	 */
	public function render($fields)
	{
		// load assets
		$this->loadAssets();

		// render
		return implode("\n", $this->renderFields($fields));
	}
	
	/**
	 * Evaluates the fields and returns them in HTML
	 *
	 * @param array $fields The fields to process
	 *
	 * @return string The HTML fields string
	 */
	function renderFields($fields)
	{
		// process XML
		if (is_object($fields)) {
			$fields = $this->parseXML($fields);

		// process json string
		} else if ($result = $this->decodeJSON($fields)) {
			$fields = $result;

		// process file
		} else if ($path = $this->app->path->path($fields)) {

			$ext = pathinfo($path, PATHINFO_EXTENSION);
			if ($ext == 'json')
				$fields = file_get_contents($path);			
			else // php
				$fields = include($path);

			$fields = $this->decodeJSON($fields);
		}

		// check if it's array, if not cancel and raise notice
		if (!is_array($fields)) {
			$this->app->error->raiseError(500, JText::_('Something went wrong. Could be a bad fields format or file not found.'));
			return;
		}

		$result = array();
		foreach ($fields as $id => $fld)
		{
			$fld = $this->app->data->create($fld);

			// adjust ctrl
			// if($adjust = $fld->get('adjust_ctrl')){
			// 	$final_ctrl = preg_replace($adjust['pattern'], $adjust['replacement'], $ctrl);
			// } else {
			// 	$final_ctrl = $ctrl;
			// }

			// wrapper control if any
			// $final_ctrl = $fld->get('control') ? $final_ctrl.'['.$fld->get('control', '').']' : $final_ctrl;

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
					$content = array_filter($this->renderFields($fld->get('fields')));
					
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
					$psv = $this->app->data->create($psv);

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
					if($res = $this->renderFields($json)) {
						$result[] = $res;
					}

					break;
				default:
					// init vars
					$value = null;

					// check old values
					// if($fld->get('check_old_value'))
					// {
					// 	// adjust ctrl for old value
					// 	$old_value_ctrl = $this->control;
					// 	if($adjust = $fld->find('check_old_value.adjust_ctrl')) $old_value_ctrl = preg_replace($adjust['pattern'], $adjust['replacement'], $old_value_ctrl);
					// 	// get old value
					// 	// $value = $this->getFieldValue($fld->find('check_old_value.id'), null, $old_value_ctrl);
					// 	// translate old value
					// 	if($translations = $fld->find('check_old_value.translate_value')){
					// 		foreach($translations as $key => $trans) if($value == $key){
					// 			if($trans == '_SKIPIT_'){
					// 				$value = null;
					// 				break;
					// 			} else {
					// 				$value = $trans;
					// 				break;
					// 			}
					// 		}
					// 	}
					// }


					// get inital value dinamicly
					if (empty($value) && $fld->get('request_value')) {

						// from url
						if ($fld->find('request_value.from') == 'url') {
							$value = $this->request->get($fld->find('request_value.param'), $fld->find('request_value.type'), $fld->find('request_value.default'));
						}
					}
					
					 // if ($psv) $specific['parents_val'] = $psv;

					$args = $this->app->data->create($fld->get('settings'));
									
					// render field
					$result[] = $this->renderField($fld, $id, $value, $args);

					// load childs
					if($childs = $fld->find('childs.loadfields'))
					{
						// create parent values
						$pid = $id;

						// add current value to parents array, if empty calculate it
						// $psv[$id] = $value ? $value : $this->field($params, $value, true); 
						$psv[$id] = $value ? $value : null; 

						$p_task = $this->request->getVar('parent_task') ? $this->request->getVar('parent_task') : $this->request->getVar('task'); // parent task necesary if double field load ex: layout / sublayout
						$url = $this->app->link(array('controller' => 'zlframework', 'format' => 'raw', 'type' => $this->type, 'layout' => $this->layout, 'group' => $this->group, 'path' => $this->request->getVar('path'), 'parent_task' => $p_task, 'zlfieldmode' => $this->mode), false);

						// rely options to be used by JS later on
						$json = $fld->find('childs.loadfields.subfield', '') ? array('paths' => $fld->find('childs.loadfields.subfield.path')) : array('fields' => $childs);
						
						$pr_opts = json_encode(array('id' => $id, 'url' => $url, 'psv' => $psv, 'json' => json_encode($json)));
						
						// all options are stored as data on DOM so can be used from JS
						$loaded_fields = $this->renderFields($childs);
						$result[] = '<div class="placeholder" data-relieson-type="'.$field_type.'"'.($pr_opts ? " data-relieson='{$pr_opts}'" : '').' data-control="'.$this->control.'" >';
						$result[] = $loaded_fields ? '<div class="loaded-fields">'.$loaded_fields.'</div>' : '';
						$result[] = '</div>';
					}
			}
		}
		return $result;
	}

	/**
	 * Returns an json formated string from an array
	 *
	 * @param array $master The main array object
	 *
	 * @return mixed The decoded json or false if string bad formated
	 */
	function parseXML($xml)
	{
		// convert the XML to array for easier managing 
		return $this->_parseXML($this->app->zlfw->xml->XMLtoArray($xml));
	}

	/**
	 * Evaluates the XML converted to array and applies the expected format
	 *
	 * @param array $master The main array
	 * @param boolean $isChild Stablish if iterating over a master or a child
	 *
	 * @return array The well formated array for further processing
	 */
	protected function _parseXML($master, $isChild=false)
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
					$fields[] = decode_json(include($json), true);
				} else {
					$fields[] = array('notice' => array("type" => "info", "specific" => array("text" => JText::_('PLG_ZLFRAMEWORK_ZLFD_FIELD_NOT_FOUND'))));
				}
			}
			else if($isChild)
			{
				$fields = array_merge($fields, array($name => array_merge($attrs, $this->_parseXML($childs, true))));
			}
			else // setfield
			{
				// get field id and remove from attributes
				$id = $attrs['id'];
				unset($attrs['id']);

				// merge val attributes
				$field = array($id => array_merge($attrs, $this->_parseXML($childs, true)));

				// merge result
				$fields = array_merge($fields, $field);
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

	/**
	 * decodeJSON
	 *
	 * @param string $string The json string
	 *
	 * @return mixed The decoded json or false if string bad formated
	 */
	function decodeJSON($string) {
		$json = json_decode($string, true);
		if (json_last_error() == JSON_ERROR_NONE)
			return $json;
		else
			return false;
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

	/*
		Function: loadAssets - Load the necesary assets
	*/
	protected $loadedAssets = false;
	public function loadAssets()
	{
		if (!$this->loadedAssets) {
			// init vars
			// $url = $this->app->link(array('controller' => 'zlframework', 'format' => 'raw', 'type' => $this->type), false);
			// $enviroment_args = json_encode($this->enviroment_args);

			// load zlfield assets
			// $this->app->document->addStylesheet('zlfield:zlfield.css');
			// $this->app->document->addStylesheet('zlfield:layouts/field/style.css');
			// $this->app->document->addStylesheet('zlfield:layouts/separator/style.css');
			// $this->app->document->addStylesheet('zlfield:layouts/wrapper/style.css');
			// $this->app->document->addScript('zlfield:zlfield.min.js');

			// load libraries
			$this->app->zlfw->zlux->loadMainAssets();

			// init scripts
			// $javascript = "jQuery(function($){ $('body').ZLfield({ url: '{$url}', type: '{$this->type}', enviroment: '{$this->enviroment}', enviroment_args: '{$enviroment_args}' }); });";
			// $this->app->document->addScriptDeclaration($javascript);

			// don't load them twice
			$this->loadedAssets = true;
		}
	}

	/*
		Function: row - Returns row html string
	*/
	public function renderField($fld, $id, $value, $args = array())
	{
		$type = $fld['type'];
		$name = $this->control.'['.$id.']';

		$fld->set('id', preg_replace(array('/\W+/', '/_$/'), array('_', ''), $name));

		$__file = $this->app->path->path("zluxFields.fields:/$type.php");

		if ($__file != false) {
			// render the field
			ob_start();
			include($__file);
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}

		return 'Field Layout "'.$type.'" not found. ('.$this->app->utility->debugInfo(debug_backtrace()).')';
	}
}