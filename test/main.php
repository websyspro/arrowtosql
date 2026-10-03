<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Entity\Schemas\SqlLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlLiteEntityStructurePersisteds;
use Websyspro\Entity\Schemas\SqlLiteSchemaManager;
use Websyspro\Test\Entities\UserEntity;
use Websyspro\Connection\Database;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\PostgresEntityStructurePersisteds;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructurePersisteds;
use Websyspro\Entity\Schemas\SqlServerSchemaManager;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\MySqlEntityStructurePersisteds;
use Websyspro\Entity\Schemas\MySqlSchemaManager;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\PostgresSchemaManager;

$schemaManager = match( Database::driver() ){
  DriverType::MySql => new MySqlSchemaManager(
    new MySqlEntityStructure(
      new ReflectionClass(
        UserEntity::class
      )
    ),
    new MySqlEntityStructurePersisteds(
      new ReflectionClass(
        UserEntity::class
      )
    ) 
  ),
  DriverType::PostgreSQL => new PostgresSchemaManager(
    new PostgresEntityStructure(
      new ReflectionClass(
        UserEntity::class
      )
    ),
    new PostgresEntityStructurePersisteds(
      new ReflectionClass(
        UserEntity::class
      )
    ) 
  ),
  DriverType::SqlServer => new SqlServerSchemaManager(
    new SqlServerEntityStructure(
      new ReflectionClass(
        UserEntity::class
      )
    ),
    new SqlServerEntityStructurePersisteds(
      new ReflectionClass(
        UserEntity::class
      )
    ) 
  ),
  DriverType::Sqlite => new SqlLiteSchemaManager(
    new SqlLiteEntityStructure(
      new ReflectionClass(
        UserEntity::class
      )
    ),
    new SqlLiteEntityStructurePersisteds(
      new ReflectionClass(
        UserEntity::class
      )
    ) 
  )
};   

$schemaManager->asyncEntity();
exit();

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