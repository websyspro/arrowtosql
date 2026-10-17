<?php

namespace Websyspro\ArrowToSql\Enums;

enum JoinType:string
{
  case Inner = "Inner Join";
  case Right = "Right Join";
  case Left = "Left Join";
}