<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpInclude
extends AbstractExpType
{
  public function __construct(
    public string $master,
    public string $inner,
    public array $scopes = [],
    public array $childs = []
  ){
    parent::__construct();
  }  
}