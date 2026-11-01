<?php

use Websyspro\Connection\Database;
use Websyspro\Connection\Enums\DriverType;
use Websyspro\Entity\Schemas\AbstractEntityStructure;
use Websyspro\Entity\Schemas\MySqlEntityStructure;
use Websyspro\Entity\Schemas\PostgresEntityStructure;
use Websyspro\Entity\Schemas\SqLiteEntityStructure;
use Websyspro\Entity\Schemas\SqlServerEntityStructure;
use Websyspro\Test\Entities\Shops\DocumentEntity;

function entityStructure(
  ReflectionClass $rf
): AbstractEntityStructure {
  return match( Database::driver() ){
    DriverType::SqlServer => new SqlServerEntityStructure($rf),
    DriverType::Postgres => new PostgresEntityStructure($rf),
    DriverType::SqLite => new SqLiteEntityStructure($rf),
    DriverType::MySql => new MySqlEntityStructure($rf)
  };
}

try {
  return entityStructure(
    new ReflectionClass(
      DocumentEntity::class
    )
  );
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}