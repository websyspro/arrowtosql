<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSelectColumn 
extends AbstractExpTypeSelect
{  
  public function __construct(
    public string $table,
    public string $column,
    public array $closures = []
  ){
    parent::__construct();
  }
}