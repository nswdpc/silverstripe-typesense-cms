<?php

namespace NSWDPC\Typesense\CMS\Tests\TestOnly;

use Psr\Log\AbstractLogger;
use SilverStripe\Dev\TestOnly;
use Stringable;

/**
 * Captures messages passed to NSWDPC\Search\Typesense\Services\Logger::log() so tests can assert on
 * what was logged, without a real log destination. Register in place of Psr\Log\LoggerInterface via
 * Injector::inst()->registerService() in a test's setUp().
 */
class SpyLogger extends AbstractLogger implements TestOnly
{
    /**
     * @var array<int, array{level: mixed, message: string}>
     */
    public array $records = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
        ];
    }

    public function hasMessageContaining(string $needle): bool
    {
        foreach ($this->records as $record) {
            if (str_contains($record['message'], $needle)) {
                return true;
            }
        }

        return false;
    }
}
