<?php
require_once("../vendor/autoload.php");

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//BOOTSTRAPING

$app = new Silex\Application();
$app["debug"] = true;

$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => __DIR__."/../logs/all.log",
  'monolog.name' => 'supbot'
));


//CONTROLLERS

$app->get('/webhook', function(Request $request) use ($app){
    return new Response("ça marche !");
});


$app->run(); 