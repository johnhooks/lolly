<?php

declare(strict_types=1);

namespace Lolly\Lib\ValueObjects\LogContext;

use Lolly\GuzzleHttp\Psr7\Request;
use Lolly\GuzzleHttp\Psr7\Response;
use Lolly\GuzzleHttp\Psr7\Uri;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * HTTP message context.
 *
 * This DTO helps maintain the relationship of the request, response and URL
 * as it moves through the logging pipeline.
 *
 * When HTTP client and WP REST API context are processed, they may contain
 * parsed request bodies. Encoding them and decoding them just to be included
 * in the `MessageInterface` is unnecessary.
 *
 * Note: Usage of this class has not been implemented yet.
 *
 * @package Lolly
 */
class HttpMessageContext {

    /**
     * @param Uri                       $url
     * @param Request                   $request
     * @param Response|null             $response
     * @param array<string, mixed>|null $request_body The parsed request body.
     * @param array<string, mixed>|null $response_body The parsed response body.
     */
    public function __construct(
        readonly public Uri $url,
        readonly public Request $request,
        readonly public ?Response $response = null,
        readonly public ?array $request_body = null,
        readonly public ?array $response_body = null,
    ) {}
}
