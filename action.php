<?php
/*
 * Twitter plugin action component.
 *
 * @license GPL 2 (http://opensource.org/licenses/gpl-2.0.php)
 * @author Christopher Smith <chris@jalakai.co.uk>
 * @author Björn Kalkbrenner <terminar@cyberphoria.org> (rewritten for twitter plugin)
 * @author Mark C. Prins <mprins@users.sf.net>
 */
if (!defined('DOKU_INC')) {
	die();
}
if (!defined('DOKU_PLUGIN')) {
	define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}
require_once(DOKU_PLUGIN . 'action.php');

/**
 * Twitter Plugin Action plugin component, for cache validity determination; based on the Source Plugin/Action scripts.
 */
class action_plugin_twitter extends DokuWiki_Action_Plugin {

	/**
	 * plugin should use this method to register its handlers with the dokuwiki's event controller.
	 * @see DokuWiki_Action_Plugin::register()
	 */
	function register(Doku_Event_Handler $controller) {
		$controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_cache_prepare');
	}

	/**
	 * prepare the cache object for default _useCache action
	 */
	function _cache_prepare($event, $param) {
		$cache =& $event->data;

		// we're only interested in wiki pages and supported render modes
		if (!isset($cache->page)) {
			return;
		}
		if (!isset($cache->mode) || !in_array($cache->mode, array('i', 'metadata'))) {
			return;
		}

		$max_age = $this->_cache_maxage($cache->page);

		if (is_null($max_age)) {
			return;
		}

		if ($max_age <= 0) {
			// expire the cache
			//no cache for twitter!
			$event->preventDefault();
			$event->stopPropagation();
			$event->result = false;
			return;
		}
		$cache->depends['age'] = !empty($cache->depends['age']) ? min($cache->depends['age'], $max_age) : $max_age;
	}

	/**
	 * determine the max allowable age of the cache
	 *
	 * @param   string    $id wiki page name
	 *
	 * @return  int max allowable age of the cache null means not applicable
	 */
	function _cache_maxage($id) {
		$hasPart = p_get_metadata($id, 'relation haspart');
		if (empty($hasPart) || !is_array($hasPart)) {
			return null;
		}
		$age = 0;
		foreach ($hasPart as $file => $data) {
			if ($file == "_plugin_twitter") {
				//this is us, outdate the cache if older than the configured seconds
				return $this->getConf('timeout');
			}
		}
		return $age ? time() - $age : null;
	}

}
