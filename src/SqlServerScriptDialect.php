<?php

namespace Websyspro\ArrowToSql;

class SqlServerScriptDialect
extends AbstractScriptDialect
{
  public function resolveMethodToDate(
    string $column
  ): string {
    return "Cast({$column} As Date)";
  }  
}