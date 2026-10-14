<?php

enum fileAction:string {
  case Create = "/create";
  case Repository = "/repository";
  case Structure = "/structure";
  case Entity = "/entity";
}

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/env.php";

[ "REQUEST_URI" => $requestUri ] = $_SERVER;

$jsonEncode = json_encode(
  require_once match( $requestUri ){
    fileAction::Create->value 
      => __DIR__ . '/test/main-create.php',
    fileAction::Repository->value
      => __DIR__ . '/test/main-repository.php',
    fileAction::Structure->value
      => __DIR__ . '/test/main-structure.php',
    fileAction::Entity->value
      => __DIR__ . '/test/main-entity.php'      
  }
);

exit( $jsonEncode );