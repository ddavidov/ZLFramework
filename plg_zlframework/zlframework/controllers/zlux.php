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
	Class: ZlframeworkController
		The controller class for zoolanders extensions
*/
class ZluxController extends AppController {

	public function __construct($default = array())
	{
		parent::__construct($default);

		// set table
		$this->itemTable = $this->app->table->item;

		// get application
		$this->application = $this->app->zoo->getApplication();
	}

	/*
		Function: saveElement
			Save Element data without the need to save the entire Item
	*/
	public function saveElement()
	{
		// get request vars
		$el_identifier = $this->app->request->getString('elm_id', '');
		$item_id	   = $this->app->request->getInt('item_id', 0);
		$post		   = $this->app->request->get('post:', 'array', array());

		// load element
		if ($item_id) {
			$item = $this->itemTable->get($item_id);
		} elseif (!empty($type)){
			$item = $this->app->object->create('Item');
			$item->application_id = $this->application->id;
			$item->type = $type;
		} else {
			echo json_encode(array('result' => false));
			return;
		}

		if(isset($post['elements'][$el_identifier]) && $item->getElement($el_identifier))
		{
			$item = $this->itemTable->get($item_id);

			$item->elements->set($el_identifier, $post['elements'][$el_identifier]);
			$this->itemTable->save($item);

			echo json_encode(array('result' => true));
		}
	}

	/*
		Function: getItemsManagerData
			Get Item list JSON formatted

		Returns:
			JSON object
	*/
	public function getItemsManagerData()
	{
		$zlux2 = $this->app->request->get('zlux2', 'boolean', false);

		echo json_encode($zlux2 ? $this->_getItemsManagerDataZLUX2() : $this->_getItemsManagerData());
	}

