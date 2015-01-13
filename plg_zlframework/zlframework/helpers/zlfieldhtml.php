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
   Class: HTMLHelper
	  A class that contains zlfield html functions
*/
class ZLFieldHTMLHelper extends AppHelper {

	public function _($type) {

		// get arguments
		$args = func_get_args();

		// Check to see if we need to load a helper file
		$parts = explode('.', $type);

		if (count($parts) >= 2) {
			$func = array_pop($parts);
			$file = array_pop($parts);

			if (in_array($file, array('zlf', 'control')) && method_exists($this, $func)) {
				array_shift($args);
				return call_user_func_array(array($this, $func), $args);
			}
		}
	}

	public function cmp($a, $b){
		// sets the default.php allways first
		if(stripos('default.php', $a) === 0) return -1;
		if(stripos('default.php', $b) === 0) return 1;
		return ($a < $b) ? -1 : 1;
	}
	
	/*
		Function: trslValues - Returns parent values array
			Used to translate the value_map to real values
	*/
	protected function trslValues($values, $map)
	{
		$pvs = array();
		if($map && is_array($map)){
			foreach ($map as $key => $parent) if (isset($values[$parent]) && $values[$parent] != 'null'){ // important
				$pvs[$key] = $values[$parent];
			}
		}
		return $pvs;
	}
	
	/**
	 * Return an HTML string
	 *
	 * @param   string    $id      Field ID
	 * @param   string    $name    Field form name
	 * @param   mixed     $value   Field value
	 * @param   object    $spec    Field specific params
	 * @param   string    $attrs   Attributes
	 * @param   boolean   $returnRawValue  If true the field will return it's current value
	 *
	 * @return  string HTML
	 *
	 * @since  3.0.8
	 */
	public function infoField($id, $name, $value, $spec, $attrs, $returnRawValue){
		$info = explode('||', $spec->get('text'));
		$text = JText::_($info[0]);
		unset($info[0]);
		return '<div class="info">'.$this->app->zlfield->replaceVars($info, $text).'</div>';
	}
	
	/*
		Function: text - Returns text input html string
	*/
	public function textField($id, $name, $value, $spec, $attrs, $returnRawValue){
		$attrs .= $spec->get('placeholder') ? ' placeholder="'.JText::_($spec->get('placeholder')).'"' : '';
		return $this->app->html->_('control.text', $name, (string)$value, 'size="60" maxlength="255"'.$attrs);
	}

	/*
		Function: textarea - Returns textarea input html string
	*/
	public function textareaField($id, $name, $value, $spec, $attrs, $returnRawValue){
		return '<textarea '.$attrs.' name="'.$name.'" >'.$value.'</textarea>';
	}
	
	/*
		Function: hidden - Returns hidden input html string
	*/
	public function hiddenField($id, $name, $value, $spec, $attrs, $returnRawValue){
		return '<input type="hidden" name="'.$name.'" value="'.$spec->get('value').'" />';
	}

	/*
		Function: urlvar - Returns text input html string
	*/
	public function requestField($id, $name, $value, $spec, $attrs, $returnRawValue){
		$value = $this->app->request->get($spec->get('var'), $spec->get('type'), $spec->get('default'));
		return $this->app->html->_('control.text', $name, (string)$value, 'size="60" maxlength="255"'.$attrs);
	}

	
	/*
		Function: password - Returns password input html string
	*/
	public function passwordField($id, $name, $value, $spec, $attrs, $returnRawValue){
		$value = $this->app->zlfw->decryptPassword($value);
		return '<input type="password" '.$attrs.' name="'.$name.'" value="'.$value.'">';
	}
	
	/*
		Function: checkbox - Returns checkbox input html string
	*/
	public function checkboxField($id, $name, $value, $spec, $attrs, $returnRawValue){
		$extra_label = $spec->get('label');
		$input_value = $spec->get('value', 1);
		$attrs = !empty($attrs) ? implode(' ', $attrs) : '';
		return '<input type="checkbox" '.$attrs.' name="'.$name.'" '.($value ? 'checked="checked"' : '').' value="'.$input_value.'" />'.($extra_label ? '<span>'.JText::_($extra_label).'</span>' : '');
	}
	
