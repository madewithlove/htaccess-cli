<?php declare(strict_types=1);

namespace Madewithlove;

use Madewithlove\Htaccess\TableRenderer;
use Madewithlove\HtaccessResult;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException as SymfonyRuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

final class HtaccessCommand extends Command
{
    /**
     * @var HtaccessClient
     */
    private $htaccessClient;

    /**
     * @var TableRenderer
     */
    private $tableRenderer;

    protected static $defaultName = 'htaccess';

    public function __construct(HtaccessClient $htaccessClient, TableRenderer $tableRenderer)
    {
        parent::__construct(self::$defaultName);

        $this->htaccessClient = $htaccessClient;
        $this->tableRenderer = $tableRenderer;
    }

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::OPTIONAL, 'The request url to test your .htaccess file with');
        $this->addOption('referrer', 'r', InputOption::VALUE_OPTIONAL, 'The referrer header, used as HTTP_REFERER in apache');
        $this->addOption('server-name', 's', InputOption::VALUE_OPTIONAL, 'The configured server name, used as SERVER_NAME in apache');
        $this->addOption('expected-url', 'e', InputOption::VALUE_OPTIONAL, 'When configured, errors when the output url does not equal this url');
        $this->addOption('share', null, InputOption::VALUE_NONE, 'When passed, you\'ll receive a share url for your test run');
        $this->addOption('url-list', 'l', InputOption::VALUE_OPTIONAL, 'Location of the yaml file containing your url list');
        $this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Path to the working directory you want to test in.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->validateInput($input);

        /** @var string $url */
        $url = $input->getArgument('url');

        /** @var string $path */
        $path = $input->getOption('path') ? $input->getOption('path') : getcwd();

        /** @var string $htaccess */
        $htaccess = file_get_contents($path . '/.htaccess');

        if ($url) {
            return $this->testSingleUrl($url, $htaccess, $input, $io);
        } else {
            /** @var string $urlListFile */
            $urlListFile = $input->getOption('url-list');
            /** @var string[] $urls */
            $urls = Yaml::parse((string) file_get_contents($path . '/' . $urlListFile));

            $results = [];

            foreach ($urls as $url => $expectedUrl) {
                $hasExpectedUrl = !is_int($url);
                if (!$hasExpectedUrl) {
                    $url = $expectedUrl;
                }

                $htaccessResult = $this->test($url, $htaccess, $input);

                $result = [
                    'url' => $url,
                    'output_url' => $htaccessResult->getOutputUrl(),
                    'status_code' => $htaccessResult->getOutputStatusCode(),
                ];

                if ($hasExpectedUrl) {
                    $result['expected url'] = $expectedUrl;
                    $result['matches'] = $expectedUrl === $result['output_url'];
                }

                $results[] = $result;
            }

            $this->tableRenderer->renderMultipleLineResult($results, $io);

            $resultsFailingExpectations = array_filter(
                $results,
                function (array $result) {
                    return isset($result['matches']) && $result['matches'] === false;
                }
            );

            if (!empty($resultsFailingExpectations)) {
                $io->error('Not all output urls matched the expectations');

                return 1;
            }
        }

        return 0;
    }

    private function testSingleUrl(string $url, string $htaccess, InputInterface $input, SymfonyStyle $io): int
    {
        try {
            $result = $this->test($url, $htaccess, $input, $io);
        } catch (HtaccessException $exception) {
            $io->error($exception->getMessage());

            return 1;
        }

        if ($input->getOption('share')) {
            try {
                /** @var ?string $referrer */
                $referrer = $input->getOption('referrer');
                /** @var ?string $serverName */
                $serverName = $input->getOption('server-name');

                $share = $this->htaccessClient->share($url, $htaccess, $referrer, $serverName);

                $io->text('You can share this test run on ' . $share->getShareUrl());
            } catch (HtaccessException $exception) {
                // when sharing failed, just ignore it
            }
        }

        /** @var ?string $expectedUrl */
        $expectedUrl = $input->getOption('expected-url');

        if ($expectedUrl && $result->getOutputUrl() !== $expectedUrl) {
            $io->error('The output url is "' . $result->getOutputUrl() . '", while we expected "' . $expectedUrl . '"');

            return 1;
        }

        $io->success('The output url is "' . $result->getOutputUrl() . '"');

        return 0;
    }

    private function validateInput(InputInterface $input): void
    {
        /** @var ?string $url */
        $url = $input->getArgument('url');
        /** @var ?string $urlList */
        $urlList = $input->getOption('url-list');
        /** @var ?string $path */
        $path = $input->getOption('path') ? $input->getOption('path') : getcwd();


        if (is_null($urlList) && is_null($url)) {
            throw new SymfonyRuntimeException('Not enough arguments (missing: "url")');
        }

        if ($urlList && $url) {
            throw new SymfonyRuntimeException('You cannot use a url list together with a regular url');
        }

        if ($urlList) {
            $urlList = $path . '/' . $urlList;
            if (!file_exists($urlList)) {
                throw new SymfonyRuntimeException('We could not load the specified url list.');
            }
        }

        $htaccessFile = $path . '/.htaccess';
        if (!file_exists($htaccessFile)) {

            throw new RuntimeException('We could not find an .htaccess file in the current directory');
        }
    }

    private function test(string $url, string $htaccess, InputInterface $input, ?SymfonyStyle $io = null): HtaccessResult
    {
        /** @var ?string $referrer */
        $referrer = $input->getOption('referrer');
        /** @var ?string $serverName */
        $serverName = $input->getOption('server-name');

        $result = $this->htaccessClient->test($url, $htaccess, $referrer, $serverName);

        if ($io) {
            $this->tableRenderer->renderHtaccessResult($result, $io);
        }

        return $result;
    }
}
