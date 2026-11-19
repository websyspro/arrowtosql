<?php

namespace Websyspro\ArrowToSql;

use Websyspro\ArrowToSql\Expressions\ExpField;
use Websyspro\ArrowToSql\Expressions\ExpFieldMethod;
use Websyspro\Entity\Decorators\EntityList;

class ExpressionSelectUtils
{
  public static function createASTExpField(
    array $tokens,
    Parser $parser,
    Repository $repository
  ): ExpField {
    $entityStructureColumn = $tokens[2]->value;
    $columnMethods = ParserUtils::mapperStatic(
      ParserUtils::groupByTypesStatic(
        [ T_OBJECT_OPERATOR ], ParserUtils::sliceStatic(
          $tokens, 4
        )
      ), fn( array $closure ) => (
        new ExpFieldMethod(
          $closure[0]->value, 
          ExpressionWhereUtils::fieldMethodType( $closure[0]->value ),
          ParserUtils::sliceStatic( $closure, 2, -1 )
        )
      )
    );

    $entityStructure = $repository->getEntityStructureByString(
      $parser->scopes[ $tokens[0]->value ]
    );

    $columnType = $entityStructure->getColumnType( $entityStructureColumn );
    $columnName = ( $columnType === EntityList::class ) 
      ? $entityStructureColumn : $entityStructure->getColumnName( $entityStructureColumn );

    return new ExpField(
      $entityStructure->getEntityAlias(),
      $columnName, $columnType, $columnMethods
    );
  }  
}