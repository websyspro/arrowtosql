<?php

namespace Websyspro\ArrowToSql\Interfaces;

class Precisions
{
  public function __construct(
    public readonly int $precision,
    public readonly int $scale
  ){}
}