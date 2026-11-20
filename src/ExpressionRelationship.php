<?php

namespace Websyspro\ArrowToSql;

class ExpressionRelationship
{
  public string|null $alias = null;
  public string|null $aliasKey = null;
  public string|null $aliasReference = null;
  public string|null $aliasReferenceKey = null;

  public function __construct(
    private Repository $repository,
    private Repository $repositoryParent
  ){
    $this->createRelationship();
  }

  private function createRelationship(
  ): void {
    $entityChild = $this->repository->getEntityStructure();
    $entityParent = $this->repositoryParent->getEntityStructure();
    
    $this->alias = $entityChild->getEntityAlias();
    $this->aliasReference = $entityParent->getEntityAlias();

    $fkFromChildToParent = $this->findForeignKey( $entityChild, $entityParent );
    
    if( $fkFromChildToParent !== null ){
      $this->aliasKey = $fkFromChildToParent['column'];
      $this->aliasReferenceKey = $fkFromChildToParent['references'];
      return;
    }

    $fkFromParentToChild = $this->findForeignKey( $entityParent, $entityChild );
    
    if( $fkFromParentToChild !== null ){
      $this->aliasKey = $entityChild->getPrimaryKey();
      $this->aliasReferenceKey = $fkFromParentToChild['column'];
    }
  }

  private function findForeignKey(
    $entitySource,
    $entityTarget
  ): array|null {
    foreach( $entitySource->foreignKeys->items as $column => $fk ){
      if( $fk->entity->alias === $entityTarget->getEntityAlias() ){
        return [
          'column' => $column,
          'references' => $fk->references
        ];
      }
    }

    return null;
  }

  public function get(
  ): string|null {
    if( $this->aliasReference === null || $this->alias === null ){
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