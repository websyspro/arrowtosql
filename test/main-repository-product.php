<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\ProductEntity;

try {
  $repo = new Repository( ProductEntity::class );
  $repo->include( fn( ProductEntity $product ) => $product->productGroup );
  $repo->select( 
    fn( ProductEntity $product ) => [
      $product->id,
      $product->name,
      $product->amount,
      $product->productGroupId
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