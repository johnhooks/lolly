<?php

declare(strict_types=1);

namespace Lolly\Lib\Processors;

use Lolly\Lib\Contracts\Redactors;
use Lolly\Lib\Enums\HttpRedactionType;
use Lolly\Lib\ValueObjects\Http\RedactionItem;
use Lolly\Monolog\LogRecord;
use Lolly\Monolog\Processor\ProcessorInterface;
use Lolly\Psr\Http\Message\MessageInterface;
use Lolly\Psr\Http\Message\RequestInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EcsHttpRedactionProcessor class.
 *
 * It redacts HTTP values matching keys from $context['redactions']
 *
 * REQUIREMENT: Must follow after the `EscHttpMessageProcessor` in the Processor order.
 *
 * $context['redactions']['all'] - If set and truthy, redact everything: query string, request and response bodies.
 * $context['redactions']['headers'] - Array of keys to redact from the headers.
 * $context['redactions']['query'] - Array of keys to redact from the query string, the original is completely redacted.
 * $context['redactions']['request'] - Array of keys to redact from the request body.
 * $context['redactions']['response'] - Array of keys to redact from the response body.
 *
 * Redacting body content only works with content-type JSON or URL encoded, for other
 * content types the entire body is entirely redacted, if any redactions for the
 * respective $context['redactions'] key provide are provided.
 *
 * @package Lolly
 */
class EcsHttpRedactionProcessor implements ProcessorInterface {
    public function __construct(
        protected readonly Redactors\HttpMessage $redactor,
    ) {}

    public function __invoke( LogRecord $record ) {
        $context = $record->context;

        $context_redactions = $record->context['redactions'] ?? [];
        $redactions         = $this->transform_context_redactions( $context_redactions );
        $raw_url            = $context['url'] ?? null;

        // In this situation we can only apply the context redactions unless handling a request.
        $url = $raw_url === null ? '' : $raw_url;

        foreach ( $context as $key => $value ) {
            if ( $value instanceof MessageInterface ) {
                if ( $url === '' && $value instanceof RequestInterface ) {
                    $url = $value->getUri();
                }

                $context[ $key ] = $this->redactor->redact( $url, $value, $redactions );
            }
        }

        return $record->with( context: $context );
    }

    /**
     * @param array<string,mixed> $context_redactions
     *
     * @return RedactionItem[]
     */
    protected function transform_context_redactions( array $context_redactions ): array {
        /** @var RedactionItem[] $result */
        $result = [];

        foreach ( $context_redactions as $key => $raw_redactions ) {
            $redaction_type = HttpRedactionType::tryFrom( $key );

            if ( $redaction_type === null ) {
                if ( $key === 'all' ) {
                    $redaction_type = HttpRedactionType::Always;
                } else {
                    continue;
                }
            }

            $split_redactions = [];

            if ( is_array( $raw_redactions ) ) {
                $split_redactions = $raw_redactions;
            } elseif ( is_string( $raw_redactions ) ) {
                $split_redactions = explode( ',', $raw_redactions );
            }

            foreach ( $split_redactions as $raw_redaction ) {
                $result = new RedactionItem(
                    type: $redaction_type,
                    value: $raw_redaction,
                );
            }
        }

        return $result;
    }
}
