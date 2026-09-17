<?php

use Websyspro\ArrowToSql\Enums\DriverType;

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

if( defined( "CONNECT_DETAILS_SQLSERVER" ) === false ){
  define( "CONNECT_DETAILS_SQLSERVER", (object)[
    "driver" => DriverType::SqlServer,
    "host" =>  "localhost",
    "port" =>  "1433",
    "name" =>  "app",
    "user" =>  "sa",
    "pass" =>  "Qazwsx@123"    
  ]);
}

if( defined( "CONNECT_DETAILS_POSTGRESSQL" ) === false ){
  define( "CONNECT_DETAILS_POSTGRESSQL", (object)[
    "driver" => DriverType::PostgreSQL,
    "host" => "localhost",
    "port" => "5434",
    "name" => "app",
    "user" => "root",
    "pass" => "Qazwsx@123"
  ]);
}

if( defined( "CONNECT_DETAILS" ) === false ){
  define( "CONNECT_DETAILS", CONNECT_DETAILS_MYSQL );
}