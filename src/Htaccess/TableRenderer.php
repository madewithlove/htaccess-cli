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

    private function prettifyBoolean(bool $boolean): string
    {
        if ($boolean) {
            return '<info>✓</info>';
        }

        return '<fg=red>✗</>';
    }
}
