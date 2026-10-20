<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\EntityList;
use Websyspro\Entity\Decorators\ForeignKey;
use Websyspro\Entity\Decorators\HasOneToMany;
use Websyspro\Entity\Decorators\HasOneToOne;
use Websyspro\Entity\Decorators\Index;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Types\ColumnBigInt;
use Websyspro\Entity\Types\ColumnDecimal;
use Websyspro\Entity\Types\ColumnList;
use Websyspro\Entity\Types\ColumnText;

class DocumentEntity
extends BaseIncrementEntity
{
  #[Length(1)]
  #[Index(1)]
  public ColumnText $type;

  #[Length(1)]
  public ColumnText $state;

  #[ForeignKey(BoxEntity::class)]
  public ColumnBigInt $boxId;
  public BoxEntity $box;  


  #[ForeignKey(OperatorEntity::class)]
  public ColumnBigInt $operatorId;
  public OperatorEntity $operator;


  #[ForeignKey(CustomerEntity::class)]
  public ColumnBigInt $customerId;
  public CustomerEntity $customer;  

  #[Precision(10,2)]
  public ColumnDecimal $value;

  #[Precision(10,2)]
  public ColumnDecimal $valueInPix;

  #[Precision(10,2)]
  public ColumnDecimal $valueInDebitCard;

  #[Precision(10,2)]
  public ColumnDecimal $valueInCreditCard;

  #[Precision(10,2)]
  public ColumnDecimal $installmentsFromCreditCard;

  #[Precision(10,2)]
  public ColumnDecimal $valueInCash;

  #[Precision(10,2)]
  public ColumnDecimal $amountReceived;

  #[Precision(10,2)]
  public ColumnDecimal $valueChange;

  #[Length(255)]
  public ColumnText $observations;

  #[EntityList(DocumentItemEntity::class)]
  public EntityList $items;  
}