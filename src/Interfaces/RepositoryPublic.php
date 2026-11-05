<?php

namespace Websyspro\ArrowToSql\Interfaces;

use Closure;
use Websyspro\Utils\Collection;

/**
 * Interface exposta ao desenvolvedor.
 * Contém apenas os métodos que compõem a API pública do Repository.
 */
interface RepositoryPublic
{
  public function include( mixed ...$closures ): static;

  public function where( Closure $closure ): static;

  public function select( Closure $closure ): static;

  public function groupBy( Closure $closure ): static;

  public function orderBy( Closure $closure ): static;

  public function orderByAsc( Closure $closure ): static;

  public function orderByDesc( Closure $closure ): static;

  public function create( Closure|array $closureOrListArr ): bool;

  public function update( Closure $closure ): mixed;

  public function delete( Closure $closure ): mixed;

  public function paged( int $page, int $pageSize ): static;

  public function row(): mixed;

  public function rows(): Collection;
}
