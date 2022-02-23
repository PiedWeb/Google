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

final class Puphpeteer extends AbstractProvider
{
    public static ?Puppeteer $puppeteer;

    public static ?Browser $browser;

    public static ?Page $browserPage;

    public static string $pageContent = '';

    public const NEXT_PAGE_SELECTOR = [
        '[aria-label="Voir plus"]',
        '[aria-label="Plus de résultats"]',
        '[aria-label="Page suivante"]',
    ];

    public const DEFAULT_LANGUAGE = 'en-US';

    public const DEFAULT_EMULATE_OPTIONS = [
        'viewport' => [
            'width' => 412,
            'height' => 992,
            'deviceScaleFactor' => 3,
            'isMobile' => true,
            'hasTouch' => true,
            'isLandscape' => false,
        ],
        'userAgent' => 'Mozilla/5.0 (Linux; Android 10; SM-A305N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.210 Mobile Safari/537.36',
    ];

    /**
     * @param array<string, string> $emulateOptions array{ viewport: mixed, userAgent: string }
     */
    public function instantiate(array $emulateOptions = [], string $language = ''): void
    {
        self::$puppeteer = new Puppeteer([
            'headless' => true,
            'read_timeout' => 9000,
            'idle_timeout' => 9000,
            'args' => ['--lang='.($language ?: self::DEFAULT_LANGUAGE)],
        ]);
        self::$browser = self::$puppeteer->launch();  // @phpstan-ignore-line
        self::$browserPage = self::$browser->newPage();
        $this->getBrowserPage()->emulate($emulateOptions ?: self::DEFAULT_EMULATE_OPTIONS); // @phpstan-ignore-line
    }

    public function getBrowserPage(): Page
    {
        if (null === self::$browserPage) {
            throw new LogicException();
        }

        return self::$browserPage;
    }

    public function close(): void
    {
        if (null === self::$browser) {
            throw new LogicException();
        }

        Logger::log('close chrome');
        self::$browser->close();

        self::$puppeteer = null;
        self::$browser = null;
        self::$browserPage = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function load(string $html, string $from = ''): string
    {
        if ($from !== '')
        $html = str_replace('<head>', '<head><base href="'.$from.'">', $html);

        $this->getBrowserPage()->setContent($html);
        self::$pageContent = $this->getBrowserPage()->content();

        return self::$pageContent;
    }

    public function get(string $url): string
    {
        $this->getBrowserPage()->goto($url, ['waitUntil' => 'domcontentloaded']); // @phpstan-ignore-line
        self::$pageContent = $this->getBrowserPage()->content();

        $this->manageMetaRefresh(pathinfo($url)['dirname']);
        $this->manageCookieConsent();
        $this->manageCookieConsentForMobile();

        self::$pageContent = $this->getBrowserPage()->content();

        return self::$pageContent;
    }

    public function getNextPage(): string
    {
        if (! $this->issetNextPage()) {
            throw new Exception('next page not found');
        }

        $this->getBrowserPage()->click(self::NEXT_PAGE_SELECTOR); // @phpstan-ignore-line
        $this->getBrowserPage()->waitForNavigation();
        self::$pageContent = $this->getBrowserPage()->content();

        return self::$pageContent;
    }

    public function issetNextPage(): bool
    {
        return $this->elementExists(implode(',', self::NEXT_PAGE_SELECTOR));
    }

    private function manageMetaRefresh(string $base = ''): void
    {
        if ($this->elementExists('[http-equiv=refresh]')) {
            //dd($this->getBrowserPage()->querySelectorEval('[http-equiv=refresh]', 'a => a.content'));
            $this->getBrowserPage()->waitForNavigation();
            Logger::log('follow redirection');
            $this->manageMetaRefresh($base);
        }
    }

    private function manageCookieConsent(): void
    {
        if ($this->elementExists('[aria-label*=cookie]')) {
            $this->getBrowserPage()->click('[aria-label*=cookie]');
            $this->getBrowserPage()->waitForNavigation();
            self::$pageContent = $this->getBrowserPage()->content();
            Logger::log('cookie consent');
        }
    }

    private function manageCookieConsentForMobile(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $this->getBrowserPage()->waitForTimeout(rand(100, 200));
            if (! $this->elementExists('button div img[src^="htt"]')) {
                break;
            }
            Logger::log('Cookie consent for mobile: click `v`');

            try {
                $this->getBrowserPage()->tryCatch->click('button div img[src^="htt"]'); // @phpstan-ignore-line
            } catch (Exception $e) {
                Logger::log('Error on click on `button div img[src^="htt"]`');
                Logger::log($e->getMessage());

                break;
            }
        }

        if ($this->elementExists('button:nth-of-type(2n) div[role="none"]')) {
            Logger::log('Cookie consent for mobile: click `J\'accepte`');
            $this->getBrowserPage()->click('button:nth-of-type(2n) div[role="none"]');
        } else {
            Logger::log('Cookie consent accept button not found...');
        }
    }

    public function elementExists(string $selector): bool
    {
        if (\count($this->getBrowserPage()->querySelectorAll($selector)) > 0) {
            return true;
        }

        return false;
    }

    public function getPageContent(): string
    {
        if (! self::$pageContent) {
            throw new Exception('you must execture a request before (Puphpeteer::get).');
        }

        return self::$pageContent;
    }

    public function clickOn(string $url): bool
    {
        $selector = 'a[href="'.$url.'"][oncontextmenu]';

        if (! $this->elementExists('a[href="'.$url.'"][oncontextmenu]')) {
            return false;
        }

        $this->getBrowserPage()->waitForTimeout(2000);
        $this->getBrowserPage()->focus($selector);
        $this->getBrowserPage()->waitForTimeout(200);
        $this->getBrowserPage()->click($selector);

        return true;
    }
}
