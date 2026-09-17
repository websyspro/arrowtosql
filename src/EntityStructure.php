<?php

namespace Websyspro\ArrowToSql;

use ReflectionClass;

class EntityStructure
{
  private static array $cache = [];
  public readonly string $table;
  public readonly array $columns;
  public readonly array $primaryKeys;
  public readonly array $indexes;
  public readonly array $uniques;
  public readonly array $foreigns;

  public static function of(string $entityClass): static
  {
      if (!isset(static::$cache[$entityClass])) {
          static::$cache[$entityClass] = new static($entityClass);
      }
      return static::$cache[$entityClass];
  }

  public function __construct(string $entityClass)
  {
      $reflection = new ReflectionClass($entityClass);
      $entityAttr = $reflection->getAttributes(Entity::class)[0] ?? null;

      if ($entityAttr === null) {
        throw new \RuntimeException("Classe {$entityClass} não possui o atributo #[Entity]");
      }

      $this->table = $entityAttr->newInstance()->table;

      // Coleta propriedades da hierarquia completa (inclui BaseEntity)
      $properties = $reflection->getProperties();
      $parent     = $reflection->getParentClass();
      while ($parent) {
          $properties = array_merge($properties, $parent->getProperties());
          $parent     = $parent->getParentClass();
      }

      $seen       = [];
      $columns    = [];
      $primaryKeys = [];
      $indexes    = [];
      $uniques    = [];
      $foreigns   = [];

      foreach ($properties as $property) {
          $colAttr  = $property->getAttributes(Column::class)[0] ?? null;
          $type     = $property->getType();
          $typeName = $type instanceof ReflectionNamedType ? ltrim($type->getName(), '?') : null;

          if ($typeName === null || !is_subclass_of($typeName, ColumnType::class)) {
              continue;
          }

          $propName = $property->getName();

          // Evita duplicatas da hierarquia de classes
          if (isset($seen[$propName])) {
              continue;
          }
          $seen[$propName] = true;

          $colName       = $colAttr ? ($colAttr->newInstance()->name ?: $propName) : $propName;
          $isPK          = !empty($property->getAttributes(PrimaryKey::class));
          $isIndex       = !empty($property->getAttributes(Index::class));
          $isUnique      = !empty($property->getAttributes(Unique::class));
          $foreignAttr   = $property->getAttributes(Foreign::class)[0] ?? null;
          $foreignEntity = null;
          $foreignColumn = null;

          if ($foreignAttr !== null) {
              $foreign       = $foreignAttr->newInstance();
              $foreignEntity = $foreign->entity;
              $foreignColumn = $foreign->references;
          }

          $col = new EntityColumn(
              property:      $propName,
              column:        $colName,
              type:          (new \ReflectionClass($typeName))->getShortName(),
              isPrimaryKey:  $isPK,
              isIndex:       $isIndex,
              isUnique:      $isUnique,
              foreignEntity: $foreignEntity,
              foreignColumn: $foreignColumn,
          );

          $columns[] = $col;

          if ($isPK)             $primaryKeys[] = $col;
          if ($isIndex)          $indexes[]     = $col;
          if ($isUnique)         $uniques[]     = $col;
          if ($foreignEntity)    $foreigns[]    = $col;
      }

      $this->columns     = $columns;
      $this->primaryKeys = $primaryKeys;
      $this->indexes     = $indexes;
      $this->uniques     = $uniques;
      $this->foreigns    = $foreigns;
  }

  public function getColumn(string $columnName): ?EntityColumn
  {
      foreach ($this->columns as $col) {
          if (strtolower($col->column) === strtolower($columnName)) {
              return $col;
          }
      }
      return null;
  }

  public function getColumnNames(): array
  {
      return array_map(fn(EntityColumn $c) => $c->column, $this->columns);
  }

  public function getPrimaryKeyColumn(): ?EntityColumn
  {
      return $this->primaryKeys[0] ?? null;
  }
}