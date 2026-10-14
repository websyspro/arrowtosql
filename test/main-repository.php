<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\DocumentEntity;
use Websyspro\Test\Entities\UserEntity;

$boxId = 5;

try {
  $repository = new Repository( DocumentEntity::class );
  $repository->where( fn( DocumentEntity $document ) => (
    // $document->boxId == $boxId &&
    $document->state->in( "C" ) &&
    $document->observations->contains( "Cancelado pelo Operador" ) 
    // && $document->createdAt->between( "02/04/2023", "10/04/2023" )

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
      $document->sum($document->value)->as( "sum_value" ),
      $document->boxId,
      $document->type,
      $document->observations,
      $document->createdAt
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