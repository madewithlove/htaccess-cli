<?php declare(strict_types=1);

namespace Madewithlove;

use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\ServerRequestFactory;
use Madewithlove\HtaccessClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

final class MultipleUrlsTest extends TestCase
{
    /**
     * @var HtaccessCommand
     */
    private $command;

    public function setUp(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteRule .* /foo"
        );

        $htaccessClient = new HtaccessClient(
            Client::createWithConfig([
                'headers' => [
                    'User-Agent' => 'HtaccessCli',
                ],
            ]),
            new ServerRequestFactory()
        );

        $this->command = new HtaccessCommand($htaccessClient);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        @unlink(getcwd() . '/.htaccess');
        @unlink(getcwd() . '/test-urls.yaml');
    }

    /** @test */
    public function it does work if the passed url list is not available(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('We could not load the specified url list.');
        $commandTester->execute([
            '--url-list' => 'test-urls.yaml',
        ]);
    }

    /** @test */
    public function it throws when passing both a url and a url list(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use a url list together with a regular url');
        $commandTester->execute([
            'url' => 'http://localhost',
            '--url-list' => 'test-urls.yaml',
        ]);
    }

    /** @test */
    public function it does run with multiple urls(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteRule (.*) /foo/$1"
        );
        file_put_contents(
            getcwd() . '/test-urls.yaml',
            "- http://localhost/test
- http://localhost/bar"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            '--url-list' => 'test-urls.yaml',
        ]);

        // it outputs the output urls
        $this->assertStringContainsString(
            'http://localhost/foo/test',
            $commandTester->getDisplay()
        );
        $this->assertStringContainsString(
            'http://localhost/foo/bar',
            $commandTester->getDisplay()
        );

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
