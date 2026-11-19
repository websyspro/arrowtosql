<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpCaseElse
extends AbstractExpType
{
  public function __construct(
    public string $result
  ){
    parent::__construct();
  }
}
