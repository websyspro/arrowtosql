<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\Enums\MethodType;

class ExpFieldCaseMethod
extends AbstractExpTypeFieldMethod
{
  public function __construct(
    public string $descriptor,
    public MethodType $type,
    public array $whens = [],
    public array $else = []
  ){
    parent::__construct( $type );
  }  
}