<?php

use Websyspro\ArrowToSql\Repository;
use Websyspro\Test\Entities\Shops\BoxEntity;
use Websyspro\Test\Entities\Shops\ConfigEntity;
use Websyspro\Test\Entities\Shops\CustomerEntity;
use Websyspro\Test\Entities\Shops\DocumentEntity;
use Websyspro\Test\Entities\Shops\OperatorEntity;
use Websyspro\Test\Entities\Shops\ProductEntity;
use Websyspro\Test\Entities\Shops\ProductGroupEntity;
use Websyspro\Test\Imports\BoxImport;
use Websyspro\Test\Imports\ConfigImport;
use Websyspro\Test\Imports\CustomerImport;
use Websyspro\Test\Imports\DocumentImport;
use Websyspro\Test\Imports\OperatorImport;
use Websyspro\Test\Imports\ProductGroupImport;
use Websyspro\Test\Imports\ProductImport;

try {
  $repository = new Repository(ConfigEntity::class);
  foreach( ConfigImport::rows() as $rows ){
    $repository->create( 
      fn( ConfigEntity $config ) => [
        $config->id => $rows["Id"],
        $config->passwordReleaseDiscount => $rows["PasswordReleaseDiscount"],
        $config->purchaseLimitPerCustomer => $rows["PurchaseLimitPerCustomer"]
      ]
    );
  }

  $repository = new Repository(OperatorEntity::class);
  foreach( OperatorImport::rows() as $rows ){
    $repository->create( 
      fn( OperatorEntity $operator ) => [
        $operator->id => $rows["Id"],
        $operator->name => $rows["Name"]
      ]
    );
  }

  $repository = new Repository(BoxEntity::class);
  foreach( BoxImport::rows() as $rows ){
    $repository->create( 
      fn( BoxEntity $box ) => [
        $box->id => $rows["Id"],
        $box->name => $rows["Name"],
        $box->state => $rows["State"],
        $box->operatorId => $rows["OperatorId"],
        $box->printer => $rows["Printer"],
        $box->openingAt => $rows["OpeningAt"],
        $box->openingBalance => $rows["OpeningBalance"],
        $box->createdAt => $rows["CreatedAt"]
      ]
    );
  }  

  $repository = new Repository(CustomerEntity::class);
  foreach( CustomerImport::rows() as $rows ){
    $repository->create( 
      fn( CustomerEntity $custommer ) => [
        $custommer->id => $rows["Id"],
        $custommer->name => $rows["Name"],
        $custommer->cpf => $rows["Cpf"],
        $custommer->lastPurchaseAt => $rows["LastPurchaseAt"],
        $custommer->createdAt => $rows["CreatedAt"]
      ]
    );
  }  

  $repository = new Repository(ProductGroupEntity::class);
  foreach( ProductGroupImport::rows() as $rows ){
    $repository->create( 
      fn( ProductGroupEntity $productGroup ) => [
        $productGroup->id => $rows["Id"],
        $productGroup->name => $rows["Name"],
        $productGroup->createdAt => $rows["CreatedAt"]
      ]
    );
  }

  $repository = new Repository(ProductEntity::class);
  foreach( ProductImport::rows() as $rows ){
    $repository->create( 
      fn( ProductEntity $product ) => [
        $product->id => $rows["Id"],
        $product->name => $rows["Name"],
        $product->value => $rows["Value"],
        $product->productGroupId => $rows["ProductGroupId"],
        $product->state => $rows["State"],
        $product->amount => $rows["Amount"],
        $product->totalStock => $rows["TotalStock"],
        $product->createdAt => $rows["CreatedAt"]
      ]
    );
  }

  $repository = new Repository(DocumentEntity::class);
  foreach( DocumentImport::rows() as $rows ){
    $repository->create( 
      fn( DocumentEntity $document ) => [
        $document->id => $rows["Id"],
        $document->type => $rows["Type"],
        $document->state => $rows["State"],
        $document->boxId => $rows["BoxId"],
        $document->operatorId => $rows["OperatorId"],
        $document->customerId => $rows["CustomerId"],
        $document->value => $rows["Value"],
        $document->valueInPix => $rows["ValueInPix"],
        $document->valueInDebitCard => $rows["ValueInDebitCard"],
        $document->valueInCreditCard => $rows["ValueInCreditCard"],
        $document->installmentsFromCreditCard => $rows["InstallmentsFromCreditCard"],
        $document->valueInCash => $rows["ValueInCash"],
        $document->amountReceived => $rows["AmountReceived"],
        $document->valueChange => $rows["ValueChange"],
        $document->observations => $rows["Observations"],
        $document->createdAt => $rows["CreatedAt"]
      ]
    );
  }  

  return $repository;
} catch( Throwable $e ){
  return (object)[ 
    "File" => $e->getFile(),
    "Error" => $e->getMessage(),
    "NumberLine" => $e->getLine(),
  ];
}