	/*
		Function: radio - Returns radio select html string
	*/
	public function radioField($id, $name, $value, $spec, $attrs, $returnRawValue){
		$preoptions = $spec->get('options') ? $spec->get('options') : array('JYES' => '1', 'JNO' => '0');
		$options = array(); foreach ($preoptions as $text => $val) $options[] = $this->app->html->_('select.option', $val, $text);
		return $this->app->html->_('select.radiolist', $options, $name, $attrs, 'value', 'text', $value, $name, true);
	}

	/*
		Function: date - Returns date select html string
	*/
	public function dateField($id, $name, $value, $spec, $attrs, $returnRawValue){
		$time = $spec->get('time') ? true : false;
		if ($value) try {
			$value = $this->app->html->_('date', $value, $this->app->date->format('%Y-%m-%d %H:%M:%S'), $this->app->date->getOffset());
		} catch (Exception $e) {}
		
		return $this->app->html->_('zoo.calendar', $value, $name, $name, '', $time);
	}

	/*
		Function: select - Returns select html string
	*/
	protected $_select_options = array();
	public function selectField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		$name   = $spec->get('multi') ? $name.'[]' : $name;
		$attrs .= $spec->get('multi') ? ' multiple="multiple" size="'.$spec->get('size', 3).'"' : '';

		$hash = md5(serialize( $spec ));
		if (!array_key_exists($hash, $this->_select_options))
		{
			$hidden_opts = $spec->get('hidden_opts', 0) ? explode('|', $spec->get('hidden_opts', '')) : '';
			
			// options file
			$opt_file = str_replace('{value}', @(string)$value, $spec->get('opt_file'));

			$preoptions = $spec->get('fix_options', array()) + $spec->get('options', array());
			if (!empty($opt_file) && $path = $this->app->path->path($opt_file))
			{	// get options from json file
				$preoptions = $preoptions + json_decode(file_get_contents($path), true);
			}
			
			$options = array(); // prepare options
			foreach ($preoptions as $text => $val) {
				if (empty($hidden_opts) || !in_array($val, $hidden_opts)) {
					// if no object, convert it to
					$options[] = is_object($val) ? $val : $this->app->html->_('select.option', $val, JText::_($text));
				}
			}

			$this->_select_options[$hash] = $options;
		}

		// get options
		$options = @$this->_select_options[$hash];

		// return current value instead
		if ($returnRawValue) {
			$option = array_shift($options);
			return $spec->get('multi') ? '' : $option->value;
		}
		
		// abort if minimal options not reached
		if (empty($options) || $spec->get('min_opts') && count($options) < $spec->get('min_opts', 0)) return;

		// render
		return $this->app->html->_('select.genericlist', $options, $name, $attrs, 'value', 'text', $value, $name)

