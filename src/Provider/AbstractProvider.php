<?php

namespace PiedWeb\Google\Provider;

use PiedWeb\Google\Result\OrganicResult;

abstract class AbstractProvider
{
    abstract public function load(string $html): string;

    abstract public function get(string $url): string;

    abstract public function getNextPage(): string;

    abstract public function issetNextPage(): bool;
}
