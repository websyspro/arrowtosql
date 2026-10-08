<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\ForeignKey;
use Websyspro\Entity\Decorators\Index;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Decorators\Unique;
use Websyspro\Entity\Types\ColumnDatetime;
use Websyspro\Entity\Types\ColumnDecimal;
use Websyspro\Entity\Types\ColumnInt;
use Websyspro\Entity\Types\ColumnText;

class BoxEntity
extends BaseIncrementEntity
{
  #[Length(32)]
  #[Index(1)]
  #[Unique(1)]
  public ColumnText $name;

  #[Length(1)]
  #[Unique(1)]
  #[Index(2)]
  public ColumnText $state;

  #[ForeignKey(OperatorEntity::class)]
  #[Unique(2)]
  #[Index(1)]
  public ColumnInt $operatorId;

  #[Length(255)]
  public ColumnText $printer;

  public ColumnDatetime $openingAt;

  #[Precision(10,2)]
  public ColumnDecimal $openingBalance;
}