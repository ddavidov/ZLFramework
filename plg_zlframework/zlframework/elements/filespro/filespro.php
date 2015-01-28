<?php
/**
* @package		ZL Framework
* @author    	JOOlanders, SL http://www.zoolanders.com
* @copyright 	Copyright (C) JOOlanders, SL
* @license   	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// register ElementRepeatablePro class
App::getInstance('zoo')->loader->register('ElementRepeatablePro', 'elements:repeatablepro/repeatablepro.php');

// load libraries
jimport('joomla.filesystem.file');

/*
	Class: ElementFilesPro
		The files pro element class
*/
abstract class ElementFilesPro extends ElementRepeatablePro {

	protected $_extensions = '';
	protected $_s3;
	protected $_jfile_path;
	protected $_storage;
	protected $_resources;
	protected $_uniqid;
	protected $_directory;

	/*
	   Function: Constructor
	*/
	public function __construct() {

		// call parent constructor
		parent::__construct();

		// set defaults
		$params = JComponentHelper::getParams('com_media');
		$this->config->set('files', array('_source_dir' => $params->get('file_path'), '_extensions' => $this->_extensions, '_max_upload_size' => '1024'));
		
		// set joomla file path
		$this->_joomla_file_path = $params->get('file_path') ? $params->get('file_path') : 'images';

		// set the events
		$this->app->event->dispatcher->connect('item:saved', array($this, 'itemSaved'));
	}

	/*
		Function: itemSaved
			Performs actions after item was saved
	*/
	public function itemSaved($event)
	{
		$item = $event->getSubject();
		$elem = $item->getElement($this->identifier);

		// if the Item is new
		if ($event['new'] && $elem) {

			/* when using dynamic var such as [zooitemid] we need to store the files in a temporal
			   folder as we ignore that var until the item is saved. Then we can move them */
			$old_path = 'tmp/zl_' . $this->identifier . '_' . $item->elements->find("{$this->identifier}.0.uniqid");
			$new_path = $elem->getDirectory(false, $item);

			// move uploaded files to new path, if succesfull...
			if ($elem->storage()->move($old_path, $new_path)) {
				// update item data
				$data = $elem->data();
				foreach ($data as &$instance) {
					$instance['file'] = str_replace($old_path, $new_path, $instance['file']);
				}
				$item->elements->set($this->identifier, $data);
				$this->app->table->item->save($item);
			}
		}
	}

	/*
		Function: getUniqueid
			Returns an unique id used for temporal storage folder

		Returns:
			String - The ID
	*/
	public function getUniqid()
	{
		if (!$this->_uniqid && $this->_item->id == 0) {
			$this->_uniqid = $this->get('uniqid') ? $this->get('uniqid') : uniqid();
		}
		
		return $this->_uniqid;
	}

	/*
		Function: storage
			Init the Storage

		Returns:
			Class - Storage php class
	*/
	public function storage()
	{
		// init storage
		if ($this->_storage == null)
		{
			// if source is an URI
			if (strpos($this->get('file'), 'http') === 0) 
			{
				$this->_storage = new ZLStorage('URI');
			}

			// s3
			else if ($this->config->find('files._s3', 0))
			{
				$bucket	   = trim($this->config->find('files._s3bucket'));
				$accesskey = trim($this->app->zlfw->decryptPassword($this->config->find('files._awsaccesskey')));
				$secretkey = trim($this->app->zlfw->decryptPassword($this->config->find('files._awssecretkey')));

				$this->_storage = new ZLStorage('AmazonS3', array('secretkey' => $secretkey, 'accesskey' => $accesskey, 'bucket' => $bucket));
			} 

			// local
			else
			{
				$this->_storage = new ZLStorage('Local');
			}
		}
		return $this->_storage;
	}

	/*
		Function: get - IMPORTANT TO KEEP DATA COMPATIBILITY WITH ZOO NO REPEATABLE ELEMENTS
			Gets the elements data.

		Returns:
			Mixed - the elements data
	*/
	public function get($key, $default = null) {
		// workaround for the repeatable element transition
		if ($value = $this->_item->elements->find("{$this->identifier}.{$key}", null)) {
			// IMPORTANT, ignore the default value
			return $value;
		} else {
			return parent::get($key, $default);
		}
	}
	
