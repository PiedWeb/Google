<?php

namespace PiedWeb\Google\Puppeteer;

use Psr\Log\LoggerInterface;

class PuppeteerLogger implements LoggerInterface
{
    /**
     * @var string
     */
    final public const TO_INDEX = 'TOINDEX: ';

    /**
     * @var string
     */
    final public const KEY_VALUE_SEPARATOR = '::KEY%VALUE%SEPARATOR:: ';

    /** @var array<string, string> */
    private array $index = [];

    public function resetIndex(): void
    {
        $this->index = [];
    }

    /**
     * @return array<string, string>
     */
    public function getIndex(): array
    {
        return $this->index;
    }

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $expectedStarting = "Received a Node log: \n".self::TO_INDEX;

        if (! str_starts_with((string) $message, $expectedStarting)) {
            return;
        }

        $message = substr((string) $message, \strlen($expectedStarting));
        $messageExploded = explode(self::KEY_VALUE_SEPARATOR, $message, 2);

        if (2 !== \count($messageExploded)) {
            throw new \Exception($message);
        }

        $this->index[$messageExploded[0]] = $messageExploded[1];
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
    }
}
