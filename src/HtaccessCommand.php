<?php declare(strict_types=1);

namespace Madewithlove;

use InvalidArgumentException;
use Madewithlove\Htaccess\TableRenderer;
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
    protected static $defaultName = 'htaccess';

    public function __construct(
        private HtaccessClient $htaccessClient,
        private TableRenderer $tableRenderer
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::OPTIONAL, 'The request url to test your .htaccess file with');
        $this->addOption('referrer', 'r', InputOption::VALUE_REQUIRED, 'The referrer header, used as HTTP_REFERER in apache');
        $this->addOption('server-name', 's', InputOption::VALUE_REQUIRED, 'The configured server name, used as SERVER_NAME in apache');
        $this->addOption('http-user-agent', null, InputOption::VALUE_REQUIRED, 'The User Agent header, used as HTTP_USER_AGENT in apache');
        $this->addOption('expected-url', 'e', InputOption::VALUE_REQUIRED, 'When configured, errors when the output url does not equal this url');
        $this->addOption('share', null, InputOption::VALUE_NONE, 'When passed, you\'ll receive a share url for your test run');
        $this->addOption('url-list', 'l', InputOption::VALUE_REQUIRED, 'Location of the yaml file containing your url list');
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to the working directory you want to test in.');
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
                $share = $this->htaccessClient->share($url, $htaccess, $this->getServerVariables($input));
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
        $result = $this->htaccessClient->test($url, $htaccess, $this->getServerVariables($input));

        if ($io) {
            $this->tableRenderer->renderHtaccessResult($result, $io);
        }

        return $result;
    }

    private function getServerVariables(InputInterface $input): ServerVariables
    {
        $serverVariables = ServerVariables::default();
        if ($referrer = $input->getOption('referrer')) {
            /** @var string $referrer */
            $serverVariables = $serverVariables->with('HTTP_REFERER', $referrer);
        }

        if ($serverName = $input->getOption('server-name')) {
            /** @var string $serverName */
            $serverVariables = $serverVariables->with('SERVER_NAME', $serverName);
        }

        if ($httpUserAgent = $input->getOption('http-user-agent')) {
            /** @var string $httpUserAgent */
            $serverVariables = $serverVariables->with('HTTP_USER_AGENT', $httpUserAgent);
        }

        return $serverVariables;
    }
}
