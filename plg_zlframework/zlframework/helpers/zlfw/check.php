<?php

/**
 * Class zlfwHelperCheck
 * Diagnistic helper for checking required system settings or server settings
 */
class zlfwHelperCheck extends AppHelper {

	/**
	 * @var Messages stack
	 */
	protected static $stack;

	/**
	 * Add message to stack
	 *
	 * @param   string
	 * @param   string
	 *
	 * @return  void
	 */
	public function addMsg($message_text, $namespace = 'error'){

		if(array_key_exists($namespace, self::$stack)){
			self::$stack[$namespace][] = JText::_($message_text);
		}else{
			self::$stack[$namespace] = array(JText::_($message_text));
		}
	}

	/**
	 * Get messages
	 *
	 * @param   string
	 *
	 * @return  array
	 */
	public function getMsg($namespace = NULL){
		$buffer = array();

		if(!empty($namespace)){
			if(array_key_exists($namespace, self::$stack)){
				$buffer = self::$stack[$namespace];
			}
		}else{
			if(!empty(self::$stack)){
				// Mix and return messages from all namespaces:
				foreach(self::$stack as $group => $messages){
					$buffer = array_merge($buffer, $messages);
				}
			}
		}

		return $buffer;
	}

	/**
	 * Check curl options
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
}

 