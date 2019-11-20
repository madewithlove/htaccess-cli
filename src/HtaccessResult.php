<?php declare(strict_types=1);

namespace Madewithlove;

final class HtaccessResult
{
    /**
     * @var string
     */
    private $outputUrl;

    /**
     * @var ResultLine[]
     */
    private $lines = [];

    public function __construct(string $outputUrl, array $lines)
    {
        $this->outputUrl = $outputUrl;

        foreach ($lines as $line) {
            $this->addLine($line);
        }
    }

    public function getOutputUrl(): string
    {
        return $this->outputUrl;
    }

    /**
     * @return ResultLine[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    private function addLine(ResultLine $line): void
    {
        $this->lines[] = $line;
    }
}
