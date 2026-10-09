<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpFieldValue
extends AbstractExpType
{
  public function __construct(
    public readonly string $table,
    public readonly string $column,
    public readonly string $type,
    public readonly array $value
  ){
    parent::__construct();
  }  
}