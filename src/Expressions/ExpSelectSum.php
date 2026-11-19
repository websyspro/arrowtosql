<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpSelectSum 
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