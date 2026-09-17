<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpMethod
extends AbstractExpType
{  
  public function __construct(
    public string $name,
    public array $childs
  ){
    parent::__construct();
  }
}