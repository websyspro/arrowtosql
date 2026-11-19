<?php

namespace Websyspro\ArrowToSql\Expressions;

class AbstractExpTypeSelect
{
  public string $object;

  public function __construct(
  ){
    $this->object = basename(
      str_replace( "\\", "/", static::class)
    );
  }
}