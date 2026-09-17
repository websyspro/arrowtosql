<?php

namespace Websyspro\ArrowToSql\Enums;

enum DriverType
{
  case MySql;
  case Sqlite;
  case PostgreSQL;
  case SqlServer;
}