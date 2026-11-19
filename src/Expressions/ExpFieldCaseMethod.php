<?php

namespace Websyspro\ArrowToSql\Expressions;

use Websyspro\ArrowToSql\Enums\MethodType;

class ExpFieldCaseMethod
extends AbstractExpTypeFieldMethod
{
  public function __construct(
    public string $descriptor,
    public MethodType $type,
    public ExpCaseWhen|null $whens = null,
    public ExpCaseElse|null $else = null
  ){
    parent::__construct( $type );
  }  
}