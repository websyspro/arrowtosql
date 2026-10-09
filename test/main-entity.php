<?php

use Websyspro\Connection\Database;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Test\Entities\Shops\BoxEntity;
use Websyspro\Test\Entities\UserEntity;

function entityStructure(
  ReflectionClass $rf
): MySqlEntityStructure|PostgresEntityStructure|SqlServerEntityStructure|SqLiteEntityStructure {
  return match( Database::driver() ){
    DriverType::MySql => new MySqlEntityStructure($rf),
    DriverType::PostgreSQL => new PostgresEntityStructure($rf),
    DriverType::SqlServer => new SqlServerEntityStructure($rf),
    DriverType::Sqlite => new SqLiteEntityStructure($rf)
  };
}

try {
  return entityStructure(
    new ReflectionClass(
      UserEntity::class
    )
  );
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}