	/*
		DEPICATED since 3.0.15, still needed for old FilesPro elements
	*/
	public function _S3()
	{
		if ($this->_s3 == null)
		{
			$bucket	   = $this->config->find('files._s3bucket');
			$accesskey = trim($this->app->zlfw->decryptPassword($this->config->find('files._awsaccesskey')));
			$secretkey = trim($this->app->zlfw->decryptPassword($this->config->find('files._awssecretkey')));			

			// register s3 class
			$this->app->loader->register('AEUtilAmazons3', 'classes:amazons3.php');

			// init S3 Utility
			$this->_s3 = AEUtilAmazons3::getInstance($accesskey, $secretkey, false);
		}
		return $this->_s3;
	}

	/*
		Function: isDownloadLimitReached
			Checks if the file has reached it's download limit

		Returns:
			Boolean
	*/
	function isDownloadLimitReached() {
		return ($limit = $this->get('download_limit')) && $this->get('hits', 0) >= $limit;
	}

	/* DEPRICATED since 3.0.15 */
	public function getExtension($file = null, $checkMime = true) {
		$file = empty($file) ? $this->get('file') : $file;
		return strtolower($this->app->zlfw->filesystem->getExtension($file, $checkMime));
	}

/* RENDER --------------------------------------------------------------------------------------------------------------------------- */

	/*
		Function: _hasValue
			Checks if the repeatables element's file is set

	   Parameters:
			$params - render parameter

		Returns:
			Boolean - true, on success
	*/
	protected function _hasValue($params = array())
	{
		$files = $this->getValidResources($this->get('file'));
		return !empty($files);
	}

	/*
		Function: getRenderedValues
			render repeatable values

		Returns:
			array
	*/
	public function getRenderedValues($params=array(), $wk=false, $opts=array())
	{
		$opts['data_is_subarray'] = true;
		return parent::getRenderedValues($params, $wk, $opts);
	}

	/* DEPRICATED */
	public function getFiles($path = null)
	{
		return $this->_resources = $this->getValidResources($path);
	}

	/*
		Function: getValidResources
			Retrieve all valid resources from a path

		Returns:
			Array or resources
	*/
	protected $_valid_resources = array();
	public function getValidResources($path = null)
	{
		// get final path
		$path = $path ? $path : $this->getDefaultSource();

		// use resouce cache if exist
		if (!array_key_exists($path, $this->_valid_resources))
		{
			// get the valid resources from the path
			$this->_valid_resources[$path] = $this->storage()->getValidResources($path, $this->getLegalExtensions());
		}
		
		return $this->_valid_resources[$path];
	}

	/*
		Function: getDefaultSource
			Retrieve default source if empty value

		Returns:
			String - Path to file(s)
	*/
	protected function getDefaultSource()
	{
		// get default, fallback to default_file as the param name was changed
		$default_source = $this->config->find('files._default_source', $this->config->find('files._default_file', ''));

		// get item author user object
		$user = $this->app->user->get($this->getItem()->created_by);

		// Replace any path variables
		$pattern = array(
			'/\[authorname\]/'
		);
		$replace = array(
			$user = $user ? $user : ''
		);
		
		return preg_replace($pattern, $replace, $default_source); 
	}

	/*
		Function: loadAssets
			Load elements css/js assets.

		Returns:
			Void
	*/
	public function loadAssets()
	{
		parent::loadAssets();

		// load ZLUX assets
		$this->app->zlfw->zlux->loadMainAssets();

		// load the FilesPro js
		$this->app->document->addScript('elements:filespro/filespro.js');
	}

/* FILE MANAGER ----------------------------------------------------------------------------------------------------------------------  */

	/*
		Function: getFileDetails
			Return file info
			
		Parameters:
			$file - source file
			$json - boolean, format
			
		Returns:
			JSON or Array
	*/
	public function getFileDetails($file = null, $json = true)
	{
		// get the object path
		$path = $file === null ? $this->get('file') : $file;

		// get the object info
		if($data = $this->storage()->getObjectInfo($path)) {

			// return in json or param object
			return $json ? json_encode($data) : $this->app->data->create($data);
		}

		return false;
	}
	
