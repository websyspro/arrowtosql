<?php

namespace Websyspro\ArrowToSql\Interfaces;

use Websyspro\ArrowToSql\Enums\DriverType;

class ConnectDetails
{
  public function __construct(
    public readonly DriverType $driver,
    public readonly string $host,
    public readonly string $name,
    public readonly string $port,
    public readonly string $user,
    public readonly string $pass
  ){}
}