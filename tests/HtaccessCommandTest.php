<?php declare(strict_types=1);

namespace Madewithlove;

use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\ServerRequestFactory;
use Madewithlove\HtaccessClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

final class HtaccessCommandTest extends TestCase
{
    /**
     * @var HtaccessCommand
     */
    private $command;

    public function setUp(): void
    {
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
    }

    /** @test */
    public function it does not run without url argument(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "url")');
        $commandTester->execute([]);
    }

    /** @test */
    public function it does not run without htaccess file available(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('We could not find an .htaccess file in the current directory');
        $commandTester->execute([
            'url' => 'http://localhost',
        ]);
    }

    /** @test */
    public function it does run(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "InvalidRule\nRewriteRule .* /foo"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
        ]);

        // it outputs the lines
        $this->assertStringContainsString(
            'InvalidRule',
            $commandTester->getDisplay()
        );
        $this->assertStringContainsString(
            'This line is not supported by our tool',
            $commandTester->getDisplay()
        );

        // it outputs the output url
        $this->assertStringContainsString(
            'The output url is "http://localhost/foo"',
            $commandTester->getDisplay()
        );
    }

    /** @test */
    public function it has exit status zero when expected url is correct(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteRule .* /foo"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--expected-url' => 'http://localhost/foo',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    /** @test */
    public function it has exit status one when expected url is incorrect(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteRule .* /foo"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--expected-url' => 'http://localhost/bar',
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
    }
}
