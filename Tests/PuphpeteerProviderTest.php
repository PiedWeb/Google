<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PiedWeb\Google\Provider\Puphpeteer;

final class PuphpeteerProviderTest extends TestCase
{
    public function testIt(): void
    {
        $provider = new Puphpeteer();
        $provider->instantiate();
        $rawHtml = $provider->get('https://www.google.fr/search?q=pied+web');

        $this->assertStringContainsString('piedweb.com', $rawHtml);

        $results = $provider->getOrganicResults();
        $this->assertSame('https://piedweb.com/', $results[0]->url);

        $this->assertTrue(count($results) > 2);

        $this->assertTrue($provider->clickOn('https://piedweb.com/'));
    }
}
