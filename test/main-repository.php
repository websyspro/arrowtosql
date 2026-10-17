<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\DocumentEntity;
use Websyspro\Test\Entities\Shops\DocumentItemEntity;

try {
  $repository = new Repository( DocumentEntity::class );
  $repository->leftJoin(
    fn( DocumentEntity $document ) => (
      $document->items
        ->leftJoin( 
          fn( DocumentItemEntity $item ) => $item->product
        )
    )
  );
  $repository->where( fn( DocumentEntity $document ) => (
    $document->boxId == 4 && 
    $document->state->in( "F" ) && 
    $document->observations->contains( "Venda Normal" ) && 
    $document->items->any( fn( DocumentItemEntity $item ) => $item->documentId == $document->id ) && 
    $document->createdAt->between( "02/04/2023", "10/04/2023" )

    // $email == $u->email
    // && $u->name == null
    // && !$u->name != "TEST%" 
    // && !$u->name
    // && !$u->isDeleted
    // && $u->deletedAt >= "01/03/2026"
    // && !$u->deletedAt->toDate()->between( "01/01/2026", "31/01/2026" )
    // && !$u->name->contains( "TEST 1", "TEST 2" )
    // && !$u->name->notIn( "TEST 1", "TEST 2" )
    // && !$u->name == [ "TEST 1", "TEST 2" ]
    // && "31/03/2026" >= $u->Deleted
    // && !$u->isActive && ( $u->name == "TEST" )
    // && !$u->access->any( fn( UserEntity $a ) => $a->isActived )
  ));

  $repository->select( 
    fn( DocumentEntity $document ) => [
      $document->id,
      // $document->sum($document->value)->as( "sum_value" ),
      $document->boxId,
      $document->type,
      $document->value,
      $document->valueInCash,
      $document->valueChange,
      $document->observations,
      $document->createdAt,
      $document->updatedAt
      
      //$document->sum( $document->value )->as( "totalBox" )
      // $document->id,
      // $document->value,
      // $document->valueInCash,
      // $document->valueInCreditCard,
      // $document->valueInDebitCard,
      // $document->valueChange,
      // $u->sum(( $u->id / $u->balance ) * $u->balance )->as( "total" ), 
      // $u->name->trim()->as( "name" )
    ]
  );

  $repository->paged(1, 2);

  // $repository->groupBy(
  //   fn( DocumentEntity $document ) => [
  //     $document->boxId
  //     // $u->sum(( $u->id / $u->balance ) * $u->balance )->as( "total" ), 
  //     // $u->name->trim()->as( "name" )
  //   ]
  // );

  //$repository->groupBy( fn( DocumentEntity $document ) => $u->document );
  return [ "success" => true, "content" => $repository->rows() ];

} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}