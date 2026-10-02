<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\UserEntity;

$email = "cpd.emersontsa@gmail.com";

$repository = new Repository(UserEntity::class);
$repository->where( fn( UserEntity $u ) => (
  $email == $u->email
  && $u->name == null
  && !$u->name != "TEST%" 
  && !$u->name
  && !$u->IsDeleted
  && $u->Deleted >= "01/03/2026"
  && !$u->Deleted->toDate()->between( "01/01/2026", "31/01/2026" )
  && !$u->name->contains( "TEST 1", "TEST 2" )
  && !$u->name->notIn( "TEST 1", "TEST 2" )
  && !$u->name == [ "TEST 1", "TEST 2" ]
  // && "31/03/2026" >= $u->Deleted
  // && !$u->isActive && ( $u->name == "TEST" )
  && !$u->access->any( fn( UserEntity $a ) => $a->IsActive )
));

$repository->select( fn( UserEntity $u ) => [
  $u->sum(( $u->Id / $u->balance ) * $u->balance ), $u->name->trim()
]);

$repository->groupBy( fn( UserEntity $u ) => $u->name );

print_r( $repository );