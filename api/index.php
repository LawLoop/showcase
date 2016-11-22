<?php
/* Parse input into PHP globals because angular doesn't pass parameters normally but
 * instead sends them in the body as json.  Stupid Angular.
 */

$__params = json_decode(file_get_contents('php://input'),true);
if(!is_array($__params)) { $__params = []; }
$_REQUEST = array_merge($_REQUEST,$__params);
$_DELETE = array ();
$_PUT = array ();
$_method = isset($_REQUEST['_m']) ? $_REQUEST['_m'] : $_SERVER['REQUEST_METHOD'];

switch ( $_method ) {
    case !strcasecmp($_method,'DELETE'):
        $_DELETE=array_merge($_DELETE,$__params);
        break;

    case !strcasecmp($_method,'PUT'):
        $_PUT = array_merge($_PUT,$__params);
        break;

    case !strcasecmp($_method,'GET'):
        $_GET = array_merge($_GET,$__params);
        break;

    case !strcasecmp($_method,'POST'):
        $_POST = array_merge($_POST,$__params);
        break;
}


require_once 'vendor/autoload.php';
require_once 'SQLModel.inc.php';
require_once 'models/User.php';
require_once 'models/Project.php';
require_once 'RESTController.inc.php';

$klein = new \Klein\Klein();

$klein->with('/api',function () use ($klein) {

    $klein->respond(['GET','POST'], '/authenticate', function ($request, $response)
    {
        $user = User::find(['username' => $request->username]);

        if(isset($user) && is_array($user) && count($user) == 1)
        {
            $user = $user[0];
            $user->token = "fake-security-token";
            $response->json($user);
        }
        else
        {
            $response->json(['error' => 'Login failed']);
        }
    });

    // get em all
    $klein->respond('GET','/users',function($request, $response)
    {
        $controller = new RESTController('User');
        $controller->all($request,$response,['username']);
    });

    // get one
    $klein->respond('GET', '/users/[i:id]', function($request, $response)
    {
        $controller = new RESTController('User');
        $controller->get($request,$response);
    });

    // update
    $klein->respond('PUT', '/users/[i:id]', function($request, $response)
    {
        $controller = new RESTController('User');
        $controller->update($request,$response);
    });

    // Projects
    // create
    $klein->respond('POST', '/projects', function($request, $response)
    {
        $controller = new RESTController('Project');
        $controller->create($request,$response);
    });

    // get em all
    $klein->respond('GET','/projects',function($request, $response)
    {
        $controller = new RESTController('Project');
        $controller->all($request,$response,['title']);
    });

    // get one
    $klein->respond('GET', '/projects/[i:id]', function($request, $response)
    {
        $controller = new RESTController('Project');
        $controller->get($request,$response);
    });

    // update
    $klein->respond('PUT', '/projects/[i:id]', function($request, $response)
    {
        $controller = new RESTController('Project');
        $controller->update($request,$response);
    });

    // create
    $klein->respond('POST', '/projects', function($request, $response)
    {
        $controller = new RESTController('Project');
        $controller->create($request,$response);
    });


});
//header('Content-Type: application/json');
$klein->dispatch();