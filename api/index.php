<?php

require_once 'vendor/autoload.php';

$klein = new \Klein\Klein();

$klein->with('/api',function () use ($klein) {


	$klein->respond('POST','/authenticate', function()
	{
		return 'Authenticate->post($request->username,$request->password)';
		/*
		require 'authenticate/AuthenticateController.php';
		$controller = new AuthenticateController();
		$controller->post();
		*/
	});

	$klein->respond('GET','/authenticate', function($request,$response)
	{
		$method = strtolower($request->method());
		return "Authenticate->{$method}()";
		/*
		require 'authenticate/AuthenticateController.php';
		$controller = new AuthenticateController();
		$controller->post();
		*/
	});


	$klein->respond('GET', '/[:entity]/[:id]', function($request,$response)
		{
			return "{$request->entity}/$request->id/index.php";

		});

/*
	$klein->respond('GET', '/api/hello-world', function () {
    	return 'Hello World!';
	});


    $klein->respond('GET', '/?', function ($request, $response) {
        // Show all users
    });

    $klein->respond('GET', '/[:id]', function ($request, $response) {
        // Show a single user
    });
*/
});

$klein->dispatch();