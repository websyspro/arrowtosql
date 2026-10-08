<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Unique;
use Websyspro\Entity\Types\ColumnText;

class ProductGroupEntity
extends BaseIncrementEntity
{
  #[Length(64)]
  #[Unique(1)]
  public ColumnText $name;  
}