	public function _getItemsManagerDataZLUX2()
	{
		$s_apps	 	= explode(',', $this->app->request->get('apps', 'string', ''));
		$s_types	= explode(',', $this->app->request->get('types', 'string', ''));
		$s_cats		= explode(',', $this->app->request->get('categories', 'string', ''));
		$s_tags		= explode(',', $this->app->request->get('tags', 'string', ''));
		$s_authors	= explode(',', $this->app->request->get('authors', 'string', ''));

		$g_apps	 	= explode(',', $this->app->request->get('filter_apps', 'string', ''));
		$g_types 	= explode(',', $this->app->request->get('filter_types', 'string', ''));
		$g_cats		= explode(',', $this->app->request->get('filter_cats', 'string', ''));
		$g_tags		= explode(',', $this->app->request->get('filter_tags', 'string', ''));
		$draw	 	= $this->app->request->get('draw', 'integer', false);


		// Array of database columns which should be read and sent back to DataTables
		$columns = array('_itemname');

		// filter values
		$s_apps = array_filter($s_apps);
		$s_types = array_filter($s_types);
		$s_cats = array_filter($s_cats);
		$s_tags = array_filter($s_tags);
		$s_authors = array_filter($s_authors);

		$g_apps = array_filter($g_apps);
		$g_types = array_filter($g_types);
		$g_cats = array_filter($g_cats);
		$g_tags = array_filter($g_tags);

		// convert App groups into IDs
		foreach ($g_apps as $key => $group) if (!is_numeric($group)) {
			// remove group
			unset($g_apps[$key]);

			// add Group Apps ID
			foreach ($this->app->application->getApplications($group) as $app) {
				$g_apps[] = $app->id;
			}
		}

		// remove duplicates
		$g_apps = array_unique($g_apps);
		$g_types = array_unique($g_types);
		$g_cats = array_unique($g_cats);
		$g_tags = array_unique($g_tags);


		// get model
		$model = $this->app->zlmodel->getNew('item');
		$model->setState('select', 'DISTINCT a.*');

		// get all Apps object
		$apps =  $this->app->table->application->all();

		// get filter listings
		$aaApps = $aaTypes = $aaCats = $aaTags = array();
		foreach($apps as $app) if (empty($s_apps) || in_array($app->id, $s_apps))
		{
			// add App to list
			$aaApps[] = array('name' => $app->name, 'id' => $app->id);

			// skip filtered apps
			if (!empty($g_apps) && !in_array($app->id, $g_apps)) {
				continue;
			}

			// apps filtering
			if (in_array($app->id, $g_apps)) {
				$model->application(array('value' => $app->id));
			}

			// get types list
			foreach ($app->getTypes() as $type) {

				// populate filter list
				if (empty($s_types) || in_array($type->id, $s_types))
					$aaTypes[] = array('name' => $type->name, 'id' => $type->id);

				// set the filtering
				if (in_array($type->id, $g_types))
					$model->type(array('value' => $type->id));
			}

			// get categories list
			$list = $this->app->tree->buildList(0, $app->getCategoryTree(), array(), '- ', '.   ', '  ');
			foreach ($list as $category) {

				// populate filter list
				if (empty($s_cats) || in_array($category->id, $s_cats))
					$aaCats[] = array('name' => $category->treename, 'id' => $category->id);

				// set the filtering
				if (in_array($category->id, $g_cats)) 
					$model->categories(array('value' => array($category->id)));
			}

			// get tag list
			$tags = $this->app->table->tag->getAll($app->id);
			foreach ($tags as $key => $tag) {

				// populate filter list
				if (empty($s_tags) || in_array($tag->name, $s_tags))
					$tags[$key] = array('name' => $tag->name, 'id' => $tag->name);

				// set the filtering
				if (in_array($tag->name, $g_tags)) {
					$model->tags(array('value' => array($tag->name)));
				}
			}
			$aaTags = array_merge($aaTags, $tags);

			// authors filtering
			if (isset($s_authors) && !empty($s_authors)) {
				$model->created_by(array('value' => implode(',', $s_authors)));
			}
		}

		// ordering
		$aOrder = array();
		if ( isset( $_POST['iSortCol_0'] ) )
		{
			for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
			{
				if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
				{
					$iColumnIndex = array_search( $_POST['mDataProp_'.$_POST['iSortCol_'.$i]], $columns );
					$aOrder[] = $columns[ $iColumnIndex ];
					$aOrder[] = $_POST['sSortDir_'.$i] == 'desc' ? '_reversed' : '';
				}
			}
		}

		$aOrder = array_values($aOrder);
		$model->setState('order_by', $aOrder);


		// paging
		if ( isset( $_POST['start'] ) && $_POST['length'] != '-1' )
		{
			$model->setState('limitstart', $_POST['start'], true);
			$model->setState('limit', $_POST['length'], true);
		}


		// global search
		if ( isset($_POST['search']) && $_POST['search']['value'] != '' ) {
			$str = $_POST['search']['value'];

			for ( $i=0, $ien=count($_POST['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $_POST['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $columns );
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['searchable'] == 'true' ) {

					/* === Core: Name element === */
					if( strlen( trim( $column == '_itemname' ) ) )
					{
						$name = array(
							'value' => trim( $str ),
							'logic' => 'AND'
						);
						
						$model->name($name);
					}
				}
			}
		}


		// get items
		$rows = array();
		$items = $model->getList();
		foreach ($items as &$item) {
			$row = array();
			$row['id'] = $item->id;
			$row['name'] = $item->name;
			$row['_itemname'] = $item->name; // necesary
			$row['application']['name'] = $item->getApplication()->name;
			$row['application']['id'] = $item->getApplication()->id;
			$row['type']['name'] = $item->getType()->name;
			$row['type']['id'] = $item->getType()->id;
			$row['access'] = JText::_($this->app->zoo->getGroup($item->access)->name);
			$row['created'] = $item->created;

			// author
			$author = $item->created_by_alias;
			$author_id = $item->created_by;
			$users  = $this->itemTable->getUsers($item->application_id);
			if (!$author && isset($users[$item->created_by])) {
				$author = $users[$item->created_by]->name;
				$author_id = $users[$item->created_by]->id;
			}
			$row['author']['name'] = $author;
			$row['author']['id'] = $author_id;

			// add row
			$rows[] = $row;
		};


		// debug sql
		// dump($model->getQuery()->__toString());


		// form and send JSON string
		$JSON = array(
			'draw' => $draw,
			'recordsTotal' => $model->getResult(),
			'recordsFiltered' => $model->getResult(),
			'data' => $rows,

			'apps' => $aaApps,
			'types' => $aaTypes,
			'categories' => $aaCats,
			'tags' => $aaTags,
			'success' => true
		);

		return $JSON;
	}

