<?php declare(strict_types=1);

namespace Madewithlove;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class HtaccessCommand extends Command
{
    /**
     * @var HtaccessClient
     */
    private $htaccessClient;

    protected static $defaultName = 'htaccess';

    public function __construct(HtaccessClient $htaccessClient)
    {
        parent::__construct();

        $this->htaccessClient = $htaccessClient;
    }

    protected function configure()
    {
        $this->addArgument('url', InputArgument::REQUIRED, 'The request url to test your .htaccess file with');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $url = $input->getArgument('url');
        $htaccessFile = getcwd() . '/.htaccess';

        if (!file_exists($htaccessFile)) {
            throw new RuntimeException('We could not find an .htaccess file in the current directory');
        }

        $htaccess = file_get_contents(getcwd() . '/.htaccess');

        $result = $this->htaccessClient->test(
            $url,
            $htaccess
        );

        $io->success('The output url is "' . $result->getOutputUrl() . '"');
    }
}
