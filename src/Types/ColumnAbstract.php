<?php

namespace Websyspro\ArrowToSql\Types;

abstract class ColumnAbstract
{
  protected mixed $value = null;
  protected string $columnName = '';

  public function __get(
    string $name
  ): mixed {
    return $this->$name ?? null;
  }

  public function __set(
    string $name,
    mixed $value
  ): void {
    $this->$name = $value;
  }

  public function setValue(
    mixed $value
  ): static {
    $this->value = $value;
    return $this;
  }

  public function setColumnName(
    string $name
  ): static {
    $this->columnName = $name;
    return $this;
  }

  public function getValue(
  ): mixed {
    return $this->value;
  }
}
