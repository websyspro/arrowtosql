<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\ArrowToSql\Interfaces\JoinResult;

class ExpInclude
extends AbstractExpType
{
  public function __construct(
    public JoinResult $joinResult,
    public ColumnResult $columnResult,
    public array $childs = []
  ){
    parent::__construct();
  }  
}