<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpValue
extends AbstractExpType
{
  public function __construct(
    public string $value
  ){
    parent::__construct();
  }  
}