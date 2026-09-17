<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpToken
extends AbstractExpType
{
  public function __construct(
    public int $key,
    public string $value,
    public string $name
  ){
    parent::__construct();
  }
}
