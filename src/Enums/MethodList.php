<?php

namespace Websyspro\ArrowToSql\Enums;

enum MethodList:string
{
  case Between = "between";
  case Contains = "contains";
  case StartsWith = "startsWith";
  case EndsWith = "endsWith";
  case ToDate = "toDate";  

  case Trim = "trim";
  case Lower = "lower";
  case Upper = "upper";
  case In = "in";
  case NotIn = "notIn";
  case Equals = "equals";
  case Count = "count";
  case Sum = "sum";
  case Avg = "avg";
  case Min = "min";
  case Max = "max";
  case Concat = "concat";
  case Exists = "exists";
  case As = "as";
}