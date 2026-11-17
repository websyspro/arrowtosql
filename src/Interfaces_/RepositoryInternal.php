<?php

namespace Websyspro\ArrowToSql\Interfaces;

use ReflectionClass;
use Websyspro\Entity\Schemas\AbstractEntityStructure;

/**
 * Interface de uso interno — exclusiva para ArrowToSql.
 * Não deve ser usada diretamente pelo desenvolvedor.
 */
interface RepositoryInternal
{
  public function getEntityStructureMatch(
    ReflectionClass $reflectionClass
  ): AbstractEntityStructure;

  public function getEntityStructure(
  ): AbstractEntityStructure;

  public function getEntityStructureByString(
    string $entity
  ): AbstractEntityStructure;

  public function registerIncludeLinear(
    JoinDetail $joinDetail
  ): JoinDetail;
}
