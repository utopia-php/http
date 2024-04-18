<?php

namespace Utopia\Http;

use Utopia\Servers\Validator as ServersValidator;

abstract class Validator extends ServersValidator
{
    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'double'; /* gettype() returns 'double' for historical reasons */

    public const TYPE_STRING = 'string';

    public const TYPE_ARRAY = 'array';

    public const TYPE_OBJECT = 'object';

    public const TYPE_MIXED = 'mixed';
}