	/*
		Function: getFileDetailsDom
			Return file details dom
			
		Parameters:
			$file - source file
			
		Returns:
			HTML
	*/
	public function getFileDetailsDom($file=null)
	{
		$file = $file === null ? $this->get('file') : $file;

		// set storage params
		$storage = array();
		if ($this->config->find('files._s3', 0))
		{
			$bucket = trim($this->config->find('files._s3bucket'));

			// decrypt the ZLField password 
			$accesskey = $this->app->zlfw->decryptPassword(trim($this->config->find('files._awsaccesskey')));
			$secretkey = $this->app->zlfw->decryptPassword(trim($this->config->find('files._awssecretkey')));

			// get signed policy
			$policy = $this->app->zlfw->zlux->getAmazonS3signedPolicy($bucket, $secretkey);

			// encrypt back for JS
			$secretkey = $this->app->zlfw->crypt($secretkey, 'encrypt');

			$storage['engine'] = 's3';
			$storage['bucket'] = $bucket;
			$storage['accesskey'] = urlencode($accesskey);
			$storage['secretkey'] = urlencode($secretkey);
			$storage['policy']	  = $policy['policy'];
			$storage['signature'] = $policy['signature'];

		} else {
			$storage['engine'] = 'local';
		}

		// set the storage root
		$storage['root'] = $this->getDirectory();

		// file details
		$fdetails = '';
		if($file_obj = $this->getFileDetails($file, false)) {
			$fdetails = $file ? " data-zlux-data='" . json_encode($file_obj) . "'" : '';
		}

		$html = "<span class=\"zlux-x-filedata\"" . $fdetails . " data-zlux-storage='" . json_encode($storage) . "'></span>";

		if ($uniqid = $this->getUniqid()) {
			$html .= '<input type="hidden" value="' . $uniqid . '" name="' . $this->getControlName('uniqid') . '">';
		}

		return $html;
	}
	
	/*
	   Function: getLegalExtensions
		   Get the legal extensions string

	   Returns:
		   String - element legal extensions
	*/
	public function getLegalExtensions($separator = '|') {
		$extensions = $this->config->find('files._extensions', $this->_extensions);
		return str_replace('|', $separator, $extensions);
	}

	/*
	 * Return the full directory path
	 */
	public function getDirectory($allowroot = false, $item = false)
	{
		if (!$this->_directory)
		{
			$user = JFactory::getUser();
			$item = $item ? $item : $this->getItem();

			// Get base directory as shared parameter
			$root = $this->config->find('files._source_dir', $this->_joomla_file_path);

			// if item is new and the path is using dynamic yet unknown vars, return temporal path
			$pattern = '/\[zooprimarycat\]|\[zooprimarycatid\]|\[zooitemid\]|\[zooitemalias\]/';
			if (!$item->id && preg_match($pattern, $root)) {
				return 'tmp/zl_' . $this->identifier . '_' . $this->getUniqid();
			}

			// Restricted Joomla! folders
			$restricted = explode(',', 'administrator,cache,components,includes,language,libraries,logs,media,modules,plugins,templates,xmlrpc');
			
			// Remove whitespace
			$root = trim($root);
			// Convert slashes / Strip double slashes
			$root = preg_replace('/[\\\\]+/', '/', $root);
			// Remove first leading slash
			$root = ltrim($root, '/');
			
			// Split in parts to better manage
			$parts = explode('/', $root);

			// abort if path starts with a variable, a . or is empty
			if (preg_match('/[\.\[]/', $parts[0]) || (empty($root) && !$allowroot)) {
				return false;
			}

			// abort if path not allowed
			if (!$allowroot && in_array(strtolower($parts[0]), $restricted)) {
				return false;
			}
			
			// join back
			$root = implode('/', $parts);
			

			// set path variables
			jimport('joomla.user.helper');

			// user
			$groups = JUserHelper::getUserGroups($user->id);
			$groups = array_keys($groups);
			// get the first group
			$usergroupid = array_shift($groups);
			// usergroup table				
			$group = JTable::getInstance('Usergroup', 'JTable');
			$group->load($usergroupid);
			// usertype	
			$usergroup = $group->title;
			
			// author
			$author = $this->app->user->get($item->created_by);
			$author = $author ? $author : $user; // if no author set use current user
			$groups = JUserHelper::getUserGroups($author->id);
			$groups = array_keys($groups);
			// get the first group
			$authorgroupid = array_shift($groups);
			// usergroup table				
			$group = JTable::getInstance('Usergroup', 'JTable');
			$group->load($authorgroupid);
			// usertype	
			$authorgroup = $group->title;

			// zoo
			$zooapp 	= strtolower($item->getApplication()->name);
			$zooprimarycat = $item->getPrimaryCategory() ? $item->getPrimaryCategory()->alias : 'none';
			$zooprimarycatid = $item->getPrimaryCategoryId();


			// Replace variables
			$pattern = array(
				'/\[userid\]/', '/\[username\]/', '/\[usergroup\]/', '/\[usergroupid\]/',
				'/\[authorid\]/', '/\[authorname\]/', '/\[authorgroup\]/', '/\[authorgroupid\]/',
				'/\[zooapp\]/', '/\[zooprimarycat\]/', '/\[zooprimarycatid\]/',
				'/\[zooitemtype\]/', '/\[zooitemid\]/', '/\[zooitemalias\]/',
				'/\[day\]/', '/\[month\]/', '/\[year\]/'
			);
			$replace = array(
				$user->id, $user->username, $usergroup, $usergroupid,
				$author->id, $author->username, $authorgroup, $authorgroupid,
				$zooapp, $zooprimarycat, $zooprimarycatid,
				$item->type, $item->id, $item->alias,
				date('d'), date('m'), date('Y')
			);
			$root = preg_replace($pattern, $replace, $root);

			// split into path parts to preserve /
			$parts = explode('/', $root);
			// clean path parts
			$parts = $this->app->zlfw->filesystem->makeSafe($parts, 'ascii');
			// join path parts
			$root = implode('/', $parts);
			
			// return the result
			$this->_directory = $root;
		}

		return $this->_directory;
	}
	
/* SUBMISSIONS ------------------------------------------------------------------------------------------------------------------------  */
	
