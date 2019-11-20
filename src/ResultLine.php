<?php declare(strict_types=1);

namespace Madewithlove;

final class ResultLine
{
    /**
     * @var string
     */
    private $line;

    /**
     * @var string
     */
    private $message;

    /**
     * @var bool
     */
    private $isMet;

    /**
     * @var bool
     */
    private $isValid;

    /**
     * @var bool
     */
    private $wasReached;

    public function __construct(string $line, string $message, bool $isMet, bool $isValid, bool $wasReached)
    {
        $this->line = $line;
        $this->message = $message;
        $this->isMet = $isMet;
        $this->isValid = $isValid;
        $this->wasReached = $wasReached;
    }

    public function getLine(): string
    {
        return $this->line;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isMet(): bool
    {
        return $this->isMet;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function wasReached(): bool
    {
        return $this->wasReached;
    }
}
