<?php

namespace Websyspro\ArrowToSql\Interfaces;

use function sprintf;

class AppendResultScript
{
  public function __construct(
    public readonly string $table,
    public readonly string $fields,
    public readonly array $values,
    public readonly array $params,
  ){} 
  
  public function prepared(
  ): array {
    return [
      sprintf(
        "Insert Into {$this->table} {$this->fields} values%s"
      ), $this->params
    ];
  }
}