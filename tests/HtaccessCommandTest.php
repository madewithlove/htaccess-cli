<?php declare(strict_types=1);

namespace Madewithlove;

use Http\Adapter\Guzzle7\Client;
use Http\Factory\Guzzle\ServerRequestFactory;
use Madewithlove\Htaccess\TableRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

final class HtaccessCommandTest extends TestCase
{
    private HtaccessCommand $command;

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

        $this->command = new HtaccessCommand($htaccessClient, new TableRenderer());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        @unlink(getcwd() . '/.htaccess');
        @unlink(getcwd() . '/tests/.htaccess');
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

    /** @test */
    public function it is possible to share a test run(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteRule .* /foo"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--share' => true,
        ]);

        $this->assertStringContainsString(
            'You can share this test run on https://htaccess.madewithlove.com?share=',
            $commandTester->getDisplay()
        );
    }

    /** @test */
    public function it can specify a custom path to the htaccess file(): void
    {
        file_put_contents(
            getcwd() . '/tests/.htaccess',
            "RewriteRule .* /foo [R=302]"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--path' => getcwd() . '/tests',
        ]);

        $this->assertStringContainsString(
            'The output url is "http://localhost/foo"',
            $commandTester->getDisplay()
        );
    }

    /** @test */
    public function it does mark an unsupported line as potentially invalid(): void
    {
        file_put_contents(
            getcwd() . '/tests/.htaccess',
            "<IfModule mod_rewrite.c>"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--path' => getcwd() . '/tests',
        ]);

        $this->assertStringContainsString(
            '?',
            $commandTester->getDisplay()
        );
    }

    /** @test */
    public function it supports http referrer(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteCond %{HTTP_REFERER} https://example.com\nRewriteRule .* /foo"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--referrer' => 'https://example.com'
        ]);

        $this->assertStringContainsString(
            'The output url is "http://localhost/foo"',
            $commandTester->getDisplay()
        );
    }

    /** @test */
    public function it supports server name(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteCond %{SERVER_NAME} example.com\nRewriteRule .* /foo"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--server-name' => 'example.com'
        ]);

        $this->assertStringContainsString(
            'The output url is "http://localhost/foo"',
            $commandTester->getDisplay()
        );
    }

    /** @test */
    public function it supports http user agent(): void
    {
        file_put_contents(
            getcwd() . '/.htaccess',
            "RewriteCond %{HTTP_USER_AGENT} iPhone\nRewriteRule .* /foo"
        );

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
            'url' => 'http://localhost',
            '--http-user-agent' => 'iPhone'
        ]);

        $this->assertStringContainsString(
            'The output url is "http://localhost/foo"',
            $commandTester->getDisplay()
        );
    }
}
