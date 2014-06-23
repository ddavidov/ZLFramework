<?php
/**
* @package      ZL Framework
* @author       JOOlanders, SL http://www.zoolanders.com
* @copyright    Copyright (C) JOOlanders, SL
* @license      http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

class ZLModelItem extends ZLModel
{
	protected $join_cats = false;
	protected $join_frontpage = false;
	protected $join_tags = false;

	/**
	 * Magic method to set states by calling a method named as the filter
	 * @param  string $name The name of the state to apply
	 * @param  array  $args The list of arguments passed to the method
	 */
	public function __call($name, $args)
	{
		// Go for the states!
		if (!method_exists($this, $name)) {
			
			// if no arguments supplied, abort
			if (!isset($args[0])) return $this;

			// The state name to set is the method name
			$state = (string) $name;
			
			// $model->categories(array('value' => '123', 'mode' => 'AND'));
			if (is_array($args[0]) || is_object($args[0])) {
				$options = new JRegistry($args[0]);
			} else {
				// $model->element('id', $options);
				if (isset($args[1])) {
					// $model->element('id', $options);
					if (is_array($args[1]) || is_object($args[1])) {
						$options = new JRegistry($args[1]);
					} else {
						// $model->element('id', 'value');
						$options = new JRegistry();
						$options->set('value', $args[1]);
						$options->set('id', $args[0]);
					}
				} else {
					$options = new JRegistry;
					// Just the value
					
					$options->set('value', $args[0]);
				}
			}

			$this->setState($state, $options);
			return $this;
		}
		
		// Normal method calling
		return parent::__call($name, $args);
	}

	/**
	 * Dont' overwrite the old state if requested
	 * @param [type] $key   [description]
	 * @param [type] $value [description]
	 */
	public function setState($key, $value = null, $overwrite = false) {
		
		if (!$overwrite) {
			$old_value = $this->getState($key, array());
			if (is_array($value)) {
				$value = array_merge($old_value, $value);
			} else {
				$old_value[] = $value;
				$value = $old_value;
			}
		}

		parent::setState($key, $value);

		return $this;
	}

	/*
		Function: _buildQueryFrom
			Builds FROM tables list for the query
	*/
	protected function _buildQueryFrom(&$query)
	{
		$query->from(ZOO_TABLE_ITEM.' AS a');
	}

	/*
		Function: _buildQueryJoins
			Builds JOINS clauses for the query
	*/
	protected function _buildQueryJoins(&$query)
	{
		// categories
		if ($this->join_cats) {
			$query->join('LEFT', ZOO_TABLE_CATEGORY_ITEM." AS b ON a.id = b.item_id");
		}

		// frontpage
		if ($this->join_frontpage) {
			$query->join('LEFT', ZOO_TABLE_CATEGORY_ITEM." AS f ON a.id = f.item_id");
		}

		// tags
		if ($this->join_tags) {
			$query->join('LEFT', ZOO_TABLE_TAG." AS t ON a.id = t.item_id");
		}

		// elements
		if ($orderby = $this->getState('order_by'))
		{
			// get item ordering
			list($join, $order) = $this->_getItemOrder($orderby);

			// save order for order query
			$this->orderby = $order;
			
			// join
			if($join){ // don't use escape() here
				$query->leftJoin($join);
			}
		}
	}

	/*
		Function: _buildQueryWhere
			Builds WHERE query
	*/
	protected function _buildQueryWhere(&$query)
	{
		// Apply basic filters
		$this->basicFilters($query);
		
		// Element filters
		$this->elementFilters($query);
	}

	/*
		Function: _buildQueryGroup
			Builds a GROUP BY clause for the query
	*/
	protected function _buildQueryGroup(&$query)
	{
		if($group_by = $this->_db->escape( $this->getState('group_by') )){
			$query->group('a.' . $group_by);
		}
	}

	/*
		Function: _buildQueryOrder
			Bilds ORDER BY query
	*/
	protected function _buildQueryOrder(&$query)
	{
		// custom order
		if ($this->getState('order_by') && isset($this->orderby))
		{
			$query->order( $this->orderby );
		}
	}

	/**
	 * Apply general filters like searchable, publicated, etc
	 */
	protected function basicFilters(&$query)
	{
		// init vars
		$date = JFactory::getDate();
		$now  = $this->_db->Quote($date->toSql());
		$null = $this->_db->Quote($this->_db->getNullDate());

		// Items id
		if ($ids = $this->getState('id', false)) {
			$where = array();
			foreach($ids as $id) {
				$where[] = 'a.id IN ('.implode(',', $id->toArray()).')';
			}
			$query->where('(' . implode(' OR ', $where) . ')');
		}

		// Searchable state
		$searchable = $this->getState('searchable');
		if (isset($searchable[0]) && !empty($searchable[0])) {
			$query->where('a.searchable = 1');
		}
		
		// Published state
		$state = $this->getState('state');
		if (isset($state[0])) $query->where('a.state = ' . (int)$state[0]->get('value', 1));

		// Accessible
		$user = $this->getState('user');
		$user = isset($user[0]) ? $this->app->user->get($this->_db->escape( $user[0] )) : null;

		$query->where('a.' . $this->app->user->getDBAccessString($user));

		// Created_by
		if ($authors = $this->getState('created_by', array())) {
			$ids = array();
			foreach ($authors as $author) $ids[] = $author->get('value');
			
			// set query
			$query->where("a.created_by IN (" . implode(',', $ids) . ")");
		}

		// Modified_by
		if ($editors = $this->getState('modified_by', array())) {
			$ids = array();
			foreach ($editors as $editor) $ids[] = $editor->get('value');
			
			// set query
			$query->where("a.modified_by IN (" . implode(',', $ids) . ")");
		}

		// Created
		if ($date = $this->getState('created', array()))
		{
			$date = array_shift($date);

			$sql_value 		= "a.created";
			$value 			= $date->get('value', '');
			$value_from		= !empty($value) ? $value : '';
			$value_to 		= $date->get('value_to', '');
			$search_type 	= $date->get('type', false);
			$period_mode 	= $date->get('period_mode', 'static');
			$interval 		= $date->get('interval', 0);
			$interval_unit 	= $date->get('interval_unit', '');
			$datetime		= $date->get('datetime', false);

			$query->where($this->getDateSearch(compact('sql_value', 'value', 'value_from', 'value_to', 'search_type', 'period_mode', 'interval', 'interval_unit', 'datetime')));
		}

		// Modified
		if ($date = $this->getState('modified', array()))
		{
			$date = array_shift($date);

			$sql_value 		= "a.modified";
			$value 			= $date->get('value', '');
			$value_from		= !empty($value) ? $value : '';
			$value_to 		= $date->get('value_to', '');
			$search_type 	= $date->get('type', false);
			$period_mode 	= $date->get('period_mode', 'static');
			$interval 		= $date->get('interval', 0);
			$interval_unit 	= $date->get('interval_unit', '');
			$datetime		= $date->get('datetime', false);

			$query->where($this->getDateSearch(compact('sql_value', 'value', 'value_from', 'value_to', 'search_type', 'period_mode', 'interval', 'interval_unit', 'datetime')));
		}

		// Published up
		if ($date = $this->getState('published', array()))
		{
			$date = array_shift($date);

			$sql_value 		= "a.publish_up";
			$value 			= $date->get('value', '');
			$value_from		= !empty($value) ? $value : '';
			$value_to 		= $date->get('value_to', '');
			$search_type 	= $date->get('type', false);
			$period_mode 	= $date->get('period_mode', 'static');
			$interval 		= $date->get('interval', 0);
			$interval_unit 	= $date->get('interval_unit', '');
			$datetime		= $date->get('datetime', false);

			$query->where($this->getDateSearch(compact('sql_value', 'value', 'value_from', 'value_to', 'search_type', 'period_mode', 'interval', 'interval_unit', 'datetime')));

		// default
		} elseif (!$this->getState('created') && !$this->getState('modified')) {
			$where = array();
			$where[] = 'a.publish_up = '.$null;
			$where[] = 'a.publish_up <= '.$now;
			$query->where('('.implode(' OR ', $where).')');
		}

		// Published down
		if ($date = $this->getState('published_down', array()))
		{
			$date = array_shift($date);

			$sql_value 		= "a.publish_down";
			$value 			= $date->get('value', '');
			$value_from		= !empty($value) ? $value : '';
			$value_to 		= $date->get('value_to', '');
			$search_type 	= $date->get('type', false);
			$period_mode 	= $date->get('period_mode', 'static');
			$interval 		= $date->get('interval', 0);
			$interval_unit 	= $date->get('interval_unit', '');
			$datetime		= $date->get('datetime', false);

			$query->where($this->getDateSearch(compact('sql_value', 'value', 'value_from', 'value_to', 'search_type', 'period_mode', 'interval', 'interval_unit', 'datetime')));

		// default
		} else if (!$this->getState('published_down')) {
			$where = array();
			$where[] = 'a.publish_down = '.$null;
			$where[] = 'a.publish_down >= '.$now;
			$query->where('('.implode(' OR ', $where).')');
		}

		// Frontpage
		$frontpage = $this->getState('frontpage', null);
		if ($frontpage !== null) {
			if ($frontpage) {
				$this->join_frontpage = true;
				$query->where('f.category_id  = 0');
			}
		}
	}

	/**
	 * Create and returns a nested array of App->Type->Elements
	 */
	protected function getNestedArrayFilter()
	{
		// init vars
		$this->apps  = $this->getState('application', array());
		$this->types = $this->getState('type', array());

		// convert apps into raw array
		if (count($this->apps)) foreach ($this->apps as $key => $app) {
			$this->apps[$key] = $app->get('value', '');
		}

		// convert types into raw array
		if (count($this->types)) foreach ($this->types as $key => $type) {
			$this->types[$key] = $type->get('value', '');
		}

		// get apps selected objects, or all if none filtered
		$apps = $this->app->table->application->all(array('conditions' => count($this->apps) ? 'id IN('.implode(',', $this->apps).')' : ''));

		// create a nested array with all app/type/elements filtering data
		$filters = array();
		foreach($apps as $app) {
			
			$filters[$app->id] = array();
			foreach ($app->getTypes() as $type) {

				// get type elements
				$type_elements = $type->getElements();
				$type_elements = array_keys($type_elements);

				// get selected elements
				$elements = $this->getState('element', array());

				// filter the current type elements
				$valid_elements = array();
				if ($elements) foreach ($elements as $key => $element) {
					$identifier = $element->get('id');

					// if element part of current type, it's valid
					if (in_array($identifier, $type_elements)) {
						$valid_elements[] = $element;

						// remove current element to avoid revalidation
						unset($elements[$key]);
					}
				}

				// if there are elements for current type, or type is selected for filtering
				if (count($valid_elements) || in_array($type->id, $this->types)) {

					// save the type and it's elements
					$filters[$app->id][$type->id] = $valid_elements;
				}
			}
		}

		return $filters;
	}

	/**
	 * Apply element filters
	 */
	protected function elementFilters(&$query)
	{
		$wheres = array('AND' => array(), 'OR' => array());

		// Item name filtering
		$names = $this->getState('name');
		if ($names){
			foreach ($names as $name) {
				$logic = strtoupper($name->get('logic', 'AND'));
				$wheres[$logic][] = 'a.name LIKE ' . $this->getQuotedValue( $name );
			}
		}
		
		// Category filtering
		$categories = $this->getState('categories', array());
		if ($categories) {
			foreach ( $categories as $cats ) {
				if ($value = $cats->get('value', array())) {
					$logic = $cats->get('logic', 'AND'); 
					// build the where for ORs
					if ( strtoupper($cats->get('mode', 'OR')) == 'OR' ){
						$wheres[$logic][] = "b.category_id IN (".implode(',', $value).")";

						// set the join only on the OR, since AND has a subquery
						$this->join_cats = true;
					} 
					else {
						// it's heavy query but the only way for AND mode
						foreach ($value as $id) {
							$wheres[$logic][] =
							"a.id IN ("
							." SELECT b.id FROM ".ZOO_TABLE_ITEM." AS b"
							." LEFT JOIN " . ZOO_TABLE_CATEGORY_ITEM . " AS y"
							." ON b.id = y.item_id"
							." WHERE y.category_id = ".(int) $id .")";
						}
					}
				}
			}
		}

		// Tags filtering
		$allTags = $this->getState('tags', array());
		if ($allTags) {
			foreach ( $allTags as $tags ) {
				if ($values = $tags->get('value', array())) {
					$logic = $tags->get('logic', 'AND'); 

					// quote the values
					foreach ($values as &$val) {
						$val = $this->_db->Quote( $val );
					}

					// build the where for ORs
					if ( strtoupper($tags->get('mode', 'OR')) == 'OR' ){
						$wheres[$logic][] = "t.name IN (".implode(',', $values ).")";

						// set the join only on the OR, since AND has a subquery
						$this->join_tags = true;
					} 
					else {
						// it's heavy query but the only way for AND mode
						foreach ($values as $val) {
							$wheres[$logic][] =
							"a.id IN ("
							." SELECT ti.id FROM " . ZOO_TABLE_ITEM . " AS ti"
							." LEFT JOIN " . ZOO_TABLE_TAG . " AS t"
							." ON ti.id = t.item_id"
							." WHERE t.name = " . $val . ")";
						}
					}
				}
			}
		}
		
		// Elements filtering
		$k = 0;

		// get the filter query
		$nestedFilter = $this->getNestedArrayFilter();
		foreach ($nestedFilter as $app => &$types) {

			// iterate over types
			$types_queries = array();
			foreach ($types as $type => &$type_elements) {
				
				// init vars
				$elements_where = array('AND' => array(), 'OR' => array());

				// set the type query
				$type_query = 'a.type LIKE ' . $this->_db->Quote($type);

				// get individual element query
				foreach ($type_elements as $element) {
					$this->getElementSearch($element, $k, $elements_where);
				}

				// merge elements ORs / ANDs
				$elements_query = '';
				if ( count( $elements_where['OR'] ) ) {
					$type_query .= ' AND (' . implode(' OR ', $elements_where['OR']) . ')';
				}

				if ( count( $elements_where['AND'] ) ) {
					$type_query .= ' AND (' . implode(' AND ', $elements_where['AND']) . ')';
				}

				// save type query
				$types_queries[] = $type_query;
			}

			// types query
			$types_query = count($types_queries) ? implode(' OR ', $types_queries) : '';

			// app query
			$app_query = in_array($app, $this->apps) ? 'a.application_id = ' . (int)$app : '';

			// set the app->type->elements query
			if ($app_query && $types_query) {
				$wheres['AND'][] = '(' . $app_query . ' AND (' . $types_query . '))';
			} else if ($app_query || $types_query) {
				$wheres['AND'][] = '(' . $app_query . $types_query . ')';
			}
		}

		// At the end, merge ORs
		if( count( $wheres['OR'] ) ) {
			$query->where('(' . implode(' OR ', $wheres['OR']) . ')');
		}
		
		// and the ANDs
		foreach ($wheres['AND'] as $where) {
			$query->where($where);
		}
		
		// Add repeatable joins
		$this->addRepeatableJoins($query, $k);
	}

	/**
	 * Get the individual element search
	 */
	protected function getElementSearch($element, &$k, &$wheres)
	{
		// abort if no value is set
		if (!$element->get('value')) return;
		
		// Options!
		$id         = $element->get('id');
		$value      = $element->get('value');
		$logic      = strtoupper($element->get('logic', 'AND'));
		$mode       = $element->get('mode', 'AND');
		$from       = $element->get('from', false);
		$to         = $element->get('to', false);
		$convert    = $element->get('convert', 'DECIMAL');
		$type       = $element->get('type', false);

		$is_select  = $element->get('is_select', false);
		$is_date    = $element->get('is_date', false);
		$is_range   = in_array($type, array('range', 'rangeequal', 'from', 'to', 'fromequal', 'toequal', 'outofrange', 'outofrangeequal'));

		// Multiple choice!
		if( is_array( $value ) && !$from && !$to) {
			$wheres[$logic][] = $this->getElementMultipleSearch($id, $value, $mode, $k, $is_select);
		} else {
			// Search ranges!
			if ($is_range && !$is_date){

				// make sure the values are set
				$from = !empty($from) ? $from : $value;
				$to = !empty($to) ? $to : $value;

				// get and add the query
				if ($sql = $this->getElementRangeSearch($id, $from, $to, $type, $convert, $k)) {
					$wheres[$logic][] = $sql;
				}

			} else  {
				// Special date case
				if ($is_date) {
					$sql_value = "b$k.value";
					$value_from = !empty($from) ? $from : '';
					$value_to = !empty($to) ? $to : '';
					$search_type = $type;
					$period_mode = $element->get('period_mode', 'static');
					$interval = $element->get('interval', 0);
					$interval_unit = $element->get('interval_unit', '');
					$wrapper = "(b$k.element_id = '$id' AND {query})";

					$wheres[$logic][] = $this->getDateSearch(compact('sql_value', 'value', 'value_from', 'value_to', 'search_type', 'period_mode', 'interval', 'interval_unit', 'wrapper'));
				} else {
					// Normal search
					$value = $this->getQuotedValue($element);
					$wheres[$logic][] = "(b$k.element_id = '" . $id . "' AND TRIM(b$k.value) LIKE " . $value .') ';     
				}
			}
		}
		$k++;
	}

	/**
	 * Get the range search sql
	 */
	protected function getElementRangeSearch($identifier, $from, $to, $type, $convert, $k)
	{	
		// basic check
		if (!isset($from) || !isset($to)) return;

		// Evaluate if is equal
		$is_equal = false;
		if (stripos($type, "equal") !== false) {
			$is_equal = true;
			$type = str_ireplace("equal", "", $type);
		}

		// Decimal conversion fix
		if ($convert == 'DECIMAL') 
			$convert = 'DECIMAL(10,2)';

		// Defaults
		$sql = array();
		$value = $from;
		$symbol = "";

		// Symbol and value based on the type
		switch($type) {
			case "from":
				$value = $from;
				$symbol = ">";
				break;
			case "to": 
				$value = $to;
				$symbol = "<";
				break;
			case "range": 
				if ($from) {
					$new_type = $is_equal ? 'fromequal' : 'from';
					$sql[] = $this->getElementRangeSearch($identifier, $from, $to, $new_type, $convert, $k);
				}
				if ($to) {
					$new_type = $is_equal ? 'toequal' : 'to';
					$sql[] = $this->getElementRangeSearch($identifier, $from, $to, $new_type, $convert, $k);
				}
				return implode(" AND ", $sql);
				break;
			case "outofrange":
				if ($to) {
					$new_type = $is_equal ? 'fromequal' : 'from';
					$sql[] = $this->getElementRangeSearch($identifier, $to, $from, $new_type, $convert, $k);
				}
				if ($from) {
					$new_type = $is_equal ? 'toequal' : 'to';
					$sql[] = $this->getElementRangeSearch($identifier, $to, $from, $new_type, $convert, $k);
				}
				return implode(" AND ", $sql);
				break;
		}

		// Add equal sign
		if ($is_equal) {
			$symbol .= "=";
		}
		
		// Build range sql
		return "(b$k.element_id = '" . $identifier . "' AND CONVERT(TRIM(b$k.value+0), $convert) " . $symbol . " " . $value.")";
	}

	/**
	 * Get the date search sql
	 * $sql_value, $value, $value_from, $value_to, $type, $period_mode, $wrapper=false, $interval, $interval_unit, $datetime
	 */
	protected function getDateSearch($__args = array())
	{
		// init vars
		if (is_array($__args)) {
			foreach ($__args as $__var => $__value) {
				$$__var = $__value;
			}
		}

		// init vars
		$tzoffset = $this->app->date->getOffset();
		$datetime = isset($datetime) ? (bool)$datetime : false;
		$regex = '/^(19[0-9]{2}|2[0-9]{3})-(0[1-9]|1[012])-([123]0|[012][1-9]|31)/'; // date format yyyy-mm-dd

		// replace vars
		$yesterday = $this->app->date->create('yesterday', $tzoffset);
		$today = $this->app->date->create('today', $tzoffset);
		$tomorrow = $this->app->date->create('tomorrow', $tzoffset);

		if (is_string($value)) $value = preg_replace(
			array('/\[yesterday\]/', '/\[today\]/', '/\[tomorrow\]/'),
			array($yesterday, $today, $tomorrow),
			$value
		);
		if (is_string($value_from)) $value_from = preg_replace(
			array('/\[yesterday\]/', '/\[today\]/', '/\[tomorrow\]/'),
			array($yesterday, $today, $tomorrow),
			$value_from
		);
		if (is_string($value_to)) {
			$yesterday = substr($yesterday, 0, 10).' 23:59:59';
			$today = substr($today, 0, 10).' 23:59:59';
			$tomorrow = substr($tomorrow, 0, 10).' 23:59:59';

			$value_to = preg_replace(
				array('/\[yesterday\]/', '/\[today\]/', '/\[tomorrow\]/'),
				array($yesterday, $today, $tomorrow),
				$value_to
			);
		}

		// set values
		$wrapper = isset($wrapper) ? $wrapper : false;
		$period_mode = isset($period_mode) ? $period_mode : 'static';
		$search_type = $search_type == 'range' ? 'period' : $search_type; // workaround

		// search_type = to:from:default
		if (!empty($value) && $search_type != 'period') { 

			if($datetime) {
				$from = $to = $value;
			} else {
				$date = substr($value, 0, 10);
				$from = $date.' 00:00:00';
				$to   = $date.' 23:59:59';
			}

		// search_type = period
		} else {

			if($datetime) {
				$from = $value_from;
				$to   = $value_to;
			} else {
				$from = substr($value_from, 0, 10).' 00:00:00';
				$to   = substr($value_to, 0, 10).' 23:59:59';
			}
		}

		// set offset if valid date format
		$from = preg_match($regex, $from) ? $this->app->date->create($from, $tzoffset)->toSQL() : $from;
		$to = preg_match($regex, $to) ? $this->app->date->create($to, $tzoffset)->toSQL() : $to;
		
		// set quotes
		$from = $this->_db->Quote($this->_db->escape($from));
		$to   = $this->_db->Quote($this->_db->escape($to));

		switch ($search_type) {
			case 'from':
				if($datetime)
					$el_where = "( ($sql_value >= $from) OR ($from <= $sql_value) )";
				else
					$el_where = "( (SUBSTR($sql_value, -19) >= $from) OR ($from <= SUBSTR($sql_value, -19)) )";
				break;

			case 'to':
				$el_where = "( (SUBSTR($sql_value, 1, 19) <= $to) OR ($to >= SUBSTR($sql_value, 1, 19)) )";
				break;

			case 'period':
				if ($period_mode == 'static') 
				{
					if($datetime)
					$el_where = "( ($from BETWEEN $sql_value AND $sql_value) OR ($to BETWEEN $sql_value AND $sql_value) OR ($sql_value BETWEEN $from AND $to) OR ($sql_value BETWEEN $from AND $to) )";
					else 
					$el_where = "( ($from BETWEEN SUBSTR($sql_value, 1, 19) AND SUBSTR($sql_value, -19)) OR ($to BETWEEN SUBSTR($sql_value, 1, 19) AND SUBSTR($sql_value, -19)) OR (SUBSTR($sql_value, 1, 19) BETWEEN $from AND $to) OR (SUBSTR($sql_value, -19) BETWEEN $from AND $to) )";
				} 
				else // dynamic
				{
					$interval_unit = strtoupper($interval_unit);
					$valid = array('MONTH', 'DAY', 'WEEK', 'YEAR', 'MINUTE', 'SECOND', 'HOUR');
					if (!in_array($interval_unit, $valid)) {
						$interval_unit = 'WEEK';
					}

					// DATE ADD
					if ($interval > 0) {
						$el_where = "( $sql_value BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $interval $interval_unit) )";
					} else {
						$el_where = "( $sql_value BETWEEN DATE_ADD(NOW(), INTERVAL $interval $interval_unit) AND NOW() )";
					}
				}
				break;

			default:
				// set offset and escape quotes
				$date = preg_match($regex, $date) ? $this->app->date->create($date, $tzoffset)->toSQL() : $date;
				$date = $this->_db->escape($date);

				if($datetime)
				$el_where = "( ($sql_value LIKE '%$date%') OR (('$date' BETWEEN $sql_value AND $sql_value) AND $sql_value NOT REGEXP '[[.LF.]]') )";
				else
				$el_where = "( ($sql_value LIKE '%$date%') OR (('$date' BETWEEN SUBSTR($sql_value, 1, 10) AND SUBSTR($sql_value, -19, 10)) AND $sql_value NOT REGEXP '[[.LF.]]') )";
		}

		// wrapper the query if necesary
		$el_where = $wrapper ? preg_replace('/{query}/', $el_where, $wrapper) : $el_where;
		
		// return with extra space
		return $el_where.' ';
	}
	
	/**
	 * Get the multiple values search sql
	 */
	protected function getElementMultipleSearch($identifier, $values, $mode, $k, $is_select = true)
	{
		$el_where = "b$k.element_id = " . $this->_db->Quote($identifier);               

		// lets be sure mode is set
		$mode = $mode ? $mode : "AND";
		
		$multiples = array();
		
		// Normal selects / radio / etc (ElementOption)
		if($is_select)
		{
			foreach($values as $value)
			{
				$multiple = "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim($this->_db->escape($value)))." OR ";
				$multiple .= "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim($this->_db->escape($value)."\n%"))." OR ";
				$multiple .= "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim("%\n".$this->_db->escape($value)))." OR ";
				$multiple .= "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim("%\n".$this->_db->escape($value)."\n%"));
				$multiples[] = "(".$multiple.")";
			}
		} 
		// This covers country element too
		else 
		{
			foreach($values as $value)
			{
				$multiple = "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim($this->_db->escape($value)))." OR ";
				$multiple .= "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim($this->_db->escape($value).' %'))." OR ";
				$multiple .= "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim('% '.$this->_db->escape($value)))." OR ";
				$multiple .= "TRIM(b$k.value) LIKE ".$this->_db->Quote(trim('% '.$this->_db->escape($value).' %'));
				$multiples[] = "(".$multiple.")";
			}
		}
		
		$el_where .= " AND (".implode(" ".$mode. " ", $multiples).")";
		
		return $el_where;
	}

	/**
	 * _getItemOrder - Returns ORDER query from an array of item order options
	 *
	 * @param array $order Array of order params
	 * Example:array(0 => '_itemcreated', 1 => '_reversed', 2 => '_random')
	 */
	protected function _getItemOrder($order)
	{
		// if string, try to convert ordering
		if (is_string($order)) {
			$order = $this->app->itemorder->convert($order);
		}

		$result = array(null, null);
		$order = (array) $order;

		// remove empty and duplicate values
		$order = array_unique(array_filter($order));

		// if random return immediately
		if (in_array('_random', $order)) {
			$result[1] = 'RAND()';
			return $result;
		}

		// get order dir
		if (($index = array_search('_reversed', $order)) !== false) {
			$reversed = 'DESC';
			unset($order[$index]);
		} else {
			$reversed = 'ASC';
		}

		// get ordering type
		$alphanumeric = false;
		if (($index = array_search('_alphanumeric', $order)) !== false) {
			$alphanumeric = true;
			unset($order[$index]);
		}

		// save item priority state
		$priority = false;
		if (($index = array_search('_priority', $order)) !== false) {
			$priority = true;
			unset($order[$index]);
		}

		// set default ordering attribute
		if (empty($order)) {
			$order[] = '_itemname';
		}

		// if there is a none core element present, ordering will only take place for those elements
		if (count($order) > 1) {
			$order = array_filter($order, create_function('$a', 'return strpos($a, "_item") === false;'));
		}

		// order by core attribute
		foreach ($order as $element) {
			if (strpos($element, '_item') === 0) {
				$var = str_replace('_item', '', $element);
				if ($alphanumeric) {
					$result[1] = $reversed == 'ASC' ? "a.$var+0<>0 DESC, a.$var+0, a.$var" : "a.$var+0<>0, a.$var+0 DESC, a.$var DESC";
				} else {
					$result[1] = $reversed == 'ASC' ? "a.$var" : "a.$var DESC";
				}
			}
		}

		// else order by elements
		if (!isset($result[1])) {
			$result[0] = ZOO_TABLE_SEARCH." AS s ON a.id = s.item_id AND s.element_id IN ('".implode("', '", $order)."')";
			if ($alphanumeric) {
				$result[1] = $reversed == 'ASC' ? "ISNULL(s.value), s.value+0<>0 DESC, s.value+0, s.value" : "s.value+0<>0, s.value+0 DESC, s.value DESC";
			} else {
				$result[1] = $reversed == 'ASC' ? "s.value" : "s.value DESC";
			}
		}

		// set priority at the end
		if ($priority) $result[1] = "a.priority DESC, " . $result[1];

		return $result;
	}

	/**
	 * One Join for each element filter
	 */
	protected function addRepeatableJoins(&$query, $k)
	{
		// 1 join for each parameter
		for ( $i = 0; $i < $k; $i++ ){
			$query->leftJoin(ZOO_TABLE_SEARCH . " AS b$i ON a.id = b$i.item_id");
		}
	}
	
	/**
	 * Get the value quoted and with %% if needed
	 */
	protected function getQuotedValue($name, $quote = true)
	{
		// init vars
		$type = $name->get('type', 'exact_phrase');

		// backward compatibility
		if($type == 'partial') $type = 'exact_phrase';
			
		switch($type) {
			case 'exact_phrase':
				$value = '%' . $name->get('value', '') . '%';
				break;

			case 'all_words':
				$value = '%' . str_replace(' ', '%', $name->get('value', '')) . '%';
				break;

			case 'any_word':
				// get all words and quote them
				$words = explode(' ', $name->get('value', ''));
				foreach ($words as &$word) {
					$word = $this->_db->Quote( "%$word%" );
				}

				// disable general quote
				$quote = false;

				// save all values
				$value = implode(' OR ', $words);
				break;

			default:
				$value = $name->get('value', '');
				break;
		}

		// quote the value
		if($quote) {
			return $this->_db->Quote( $value );
		}
		
		return $value;
	}
}