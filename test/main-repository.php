<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\BoxEntity;

try {
  $repo = new Repository( BoxEntity::class );
  $repo
    ->include( fn( BoxEntity $i ) => $i->operator )
    ->where(
      fn( BoxEntity $i ) => (
        $i->isActived == true
        && $i->isDeleted == false 
        && $i->id == 4
      )
    )
    ->select(
      fn( BoxEntity $i ) => [
        $i->id, 
        $i->name, 
        $i->openingAt,
        $i->printer,
        $i->isActived,
        $i->operatorId
      ]
    );
  return [ "success" => true, "content" => $repo->rows() ];

} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}