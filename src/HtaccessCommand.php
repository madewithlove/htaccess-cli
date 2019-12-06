<?php declare(strict_types=1);

namespace Madewithlove;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption('referrer', 'r', InputOption::VALUE_OPTIONAL, 'The referrer header, used as HTTP_REFERER in apache');
        $this->addOption('server-name', 's', InputOption::VALUE_OPTIONAL, 'The configured server name, used as SERVER_NAME in apache');
        $this->addOption('expected-url', 'e', InputOption::VALUE_OPTIONAL, 'When configured, errors when the output url does not equal this url');
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

        try {
            $result = $this->htaccessClient->test(
                $url,
                $htaccess,
                $input->getOption('referrer'),
                $input->getOption('server-name')
            );
        } catch (HtaccessException $exception) {
            $io->error($exception->getMessage());

            return 1;
        }

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
                        $this->booleanToEmoji($resultLine->isValid()),
                        $this->booleanToEmoji($resultLine->wasReached()),
                        $this->booleanToEmoji($resultLine->isMet()),
                        $resultLine->getLine(),
                        $resultLine->getMessage(),
                    ];
                },
                $result->getLines()
            )
        );

        if ($input->getOption('expected-url') && $result->getOutputUrl() !== $input->getOption('expected-url')) {
            $io->error('The output url is "' . $result->getOutputUrl() . '", while we expected "' . $input->getOption('expected-url') . '"');

            return 1;
        }

        $io->success('The output url is "' . $result->getOutputUrl() . '"');

        return 0;
    }

    private function booleanToEmoji(bool $boolean): string
    {
        if ($boolean) {
            return '<info>✓</info>';
        }

        return '<fg=red>✗</>';
    }
}
