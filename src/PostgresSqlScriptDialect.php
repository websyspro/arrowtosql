<?php

namespace Websyspro\ArrowToSql;

class PostgresSqlScriptDialect
extends AbstractScriptDialect
{
  public function resolveMethodToDate(
    string $column
  ): string {
    return "Cast({$column} As Date)";
  }  
}