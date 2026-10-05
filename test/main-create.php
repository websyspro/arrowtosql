<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\UserEntity;

try {
  $repository = new Repository(UserEntity::class);
  $repository->create( 
    fn( UserEntity $u ) => [
      $u->name => "EMERSON",
      $u->email => "cpd.emersonts@gmail.com"
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