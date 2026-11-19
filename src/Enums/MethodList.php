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

  case Where = "where";
  case Select = "select";
  
  // ===========================================
  // FIELD METHODS (called on field directly)
  // ===========================================
  
  // COALESCE - Substitui NULL por valor padrão
  // Exemplo: $p->Email->coalesce("sem-email@exemplo.com")
  // SQL: COALESCE(Proposta.Email, 'sem-email@exemplo.com')
  case Coalesce = "coalesce";
  
  // SUBSTRING - Extrai parte de string (posição inicial, tamanho)
  // Exemplo: $p->Descricao->substring(1, 100)
  // SQL: SUBSTRING(Proposta.Descricao, 1, 100)
  case Substring = "substring";
  
  // ROUND - Arredonda número (casas decimais)
  // Exemplo: $p->Valor->round(2)
  // SQL: ROUND(Proposta.Valor, 2)
  case Round = "round";
  
  // FLOOR - Arredonda para baixo
  // Exemplo: $p->Preco->floor()
  // SQL: FLOOR(Proposta.Preco)
  case Floor = "floor";
  
  // CEIL - Arredonda para cima
  // Exemplo: $p->Preco->ceil()
  // SQL: CEIL(Proposta.Preco)
  case Ceil = "ceil";
  
  // YEAR - Extrai ano de data
  // Exemplo: $p->Created->year()
  // SQL: YEAR(Proposta.Created)
  case Year = "year";
  
  // MONTH - Extrai mês de data (1-12)
  // Exemplo: $p->Created->month()
  // SQL: MONTH(Proposta.Created)
  case Month = "month";
  
  // DAY - Extrai dia de data (1-31)
  // Exemplo: $p->Created->day()
  // SQL: DAY(Proposta.Created)
  case Day = "day";
  
  // DATE_FORMAT - Formata data customizada
  // Exemplo: $p->Created->dateFormat("%d/%m/%Y")
  // SQL: DATE_FORMAT(Proposta.Created, '%d/%m/%Y')
  case DateFormat = "dateFormat";
  
  // LENGTH - Retorna tamanho da string
  // Exemplo: $p->Nome->length()
  // SQL: LENGTH(Proposta.Nome)
  case Length = "length";
  
  // NULLIF - Retorna NULL se valores são iguais
  // Exemplo: $p->Email->nullif("")
  // SQL: NULLIF(Proposta.Email, '')
  case NullIf = "nullif";
  
  // JSON_EXTRACT - Extrai valor de campo JSON
  // Exemplo: $p->Metadata->jsonExtract("$.chave")
  // SQL: JSON_EXTRACT(Proposta.Metadata, '$.chave')
  case JsonExtract = "jsonExtract";
  
  // ===========================================
  // ENTITY METHODS (called on entity)
  // ===========================================
  
  // CASE WHEN - Condicional complexo (builder especial)
  // Exemplo: $p->case()->when($p->Status == 1, "Ativo")->else("Inativo")
  // SQL: CASE WHEN Status = 1 THEN 'Ativo' ELSE 'Inativo' END
  case CaseWhen = "case";
  
  // DISTINCT - Valores únicos/distintos
  // Exemplo: $p->distinct($p->Status)
  // SQL: DISTINCT Proposta.Status
  case Distinct = "distinct";
  
  // IF - Condicional simples (condição, valor_true, valor_false)
  // Exemplo: $p->if($p->Status == 1, "Sim", "Não")
  // SQL: IF(Proposta.Status = 1, 'Sim', 'Não')
  case If = "if";
}
