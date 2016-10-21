<?php
require_once("../vendor/autoload.php");

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


// CONSTANTES

define('APP_ID', get_env('APP_ID'));
define('APP_SECRET', get_env('APP_SECRET'));
define('PAGE_ACCESS_TOKEN', get_env('PAGE_ACCESS_TOKEN'));
define('VALIDATION_TOKEN', get_env('VALIDATION_TOKEN'));

//BOOTSTRAPING

$app = new Silex\Application();
$app["debug"] = true;

$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => __DIR__."/../logs/all.log",
  'monolog.name' => 'supbot'
));


//CONTROLLERS

$app->get('/webhook', function(Request $request) use ($app){
    //?hup_mode=subscribe&hub_verify_token=valeur_secret&hub_challenge==1234566
    if(   $request->query->get('hub_mode') === 'subscribe'
       && $request->query->get('hub_verify_token') === VALIDATION_TOKEN){
      return new Response($request->query->get('hub_challenge') );
    }else{
      return new Response("SUBSCRIBE_FAILED");
    }

    return new Response("ça marche !");
});


$app->run(); 