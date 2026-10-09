<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\OperatorEntity;

$operador = $_GET[ "name" ];

try {
  $repository = new Repository(OperatorEntity::class);
  $repository->create( 
    fn( OperatorEntity $operator ) => [
      $operator->name => $operador
    ]
  );

  return $repository;
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}