	public function _getItemsManagerData()
	{
		// init vars
		$s_apps	 	= explode(',', $this->app->request->get('apps', 'string', ''));
		$s_types	= explode(',', $this->app->request->get('types', 'string', ''));
		$s_cats		= explode(',', $this->app->request->get('categories', 'string', ''));
		$s_tags		= explode(',', $this->app->request->get('tags', 'string', ''));
		$s_authors	= explode(',', $this->app->request->get('authors', 'string', ''));

		$g_apps	 	= explode(',', $this->app->request->get('filter_apps', 'string', ''));
		$g_types 	= explode(',', $this->app->request->get('filter_types', 'string', ''));
		$g_cats		= explode(',', $this->app->request->get('filter_cats', 'string', ''));
		$g_tags		= explode(',', $this->app->request->get('filter_tags', 'string', ''));
		$sEcho	 	= $this->app->request->get('sEcho', 'string', '');

		/* Array of database columns which should be read and sent back to DataTables. Use a space where
		   you want to insert a non-database field (for example a counter or static image) */
		$aColumns = array('_itemname', 'application', 'type', 'access', 'author', 'id');

		/* Using _itemname for core filtering purposes */

		// filter values
		$s_apps = array_filter($s_apps);
		$s_types = array_filter($s_types);
		$s_cats = array_filter($s_cats);
		$s_tags = array_filter($s_tags);
		$s_authors = array_filter($s_authors);

		$g_apps = array_filter($g_apps);
		$g_types = array_filter($g_types);
		$g_cats = array_filter($g_cats);
		$g_tags = array_filter($g_tags);

		// convert App groups into IDs
		foreach ($g_apps as $key => $group) if (!is_numeric($group)) {
			// remove group
			unset($g_apps[$key]);

			// add Group Apps ID
			foreach ($this->app->application->getApplications($group) as $app) {
				$g_apps[] = $app->id;
			}
		}

		// remove duplicates
		$g_apps = array_unique($g_apps);
		$g_types = array_unique($g_types);
		$g_cats = array_unique($g_cats);
		$g_tags = array_unique($g_tags);


		// get model
		$model = $this->app->zlmodel->getNew('item');
		$model->setState('select', 'DISTINCT a.*');

		// get all Apps object
		$apps =  $this->app->table->application->all();

		// get filter listings
		$aaApps = $aaTypes = $aaCats = $aaTags = array();
		foreach($apps as $app) if (empty($s_apps) || in_array($app->id, $s_apps))
		{
			// add App to list
			$aaApps[] = array('name' => $app->name, 'id' => $app->id);

			// skip filtered apps
			if (!empty($g_apps) && !in_array($app->id, $g_apps)) {
				continue;
			}

			// apps filtering
			if (in_array($app->id, $g_apps)) {
				$model->application(array('value' => $app->id));
			}

			// get types list
			foreach ($app->getTypes() as $type) {

				// populate filter list
				if (empty($s_types) || in_array($type->id, $s_types))
					$aaTypes[] = array('name' => $type->name, 'id' => $type->id);

				// set the filtering
				if (in_array($type->id, $g_types))
					$model->type(array('value' => $type->id));
			}

			// get categories list
			$list = $this->app->tree->buildList(0, $app->getCategoryTree(), array(), '- ', '.   ', '  ');
			foreach ($list as $category) {

				// populate filter list
				if (empty($s_cats) || in_array($category->id, $s_cats))
					$aaCats[] = array('name' => $category->treename, 'id' => $category->id);

				// set the filtering
				if (in_array($category->id, $g_cats)) 
					$model->categories(array('value' => array($category->id)));
			}

			// get tag list
			$tags = $this->app->table->tag->getAll($app->id);
			foreach ($tags as $key => $tag) {

				// populate filter list
				if (empty($s_tags) || in_array($tag->name, $s_tags))
					$tags[$key] = array('name' => $tag->name, 'id' => $tag->name);

				// set the filtering
				if (in_array($tag->name, $g_tags)) {
					$model->tags(array('value' => array($tag->name)));
				}
			}
			$aaTags = array_merge($aaTags, $tags);

			// authors filtering
			if (isset($s_authors) && !empty($s_authors)) {
				$model->created_by(array('value' => implode(',', $s_authors)));
			}
		}

		// ordering
		$aOrder = array();
		if ( isset( $_POST['iSortCol_0'] ) )
		{
			for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
			{
				if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
				{
					$iColumnIndex = array_search( $_POST['mDataProp_'.$_POST['iSortCol_'.$i]], $aColumns );
					$aOrder[] = $aColumns[ $iColumnIndex ];
					$aOrder[] = $_POST['sSortDir_'.$i] == 'desc' ? '_reversed' : '';
				}
			}
		}

		$aOrder = array_values($aOrder);
		$model->setState('order_by', $aOrder);

		// paging
		if ( isset( $_POST['iDisplayStart'] ) && $_POST['iDisplayLength'] != '-1' )
		{

			$model->setState('limitstart', $_POST['iDisplayStart'], true);
			$model->setState('limit', $_POST['iDisplayLength'], true);
		}

		// Input search filtering
		if ( isset($_POST['sSearch']) && $_POST['sSearch'] != "" )
		{
			for ( $i=0 ; $i<count($aColumns) ; $i++ )
			{
				if ( isset($_POST['bSearchable_'.$i]) && $_POST['bSearchable_'.$i] == "true" )
				{
					/* === Core: Name element === */
					if( strlen( trim( $aColumns[$i] == '_itemname' ) ) )
					{
						$name = array(
							'value' => trim( $_POST['sSearch'] ),
							'logic' => 'AND'
						);
						
						$model->name($name);
					}
				}
			}
		}

		// pretty print of sql
		if (false) {
			$find = Array("FROM", "WHERE", "AND", "ORDER BY", "LIMIT", "OR");
			$replace = Array(" FROM", " WHERE", " AND", " ORDER BY", " LIMIT", " OR");
			$in = $model->getQuery();
			// dump(str_replace($find, $replace, $in));
		}

		// get items
		$rows = array();
		$items = $model->getList();
		foreach ($items as &$item) {
			$row = array();
			$row['id'] = $item->id;
			$row['name'] = $item->name;
			$row['_itemname'] = $item->name; // necesary
			$row['application']['name'] = $item->getApplication()->name;
			$row['application']['id'] = $item->getApplication()->id;
			$row['type']['name'] = $item->getType()->name;
			$row['type']['id'] = $item->getType()->id;
			$row['access'] = JText::_($this->app->zoo->getGroup($item->access)->name);
			$row['created'] = $item->created;

			// author
			$author = $item->created_by_alias;
			$author_id = $item->created_by;
			$users  = $this->itemTable->getUsers($item->application_id);
			if (!$author && isset($users[$item->created_by])) {
				$author = $users[$item->created_by]->name;
				$author_id = $users[$item->created_by]->id;
			}
			$row['author']['name'] = $author;
			$row['author']['id'] = $author_id;

			// add row
			$rows[] = $row;
		};


		// form and send JSON string
		$JSON = array(
			'sEcho' => $sEcho,
			'iTotalRecords' => $model->getResult(),
			'iTotalDisplayRecords' => $model->getResult(),
			'sColumns' => implode(', ', $aColumns),
			'aaApps' => $aaApps,
			'aaTypes' => $aaTypes,
			'aaCategories' => $aaCats,
			'aaTags' => $aaTags,
			'aaData' => $rows // items
		);

		return $JSON;
	}

