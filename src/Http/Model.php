<?php

namespace Utopia\Http;

interface Model
{
    public static function fromArray(array $value): static;
}
