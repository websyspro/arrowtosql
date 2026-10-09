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

class CashMovementEntity
extends BaseIncrementEntity
{
  #[Length(1)]
  public ColumnText $type;

  #[Length(3)]
  public ColumnText $paymentMethod;

  #[Index()]
  #[ForeignKey(DocumentEntity::class)]
  public ColumnBigInt $documentId;

  #[Index()]
  #[ForeignKey(BoxEntity::class)]
  public ColumnBigInt $boxId;

  #[Precision(10,2)]
  public ColumnDecimal $value;

  #[Length(255)]
  public ColumnText $observations; 
}