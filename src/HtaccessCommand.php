<?php declare(strict_types=1);

namespace Madewithlove;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HtaccessCommand extends Command
{
    protected static $defaultName = 'run';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello!');
    }
}
