<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\ForeignKey;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Decorators\Unique;
use Websyspro\Entity\Types\ColumnBigInt;
use Websyspro\Entity\Types\ColumnDecimal;
use Websyspro\Entity\Types\ColumnInt;
use Websyspro\Entity\Types\ColumnText;

class ProductEntity
extends BaseIncrementEntity
{
  #[Length(64)]
  #[Unique(1)]
  public ColumnText $name;
  
  #[Precision(10,2)]
  public ColumnDecimal $value;

  #[ForeignKey(ProductGroupEntity::class)]
  public ColumnBigInt $productGroupId;

  #[Length(1)]
  public ColumnText $state;

  #[Precision(10,2)]
  public ColumnDecimal $amount;

  #[Precision(10,2)]
  public ColumnDecimal $totalStock;  
}