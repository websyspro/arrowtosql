<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\BoxEntity;
use Websyspro\Test\Entities\Shops\CashMovementEntity;
use Websyspro\Test\Entities\Shops\DocumentEntity;
use Websyspro\Test\Entities\Shops\DocumentItemEntity;
use Websyspro\Test\Entities\Shops\ProductEntity;

try {
  $repo = new Repository( CashMovementEntity::class );
  $repo->include( 
    fn( CashMovementEntity $cm ) => $cm->box->include(
      fn( BoxEntity $box ) => $box->operator
    ),
    fn( CashMovementEntity $cm ) => $cm->document->include(
      fn( DocumentEntity $dc ) => $dc->items->include(
        fn( DocumentItemEntity $di ) => $di->product->include(
          fn( ProductEntity $pr ) => $pr->productGroup
        )
      )
    )
  );
  $repo->where(
    fn( CashMovementEntity $cm ) => $cm->documentId == 3596
  );
  $repo->select( 
    fn( CashMovementEntity $cm ) => [
      $cm->id,
      $cm->type,
      $cm->paymentMethod,
      $cm->boxId,
      $cm->value,
      $cm->documentId,
      $cm->observations,
      $cm->createdAt
    ]
  );

  $repo->paged(1, 1);
  return [ "success" => true, "content" => $repo->rows() ];

} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}