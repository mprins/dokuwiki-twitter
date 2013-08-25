<?php
/**
 * English language file for twitter plugin.
 *
 * @license GPL 2 (http://opensource.org/licenses/gpl-2.0.php)
 * @author  Mark C. Prins <mprins@users.sf.net>
 * @author  Damien Clochard <damien@dalibo.info>
 * 
 * @copyright Copyright (c) 2012-2013 Mark C. Prins <mprins@users.sf.net>
 *
 */
$lang ['header'] = 'Timeline de';
$lang ['results'] = 'Résultats de la recherche :';
$lang ['timestamp'] = 'Il y a environ %s par :';
$lang ['timechunks'] = array (
		array (
				60 * 60 * 24 * 365,
				'année',
				'années'
		),
		array (
				60 * 60 * 24 * 30,
				'mois',
				'mois'
		),
		array (
				60 * 60 * 24 * 7,
				'semaine',
				'semaines'
		),
		array (
				60 * 60 * 24,
				'jour',
				'jours'
		),
		array (
				60 * 60,
				'heure',
				'heures'
		),
		array (
				60,
				'min',
				'mins'
		),
		array (
				1,
				'sec',
				'secs'
		)
);
$lang ['configerror'] = 'Les valeurs de "oauth_consumer_key", "oauth_consumer_secret", "oauth_token" et "oauth_token_secret" doivent être définies pour générer des requêtes authentifiées.';