	/*
		Function: _renderSubmission
			Renders the element in submission.

		Parameters:
			$params - AppData submission parameters

		Returns:
			String - html
	*/
	public function _renderSubmission($params = array())
	{
		// init vars
		$trusted_mode = $params->get('trusted_mode');
		$layout		  = $params->find('layout._layout', 'default.php');

		if ($layout == 'advanced') {
			if ($trusted_mode)
				return $this->_edit();
			else
				$layout = 'default.php';
		} 
		
		if ($layout = $this->getLayout("submission/$layout"))
		{
			return $this->renderLayout($layout, compact('params', 'trusted_mode'));
		}
	}
	
	/*
		Function: validateSubmission
			Validates the submitted element

		Parameters:
			$value  - AppData value
			$params - AppData submission parameters

		Returns:
			Array - cleaned value
	*/
	public function validateSubmission($value, $params)
	{
		// add parameter uniqid for default layout
		if (empty($value[0]['uniqid'])) {
			$value[0]['uniqid'] = $this->getUniqid();
		}

		// get old file values, the allready stored ones
		$old_files = array();
		foreach($this as $self) {
			$old_files[] = $this->get('file');
		}
		$old_files = array_filter($old_files);

		// Reorganize the files to make them easier to manage (tricky)
		$userfiles = array();
		foreach($value->get('userfile', array()) as $key => $vals) {
			$vals = array_filter($vals);
			foreach($vals as $i => $val){
				$userfiles[$i][$key] = $val;
			}
		}

		// remove the old user info
		if(isset($value['userfile']))
			unset($value['userfile']);

		// reindex values, important
		$value = array_values((array)$value);

		$result = array();
		foreach($value as $key => &$single_value)
		{
			if (isset($userfiles[$key]))
			{	
				$single_value = array('old_file' => (isset($old_files) ? $old_files : ''), 'userfile' => $userfiles[$key], 'values' => $single_value);
			} else {
				$single_value = array('values' => $single_value);
			}

			// validate
			try {
				$result[] = $this->_validateSubmission($this->app->data->create($single_value), $params);

			} catch (AppValidatorException $e) {

				if ($e->getCode() != AppValidator::ERROR_CODE_REQUIRED) {
					throw $e;
				}
			}
		}
		
		if ($params->get('required') && !count($result)) {
			if (isset($e)) {
				throw $e;
			}
			throw new AppValidatorException('This field is required');
		}
		
		// connect to submission beforesave event
		$this->params = $params;
		$this->app->event->dispatcher->connect('submission:beforesave', array($this, 'submissionBeforeSave'));

		// validate the uniqid
		if (isset($value[0]['values']['uniqid'])) $result[0]['uniqid'] = $value[0]['values']['uniqid'];

		return $result;
	}
	