	/*
		Function: FilesManager
			Get directory/file list JSON formatted

		Returns:
			JSON object
	*/
	public function getFilesManagerData()
	{
		// init vars
		$root = trim($this->app->request->get('root', 'string'), '/');
		$legalExt = str_replace(array(' ', ','), array('', '|'), $this->app->request->get('extensions', 'string'));
		$storage = $this->app->request->get('storage', 'string');

		// init storage
		switch($storage) {
			case 's3':
				$bucket 	= $this->app->request->get('bucket', 'string');
				$accesskey 	= urldecode($this->app->request->get('accesskey', 'string'));
				$secretkey 	= $this->app->zlfw->crypt(urldecode($this->app->request->get('key', 'string')), 'decrypt');
				$storage = new ZLStorage('AmazonS3', array('secretkey' => $secretkey, 'accesskey' => $accesskey, 'bucket' => $bucket));
				
				// get root
				$root = $this->app->zlfw->path->getDirectory($root, true);
				break;

			default:
				$storage = new ZLStorage('Local');

				// get root, if false use joomla default
				if (!$root = $this->app->zlfw->path->getDirectory($root))
					$root = JComponentHelper::getParams('com_media')->get('file_path');
				break;
		}

		// retrieve tree
		$result = $storage->getTree($root, $legalExt);

		// get any error / warning
		$errors = array_merge($storage->getErrors(), $storage->getWarnings());

		/* Array of database columns which should be read and sent back to DataTables. Use a space where
		   you want to insert a non-database field (for example a counter or static image) */
		$aColumns = array('type', 'name', 'size', 'path');

		// JSON
		$result = array(
			'iTotalRecords' => 40,
			'iTotalDisplayRecords' => 40,
			'sColumns' => implode(', ', $aColumns),
			'root' => $result['root'],
			'aaData' => $result['rows'],
			'errors' => $errors
		);

		echo json_encode(compact('result', 'errors'));
	}

