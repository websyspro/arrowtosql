<?php

namespace Websyspro\Test\Imports;

class BoxImport {
	public static function rows(
	): array {
		return [
			[ "Id" => 1, "Name" => "CAIXA 1", "State" => "A", "OperatorId" => 4, "Printer" => "//localhost/Fiscal/", "OpeningAt" => "06/04/2023 10:11:31", "OpeningBalance" => "0,0000", "CreatedAt" => "23/03/2023 14:43:03" ],
			[ "Id" => 2, "Name" => "CAIXA_2", "State" => "A", "OperatorId" => 3, "Printer" => "//localhost/fiscal/", "OpeningAt" => "05/04/2023 08:28:28", "OpeningBalance" => "0,0000", "CreatedAt" => "01/04/2023 11:04:56" ],
			[ "Id" => 3, "Name" => "CAIXA_3", "State" => "A", "OperatorId" => 5, "Printer" => "//localhost/caixa/", "OpeningAt" => "03/04/2023 09:03:18", "OpeningBalance" => "0,0000", "CreatedAt" => "01/04/2023 11:05:12" ],
			[ "Id" => 4, "Name" => "CAIXA_4", "State" => "A", "OperatorId" => 6, "Printer" => "//localhost/fiscal/", "OpeningAt" => "05/04/2023 08:15:11", "OpeningBalance" => "0,0100", "CreatedAt" => "01/04/2023 11:07:34" ],
			[ "Id" => 5, "Name" => "CAIXA_5", "State" => "F", "OperatorId" => null, "Printer" => "//localhost/fiscal/", "OpeningAt" => "06/04/2023 07:32:05", "OpeningBalance" => "0,0100", "CreatedAt" => "01/04/2023 11:07:34" ],
			[ "Id" => 6, "Name" => "CAIXA_6", "State" => "F", "OperatorId" => null, "Printer" => "//192.168.11.156/fiscal/", "OpeningAt" => "04/04/2023 08:13:33", "OpeningBalance" => "0,0100", "CreatedAt" => "01/04/2023 11:07:34" ],
			[ "Id" => 7, "Name" => "CAIXA_7", "State" => "A", "OperatorId" => 9, "Printer" => "//localhost/fiscal/", "OpeningAt" => "06/04/2023 07:27:49", "OpeningBalance" => "0,0100", "CreatedAt" => "01/04/2023 11:07:34" ],
			[ "Id" => 8, "Name" => "CAIXA_8", "State" => "F", "OperatorId" => null, "Printer" => "//localhost/fiscal/", "OpeningAt" => "06/04/2023 08:07:07", "OpeningBalance" => "0,0000", "CreatedAt" => "01/04/2023 11:07:34" ],
			[ "Id" => 9, "Name" => "CAIXA_9", "State" => "F", "OperatorId" => null, "Printer" => "//localhost/fiscal/", "OpeningAt" => "06/04/2023 07:24:55", "OpeningBalance" => "0,0100", "CreatedAt" => "01/04/2023 11:07:34" ],
			[ "Id" => 99, "Name" => "CAIXA PRINCIPAL", "State" => "F", "OperatorId" => null, "Printer" => "//192.168.11.156/fiscal/", "OpeningAt" => "01/01/1970 00:00:00", "OpeningBalance" => "0,0000", "CreatedAt" => "23/03/2023 14:43:03" ]
		];
	}
}