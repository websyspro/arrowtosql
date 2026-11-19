<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpCaseWhen
extends AbstractExpType
{
  public function __construct(
    public string $condition,
    public string $result
  ){
    parent::__construct();
  }
}
