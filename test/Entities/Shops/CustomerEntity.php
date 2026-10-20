<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\Index;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Unique;
use Websyspro\Entity\Types\ColumnDatetime;
use Websyspro\Entity\Types\ColumnText;

class CustomerEntity
extends BaseIncrementEntity
{
  #[Length(255)]
  #[Index(1)]
  public ColumnText $name;  

  #[Length(14)]
  #[Unique(1)]
  #[Index(2)]
  public ColumnText $cpf;  
  public ColumnDatetime $lastPurchaseAt;
}