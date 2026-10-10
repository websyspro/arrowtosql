<?php

use Websyspro\Connection\Enums\DriverType;

header('Content-Type: application/json; charset=utf-8');

if( defined( "CONNECT_DETAILS_MYSQL" ) === false ){
  define( "CONNECT_DETAILS_MYSQL", (object)[
    "driver" => DriverType::MySql, 
    "host" => "localhost", 
    "port" => "3308", 
    "name" => "app",
    "user" => "root", 
    "pass" => "Qazwsx@123"
  ]);
}

if( defined( "CONNECT_DETAILS_POSTGRES" ) === false ){
  define( "CONNECT_DETAILS_POSTGRES", (object)[
    "driver" => DriverType::PostgreSQL, 
    "host" => "localhost", 
    "port" => "5434", 
    "name" => "app",
    "user" => "root", 
    "pass" => "Qazwsx@123"
  ]);
}

if( defined( "CONNECT_DETAILS_SQL_SERVER" ) === false ){
  define( "CONNECT_DETAILS_SQL_SERVER", (object)[
    "driver" => DriverType::SqlServer, 
    "host" => "localhost", 
    "port" => "1433", 
    "name" => "app",
    "user" => "sa", 
    "pass" => "Qazwsx@123"
  ]);
}

if( defined( "CONNECT_DETAILS_SQLITE" ) === false ){
  define( "CONNECT_DETAILS_SQLITE", (object)[
    "driver" => DriverType::Sqlite, 
    "name" => "db.sqlite"
  ]);
}

if( defined( "CONNECT_DETAILS" ) === false ){
  define( "CONNECT_DETAILS", CONNECT_DETAILS_SQLITE );
}