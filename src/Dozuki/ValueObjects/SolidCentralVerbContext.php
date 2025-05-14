<?php

declare(strict_types=1);

namespace Dozuki\ValueObjects;

use JsonSerializable;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SolidCentralVerbContext.
 *
 * Value object representing a Solid Central verb request/response.
 */
class SolidCentralVerbContext implements JsonSerializable {
    /**
     * @param ?array<string,mixed> $request  The verb request.
     * @param ?array<string,mixed> $response The verb response.
     */
    public function __construct(
        public readonly ?array $request,
        public readonly ?array $response,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array {
        return [
            'request'  => $this->request,
            'response' => $this->response,
        ];
    }
}
