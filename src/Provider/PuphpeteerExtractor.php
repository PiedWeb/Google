<?php

namespace PiedWeb\Google\Provider;

use LogicException;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Browser;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;
use Nesk\Rialto\Exceptions\Node\Exception;
use PiedWeb\Google\Logger;
use PiedWeb\Google\Result\OrganicResult;

final class PuphpeteerExtractor
{
    public  Puphpeteer $puppeteer;

    public function __construct(Puphpeteer $puppeteer)
    {
        $this->puppeteer = $puppeteer;
    }

    public function getNbrResults(): int
    {
        $this->puppeteer->getPageContent();

        if (! $this->puppeteer->elementExists('#resultStats')) {
            return 0;
        }

        $resultsNumberBlock = $this->puppeteer->getBrowserPage()->querySelector('#resultStats')->evaluate(JsFunction::createWithParameters(['node']) // @phpstan-ignore-line
                    ->body('return node.innerText'));

        return (int) (preg_replace('/[^0-9]/', '', $resultsNumberBlock));
    }

    /**
     * @return OrganicResult[]
     */
    public function getOrganicResults(): array
    {
        $this->puppeteer->getPageContent();

        $selector = 'a[oncontextmenu] [role="heading"]';
        if (! $this->puppeteer->elementExists($selector)) {
            Logger::log($selector.' not found');

            return [];
        }

        $toReturn = [];
        $results = $this->puppeteer->getBrowserPage()->querySelectorAll($selector);
        foreach ($results as $k => $result) {
            $toReturn[$k] = new OrganicResult();
            $toReturn[$k]->pos = $k + 1;
            $toReturn[$k]->pixelPos = $result->boundingBox()['y']; // @phpstan-ignore-line
            $toReturn[$k]->url = $result->querySelectorXPath('..')[0] // @phpstan-ignore-line
                ->evaluate(JsFunction::createWithParameters(['node']) // @phpstan-ignore-line
                    ->body('return node.href'));
            $toReturn[$k]->anchor = $result->evaluate(JsFunction::createWithParameters(['node']) // @phpstan-ignore-line
                    ->body('return node.innerText'));
        }

        return array_values($toReturn);
    }
}
