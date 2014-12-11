<?php

/**
 * Class zlfwHelperCheck
 * Diagnistic helper for checking required system settings or server settings
 */
class zlHelperCheck extends AppHelper {

	/**
	 * @var Messages stack
	 */
	protected static $stack = array();

	/**
	 * Add message to stack
	 *
	 * @param   string
	 * @param   string
	 *
	 * @return  void
	 */
	public function addMsg($message_text, $namespace = 'error'){

		$key = md5($message_text);

		if(array_key_exists($namespace, self::$stack)){
			if(!array_key_exists($key, self::$stack[$namespace])){
				self::$stack[$namespace][$key] = JText::_($message_text);
			}
		}else{
			self::$stack[$namespace] = array($key => JText::_($message_text));
		}
	}

	/**
	 * Get messages
	 *
	 * @param   string
	 *
	 * @return  array
	 */
	public function getMsg($namespace = NULL, $keepindex = false){
		$buffer = array();

		if(!empty($namespace)){
			if(array_key_exists($namespace, self::$stack)){
				$buffer = self::$stack[$namespace];
			}
		}else{
			if(!empty(self::$stack)){
				if(!$keepindex){
					// Mix and return messages from all namespaces:
					foreach(self::$stack as $group => $messages){
						$buffer = array_merge($buffer, $messages);
					}
				}else{
					$buffer = self::$stack;
				}
			}
		}

		return $buffer;
	}

	/**
	 * Check curl config
	 *
	 * @return  bool
	 */
	public function checkCurl(){

		$success = function_exists('curl_version');

		if(!$success){
			$this->addMsg('PLG_ZLFRAMEWORK_CURL_NOT_INSTALLED');
		}else{
			// Check further
			$version = curl_version();
			$ssl_support = ($version['features'] & CURL_VERSION_SSL);
			$success = $success && $ssl_support;

			if(!$ssl_support){
				$this->addMsg('PLG_ZLFRAMEWORK_CURL_SSL_NOT_SUPPORTED', 'warning');
			}
		}

		return $success;
	}

	/**
	 * Check fopen and permissions
	 *
	 * @return  bool
	 */
	public function checkWrappers(){

		// If we are not allowed to use ini_get, we assume that URL fopen is disabled
		if (!function_exists('ini_get')) {
			$success = false;
		} else {
			$success = (bool)ini_get('allow_url_fopen');
		}

		if(!$success){
			$this->addMsg('PLG_ZLFRAMEWORK_REMORE_FILE_READ_DISABLED');
		}

		return $success;
	}
}

 