<html>
  <head>
    <base href="/">
    <title>ProLoop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- include material design CSS -->
    <link rel="stylesheet" href="node_modules/materialize-css/dist/css/materialize.min.css" />
     
    <!-- include material design icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    <link rel="stylesheet" href="libs/css/styles.css">

    <!-- 1. Load libraries -->
    <!-- IE required polyfill -->
    <script src="node_modules/core-js/client/shim.min.js"></script>

	<!-- include jquery -->
	<script type="text/javascript" src="libs/js/jquery-3.1.1.min.js"></script>
	 
	<!-- material design js -->
	<script src="node_modules/materialize-css/dist/js/materialize.min.js"></script>
 

    <script src="node_modules/zone.js/dist/zone.js"></script>
    <script src="node_modules/reflect-metadata/Reflect.js"></script>
    <script src="node_modules/systemjs/dist/system.src.js"></script>

    <!-- 2. Configure SystemJS -->
    <script src="systemjs.config.js"></script>
    <script>
      System.import('app').catch(function(err){ console.error(err); });
    </script>
  </head>
  <!-- 3. Display the application -->
  <body>
    <proloop>Loading...</proloop>
  </body>
</html>