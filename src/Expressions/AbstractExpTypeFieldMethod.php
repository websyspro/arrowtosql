<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\Enums\MethodType;

class AbstractExpTypeFieldMethod
extends AbstractExpType 
{
  public function __construct(
    public MethodType $type
  ){ 
    parent::__construct();
  }  
}