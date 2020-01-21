<?php declare(strict_types=1);

namespace Madewithlove\Htaccess;

use Madewithlove\HtaccessResult;
use Madewithlove\ResultLine;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TableRenderer
{
    public function renderHtaccessResult(HtaccessResult $result, SymfonyStyle $io): void
    {
        $io->table(
            [
                'valid',
                'reached',
                'met',
                'line',
                'message',
            ],
            array_map(
                function (ResultLine $resultLine): array {
                    return [
                        $this->prettifyBoolean($resultLine->isValid()),
                        $this->prettifyBoolean($resultLine->wasReached()),
                        $this->prettifyBoolean($resultLine->isMet()),
                        $resultLine->getLine(),
                        $resultLine->getMessage(),
                    ];
                },
                $result->getLines()
            )
        );
    }

    public function renderMultipleLineResult(array $results, $io): void
    {
        $hasExpectedUrl = !empty(array_filter(
            $results,
            function (array $result) {
                return isset($result['expected url']);
            }
        ));

        $headers = [ 'url', 'output url' ];
        if ($hasExpectedUrl) {
            $headers = $headers + ['expected url', 'matches'];
        }

        $io->table(
            $headers,
            array_map(
                function (array $result): array {
                    if (isset($result['matches'])) {
                        $result['matches'] = $this->prettifyBoolean($result['matches']);
                    }

                    return $result;
                },
                $results
            )
        );
    }

    private function prettifyBoolean(bool $boolean): string
    {
        if ($boolean) {
            return '<info>✓</info>';
        }

        return '<fg=red>✗</>';
    }
}
