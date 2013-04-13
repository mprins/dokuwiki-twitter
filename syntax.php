<?php

/*
 * Twitter Plugin
*
* @license GPL 2 (http://opensource.org/licenses/gpl-2.0.php)
* @author Christoph Lang <calbity@gmx.de>
* @author Mark C. Prins <mprins@users.sf.net>
*/

if (!defined('DOKU_INC'))
	die();
if (!defined('DOKU_PLUGIN'))
	define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');

/**
 * Twitter syntax plugin.
*/
class syntax_plugin_twitter extends DokuWiki_Syntax_Plugin {

	private $_oauth_consumer_key;
	private $_oauth_consumer_secret;
	private $_oauth_token;
	private $_oauth_token_secret;

	private function replace($data) {
		$sTitle = $data[1];
		$data = $data[0];

		$sResponse = '<div class="twtWrapper">';
		if (!isset($data)) {
			return $sResponse . '<div class="error">Twitter error....</div></div>';
		}
		$sResponse .= '<table class="twtEntries" >';
		$sResponse .= '<caption class="twtHeader">';
		$sResponse .= '<img class="twtLogo" src="' . DOKU_BASE . 'lib/plugins/' . $this->getPluginName() . '/bird_blue_32.png" alt=""/>';
		$sResponse .= $sTitle;
		$sResponse .= '</caption>';

		foreach ($data as $entry) {
			// dbglog($entry, "=================entry=================");
			$text = $entry->text . " ";
			$image = $entry->user->profile_image_url;
			$time = $entry->created_at;
			$time = strtotime($time);
			$time = $this->Timesince($time);
			$from = $entry->from_user;
			$name = "";
			if (!empty($entry->user->name)) {
				$name = $entry->user->name;
			}
			if (empty($from)) {
				$from = $entry->user->screen_name;
			}
			$permalink = 'https://twitter.com/' . $from . '/status/' . $entry->id_str;
			if (isset($entry->profile_image_url)) {
				$image = $entry->profile_image_url;
			}
			// get links
			$search = array(
					'`((?:https?|ftp)://\S+[[:alnum:]]/?)`si',
					'`((?<!//)(www\.\S+[[:alnum:]]/?))`si'
			);
			$replace = array(
					'<a href="$1" class="urlextern" target="_blank">$1</a> ',
					'<a href="http://$1" class="urlextern" target="_blank">$1</a>'
			);
			$text = preg_replace($search, $replace, $text);

			// get hashtags
			if (preg_match_all('/#(.*?)\s/', $text, $arMatches)) {
				for ($i = 0; $i < count($arMatches[0]); $i++) {
					$text = str_replace($arMatches[0][$i], '<a class="urlextern" target="_blank" href="https://search.twitter.com/search?q=' . $arMatches[1][$i] . '">' . $arMatches[0][$i] . "</a>", $text);
				}
			}

			// get twitterer
			if (preg_match_all('/@(.*?)\s/', $text, $arMatches)) {
				for ($i = 0; $i < count($arMatches[0]); $i++) {
					$strTwitterer = preg_replace('/\W/', '', $arMatches[0][$i]);
					$text = str_replace($strTwitterer, '<a class="urlextern" target="_blank" href="https://twitter.com/' . $strTwitterer . '">' . $strTwitterer . "</a>", $text);
				}
			}
			$sResponse .= '<tr class="twtRow">';
			$sResponse .= '  <td class="twtImage">' . p_render('xhtml', p_get_instructions('{{' . $image . '?48&nolink|' . $from . ' avatar}}'), $info) . '</td>';
			$sResponse .= '  <td class="twtMsg">' . $text . '<br/><a href="' . $permalink . '" class="urlextern twtUrlextern" target="_blank">' . sprintf($this->getLang('timestamp'), $time) . '</a> <a class="urlextern twtUrlextern" target="_blank" href="https://twitter.com/' . $from . '">' . $name . " (@" . $from . ")" . '</a></td>';
			$sResponse .= '</tr>';
		}
		$sResponse .= '</table></div>';
		return $sResponse;
	}

