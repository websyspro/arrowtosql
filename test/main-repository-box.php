<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\BoxEntity;

try {
  $repo = new Repository( BoxEntity::class );
  $repo->include( fn( BoxEntity $box ) => $box->operator );
  $repo->select( 
    fn( BoxEntity $box ) => [
      $box->id,
      $box->name,
      $box->printer,
      $box->openingBalance,
      $box->createdAt,
      $box->operatorId,
    ]
  );

  $repo->paged(1, 12);
  return [ "success" => true, "content" => $repo->rows() ];

} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}