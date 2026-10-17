<?php

namespace Websyspro\ArrowToSql;

use Closure;

class JoinArrowToSql
{
  public function __construct(
    public Closure $closure
  ){}
}