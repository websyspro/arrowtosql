<?php

use Websyspro\Connection\Enums\DriverType;

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

if( defined( "CONNECT_DETAILS" ) === false ){
  define( "CONNECT_DETAILS", CONNECT_DETAILS_MYSQL );
}