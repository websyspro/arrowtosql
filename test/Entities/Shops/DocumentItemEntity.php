<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\ForeignKey;
use Websyspro\Entity\Decorators\HasOneToOne;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Types\ColumnBigInt;
use Websyspro\Entity\Types\ColumnDecimal;

class DocumentItemEntity
extends BaseIncrementEntity
{
  #[ForeignKey(DocumentEntity::class)]
  public ColumnBigInt $documentId;
  public DocumentEntity $document;  

  #[ForeignKey(ProductEntity::class)]
  public ColumnBigInt $productId;
  public ProductEntity $product;

  #[Precision(10,2)]
  public ColumnDecimal $value;

  #[Precision(10,2)]
  public ColumnDecimal $amount;

  #[Precision(10,2)]
  public ColumnDecimal $discount;

  #[Precision(10,2)]
  public ColumnDecimal $totalValue;
}