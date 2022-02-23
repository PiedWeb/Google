<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PiedWeb\Google\Provider\Puphpeteer;
use PiedWeb\Google\Provider\PuphpeteerExtractor;

final class PuphpeteerProviderTest extends TestCase
{
    public function testIt(): void
    {
        $provider = new Puphpeteer();
        $extractor = new PuphpeteerExtractor($provider);
        $provider->instantiate();
        $rawHtml = $provider->get('https://www.google.fr/search?q=pied+web');

        $this->assertStringContainsString('piedweb.com', $rawHtml);

        $results = $extractor->getOrganicResults();
        $this->assertTrue(count($results) > 2);
    }
}
