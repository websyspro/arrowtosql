<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\ColumnResultType;

class SqlServerScriptDialect
extends AbstractScriptDialect
{
  public function resolveMethodToDate(
    string $column,
    ColumnResultType $columnResultType
  ): string|array {
    return [ "Cast({$column} As Date)" ];
  }  
}