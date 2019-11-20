<?php declare(strict_types=1);

namespace Madewithlove;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

final class HtaccessClient
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var ServerRequestFactoryInterface
     */
    private $requestFactory;

    public function __construct(ClientInterface $httpClient, ServerRequestFactoryInterface $requestFactory)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    public function test(string $url, string $htaccess)
    {
        $request = $this->requestFactory->createServerRequest(
            'POST',
            'https://htaccess.madewithlove.be/api'
        );

        $body = $request->getBody();
        $body->write(json_encode([
            'url' => $url,
            'htaccess' => $htaccess,
            'referrer' => '',
            'serverName' => '',
        ]));

        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        $response = $this->httpClient->sendRequest($request);
        return json_decode($response->getBody()->getContents(), true);
    }
}
