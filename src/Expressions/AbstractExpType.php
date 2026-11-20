<?php

namespace Websyspro\ArrowToSql\Expressions;

class AbstractExpType
{
  public string $object;

  public function __construct(
  ){
    $this->object = basename(
      str_replace( "\\", "/", static::class)
    );
  }

  public function get(
  ): string {
    return $this->object;
  }
}