<?php
/**
* @package		ZOOcart
* @author		ZOOlanders http://www.zoolanders.com
* @copyright	Copyright (C) JOOlanders, SL
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// register and load (necessary) the main pagination class
JLoader::register('AppPagination', App::getInstance('zoo')->path->path('component.admin:framework/classes/pagination.php'));
JLoader::load('AppPagination');

/**
 * The Uikit Pagination class
 */
class UikitPagination extends AppPagination {

	public function render($url = 'index.php', $layout = null)
	{
		$html = '';

		// check if show all
		if ($this->_showall) {
			return $html;
		}

		if ($this->pages() > 1) {

			$range_start = max($this->current() - $this->range(), 1);
			$range_end   = min($this->current() + $this->range() - 1, $this->pages());

			if ($this->current() > 1) {
				$link  = $url;
				$html .= '<li><a href="'.JRoute::_($link).'"><i class="uk-icon-angle-double-left"></i></a></li>';
				$link  = $this->current() - 1 == 1 ? $url : $this->link($url, $this->name().'='.($this->current() - 1));
				$html .= '<li><a href="'.JRoute::_($link).'"><i class="uk-icon-angle-left"></i></a></li>';
			}

			for ($i = $range_start; $i <= $range_end; $i++) {
				if ($i == $this->current()) {
					$html .= '<li class="uk-active"><span>'.$i.'</span>';
				} else {
					$link  = $i == 1 ? $url : $this->link($url, $this->name().'='.$i);
					$html .= '<li><a href="'.JRoute::_($link).'">'.$i.'</a></li>';
				}
			}

			if ($this->current() < $this->pages()) {
				$link  = $this->link($url, $this->name().'='.($this->current() + 1));
				$html .= '<li><a href="'.JRoute::_($link).'"><i class="uk-icon-angle-right"></i></a></li>';
				$link  = $this->link($url, $this->name().'='.($this->pages()));
				$html .= '<li><a href="'.JRoute::_($link).'"><i class="uk-icon-angle-double-right"></i></a></li>';
			}

		}

		return $html;
	}

}