		// add expander tool for multiple selects
		.($spec->get('multi') && count($options) > 3 ? '<span class="zl-btn-small zl-btn-expand zl-select-expand" data-zl-qtip="'.JText::_('PLG_ZLFRAMEWORK_EXPAND').'"></span>' : '');
	}

	// list - select field alias
	public function listField($id, $name, $value, $spec, $attrs, $returnRawValue) {
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}
	
	/*
		Function: layout - Returns select html string
			It list the files or folder of specified path as options
	*/
	protected $_layout_options = array();
	public function layoutField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// if no path supplied abort
		if(!$spec->get('path')) return JText::_('PLG_ZLFRAMEWORK_ZLFD_NO_OPTIONS');

		// return;
		$hash = md5(serialize( $spec ));
		if (!array_key_exists($hash, $this->_layout_options))
		{
			$psv	 = $spec->get('parents_val');
			$mode	 = $spec->get('mode', 'files'); // OR folders
			$regex	 = $spec->get('regex', '^([_A-Za-z0-9]*)');
			$options = (array)$spec->get('options', array());
			$path 	 = $spec->get('path');

			// dynamic values {}
			if (!is_array($value)) {
				$path = str_replace('{value}', (string)$value, $path);
			}

			// replace parent values in path
			foreach ((array)$psv as $key => $pvalue) {
				if (!is_array($pvalue)) {
					$path = str_replace('{'.$key.'}', basename($pvalue, '.php'), $path);
				}
			}

			// get all resources
			$resources = array();
			$paths = array_map('trim', explode(',',$path)); // multiple paths allowed with comma separator
			foreach($paths as $path) {
				if(preg_match('/(.*){subfolders}(.*)/', $path, $result)) { // process subfolders
					$path = trim(@$result[1], '/');
					$postpath = trim(@$result[2], '/');
					foreach ($this->app->path->dirs($path) as $dir) {
						$resources = array_merge($resources, $this->app->zlfw->path->resources("$path/$dir/$postpath"));
					}
				} else {
					$resources = array_merge($resources, $this->app->zlfw->path->resources($path));
				}
			}

			// get layout options from resources
			foreach($resources as $resource) {
				if(is_dir($resource)) foreach(JFolder::$mode($resource, $regex) as $tmpl) {
					$basename = basename($tmpl, '.php');
					$options[ucwords($basename)] = $tmpl;
				}
			}
			
			// sort letting default.php the first
			uasort($options, array($this, 'cmp'));

			// merge with current options
			$options = $spec->get('options', array()) + $options;
			
			// save the options
			$this->_layout_options[$hash] = $options;
		}

		// set options
		$spec->set('options', $this->_layout_options[$hash]);

		// process select field
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}

	/*
		Function: apps - Returns zoo apps html string
	*/
	public function appsField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$group   = $spec->get('group', ''); // filter apps
		$options = (array)$spec->get('options', array());

		foreach ($this->app->table->application->all(array('order' => 'name')) as $app) {
			if (empty($group) || $app->getGroup() == $group){
				$options[$app->name] = $app->id;
			}
		}

		// merge with current options
		$options = $spec->get('options', array()) + $options;

		// set options for select
		$spec->set('options', $options);
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}

	/*
		Function: submissions - Returns zoo submissions html select
	*/
	public function submissionsField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$pv	 	 = $this->app->data->create( $this->trslValues($spec->get('parents_val'), $spec->get('value_map')) );
		$group   = $spec->get('group', ''); // filter Types with app groups
		$apps    = (array)$spec->get('apps', $pv->get('apps', array())); // get static or parent app value
		$ft	     = (array)$spec->get('types', array()); // filterTypes
		
		// if is child and no filter provided, don't render
		if ($spec->find('value_map.apps') && empty($apps)) return;

		// get apps
		$apps = $this->app->zlfw->getApplications($apps, true, $group); // if empty will return All apps
 		
		// prepare submissions avoiding duplicates
		$submissions = array();
		foreach ($apps as $app){
			$submissions = array_merge($submissions, $app->getSubmissions());
		}
		
		// create options
		$options = array();
		foreach ($submissions as $submission) {
			$options[$submission->name] = $submission->id;
		}

		// merge with current options
		$options = $spec->get('options', array()) + $options;

		// set options for select
		$spec->set('options', $options);
		
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}
	
	/*
		Function: types - Returns zoo types html string
	*/
	public function typesField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$pv	 	 = $this->app->data->create( $this->trslValues($spec->get('parents_val'), $spec->get('value_map')) );
		$group   = $spec->get('group', ''); // filter Types with app groups
		$apps    = (array)$spec->get('apps', $pv->get('apps', array())); // get static or parent app value
		$ft	     = (array)$spec->get('types', array()); // filterTypes
		$subms	 = (array)$spec->get('submissions', $pv->get('submissions', array()));

		// clean values
		$apps  = array_filter($apps);
		$subms = array_filter($subms);

		// if is child and no filter provided, don't render
		if ($spec->find('value_map.submissions') && empty($subms)) return;

		// get apps
		$apps = $this->app->zlfw->getApplications($apps, true, $group); // if empty will return All apps
 		
		// prepare types avoiding duplicates
		$types = array();
		foreach ($apps as $app){
			$types = array_merge($types, $app->getTypes());
		}

		// filter types by submissions

		if ($subms)
		{
			// get allowed types from sumbissions
			$allowed_types_objects = array();
			foreach ($subms as $subm_id){
				$subm = $this->app->table->submission->get($subm_id);
				$allowed_types_objects = array_merge($allowed_types_objects, $subm->getSubmittableTypes());
			}

			// convert type objects to ID array
			$allowed_types = array();
			foreach ($allowed_types_objects as $type){
				$allowed_types[] = $type->id;
			}

			// check if type is valid
			foreach ($types as $id => $type){
				if (!in_array($type->id, $allowed_types)) {
					unset($types[$id]);
				}
			}
		}
		
		// create options
		$options = array();
		foreach ($types as $type) {
			if (empty($ft) || in_array($type->id, $ft)){ // type filter
				$options[$type->name] = $type->id;
			}
		}

		// merge with current options
		$options = $spec->get('options', array()) + $options;

		// set options for select
		$spec->set('options', $options);
		
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}

	/*
		Function: elements - Returns zoo elements html string
	*/
	public function elementsField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$pv = $this->app->data->create( $this->trslValues($spec->get('parents_val'), $spec->get('value_map')) );

		// apps
		$apps = (array)$pv->get('apps'); // from parent value
		$apps = array_merge($apps, explode(' ', $spec->get('apps', '')));
		// convert apps id to group
		foreach ($apps as &$app) if(is_numeric($app)) {
			$app = $this->app->table->application->get($app);
			$app = (is_object($app)) ? $app->getGroup() : null;
		}
		// clean duplicates
		$apps = array_unique($apps);

		// types
		$types = (array)$pv->get('types'); // from parent value
		$types = array_merge($types, explode(' ', (string)$spec->get('types', '')));

		// elements
		$element_type = explode(' ', $spec->get('elements', ''));
		
		// get elements list
		$options = $this->app->zlfield->elementsList($apps, $element_type, $types);
		
		if(empty($options)){
			// set text
			$spec->set('text', JText::_('PLG_ZLFRAMEWORK_APP_NO_ELEMENTS'));
			return $this->infoField($id, $name, $value, $spec, $attrs, $returnRawValue);
		} else {

			// merge with current options
			$options = $spec->get('options', array()) + $options;

			$spec->set('options', $options);			
			return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
		}
	}
	
	/*
		Function: cats - Returns zoo cats html string
	*/
	public function catsField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$pv	  = $this->app->data->create( $this->trslValues($spec->get('parents_val'), $spec->get('value_map')) );
		$apps = (array)$spec->get('apps', $pv->get('apps', array())); // get static or relied app value

		// load all apps only if is not child (processing all apps can exhaust the memory, should be avoided)
		$all_apps = $spec->get('parents_val') ? false : true; // avoid
		$apps = $this->app->zlfw->getApplications($apps, $all_apps);

		$options = array();
 		if (!empty($apps)) foreach($apps as $app)
		{
			// get category tree list
			$list = $this->app->tree->buildList(0, $app->getCategoryTree(), array(), '- ', '.   ', '  ');

			// create options
			$options[] = $this->app->html->_('select.option', 0, '-- -- -- '.$app->name.' ROOT -- -- --');
			foreach ($list as $category) {
				$options[] = $this->app->html->_('select.option', $category->id, $category->treename);
			}
		}

		// merge with current options
		$options = $spec->get('options', array()) + $options;

		$spec->set('options', $options);		
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}

	/*
		Function: tags - Returns zoo tags html string
	*/
	public function tagsField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$tags = $this->app->table->tag->getAll();

		// get options
		$options = array();
 		if (!empty($tags)) foreach($tags as $tag)
		{
			$options[] = $this->app->html->_('select.option', $tag->name, $tag->name);
		}

		$spec->set('options', $options);		
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}

	/*
		Function: items - Returns zoo items html string field
	*/
	public function itemsField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$html = array();
		$pv = $this->app->data->create( $this->trslValues($spec->get('parents_val'), $spec->get('value_map')) );

		// merge parent with specific declared values
		$apps = array_merge( $pv->get('apps', array()), explode(' ', $spec->get('apps', '')) );
		$types = array_merge( $pv->get('types', array()), explode(' ', $spec->get('types', '')) );

		// clean duplicates
		$apps = array_unique($apps);

		// clean values
		$apps = array_filter($apps);
		$types = array_filter($types);

		// set field JS params
		$params = compact('apps', 'types');

		// validate data
		settype($value, 'array');

		// if multi
		$name = $name.'[]';

		// if value
		if(!empty($value)) {

			// set model and query
			$model = $this->app->zlmodel->getNew('item')->setState('select', 'DISTINCT a.*')->id($value);

			// perform the query
			$items = $model->getList();

			// get Items object
			$objects = $this->app->zlfw->zlux->getItemObject($items);

			// set the form values
			foreach ($value as $item_id) {
				$html[] = "<div class=\"zlux-x-item\" data-info='".json_encode($objects[$item_id])."'>";
					$html[] = $objects[$item_id]['name'];
					$html[] = '<input type="hidden" name="'.$name.'" value="'.$item_id.'"  />';
				$html[] = '</div>';
			}
		}

		// render the field control name and params
		$html[] = "<input class=\"zlux-x-dummy\" type=\"hidden\" value=\"{$name}\" data-params='".json_encode($params)."' />";

		return implode('', $html);
	}

	/*
		Function: modulelist - Returns module list
	*/
	public function modulelistField($id, $name, $value, $specific=array(), $attribs='') {
		return $this->app->html->_('zoo.modulelist', array(), $name, null, 'value', 'text', $value);
	}
	
	/*
		Function: separatedBy - Returns separated options for repeatable elements
	*/
	public function separatedbyField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$constraint = $spec->get('constraint', ''); // filter layouts by metadata
		$options    = (array)$spec->get('options', array());
		
		$options['None'] 							= '';
		$options['Space'] 							= 'separator=[ ]';
		$options['Span'] 							= 'tag=[<span>%s</span>]';
		$options['Paragraph']						= 'tag=[<p>%s</p>]';
		$options['Div'] 							= 'tag=[<div>%s</div>]';

		$options['Comma'] 							= 'separator=[, ]';
		$options['Hyphen'] 							= 'separator=[ - ]';
		$options['Pipe'] 							= 'separator=[ | ]';
		$options['Break'] 							= 'separator=[<br />]';
		$options['List Item'] 						= 'tag=[<li>%s</li>]';
		$options['Unordered List'] 					= 'tag=[<li>%s</li>] enclosing_tag=[<ul>%s</ul>]';
		$options['Ordered List'] 					= 'tag=[<li><div>%s</div></li>] enclosing_tag=[<ol>%s</ol>]';
		$options['Warp Item'] 						= 'tag=[<article class="item">%s</article>]';
		$options['Blank'] 							= 'tag=[%s]';
		$options['UIkit Block'] 					= 'tag=[<div class="uk-margin">%s</div>]';
		$options['UIkit Article'] 					= 'tag=[<article class="uk-article">%s</article>]';
		$options['UIkit List'] 						= 'tag=[<li>%s</li>] enclosing_tag=[<ul class="uk-list">%s</ul>]';
		$options['UIkit List Line'] 				= 'tag=[<li>%s</li>] enclosing_tag=[<ul class="uk-list uk-list-line">%s</ul>]';
		$options['PLG_ZLFRAMEWORK_CUSTOM'] 			= 'custom';

		// merge with current options
		$options = $spec->get('options', array()) + $options;

		$spec->set('options', $options);
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}

	/*
		Function: options - Returns options field html string
	*/
	public function optionsField($id, $name, $value, $spec, $attrs, $returnRawValue){
		// set value as data
		$value = is_array($value) ? $value : array();

		// init vars
		$html = array();
		$num = 0;

		$html[] = '<div class="zlux-field-options">';
			$html[] = '<ul>';
	
				// render current options
				foreach ($value as $opt) {
					$html[] = '<li class="zlux-x-option">' . $this->_optionsFieldOption($name, $num, $opt) . '</li>';
					$num++;
				}
	
				$html[] = '<li class="zlux-x-option hidden">';
						$html[] = $this->_optionsFieldOption($name, $num, '');
				$html[] = '</li>';

			$html[] = '</ul>';

			$html[] = '<button type="button" class="btn btn-mini zlux-x-add"><i class="icon-plus-sign"></i>' . JText::_('Add Option') . '</button>';
		$html[] = '</div>';

		return implode('', $html);
	}

	protected function _optionsFieldOption($name, $num, $opt) {
		// init vars
		$html = array();

		$html[] = '<div class="zlux-x-name">';
			$html[] = '<label for="name">Name</label>';
			$html[] = '<input type="text" name="' .$name.'['.$num.'][name]'. '" value="' . @$opt['name'] .'" />';
		$html[] = '</div>';

		$html[] = '<div class="zlux-x-value">';
			$html[] = '<label for="value">Value</label>';
			$html[] = '<a class="trigger" href="#" title="' . JText::_('Edit Option Value') . '">' . @$opt['value'] . '</a>';

			$html[] = '<div class="panel">';
				$html[] = '<input type="text" name="' . $name.'['.$num.'][value]' . '" value="' . @$opt['value'] . '" />';
				$html[] = '<div class="btns">';
					$html[] = '<input type="button" class="accept" value="' . JText::_('Accept') . '">';
					$html[] = '<a href="#" class="cancel">' . JText::_('Cancel') . '</a>';
				$html[] = '</div>';
			$html[] = '</div>';
		$html[] = '</div>';
	
		$html[] = '<div class="zlux-x-tools">';
			$html[] = '<i class="zlux-x-delete icon-remove-circle" title="' .JText::_('Delete option'). '"></i>';
			$html[] = '<i class="zlux-x-sort icon-move" title="' .JText::_('Sort option'). '"></i>';
		$html[] = '</div>';

		return implode('', $html);
	}

	/*
		Function: attributes - Returns attributes field html string
	*/
	public function attributesField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// set value as data
		$value = is_array($value) ? $value : array();

		// init vars
		$html = array();
		$html[] = '<ul class="zlux-field-attributes">';
			$html[] = $this->_attributesFieldLevel($name, $value);
		$html[] = '</ul>';

		// hidden
		$html[] = '<ul class="hidden">';
			$html[] = $this->_attributesFieldLevel($name, array(
				array('name' => '', 'value' => '', 'options' => array(array('name' => '', 'value' => ''))))
			);
		$html[] = '</ul>';

		// Add attr
		$html[] = '<button type="button" class="btn btn-mini zlux-x-add-attr"><i class="icon-plus-sign"></i>Add Attribute</button>';

		return implode('', $html);
	}

	protected function _attributesFieldLevel($name, $value) {
		$html = array();

		// process attributes
		foreach ($value as $key => $val) {
			$num = 0;
			$html[] = '<li class="zlux-x-attr">';

				$html[] = '<div class="zlux-x-tools">';
					$html[] = '<i class="zlux-x-delete icon-remove-circle" title="' .JText::_('Delete option'). '"></i>';
					$html[] = '<i class="zlux-x-sort icon-move" title="' .JText::_('Sort option'). '"></i>';
				$html[] = '</div>';

				$html[] = '<div>Name <input type="text" name="' . $name.'['.$key.'][name]' . '" value="' . @$val['name'] . '" /></div>';
				$html[] = '<div>Value <input type="text" name="' . $name.'['.$key.'][value]' . '" value="' . @$val['value'] . '" /></div>';
					

				$html[] = '<div>Options<ul>';

				// process options
				if (isset($val['options'])) foreach ($val['options'] as $option) {
					$html[] = '<li class="zlux-x-option">';

						$html[] = $this->_optionsFieldOption($name.'['.$key.'][options]', $num, $option);
						$num++;

					$html[] = '</li>';
				}

				$html[] = '</ul>';

				// add option button
				$html[] = '<button type="button" class="btn btn-mini zlux-x-add"><i class="icon-plus-sign"></i>Option</button>';
				$html[] = '</div>';


			$html[] = '</li>';
		}

		return implode('', $html);
	}

	protected function _attributesFieldOptionlayout($name, $opt) {
		// init vars
		$html = array();

		$html[] = '<div class="zlux-x-name">';
			$html[] = '<label for="name">Name</label>';
			$html[] = '<input type="text" name="' .$name.'[name]'. '" value="' . @$opt['name'] .'" />';
		$html[] = '</div>';

		$html[] = '<div class="zlux-x-value">';
			$html[] = '<label for="value">Value</label>';
			$html[] = '<a class="trigger" href="#" title="' . JText::_('Edit Option Value') . '">' . @$opt['value'] . '</a>';

			$html[] = '<div class="panel">';
				$html[] = '<input type="text" name="' . $name.'[value]' . '" value="' . @$opt['value'] . '" />';
				$html[] = '<div class="btns">';
					$html[] = '<input type="button" class="accept" value="' . JText::_('Accept') . '">';
					$html[] = '<a href="#" class="cancel">' . JText::_('Cancel') . '</a>';
				$html[] = '</div>';
			$html[] = '</div>';
		$html[] = '</div>';
	
		$html[] = '<div class="zlux-x-tools">';
			$html[] = '<i class="zlux-x-delete icon-remove-circle" title="' .JText::_('Delete option'). '"></i>';
			$html[] = '<i class="zlux-x-sort icon-move" title="' .JText::_('Sort option'). '"></i>';
		$html[] = '</div>';

		return implode('', $html);
	}


	/*
		Function: wk2 - Returns Widgetkit2 field html string
	*/

	public function wk2Field($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		if (!$app = @include(JPATH_ADMINISTRATOR . '/components/com_widgetkit/widgetkit-app.php')) {
            return;
        }

        $app['scripts']->add('widgetkit-zlfw', JPATH_ROOT.'/plugins/system/zlframework/zlframework/zlfield/assets/wk2Field.js', array('widgetkit-application'));
        
        $app->trigger('init.admin', array($app));

        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $widgets = json_encode(explode(',', str_replace(' ', '', $spec->get('widgets'))));

        return <<<EOT
	    <a class="wk2-zl-selector" data-zl-widgets=$widgets>Select Widget</a>
	    <span class="wk2-zl-selected"></span>
	    <input type="hidden" name="$name" value="$value">
EOT;
	}

	/* HTML Fields Helpers
	--------------------------------------------------------------------------------------------------------------------------------------------*/
	
	/*
		Function: itemLayoutList - Returns related layouts list
	*/
	public function itemLayoutListField($id, $name, $value, $spec, $attrs, $returnRawValue)
	{
		// init vars
		$constraint = $spec->get('constraint', ''); // filter layouts by metadata
		$options    = (array)$spec->get('options', array());
		$typeFilter = $spec->get('typefilter') ? explode(',', 'event') : null;
		
		// pass trough all apps
		$layouts = array();	
		foreach($this->app->table->application->all(array('order' => 'name')) as $application) if ($template = $application->getTemplate())
		{	
			$layout_path = str_replace("\\", "/", $template->getPath());
			
			// get renderer
			$renderer = $this->app->renderer->create('item')->addPath($layout_path);
			
			// get all types
			$folders = array();			
			foreach (JFolder::folders($layout_path.'/'.$renderer->getFolder().'/item') as $folder) {
				$folders[] = $folder;
			}
			
			// Check for root folder, in case app doesn't have type related layouts
			$layouts = array_merge($layouts, $this->_getLayouts(null, $constraint, $renderer));
			
			// Now in subfolders
			foreach ($folders as $type){
				if (empty($typeFilter) || in_array($type, $typeFilter)) {
					$layouts = array_merge($layouts, $this->_getLayouts($type, $constraint, $renderer));
				}
			}
		}
		
		// create layout options
		foreach ($layouts as $layout) $options[$layout['name']] = $layout['layout'];

		// merge with current options
		$options = $spec->get('options', array()) + $options;

		$spec->set('options', $options);
		return $this->selectField($id, $name, $value, $spec, $attrs, $returnRawValue);
	}
	
	protected function _getLayouts($type = null, $constraint = null, $renderer = null)
	{
		$path   = 'item';
		$prefix = 'item.';
		if (!empty($type) && $renderer->pathExists($path.DIRECTORY_SEPARATOR.$type)) {
			$path   .= DIRECTORY_SEPARATOR.$type;
			$prefix .= $type.'.';
		}
		
		$layouts = array();
		foreach ($renderer->getLayouts($path) as $layout) {
	
			$metadata = $renderer->getLayoutMetaData($prefix.$layout);
			
			if (empty($constraint) || $metadata->get('type') == $constraint) {
				$name = $metadata->get('name') ? $metadata->get('name') : ucfirst($layout);
				$layouts[$layout] = array('name' => $name, 'layout' => $layout);
			}
		}
		return $layouts;
	}
}