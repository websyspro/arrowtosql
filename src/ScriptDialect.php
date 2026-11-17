<?php

namespace Websyspro\ArrowToSql;

use Websyspro\Connection\Database;
use Websyspro\Connection\Enums\DriverType;

class ScriptDialect
{
  public static function get(
  ): AbstractScriptDialect {
    return match( Database::driver()){
      DriverType::MySql => new MySqlScriptDialect(),
      DriverType::SqLite => new SqlLiteScriptDialect(),
      DriverType::Postgres => new PostgresScriptDialect(),
      DriverType::SqlServer => new SqlServerScriptDialect()
    };    
  }
}