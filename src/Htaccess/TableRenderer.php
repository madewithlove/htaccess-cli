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
                        $resultLine->isSupported()
                            ? $this->prettifyBoolean($resultLine->isValid())
                            : '<comment>?</comment>',
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

    /**
     * @param array<int, array{'url': string, 'output_url': string, 'status_code': ?int, 'expected url'?: string, 'matches'?: bool}> $results
     */
    public function renderMultipleLineResult(array $results, SymfonyStyle $io): void
    {
        $hasExpectedUrl = !empty(array_filter(
            $results,
            function (array $result) {
                return isset($result['expected url']);
            }
        ));

        $headers = [ 'url', 'output url', 'status code' ];
        if ($hasExpectedUrl) {
            $headers = array_merge($headers, ['expected url', 'matches']);
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
