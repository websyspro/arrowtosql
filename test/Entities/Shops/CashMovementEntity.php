<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\ForeignKey;
use Websyspro\Entity\Decorators\Index;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Types\ColumnBigInt;
use Websyspro\Entity\Types\ColumnDecimal;
use Websyspro\Entity\Types\ColumnText;

class CashMovementEntity
extends BaseIncrementEntity
{
  #[Length(1)]
  public ColumnText $type;

  #[Length(3)]
  public ColumnText $paymentMethod;

  #[ForeignKey(DocumentEntity::class)]
  public ColumnBigInt $documentId;
  public DocumentEntity $document;

  #[ForeignKey(BoxEntity::class)]
  public ColumnBigInt $boxId;
  public BoxEntity $box;

  #[Precision(10,2)]
  public ColumnDecimal $value;

  #[Length(255)]
  public ColumnText $observations; 
}