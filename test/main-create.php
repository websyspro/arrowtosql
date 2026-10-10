<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\OperatorEntity;
use Websyspro\Test\Imports\OperatorImport;

try {
  $repository = new Repository(OperatorEntity::class);
  foreach( OperatorImport::rows() as $rows ){
    $repository->create( 
      fn( OperatorEntity $operator ) => [
        // $operator->id => $rows["Id"],
        $operator->name => $rows["Name"]
      ]
    );
    
    break;
  }

  return $repository;
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}