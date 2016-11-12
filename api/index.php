<?php

require_once 'vendor/autoload.php';
require_once 'SQLModel.inc.php';
require_once 'models/User.php';

$klein = new \Klein\Klein();

$klein->with('/api',function () use ($klein) {

    $klein->respond(['GET','POST'], '/authenticate', function ($request, $response)
    {
        $user = User::find(['username' => $request->username]);

        if(isset($user))
        {
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
    $klein->response('GET', '/users/[i:id]', function($request, $response)
    {
        $user = User::find($request->id);
        $response->json($user);
    });

    // update
    $klein->response('PUT', '/users/[i:id]', function($request, $response)
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
    $klein->response('POST', '/users', function($request, $response)
    {
        $user = User::find(['username' => $request->username]);

        if(isset($user))
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
            $user->save();
            $response->json($user);
        }
    });



});
//header('Content-Type: application/json');
$klein->dispatch();