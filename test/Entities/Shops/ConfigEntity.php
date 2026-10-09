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

class ConfigEntity
extends BaseIncrementEntity
{
  #[Length(32)]
  #[Unique(1)]
  public ColumnText $passwordReleaseDiscount;

  #[Precision(10,2)]
  public ColumnDecimal $purchaseLimitPerCustomer;

  #[ForeignKey(BoxEntity::class)]
  public ColumnBigInt $boxId;
}