<?php

namespace Websyspro\ArrowToSql\Interfaces;

use Websyspro\Server\Commons\Collection;
use Websyspro\ArrowToSql\Connection;

use function sprintf;

class CommandScript
{
  public function __construct(
    public readonly Collection|string $command,
    public readonly string|null $message = null
  ){}

  public function execute(
  ): void {
    Connection::execute(
      $this->command
    );
  }
}