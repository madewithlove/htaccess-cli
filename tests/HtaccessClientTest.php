<?php declare(strict_types=1);

namespace Madewithlove;

use Http\Factory\Guzzle\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Http\Adapter\Guzzle6\Client;

final class HtaccessClientTest extends TestCase
{
    /** @test */
    public function it returns the result from the api(): void
    {
        $client = new HtaccessClient(
            new Client(),
            new ServerRequestFactory()
        );

        $response = $client->test(
            'http://localhost',
            'RewriteRule .* /foo [R]'
        );

        $this->assertEquals(
            'http://localhost/foo',
            $response['output_url']
        );
    }
}
