<?php

namespace Websyspro\Test\Entities;

use Websyspro\Entity\BaseIncrementEntity;
use Websyspro\Entity\Decorators\Entity;
use Websyspro\Entity\Decorators\HasMany;
use Websyspro\Entity\Decorators\Index;
use Websyspro\Entity\Decorators\Length;
use Websyspro\Entity\Decorators\Precision;
use Websyspro\Entity\Decorators\Required;
use Websyspro\Entity\Types\ColumnDecimal;
use Websyspro\Entity\Types\ColumnList;
use Websyspro\Entity\Types\ColumnText;


#[Entity("users")]
class UserEntity
extends BaseIncrementEntity
{
  #[Required()]
  #[Length(100)]
  public ColumnText $name;

  #[Required]
  #[Index()]
  #[Length(255)]
  public ColumnText $email;

  #[Precision(10, 2)]
  public ColumnDecimal $balance;

  #[HasMany(AccessEntity::class)]
  public ColumnList $access;
}
