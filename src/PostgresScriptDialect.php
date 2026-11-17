<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Enums\ColumnResultType;

class PostgresScriptDialect
extends AbstractScriptDialect
{
  public function resolveMethodToDate(
    string $column
  ): string {
    return "Cast({$column} As Date)";
  }  
}