<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSelectColumn 
extends AbstractExpType
{  
  public function __construct(
    public string $table,
    public string $column,
    public string $columnAs,
  ){
    parent::__construct();
  }
}