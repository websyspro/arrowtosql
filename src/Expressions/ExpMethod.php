<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpMethod
extends AbstractExpType
{  
  public function __construct(
    public string $table,
    public string $descriptor,
    public array $closures = [],
    public array $childs = []
  ){
    parent::__construct();
  }
}