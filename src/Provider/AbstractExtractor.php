<?php

namespace PiedWeb\Google\Provider;

use PiedWeb\Google\Result\OrganicResult;

abstract class AbstractExtractor
{
    abstract public function getNbrResults(): int;

    /**
     * @return OrganicResult[]
     */
    abstract public function getOrganicResults(): array;
}