	/*
		Function: submissionBeforeSave
			Callback before item submission is saved

		Returns:
			void
	*/
	public function submissionBeforeSave($event)
	{
		$userfiles = array();
		// merge userfiles element data with post data
		foreach ($_FILES as $key => $userfile) {
			if (strpos($key, 'elements_'.$this->identifier) === 0) {
				// Reorganize the files to make them easier to manage (tricky)
				foreach($userfile as $key => $values) foreach($values as $i => $value){
					$userfiles[$i][$key] = $value;
				}
			}
		}
		
		$files = array();
		// now for the real upload
		foreach($userfiles as $userfile)
		{
			// get the uploaded file information
			if ($userfile && $userfile['error'] == 0 && is_array($userfile)) {

				// get filename and make it websafe
				$fileName = $this->app->zlfw->filesystem->makeSafe($userfile['name'], 'ascii');

				// init vars
				$ext 		= strtolower(JFile::getExt($fileName));
				$basename 	= substr($fileName, 0, strrpos($fileName, '.'));
				$targetDir 	= JPATH_ROOT.'/'.$this->getDirectory();

				// construct filename
				$fileName = "{$basename}.{$ext}";

				// Make sure the fileName is unique
				if (JFile::exists("$targetDir/$fileName")) {
					$count = 1;
					while (JFile::exists("{$targetDir}/{$basename}_{$count}.{$ext}"))
						$count++;
				
					$fileName = "{$basename}_{$count}.{$ext}";
				}

				// Create target dir
				if (!JFolder::exists($targetDir))
					JFolder::create($targetDir);
				
				// upload the file
				if (!JFile::upload($userfile['tmp_name'], "$targetDir/$fileName")) {
					throw new AppException('Unable to upload file.');
				}

				$files[] = $userfile;
			}
		}
	}
}

// register AppValidatorFile class
App::getInstance('zoo')->loader->register('AppValidatorFile', 'classes:validator.php');

/**
 * Filespro validator
 *
 * @package Component.Classes.Validators
 */
class AppValidatorFilepro extends AppValidatorFile {

