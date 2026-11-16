<?php

namespace Websyspro\ArrowToSql\Query;

/**
 * Coleção ordenada de parâmetros de uma consulta.
 * A ordem em que os parâmetros são adicionados deve ser a mesma
 * dos placeholders (?) no SQL final.
 */
class ParamBag
{
  private array $params = [];

  public function add(
    mixed $value
  ): static {
    $this->params[] = $value;
    return $this;
  }

  public function merge(
    array $params
  ): static {
    $this->params = array_merge( $this->params, $params );
    return $this;
  }

  public function all(
  ): array {
    return $this->params;
  }
}