	/*
		Function: deleteObject
			Delete the Object
			
		Request parameters:
			$path: the relative path to the object
			$storage: the storage related information
	*/
	public function deleteObject()
	{
		// init vars
		$path = $this->app->request->get('path', 'string', '');
		$storage = $this->app->request->get('storage', 'string');
		$result = false;

		// init storage
		switch($storage) {
			case 's3':
				$bucket 	= $this->app->request->get('bucket', 'string');
				$accesskey 	= urldecode($this->app->request->get('accesskey', 'string'));
				$secretkey 	= $this->app->zlfw->crypt(urldecode($this->app->request->get('key', 'string')), 'decrypt');
				$storage = new ZLStorage('AmazonS3', array('secretkey' => $secretkey, 'accesskey' => $accesskey, 'bucket' => $bucket));
				break;

			default:
				$storage = new ZLStorage('Local');
				break;
		}

		// proceed
		$result = $storage->delete($path);

		// get any error / warning
		$errors = array_merge($storage->getErrors(), $storage->getWarnings());

		echo json_encode(compact('result', 'errors'));
	}

	/*
		Function: moveObject
			Move the Object
			
		Request parameters:
			$src: the relative path to the source object
			$dest: the relative path to the destination object
			$storage: the storage related information
	*/
	public function moveObject()
	{
		// init vars
		$src = $this->app->request->get('src', 'string', '');
		$dest = $this->app->request->get('dest', 'string', '');
		$storage = $this->app->request->get('storage', 'string');
		$result = false;

		// clean the destination path
		$dest = dirname($dest) . '/' . $this->app->zlfw->filesystem->makeSafe(basename($dest), 'ascii');

		// workaround as the path does not exist yet
		$dest = dirname($src) . '/' . basename($dest);

		// init storage
		switch($storage) {
			case 's3':
				$bucket 	= $this->app->request->get('bucket', 'string');
				$accesskey 	= urldecode($this->app->request->get('accesskey', 'string'));
				$secretkey 	= $this->app->zlfw->crypt(urldecode($this->app->request->get('key', 'string')), 'decrypt');

				// workaround when object is on root
				$dest = preg_replace('/^(\.\/)/', '', $dest);

				// workaround to get back the slash if the object is folder
				if (preg_match('/\/$/', $src)) $dest = $dest . '/';

				// construct storage
				$storage = new ZLStorage('AmazonS3', array('secretkey' => $secretkey, 'accesskey' => $accesskey, 'bucket' => $bucket));
				break;

			default:
				$storage = new ZLStorage('Local');
				break;
		}

		// proceed
		$result = $storage->move($src, $dest);

		// get any error / warning
		$errors = array_merge($storage->getErrors(), $storage->getWarnings());

		// get final name
		$name = basename($dest);

		echo json_encode(compact('result', 'errors', 'name'));
	}
	
	/*
		Function: newfolder
			Create new Folder
			
		Parameters:
			$path: parent folder path
	*/
	public function newFolder()
	{
		// init vars
		$path = $this->app->request->get('path', 'string', '');
		$storage = $this->app->request->get('storage', 'string');
		$result = false;

		// clean the destination path
		$path = dirname($path) . '/' . $this->app->zlfw->filesystem->makeSafe(basename($path), 'ascii');

		// init storage
		switch($storage) {
			case 's3':
				$bucket 	= $this->app->request->get('bucket', 'string');
				$accesskey 	= urldecode($this->app->request->get('accesskey', 'string'));
				$secretkey 	= $this->app->zlfw->crypt(urldecode($this->app->request->get('key', 'string')), 'decrypt');

				// workaround when object is on root
				$path = preg_replace('/^(\.\/)/', '', $path);

				// workaround to get back the slash
				$path = $path . '/';

				// construct storage
				$storage = new ZLStorage('AmazonS3', array('secretkey' => $secretkey, 'accesskey' => $accesskey, 'bucket' => $bucket));
				break;

			default:
				$storage = new ZLStorage('Local');
				break;
		}

		// proceed
		$result = $storage->createFolder($path);

		// get any error / warning
		$errors = array_merge($storage->getErrors(), $storage->getWarnings());

		// get final name
		$name = basename($path);

		echo json_encode(compact('result', 'errors', 'name'));
	}

