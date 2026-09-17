<?php

namespace Websyspro\ArrowToSql\Expressions;

class ExpEqual
extends AbstractExpType
{
  public function __construct(
    public string $value
  ){
    parent::__construct();
  }

  public function invert(
  ): self {
    $this->value = match( $this->value ){
      ">" => "<", "<" => ">", ">=" => "<=", "<=" => ">=",
        default => $this->value
    };

    return $this;
  }
}