<?php

namespace Websyspro\ArrowToSql\Interfaces;

class JoinDetail
{
  public function __construct(
    public string|null $type,
    public string|null $column = null,
    public string|null $alias = null,
    public string|null $aliasKey = null,
    public string|null $aliasReference = null,
    public string|null $aliasReferenceKey = null,
    public array|null $columnsSelecteds = [],
    public string|null $entityClass = null,
  ){}

  public function getScript(
  ): string|null {
    if( $this->aliasReference === null ){
      return null;
    }

    return sprintf( 
      "Left Join %s On %s.%s = %s.%s",
        $this->alias,
        $this->aliasReference,
        $this->aliasReferenceKey,
        $this->alias,
        $this->aliasKey
    );
  }  
}