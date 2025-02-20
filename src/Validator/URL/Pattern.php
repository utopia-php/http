<?php

namespace Utopia\Validator\URL;

class Pattern
{
    public array $schemes;
    public array $hosts;

    public function __construct(string|array $schemes = [], string|array $hosts = [])
    {
        if (!is_array($schemes)) {
            $schemes = [$schemes];
        }
        $this->schemes = $schemes;

        if (!is_array($hosts)) {
            $hosts = [$hosts];
        }
        $this->hosts = $hosts;
    }
}
