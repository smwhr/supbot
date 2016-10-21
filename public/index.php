<?php
require_once("../vendor/autoload.php");

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


// CONSTANTES

define('APP_ID', getenv('APP_ID'));
define('APP_SECRET', getenv('APP_SECRET'));
define('PAGE_ACCESS_TOKEN', getenv('PAGE_ACCESS_TOKEN'));
define('VALIDATION_TOKEN', getenv('VALIDATION_TOKEN'));

//BOOTSTRAPING

$app = new Silex\Application();
$app["debug"] = true;

$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => __DIR__."/../logs/all.log",
  'monolog.name' => 'supbot'
));


//SERVICES

$app['messager'] = $app->protect(function($user_id, $message, $qrs = null, $image="null") use ($app){
  $url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.PAGE_ACCESS_TOKEN;
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $container = ["recipient" => ["id" => $user_id], "message" => ["text" => $message]];

  if($qrs){
    $container["message"]["metadata"] = "DEFAULT_QUICK_REPLY";
    $container["message"]["quick_replies"] = [];
    foreach ($qrs as $pl => $text) {
      $container["message"]["quick_replies"][] = [
          "content_type" => "text",
          "title" => substr($text,0,20),
          "payload" => $pl
      ];
    }
  }

  if($image){
    unset($container["message"]["text"]);
    $container["message"]["attachment"] = [
      "type" => "image",
      "payload" => ["url" => $image]
    ];
  }

  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($container));
  $result = curl_exec($ch);
  curl_close($ch);

  return $result;
});


//CONTROLLERS

$app->before(function(Request $request){
  if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
        $request->request->set('raw_json', $data);
    }
});

$app->get('/webhook', function(Request $request) use ($app){
    //?hub_mode=subscribe&hub_verify_token=valeur_secret&hub_challenge==1234566
    if(   $request->query->get('hub_mode') === 'subscribe'
       && $request->query->get('hub_verify_token') === VALIDATION_TOKEN){
      return new Response($request->query->get('hub_challenge') );
    }else{
      return new Response("SUBSCRIBE_FAILED ".VALIDATION_TOKEN);
    }

    return new Response("ça marche !");
});


$app->post('/webhook', function(Request $request) use ($app){
    $entry = $request->request->get("entry")[0];
    $app['monolog']->addInfo(json_encode($entry));

    $user_id = $entry['messaging'][0]['sender']['id'];
    $message = $entry['messaging'][0]['message']['text'];

    if($message){
      $messager = $app['messager'];

      if($message == "chipolata"){
        $result = $messager($user_id, "Fais ton choix : ", [
                      "BLUE" => "Bleue",
                      "RED" => "Rouge",
          ], "http://i.imgur.com/fktskYH.jpg"); 
      }else{
        $result = $messager($user_id, "Et ta mère ? Elle aime les salsifis ?");  
      }
      
      
      $app['monolog']->addInfo($result);
    }

    return new Response("ok", 200);
});


$app->run(); 