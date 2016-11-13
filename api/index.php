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
        $response->json(User::all(['username']));
    });

    // get one
    $klein->respond('GET', '/users/[i:id]', function($request, $response)
    {
        $user = User::find($request->id);
        $response->json($user);
    });

    // update
    $klein->respond('PUT', '/users/[i:id]', function($request, $response)
    {
        $user = User::find($request->id);
        if(!empty($user))
        {
            $user->username = $request->username;
            $user->password = $request->password;
            $user->firstName = $request->firstName;
            $user->lastName = $request->lastName;
            $user->save();
            $response->json($user);
        }
        else
        {
            $response->json(['error' => 'User not found']);
        }
    });

    // create
    $klein->respond('POST', '/users', function($request, $response)
    {
        $user = User::find(['username' => $request->username]);

        if(isset($user) && is_array($user) && count($user) == 1)
        {
            $response->json(['error' => 'User exists']);
        }
        else
        {
            $user = new User();
            $user->username = $request->username;
            $user->password = $request->password;
            $user->firstName = $request->firstName;
            $user->lastName = $request->lastName;
            try
            {
                $user->save();
                $response->json($user);
            }
            catch(\Exception $ex)
            {
                $response->json(['error' => $ex->getMessage()]);

            }
        }
    });



});
//header('Content-Type: application/json');
$klein->dispatch();