<?php

namespace Websyspro\ArrowToSql;

class PostgresScriptDialect
extends AbstractScriptDialect
{
  public function resolveMethodToDate(
    string $column
  ): string {
    return "Cast({$column} As Date)";
  }  
}