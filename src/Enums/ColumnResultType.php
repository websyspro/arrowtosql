<?php

namespace Websyspro\ArrowToSql\Enums;

enum ColumnResultType
{
  case Select;
  case Where;
  case GroupBy;
  case OrderBy;
  case None;
}