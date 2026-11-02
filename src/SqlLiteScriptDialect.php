<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\ColumnResultType;
use Websyspro\ArrowToSql\Interfaces\ColumnResult;
use Websyspro\Entity\Interfaces\ColumnType;

class SqlLiteScriptDialect
extends AbstractScriptDialect
{
  public function resolveMethodToDate(
    string $column,
    ColumnResultType $columnResultType
  ): string|array {
    return [ "Cast({$column} As Date)" ];
  }  
}