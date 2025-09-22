<?php

namespace Utopia;

interface Model
{
    public static function fromArray(array $value): static;
}
