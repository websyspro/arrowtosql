<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\ForeignKey;
use Websyspro\Entity\Decorators\Index;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Types\ColumnBigInt;
use Websyspro\Entity\Types\ColumnDecimal;
use Websyspro\Entity\Types\ColumnInt;
use Websyspro\Entity\Types\ColumnText;

class DocumentEntity
extends BaseIncrementEntity
{
  #[Length(1)]
  #[Index(1)]
  public ColumnText $type;

  #[Length(1)]
  public ColumnText $state;

  #[Index(2)]
  #[ForeignKey(BoxEntity::class)]
  public ColumnBigInt $boxId;

  #[Index(2)]
  #[ForeignKey(OperatorEntity::class)]
  public ColumnBigInt $operatorId;

  #[ForeignKey(CustomerEntity::class)]
  public ColumnBigInt $customerId;

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
}