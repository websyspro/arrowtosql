<?php

namespace Websyspro\ArrowToSql;

class SqlLiteScriptDialect
extends AbstractScriptDialect
{
  public function resolveMethodToDate(
    string $column
  ): string {
    return "Cast({$column} As Date)";
  }  
}