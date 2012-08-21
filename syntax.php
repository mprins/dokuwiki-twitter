<?php
/*
 * Twitter Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christoph Lang <calbity@gmx.de>
 */
 
// based on http://wiki.splitbrain.org/plugin:tutorial
 
// must be run within DokuWiki
if (!defined('DOKU_INC')) die();
 
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_twitter extends DokuWiki_Syntax_Plugin {

    private function replace($data) {
 
 
    		$sResponse = "<h1>".$data[1]."</h1>";
    		$data = $data[0];
 
    		if(!isset($data)){
    			return $sResponse."<h4>Twitter is down....</h4>";
    		}
    		$sResponse .= '<table class="twitterentries" style="width:100%;">';
 
 
    		foreach($data as $entry){
 
    			$text=$entry->text." ";
    			$image=$entry->user->profile_image_url;
    			$time=$entry->created_at;    			
    			$time = strtotime($time);
    			//$time = date("Y-m-d H:i:s",$time);
    			$time = $this->Timesince($time);
    			$from=$entry->from_user;
    			$name = "";
    			if(!empty($entry->user->name))
	    			$name = " (".$entry->user->name.")";
    			
    			if(empty($from))
    				$from=$entry->user->screen_name;
 
    			if(isset($entry->profile_image_url))
    			{
    			 $image=$entry->profile_image_url;
    			}
 
    			// get links
          $search = array(
            '`((?:https?|ftp)://\S+[[:alnum:]]/?)`si',
            '`((?<!//)(www\.\S+[[:alnum:]]/?))`si'
          );
          $replace = array(
            '<a href="$1"  target="_blank">$1</a> ',
            '<a href="http://$1" target="_blank">$1</a>'
          );
          $text = preg_replace($search, $replace, $text);
 
    			// get hashtags
    			if (preg_match_all('/#(.*?)\s/', $text, $arMatches))
    			{
    				for($i=0; $i < count($arMatches[0]); $i++)
    			   		$text = str_replace($arMatches[0][$i], '<a target="_blank" href="http://search.twitter.com/search?q=' . $arMatches[1][$i] . '">' . $arMatches[0][$i] . "</a>", $text);
          }
 
    			// get twitterer
    			if (preg_match_all('/@(.*?)\s/', $text, $arMatches))
    			{
    				for($i=0; $i < count($arMatches[0]); $i++){
    			   $strTwitterer = preg_replace('/\W/', '', $arMatches[0][$i]);
    			   $text = str_replace($strTwitterer, '<a target="_blank" href="http://twitter.com/' . $strTwitterer . '">' . $strTwitterer . "</a>", $text);
    			  }
          }
 
    			$sResponse .= '<tr onmouseover="this.style.backgroundColor=\'#cccccc\';" onmouseout="this.style.backgroundColor=\'\';" style="width:100%;"><td style="width:48px;"><img width="48" src="'.$image.'" alt="'.$from.'"/></td><td>'.$text.'<br/>About '.$time.' ago from <a target="_blank" href="http://twitter.com/'.$from.'">'.$from. $name.'</a></td></tr>';
    		}
    		$sResponse .= "</table>";
 
 
        return $sResponse;
    }
    /* Works out the time since the entry post, takes a an argument in unix time (seconds)  
		*/  
		public function Timesince($original) {   
		    // array of time period chunks   
		    $chunks = array(   
		    array(60 * 60 * 24 * 365 , 'year'),   
		    array(60 * 60 * 24 * 30 , 'month'),   
		    array(60 * 60 * 24 * 7, 'week'),   
		    array(60 * 60 * 24 , 'day'),   
		    array(60 * 60 , 'hour'),   
		    array(60 , 'min'),   
		    array(1 , 'sec'),   
		    );   
		  
		    $today = time(); /* Current unix time  */  
		    $since = $today - $original;   
		  
		    // $j saves performing the count function each time around the loop   
		    for ($i = 0, $j = count($chunks); $i < $j; $i++) {   
		  
		    $seconds = $chunks[$i][0];   
		    $name = $chunks[$i][1];   
		  
		    // finding the biggest chunk (if the chunk fits, break)   
		    if (($count = floor($since / $seconds)) != 0) {   
		        break;   
		    }   
		    }   
		  
		    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";   
		  
		    if ($i + 1 < $j) {   
		    // now getting the second item   
		    $seconds2 = $chunks[$i + 1][0];   
		    $name2 = $chunks[$i + 1][1];   
		  
		    // add second item if its greater than 0   
		    if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {   
		        $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";   
		    }   
		    }   
		    return $print;   
		}  

 
    function connectTo($mode) {	
				$this->Lexer->addSpecialPattern('\[TWITTER\:USER\:.*?\]', $mode, 'plugin_twitter');
				$this->Lexer->addSpecialPattern('\[TWITTER\:SEARCH\:.*?\]', $mode, 'plugin_twitter');
 
				$this->Lexer->addSpecialPattern('{{twitter>user\:.*?}}', $mode, 'plugin_twitter');
				$this->Lexer->addSpecialPattern('{{twitter>search\:.*?}}', $mode, 'plugin_twitter');
 
    }
 
    function getType() { return 'substition'; }
 
    function getSort() { return 314; }
 
    function handle($match, $state, $pos, &$handler) {
 
    		$match = str_replace(array(">","{{","}}"),array(":","[","]"),$match);
 
    		$match = substr($match,1,-1);
    		$data = explode(":",$match);    	
 
    		$number ="";    		
 		
 				$data[2] = str_replace(" ","%20",$data[2]);
    		if(strtoupper($data[1]) == "SEARCH"){

    			@$json = file_get_contents("http://search.twitter.com/search.json?q=".$data[2].$number);
    		}else{
 
    			if(isset($data[3]))
    				$number = "?count=".$data[3];
 
    			@$json = file_get_contents("http://twitter.com/statuses/user_timeline/".$data[2].".json".$number);		
    			
				}
 
				$decode = json_decode ( $json );
 
				if(isset($decode->results))
				{
				  return array($decode->results,"Results for search: ".str_replace("%20"," and ",$data[2]));
				}
 
        return array($decode,"Timeline from ".$data[2]);
    }
 
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