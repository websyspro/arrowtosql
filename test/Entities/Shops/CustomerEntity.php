<?php

namespace Websyspro\Test\Entities\Shops;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Unique;
use Websyspro\Entity\Types\ColumnDatetime;
use Websyspro\Entity\Types\ColumnText;

class CustomerEntity
extends BaseIncrementEntity
{
  #[Length(255)]
  public ColumnText $name;  

  #[Length(14)]
  #[Unique()]
  public ColumnText $cpf;  
  public ColumnDatetime $LastPurchaseAt;
}