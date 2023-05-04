<?php declare(strict_types=1);

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;

require __DIR__ . '/../.helper/functions.php';

try {
    // Instantiate the HTTP client
    $client = HttpClientBuilder::buildDefault();

    // Make an asynchronous HTTP request
    $response = $client->request(new Request($argv[1] ?? 'https://httpbin.org/user-agent'));

    dumpRequestTrace($response->getRequest());
    dumpResponseTrace($response);

    dumpResponseBodyPreview($response->getBody()->buffer());
} catch (HttpException $error) {
    echo $error;
}
