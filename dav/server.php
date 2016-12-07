<?php
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('UTC');

// The autoloader
require_once '../api/vendor/autoload.php';
require_once 'EFS/Node.php';
require_once 'EFS/Directory.php';
require_once 'EFS/File.php';
require_once 'EFS/Directory.php';

use Sabre\DAV;

$path = '/efs/dav/';
if(isset($_REQUEST['project']))
{
    $path .= 'Projects/'.$_REQUEST['project'];
}

// Now we're creating a whole bunch of objects
$rootDirectory = new SabreAWS\EFS\Directory($path);

// The server object is responsible for making sense out of the WebDAV protocol
$server = new DAV\Server($rootDirectory);

// If your server is not on your webroot, make sure the following line has the
// correct information
$server->setBaseUri('/dav/server.php');

// The lock manager is reponsible for making sure users don't overwrite
// each others changes.
$lockBackend = new DAV\Locks\Backend\File('/efs/locks');
$lockPlugin = new DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);

// This ensures that we get a pretty index in the browser, but it is
// optional.
$server->addPlugin(new DAV\Browser\Plugin());

// All we need to do now, is to fire up the server
$server->exec();

?>
