<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\Interfaces\JoinDetail;

class ExpJoin
extends AbstractExpType
{  
  public function __construct(
    public JoinDetail $joinDetail,
    public array $includes = []
  ){
    parent::__construct();
  }
}
