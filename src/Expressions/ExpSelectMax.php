<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSelectMax
extends AbstractExpTypeSelect
{  
  public function __construct(
    public string $table,
    public string $column,
    public array $childs = []
  ){
    parent::__construct();
  }
}