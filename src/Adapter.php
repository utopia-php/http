<?php

namespace Utopia;

use Utopia\Request;
use Utopia\Response;

abstract class Adapter {
    abstract public function getRequest(): Request;
    abstract public function getResponse(): Response;
}