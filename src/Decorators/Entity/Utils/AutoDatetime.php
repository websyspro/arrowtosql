<?php

namespace Websyspro\Server\Decorators\Entity\Utils;

class AutoDatetime
{
    public static function generate(): string
    {
        return date('Y-m-d H:i:s');
    }
}
