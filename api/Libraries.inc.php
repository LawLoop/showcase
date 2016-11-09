<?php

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('UTC');
$__start_time = microtime(true);

$aws_config = [
	'credentials' => [
		'key'    => get_cfg_var('aws.access_key') ? get_cfg_var('aws.access_key') : 'AKIAJU3UGXPOA7LB3GEA',
		'secret' => get_cfg_var('aws.secret_key') ? get_cfg_var('aws.secret_key') : '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'],
	'region' => 'us-east-1', 
	'version' => 'latest'
];

$aws = null;
$s3 = null;
$cache = null;
//$bucket = 'rewindfiles';

require_once 'vendor/autoload.php';
/*
// keep the namespace clean
function DataDogSetup()
{
	$ddapiKey = 'b334594a1777d3be58571eabe71ee79d';
	$ddappKey = 'e2e3cac5ad11ee267ab78be74f0c1e1ebd7886f0';

	DataDogStatsD::configure($ddapiKey, $ddappKey);
}

DataDogSetup();
*/
require_once 'Utilities.inc.php';
require_once 'Database.inc.php';

try
{
	$aws = Aws\Common\Aws::factory(__DIR__ . '/config.inc.php');
	//$aws = new Aws\Common\Aws::factory('config.inc.php');
	$s3 = $aws->get('s3');
	$ses = $aws->get('ses');
	//$s3 = new Aws\S3\S3Client($aws_config);
    //$ses =  new Aws\Ses\SesClient($aws_config);

	$cacheId = get_cfg_var('aws.param1') ? get_cfg_var('aws.param1') : get_cfg_var('ns.aws.param1');

	// little trick to get the right redis cloud happening -tb
	$host = $_SERVER['HTTP_HOST'];
	$prefix = explode('.', $host)[0];
	$cache_config = __DIR__ . "/{$prefix}.inc.php"; 
	if(file_exists($cache_config))
	{
		require_once $cache_config;
	}
	else
	{
		require_once 'RedisCache.inc.php';
	}
    
    // $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
	//require_once 'UrbanAirship.inc.php';
	//require_once 'NumberStation.inc.php';
	//require_once 'GeoHashDistance.inc.php';
    //require_once 'PushNotification.inc.php';
}
catch(Exception $ex)
{
	Fatal($ex);
}

