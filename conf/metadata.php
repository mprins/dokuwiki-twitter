<?php
/**
 * Options for twitter plugin.
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl2.html)
 * @author  Mark C. Prins <mprins@users.sf.net>
 *
 * @copyright Copyright (c) 2012-2013 Mark C. Prins <mprins@users.sf.net>
 */
$meta ['timeout'] = array (
		'numeric',
		'_min' => 15
);
$meta ['useCURL'] = array (
		'onoff'
);
$meta ['maxresults'] = array (
		'numeric',
		'_min' => 5
);
$meta ['oauth_consumer_key'] = array (
		'string'
);
$meta ['oauth_consumer_secret'] = array (
		'string'
);
$meta ['oauth_token'] = array (
		'string'
);
$meta ['oauth_token_secret'] = array (
		'string'
);
