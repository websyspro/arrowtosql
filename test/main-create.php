<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\BoxEntity;
use Websyspro\Test\Entities\Shops\CashMovementEntity;
use Websyspro\Test\Entities\Shops\ConfigEntity;
use Websyspro\Test\Entities\Shops\CustomerEntity;
use Websyspro\Test\Entities\Shops\DocumentEntity;
use Websyspro\Test\Entities\Shops\DocumentItemEntity;
use Websyspro\Test\Entities\Shops\OperatorEntity;
use Websyspro\Test\Entities\Shops\ProductEntity;
use Websyspro\Test\Entities\Shops\ProductGroupEntity;
use Websyspro\Test\Imports\BoxImport;
use Websyspro\Test\Imports\CashMovementImport;
use Websyspro\Test\Imports\ConfigImport;
use Websyspro\Test\Imports\CustomerImport;
use Websyspro\Test\Imports\DocumentImport;
use Websyspro\Test\Imports\DocumentItemImport;
use Websyspro\Test\Imports\OperatorImport;
use Websyspro\Test\Imports\ProductGroupImport;
use Websyspro\Test\Imports\ProductImport;

try {
  $entityResults = [];
  $entitysImports = [
    OperatorEntity::class => OperatorImport::class,
    CustomerEntity::class => CustomerImport::class,
    ProductGroupEntity::class => ProductGroupImport::class,
    ProductEntity::class => ProductImport::class,
    BoxEntity::class => BoxImport::class,
    ConfigEntity::class => ConfigImport::class,
    DocumentEntity::class => DocumentImport::class,
    DocumentItemEntity::class => DocumentItemImport::class,
    CashMovementEntity::class => CashMovementImport::class
  ];

  foreach( $entitysImports as $entity => $import ){
    $repository = new Repository( $entity );
    $repository->create( $import::rows());

    $entityResults[] = sprintf(
      "Importing %s records into table %s", 
        sizeof($import::rows()), basename( $entity )
    );
  }

  return [ "rowsImporteds" => $entityResults ];
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}