<?php declare(strict_types=1);

namespace Madewithlove;

final class HtaccessResult
{
    /**
     * @var string
     */
    private $outputUrl;

    public function __construct(string $outputUrl)
    {
        $this->outputUrl = $outputUrl;
    }

    public function getOutputUrl(): string
    {
        return $this->outputUrl;
    }
}
