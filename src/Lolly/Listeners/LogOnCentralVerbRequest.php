<?php

declare(strict_types=1);

namespace Lolly\Listeners;

use Lolly\ValueObjects\SolidCentralVerbContext;
use Lolly\Psr\Log\LoggerInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class LogOnCentralVerbRequest.
 *
 * Listens for the actions emitted by Solid Central when receiving external
 * requests from the Solid Central server.
 *
 * @package Lolly
 */
class LogOnCentralVerbRequest {
    /**
     * The buffered request. Stored until handling response.
     *
     * @var ?array<string,mixed> $request
     */
    private ?array $request = null;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param array<string,mixed> $request
     *
     * @return void
     */
    public function handle_request( array $request ): void {
        $this->request = $request;
    }

    /**
     * @param array<string,mixed> $response
     *
     * @return void
     */
    public function handle_response( array $response ): void {
        $request = $this->flush();

        $this->log( $request, $response );
    }

    public function shutdown(): void {
        $request = $this->flush();

        if ( $request !== null ) {
            $this->log( $request, null );
        }
    }

    /**
     * @return ?array<string,mixed>
     */
    protected function flush(): ?array {
        $request = $this->request;

        if ( $this->request !== null ) {
            $this->request = null;

            return $request;
        }

        return null;
    }

    /**
     * @param ?array<string,mixed> $request
     * @param ?array<string,mixed> $response
     *
     * @return void
     */
    protected function log( ?array $request, ?array $response ): void {
        $action      = $request['action'] ?? 'unknown';
        $log_context = [
            'action'             => $action,
            'solid_central_verb' => new SolidCentralVerbContext(
                $request,
                $response,
            ),
        ];

        if ( is_wp_error( $response['response'] ) ) {
            $this->logger->error( 'Solid Central verb request: {action}', $log_context );
        } else {
            $this->logger->info( 'Solid Central verb request: {action}', $log_context );
        }
    }
}
