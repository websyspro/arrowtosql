<?php

use Websyspro\Connection\Database;
use Websyspro\Test\Entities\UserEntity;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructurePersisteds;
use Websyspro\Entity\Schemas\SqLiteSchemaManager;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructurePersisteds;
use Websyspro\Entity\Schemas\SqlServerSchemaManager;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\MySqlEntityStructurePersisteds;
use Websyspro\Entity\Schemas\MySqlSchemaManager;
use Websyspro\Entity\Schemas\PostgresEntityStructurePersisteds;
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
  DriverType::Sqlite => new SqLiteSchemaManager(
    new SqLiteEntityStructure(
      new ReflectionClass(
        UserEntity::class
      )
    ),
    new SqLiteEntityStructurePersisteds(
      new ReflectionClass(
        UserEntity::class
      )
    ) 
  )
};   

try {
  $schemaManager->asyncEntity();
  return $schemaManager;
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}