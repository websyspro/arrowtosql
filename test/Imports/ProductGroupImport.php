<?php

namespace Websyspro\Test\Imports;

class ProductGroupImport {
	public static function rows(
	): array {
		return [
			[ "Id" => 1, "Name" => "Eletrônicos", "CreatedAt" => "24/03/2023 13:43:41" ],
			[ "Id" => 2, "Name" => "Perfumaria", "CreatedAt" => "24/03/2023 13:43:48" ],
			[ "Id" => 3, "Name" => "Salão", "CreatedAt" => "24/03/2023 13:58:07" ],
			[ "Id" => 4, "Name" => "Pesca", "CreatedAt" => "24/03/2023 13:58:12" ],
			[ "Id" => 5, "Name" => "Automotivos", "CreatedAt" => "24/03/2023 13:58:57" ]
		];
	}
}