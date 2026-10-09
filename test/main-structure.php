<?php

use Websyspro\Connection\Database;
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
use Websyspro\Test\Entities\Shops\BoxEntity;
use Websyspro\Test\Entities\Shops\CashMovementEntity;
use Websyspro\Test\Entities\Shops\ConfigEntity;
use Websyspro\Test\Entities\Shops\CustomerEntity;
use Websyspro\Test\Entities\Shops\DocumentEntity;
use Websyspro\Test\Entities\Shops\DocumentItemEntity;
use Websyspro\Test\Entities\Shops\OperatorEntity;
use Websyspro\Test\Entities\Shops\ProductEntity;
use Websyspro\Test\Entities\Shops\ProductGroupEntity;

function schemaManager(
  ReflectionClass $rf
): MySqlSchemaManager|PostgresSchemaManager|SqlServerSchemaManager|SqLiteSchemaManager {
  return match( Database::driver() ){
    DriverType::MySql => new MySqlSchemaManager( new MySqlEntityStructure($rf), new MySqlEntityStructurePersisteds($rf)),
    DriverType::PostgreSQL => new PostgresSchemaManager( new PostgresEntityStructure($rf), new PostgresEntityStructurePersisteds($rf)),
    DriverType::SqlServer => new SqlServerSchemaManager( new SqlServerEntityStructure($rf), new SqlServerEntityStructurePersisteds($rf)),
    DriverType::Sqlite => new SqLiteSchemaManager( new SqLiteEntityStructure($rf), new SqLiteEntityStructurePersisteds($rf))
  };
}

$entityArrs = [
  BoxEntity::class,
  ConfigEntity::class,
  CashMovementEntity::class,
  CustomerEntity::class,
  DocumentEntity::class,
  DocumentItemEntity::class,
  OperatorEntity::class,
  ProductEntity::class,
  ProductGroupEntity::class
];

try {
  foreach( $entityArrs as $classEntity ){
    $schemaManager = schemaManager( new ReflectionClass( $classEntity ));
    $schemaManager->asyncEntity();
    $schemaManager;
  }

  foreach( $entityArrs as $classEntity ){
    $schemaManager = schemaManager( new ReflectionClass( $classEntity ));
    $schemaManager->asyncConstraint();
  }  
  
  return [];
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}