	/*
		Function: validateObjectName
			Validate the name for uploading
			
		Parameters:
			$name: file name
	*/
	public function validateObjectName()
	{
		// init vars
		$name = $this->app->request->get('name', 'string', '');

		// convert to ASCII		
		$result = $this->app->zlfw->filesystem->makeSafe($name, 'ascii');

		// lowercase the extension
		$result = JFile::stripExt($result) . '.' . strtolower( JFile::getExt($result) );

		// return result
		echo json_encode(compact('result'));
	}

	/**
	 * Plupload Upload Function
	 *
	 * Original Credits:
	 * Copyright 2009, Moxiecode Systems AB
	 * Released under GPL License.
	 * License: http://www.plupload.com/license
	 * 
	 * Adapted to ZOO by ZOOlanders.com
	 * Copyright (C) JOOlanders, SL
	 */
	public function upload()
	{
		// load storage engine
		$storage = new ZLStorage('Local');

		// Make sure file is not cached (as it happens for example on iOS devices)
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// 5 minutes execution time
		@set_time_limit(5 * 60);

		// Uncomment this one to fake upload time
		// usleep(5000);

		// Settings
		$targetDir = JPATH_ROOT . '/tmp/zlux/uploads'; // temporal upload folder
		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// get filename and make it websafe
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : uniqid("file_");
		$fileName = $this->app->zlfw->filesystem->makeSafe(JRequest::getVar("name", ''), 'ascii');

		// Create target dir
		if (!$storage->exists($this->app->path->relative($targetDir))) {
			$this->app->zlfw->filesystem->folderCreate($targetDir);
		}

		// get file path
		$filePath = $targetDir . '/' . $fileName;
		$filePath_rel = $this->app->zlfw->path->relative($filePath);

		// get chunking
		$chunking = isset($_REQUEST["offset"]) && isset($_REQUEST["total"]);

		// Remove old temp files	
		if ($cleanupTargetDir) {
			if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
			}

			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . '/' . $file;

				// If temp file is current file proceed to the next
				if ($tmpfilePath == "{$filePath}.part") {
					continue;
				}

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
					@unlink($tmpfilePath);
				}
			}
			closedir($dir);
		}

		// Open temp file
		if (!$out = @fopen("{$filePath}.part", $chunking ? "cb" : "wb")) {
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}

		if (!empty($_FILES)) {
			if ($_FILES['file']['error'] || !is_uploaded_file($_FILES['file']['tmp_name'])) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			}

			// Read binary input stream and append it to temp file
			if (!$in = @fopen($_FILES['file']['tmp_name'], "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			}
		} else {	
			if (!$in = @fopen("php://input", "rb")) {
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			}
		}

		if ($chunking) {
			fseek($out, $_REQUEST["offset"]); // write at a specific offset
		}

		while ($buff = fread($in, 4096)) {
			fwrite($out, $buff);
		}

		@fclose($out);
		@fclose($in);

		// set initial state
		$result = false;

		// Check if file has been uploaded
		if (!$chunking || filesize("{$filePath}.part") >= $_REQUEST["total"]) {
			// Strip the temp .part suffix off 
			$result = $storage->move("{$filePath_rel}.part", $filePath_rel);
		}

		// get any error / warning
		$errors = array_merge($storage->getErrors(), $storage->getWarnings());

		// if fails
		if (!$result) die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "' . implode('; ', $errors) . '"}, "id" : "id"}');

		// get paths for the final destination
		$path = $this->app->request->get('path', 'string', '');
		$dest = $path . '/' . basename($filePath);

		// move to the final destination
		$result = $storage->upload($filePath_rel, $dest);

		// get any error / warning
		$errors = array_merge($storage->getErrors(), $storage->getWarnings());

		// if fails
		if (!$result) die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "' . implode('; ', $errors) . '"}, "id" : "id"}');

		// Return Success JSON-RPC response
		die(json_encode(array('jsonrpc' => '2.0', 'result' => basename($result), 'id' => 'id')));
	}
}

/*
	Class: ZoolandersControllerException
*/
class ZluxControllerException extends AppException {}