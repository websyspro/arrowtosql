<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\Enums\MethodType;

class ExpFieldMethod
extends AbstractExpType
{
  public function __construct(
    public string $name,
    public MethodType $type,
    public array $args = []
  ){
    parent::__construct();
  }  
}