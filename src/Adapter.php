<?php

namespace Utopia;

abstract class Adapter
{
    abstract public function getRequest(): Request;
    abstract public function getResponse(): Response;
}