  /**
	 * Clean the file value
	 *
	 * @param  mixed $value The value to clean
	 *
	 * @return mixed        The cleaned value
	 *
	 * @see AppValidator::clean()
	 *
	 * @since 2.0
	 */
	public function clean($value) {
		if (!is_array($value) || !isset($value['tmp_name'])) {
			throw new AppValidatorException($this->getMessage('invalid'));
		}

		if (!isset($value['name'])) {
			$value['name'] = '';
		}

		// init vars
		$ext 		= strtolower(JFile::getExt($value['name']));
		$basename 	= substr($value['name'], 0, strrpos($value['name'], '.'));

		// construct filename
		$value['name'] = "{$basename}.{$ext}";

		// split into parts
		$parts = explode('/', $value['name']);

		// clean path parts
		$parts = $this->app->zlfw->filesystem->makeSafe($parts, 'ascii');

		// join path parts
		$value['name'] = implode('/', $parts);

		if (!isset($value['error'])) {
			$value['error'] = UPLOAD_ERR_OK;
		}

		if (!isset($value['size'])) {
			$value['size'] = filesize($value['tmp_name']);
		}

		if (!isset($value['type'])) {
			$value['type'] = 'application/octet-stream';
		}

		switch ($value['error']) {
			case UPLOAD_ERR_INI_SIZE:
				throw new AppValidatorException(sprintf($this->getMessage('max_size'), $this->returnBytes(@ini_get('upload_max_filesize')) / 1024), UPLOAD_ERR_INI_SIZE);
			case UPLOAD_ERR_FORM_SIZE:
				throw new AppValidatorException($this->getMessage('max_size'), UPLOAD_ERR_FORM_SIZE);
			case UPLOAD_ERR_PARTIAL:
				throw new AppValidatorException($this->getMessage('partial'), UPLOAD_ERR_PARTIAL);
			case UPLOAD_ERR_NO_FILE:
				throw new AppValidatorException($this->getMessage('no_file'), UPLOAD_ERR_NO_FILE);
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new AppValidatorException($this->getMessage('no_tmp_dir'), UPLOAD_ERR_NO_TMP_DIR);
			case UPLOAD_ERR_CANT_WRITE:
				throw new AppValidatorException($this->getMessage('cant_write'), UPLOAD_ERR_CANT_WRITE);
			case UPLOAD_ERR_EXTENSION:
				throw new AppValidatorException($this->getMessage('err_extension'), UPLOAD_ERR_EXTENSION);
		}

		// check mime type
		if ($this->hasOption('mime_types')) {
			$mime_types = $this->getOption('mime_types') ? $this->getOption('mime_types') : array();
			if (!in_array($value['type'], array_map('strtolower', $mime_types))) {
				throw new AppValidatorException($this->getMessage('mime_types'));
			}
		}

		// check mime type group
		if ($this->hasOption('mime_type_group')) {
			if (!in_array($value['type'], $this->_getGroupMimeTypes($this->getOption('mime_type_group')))) {
				throw new AppValidatorException($this->getMessage('mime_type_group'));
			}
		}

		// check file size
		if ($this->hasOption('max_size') && $this->getOption('max_size') < (int) $value['size']) {
			throw new AppValidatorException(sprintf(JText::_($this->getMessage('max_size')), ($this->getOption('max_size') / 1024)));
		}

		// check extension
		if ($this->hasOption('extension') && !in_array($this->app->filesystem->getExtension($value['name']), $this->getOption('extension'))) {
			throw new AppValidatorException($this->getMessage('extension'));
		}

		return $value;
	}
}


/*
	Class: ZLSplFileInfo
		The ZLSplFileInfo extends SplFileInfo class which offers a high-level object oriented interface to information for an individual file.
		http://au1.php.net/manual/en/class.splfileinfo.php
*/
class FilesProSplFileInfo extends SplFileInfo
{
	/**
	 * Reference to the global App object
	 *
	 * @var App
	 * @since 3.0.5
	 */
	public $app;
	
	/**
	 * Class constructor. Creates a new ZLSplFileInfo object for the file_path specified.
	 * The file does not need to exist, or be readable
	 *
	 * @param String $file_path Path to the file
	 */
	public function __construct($file_path, &$element) {

		// call parent constructor
		parent::__construct($file_path);

		// set application
		$this->app = App::getInstance('zoo');
		
		// set element
		$this->element = $element;
	}

	/**
	 * Gets the file extension
	 *
	 * @return string The file extension or empty if the file has no extension
	 *
	 * @since 3.0.4
	 */
	public function getExtension()
	{
		if (version_compare(PHP_VERSION, '5.3.6', '>=')) {
			return parent::getExtension();
		} else {
			return pathinfo($this->getPathname(), PATHINFO_EXTENSION);
		}
	}

	/**
	 * Get the file content type
	 *
	 * @return string The content type
	 *
	 * @since 3.0.5
	 */
	public function getContentType()
	{
		return $this->app->filesystem->getContentType($this->getPathname());
	}

	/**
	 * Get the absolute url to a file
	 *
	 * @return string The absolute url
	 *
	 * @since 3.0.5
	 */
	public function getURL()
	{
		if ($this->element->config->find('files._s3', 0)) // Amazon S3
		{
			$bucket = $this->element->config->find('files._s3bucket');
			return $this->element->_S3()->getAuthenticatedURL($bucket, $this->getPathname(), 3600);
		} else if ($rel_url = $this->app->path->url("root:{$this->getPathname()}")) {
			return $rel_url;
		} else {
			return $this->getPathname();
		}
	}

	/**
	 * Gets the file title
	 *
	 * @return string The file title
	 *
	 * @since 3.0.5
	 */
	public function getTitle($title = null)
	{
		$title = $title ? $title : $this->getBasename('.'.$this->getExtension());

		// return without _ carachter
		return str_replace('_', ' ', $title);
	}
}