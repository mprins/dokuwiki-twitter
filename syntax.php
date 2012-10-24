<?php
/*
 * Twitter Plugin
*
* @license GPL 2 (http://opensource.org/licenses/gpl-2.0.php)
* @author Christoph Lang <calbity@gmx.de>
* @author Mark C. Prins <mprins@users.sf.net>
*/

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');

/**
 * Twitter syntax plugin.
 */
class syntax_plugin_twitter extends DokuWiki_Syntax_Plugin {

	private function replace($data) {
		$sResponse = '<div class="twtWrapper">';
		$sResponse .= '<div class="twtHeader">'.$data[1].'</div>';
		$data = $data[0];

		if(!isset($data)){
			return $sResponse.'<div class="error">Twitter is down....</div>';
		}
		$sResponse .= '<table class="twtEntries" >';

		foreach($data as $entry){
			$text=$entry->text." ";
			$image=$entry->user->profile_image_url;
			$time=$entry->created_at;
			$time = strtotime($time);
			//$time = date("Y-m-d H:i:s",$time);
			$time = $this->Timesince($time);
			$from=$entry->from_user;
			$name = "";
			if(!empty($entry->user->name)){
				$name = " (".$entry->user->name.")";
			}
			if(empty($from)){
				$from=$entry->user->screen_name;
			}
			if(isset($entry->profile_image_url)) {
				$image=$entry->profile_image_url;
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
				for($i=0; $i < count($arMatches[0]); $i++){
					$text = str_replace($arMatches[0][$i], '<a class="urlextern" target="_blank" href="http://search.twitter.com/search?q=' . $arMatches[1][$i] . '">' . $arMatches[0][$i] . "</a>", $text);
				}
			}

			// get twitterer
			if (preg_match_all('/@(.*?)\s/', $text, $arMatches)) {
				for($i=0; $i < count($arMatches[0]); $i++){
					$strTwitterer = preg_replace('/\W/', '', $arMatches[0][$i]);
					$text = str_replace($strTwitterer, '<a class="urlextern" target="_blank" href="http://twitter.com/' . $strTwitterer . '">' . $strTwitterer . "</a>", $text);
				}
			}
			$sResponse .= '<tr class="twtRow">';
			//$sResponse .= '  <td class="twtImage"><img width="48" src="'.$image.'" alt="'.$from.' avatar"/></td>';
			$sResponse .= '  <td class="twtImage">'.p_render('xhtml', p_get_instructions('{{'.$image.'?48&nolink&recache|'.$from.' avatar}}'), $info).'</td>';
			$sResponse .= '  <td class="twtMsg">'.$text.'<br/>'.sprintf($this->getLang('timestamp'),$time).' <a class="urlextern" target="_blank" href="http://twitter.com/'.$from.'">'.$from. $name.'</a></td>';
			$sResponse .= '</tr>';
		}
		$sResponse .= '</table></div>';
		return $sResponse;
	}

	/**
	 *  Works out the time since the entry post, takes a an argument in unix time (seconds).
	 * @param numeric $original unix time (seconds)
	 * @return string
	 */
	public function Timesince($original) {
		global $conf;
		/*
		 $chunks_en = array(
		 		array(60 * 60 * 24 * 365 , 'year', 'years'),
		 		array(60 * 60 * 24 * 30 , 'month', 'months'),
		 		array(60 * 60 * 24 * 7, 'week', 'weeks'),
		 		array(60 * 60 * 24 , 'day', 'days'),
		 		array(60 * 60 , 'hour', 'hours'),
		 		array(60 , 'min', 'mins'),
		 		array(1 , 'sec', 'secs'),
		 );

		$chunks_nl = array(
				array(60 * 60 * 24 * 365 , 'jaar', 'jaren'),
				array(60 * 60 * 24 * 30 , 'maand', 'maanden'),
				array(60 * 60 * 24 * 7, 'week', 'weken'),
				array(60 * 60 * 24 , 'dag', 'dagen'),
				array(60 * 60 , 'uur', 'uren'),
				array(60 , 'min', 'min'),
				array(1 , 'sec', 'sec'),
		);

		switch ( $conf['lang']){
		case 'nl':
		$chunks =$chunks_nl;
		break;
		case 'en':
		default:
		$chunks =$chunks_en;
		}
		*/
		// This is a HACK, it may break at some stage when there is type checking and getLang stich to the contract
		// for now getLang() will return anything
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
		$print = ($count == 1) ? '1 '.$name : "$count {$names}";
		if ($i + 1 < $j) {
			// now getting the second item
			$seconds2 = $chunks[$i + 1][0];
			$name2 = $chunks[$i + 1][1];
			$name2s = $chunks[$i + 1][2];
			// add second item if its greater than 0
			if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
				$print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2s}";
			}
		}
		return $print;
	}

	/**
	 * Syntax patterns.
	 * (non-PHPdoc)
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
	 * @see DokuWiki_Syntax_Plugin::getType()
	 */
	function getType() {
		return 'substition';
	}
	/**
	 * (non-PHPdoc)
	 * @see Doku_Parser_Mode::getSort()
	 */
	function getSort() {
		return 314;
	}
	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Syntax_Plugin::handle()
	 */
	function handle($match, $state, $pos, &$handler) {
		global $conf;
		$match = str_replace(array(">","{{","}}"),array(":","[","]"),$match);

		$match = substr($match,1,-1);
		$data = explode(":",$match);

		$number ="";
		$data[2] = str_replace(" ","%20",$data[2]);
		if(strtoupper($data[1]) == "SEARCH"){
			$json=$this->getData("http://search.twitter.com/search.json?q=".$data[2].$number);
		}else{
			if(isset($data[3])) {
				$number = "?count=".$data[3];
			} else {
				$number = "?count=".$this->getConf('maxresults');
			}
            $json=$this->getData("http://api.twitter.com/1/statuses/user_timeline.json?screen_name=".$data[2]."&count=".$this->getConf('maxresults'));
        }
		$decode = json_decode ( $json );
		if(isset($decode->results)) {
			return array($decode->results,$this->getLang('results')." ".str_replace("%20"," and ",$data[2]));
		}
		return array($decode,$this->getLang('header')." ".$data[2]);
	}

	/**
	 * get the data from twitter using either cURL or file_get_contents.
	 * @param String $url
	 */
	private function getData($url){
		$json;
		if ($this->getConf('useCURL')){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; DokuWiki HTTP Client; '.PHP_OS.')');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_URL, $url);
			$json = curl_exec($ch);
			curl_close($ch);
		} else{
			global $conf;
			$ctx = array('http' => array(
					'proxy' => 'tcp:'.$conf['proxy']['host'].':'.$conf['proxy']['port'],
					'request_fulluri' => true)
			);
			$ctx = stream_context_create($ctx);
			$json = file_get_contents($url,true,$ctx);
		}
		return $json;
	}

	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Syntax_Plugin::render()
	 */
	function render($mode, &$renderer, $data) {
		if ($mode == 'xhtml') {
			// prevent caching to ensure content is always fresh
			$renderer->info['cache'] = false;
			$renderer->doc .= $this->replace($data);
			return true;
		} elseif ($mode == 'metadata'){
			// for metadata renderer
			$renderer->meta['relation']['haspart']['_plugin_twitter'] = true;
			return true;
		}
		return false;
	}
}
