<?php

namespace Lolly\Lib\Enums;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * HttpRedactionType class.
 *
 * @package Lolly
 */
enum HttpRedactionType: string {
    case Always   = '*';
    case Query    = 'query';
    case Header   = 'header';
    case Request  = 'request';
    case Response = 'response';
}
