<?php

namespace Websyspro\ArrowToSql\Interfaces;

class AppendResultList
{
  public array $values = [];
  public array $params = [];

  public function __construct(
    public readonly string $table,
    public readonly string $fields, 
    public array $appendResults = []
  ){
    foreach( $this->appendResults as $appendResults ){
      $this->values[] = implode( ",", array_map( fn(AppendResult $appendResult) => $appendResult->values, $appendResults ));
      $this->params[] = array_reduce(
        $appendResults, fn( array $currentArr, AppendResult $appendResult) =>
          [ ...$currentArr, ...$appendResult->params ],
        []
      );
    }

    unset( $this->appendResults );
  }
}