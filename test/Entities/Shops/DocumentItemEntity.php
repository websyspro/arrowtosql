<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\ForeignKey;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Types\ColumnDecimal;
use Websyspro\Entity\Types\ColumnInt;

class DocumentItemEntity
extends BaseIncrementEntity
{
  #[ForeignKey(DocumentEntity::class)]
  public ColumnInt $documentId;

  #[ForeignKey(ProductEntity::class)]
  public ColumnInt $productId;

  #[Precision(10,2)]
  public ColumnDecimal $value;

  #[Precision(10,2)]
  public ColumnDecimal $amount;

  #[Precision(10,2)]
  public ColumnDecimal $discount;

  #[Precision(10,2)]
  public ColumnDecimal $totalValue; 
}