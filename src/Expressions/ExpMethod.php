<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpMethod
extends AbstractExpType
{  
  public function __construct(
    public string $table,
    public string $name,
    public array $methods = [],
    public array $childs = [],
  ){
    parent::__construct();
  }
}