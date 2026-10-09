<?php

enum fileAction {
  case Create;
  case Repository;
  case Structure;
  case Entity;
}

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/env.php";

$jsonEncode = json_encode(
  require_once match( fileAction::{$_GET["fileAction"]} ){
    fileAction::Create 
      => __DIR__ . '/test/main-create.php',
    fileAction::Repository
      => __DIR__ . '/test/main-repository.php',
    fileAction::Structure
      => __DIR__ . '/test/main-structure.php',
    fileAction::Entity
      => __DIR__ . '/test/main-entity.php'      
  }
);

exit( $jsonEncode );