<?php

namespace PiedWeb\Google\Puppeteer;

class PuppeteerConnector
{
    /**
     * @var array<string, string>
     */
    public static array $wsEndpointList = [];

    private static string $lastWsEndpointUsed = '';

    public function close(): void
    {
        $id = (string) \Safe\getmypid();
        foreach (static::$wsEndpointList as $key => $wsEndpoint) {
            if (str_starts_with($key, $id)) {
                exec('PUPPETEER_WS_ENDPOINT='.escapeshellarg($wsEndpoint).' node '.escapeshellarg(__DIR__.'/closeBrowser.js'));
            }
        }
    }

    public function get(string $url): string
    {
        $wsEndpoint = $this->getWsEndpoint();

        $outputFileLog = sys_get_temp_dir().'/puppeteer-direct-'.\Safe\getmypid();
        $cmd = 'PUPPETEER_WS_ENDPOINT='.escapeshellarg($wsEndpoint).' '
            .'node '.escapeshellarg(__DIR__.'/scrap.js').' '
            .escapeshellarg($url)
            .' > '.escapeshellarg($outputFileLog);

        \Safe\exec($cmd);
        $rawOutput = \Safe\file_get_contents($outputFileLog); // going with file io to avoid truncated output

        return $rawOutput;
    }

    public static function screenshot(string $path, string $wsEndpoint = ''): void
    {
        $wsEndpoint = $wsEndpoint ?: self::$lastWsEndpointUsed ?: throw new \Exception();
        $cmd = 'PUPPETEER_WS_ENDPOINT='.escapeshellarg($wsEndpoint).' '
           .'node '.escapeshellarg(__DIR__.'/screenshot.js').' '
           .escapeshellarg($path);

        \Safe\exec($cmd);
    }

    /**
     * @param string $language if language or proxy are changed, a new chrome will be launched
     */
    public function __construct(public string $language = 'fr', public string $proxy = '')
    {
    }

    /**
     *  @psalm-suppress NullableReturnStatement
     *  @psalm-suppress InvalidNullableReturnType
     *
     * @return string could be empty if create = false and no endpoint match
     */
    public function getWsEndpoint(bool $create = true): string
    {
        $id = \Safe\getmypid().'-'.$this->language.'-'.$this->proxy;

        if (isset(static::$wsEndpointList[$id])) {
            self::$lastWsEndpointUsed = static::$wsEndpointList[$id];

            return static::$wsEndpointList[$id];
        }

        if (! $create) {
            return '';
        }

        $cmd = '';

        if ('' !== $this->proxy) {
            $cmd .= 'PROXY_GATE='.escapeshellarg($this->proxy).' ';
        }

        if (isset($_SERVER['PUPPETEER_HEADLESS'])) {
            $cmd .= 'PUPPETEER_HEADLESS='.$_SERVER['PUPPETEER_HEADLESS'].' '; // @phpstan-ignore-line
        }

        $outputFileLog = sys_get_temp_dir().'/puppeteer-direct-'.$id;
        $cmd .= 'node '.escapeshellarg(__DIR__.'/launchBrowser.js').' '.escapeshellarg($this->language)
                    .' > '.escapeshellarg($outputFileLog).' 2>&1 &';
        \Safe\exec($cmd);
        for ($i = 0; $i < 5; ++$i) {
            sleep(1);
            static::$wsEndpointList[$id] = trim((string) file_get_contents($outputFileLog));
            if ('' !== static::$wsEndpointList[$id]) {
                break;
            }
        }

        register_shutdown_function([$this, 'close']);

        self::$lastWsEndpointUsed = static::$wsEndpointList[$id];

        return static::$wsEndpointList[$id];
    }
}