	/**
	 * Works out the time since the entry post, takes a an argument in unix time (seconds).
	 *
	 * @param numeric $original unix time (seconds)
	 * @return string
	 */
	public function Timesince($original) {
		global $conf;
		// This is a HACK, it may break at some stage when there is type checking
		// and getLang sticks to the contract for now getLang() will return anything
		$chunks = $this->getLang('timechunks');

		$today = time(); /* Current unix time  */
		$since = $today - $original;

		// $j saves performing the count function each time around the loop
		for ($i = 0, $j = count($chunks); $i < $j; $i++) {
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
			$names = $chunks[$i][2];
			// finding the biggest chunk (if the chunk fits, break)
			if (($count = floor($since / $seconds)) != 0) {
				break;
			}
		}
		$print = ($count == 1) ? '1 ' . $name : "$count {$names}";
		if ($i + 1 < $j) {
			// now getting the second item
			$seconds2 = $chunks[$i + 1][0];
			$name2 = $chunks[$i + 1][1];
			$name2s = $chunks[$i + 1][2];
			// add second item if its greater than 0
			if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
				$print .= ($count2 == 1) ? ', 1 ' . $name2 : ", $count2 {$name2s}";
			}
		}
		return $print;
	}

	/**
	 * Syntax patterns.
	 * (non-PHPdoc)
	 *
	 * @see Doku_Parser_Mode::connectTo()
	 */
	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\[TWITTER\:USER\:.*?\]', $mode, 'plugin_twitter');
		$this->Lexer->addSpecialPattern('{{twitter>user\:.*?}}', $mode, 'plugin_twitter');

		$this->Lexer->addSpecialPattern('\[TWITTER\:SEARCH\:.*?\]', $mode, 'plugin_twitter');
		$this->Lexer->addSpecialPattern('{{twitter>search\:.*?}}', $mode, 'plugin_twitter');
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DokuWiki_Syntax_Plugin::getType()
	 */
	function getType() {
		return 'substition';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Doku_Parser_Mode::getSort()
	 */
	function getSort() {
		return 314;
	}

	/**
	 * Paragraph Type.
	 *
	 * Defines how this syntax is handled regarding paragraphs. This is important
	 * for correct XHTML nesting. Should return one of the following:
	 *
	 * 'normal' - The plugin can be used inside paragraphs
	 * 'block'  - Open paragraphs need to be closed before plugin output
	 * 'stack'  - Special case. Plugin wraps other paragraphs.
	 *
	 * @see Doku_Handler_Block::getPType()
	 */
	function getPType() {
		return 'block';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DokuWiki_Syntax_Plugin::handle()
	 */
	function handle($match, $state, $pos, &$handler) {
		global $conf;
		$match = str_replace(array(">", "{{", "}}"), array(":", "[", "]"), $match);
		$match = substr($match, 1, -1);
		$data = explode(":", $match);

		$this->_oauth_consumer_key = $this->getConf('oauth_consumer_key');
		$this->_oauth_consumer_secret = $this->getConf('oauth_consumer_secret');
		$this->_oauth_token = $this->getConf('oauth_token');
		$this->_oauth_token_secret = $this->getConf('oauth_token_secret');
		if (empty($this->_oauth_consumer_secret)) {
			throw new Exception('To generate a hash, the consumer secret must be set.');
		}

		$number = $this->getConf('maxresults');
		if (isset($data[3])) {
			$number = $data[3];
		}
		$data[2] = str_replace(" ", "%20", $data[2]);
		if (strtoupper($data[1]) == "SEARCH") {
			$json = $this->getData("https://api.twitter.com/1.1/search/tweets.json", array('q' => $data[2], 'count' => $number, 'include_entities' => false));
		} else {
			$json = $this->getData("https://api.twitter.com/1.1/statuses/user_timeline.json", array('screen_name' => $data[2], 'count' => $number));
		}
		$decode = json_decode($json);
		// dbglog($decode, "=======================decoded json from Twitter============================");
		if (isset($decode->search_metadata)) {
			return array($decode->statuses, $this->getLang('results') . ' <a class="urlextern" target="_blank" href="https://twitter.com/search?q=' . $data[2] . '">' . str_replace("%20", " and ", $data[2].'</a>'));
		}
		return array($decode, $this->getLang('header') . ' <a class="urlextern" target="_blank" href="https://twitter.com/' . $data[2] . '">@' . $data[2] . '</a>');
	}

	/**
	 * get the data from twitter using either cURL or file_get_contents.
	 * @param String $url
	 */
	private function getData($url, $param) {
		//dbglog($url, "Getting url from Twitter");
		$json;
		if ($this->getConf('useCURL')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; DokuWiki HTTP Client; ' . PHP_OS . ')');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_URL, $this->signRequest($url, $param));
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$json = curl_exec($ch);
			curl_close($ch);
		} else {
			global $conf;
			$ctx = array('http' => array(
					'proxy' => 'tcp:' . $conf['proxy']['host'] . ':' . $conf['proxy']['port'],
					'request_fulluri' => true)
			);
			$ctx = stream_context_create($ctx);
			$json = file_get_contents($this->signRequest($url, $param), true, $ctx);
		}
		return $json;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DokuWiki_Syntax_Plugin::render()
	 */
	function render($mode, &$renderer, $data) {
		if ($mode == 'xhtml') {
			// prevent caching to ensure content is always fresh
			$renderer->info['cache'] = false;
			$renderer->doc .= $this->replace($data);
			return true;
		} elseif ($mode == 'metadata') {
			// for metadata renderer
			$renderer->meta['relation']['haspart']['_plugin_twitter'] = true;
			return true;
		}
		return false;
	}

	/**
	 * Generates the OAuth signed request url.
	 *
	 * @param string $endpointUrl The API endpoint to call
	 * @param array optional $params The API call parameters, associative
	 *
	 * @return string The signed API endpoint call including the parameters
	 */
	private function signRequest($endpointUrl, $params = array()) {
		$sign_params = array(
				'oauth_consumer_key' => $this->_oauth_consumer_key,
				'oauth_version' => '1.0',
				'oauth_timestamp' => time(),
				'oauth_nonce' => substr(md5(microtime(true)), 0, 16),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_token' => $this->_oauth_token
		);

		$sign_base_params = array();
		foreach ($sign_params as $key => $value) {
			$sign_base_params[$key] = $this->urlencode($value);
		}

		foreach ($params as $key => $value) {
			$sign_base_params[$key] = $this->urlencode($value);
		}

		ksort($sign_base_params);
		$sign_base_string = '';
		foreach ($sign_base_params as $key => $value) {
			$sign_base_string .= $key . '=' . $value . '&';
		}
		$sign_base_string = substr($sign_base_string, 0, -1);
		$signature = base64_encode(hash_hmac('sha1', ('GET&' . $this->urlencode($endpointUrl) .
				'&' . $this->urlencode($sign_base_string)), $this->_oauth_consumer_secret .
				'&' . ($this->_oauth_token_secret != null ? $this->_oauth_token_secret : ''), true));

		return $endpointUrl . '?' . $sign_base_string . '&oauth_signature=' . $this->urlencode($signature);
	}

	/**
	 * URL-encodes the data.
	 *
	 * @param mixed $data
	 *
	 * @return mixed The encoded data
	 */
	private function urlencode($data) {
		if (is_array($data)) {
			return array_map(array(
					$this,
					'urlencode'
			), $data);
		} elseif (is_scalar($data)) {
			return str_replace(
					array('+', '!', '*', "'", '(', ')'),
					array(' ', '%21', '%2A', '%27', '%28', '%29'),
					rawurlencode($data));
		} else {
			return '';
		}
	}
}
