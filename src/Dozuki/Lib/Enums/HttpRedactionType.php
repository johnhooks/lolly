<?php

namespace Dozuki\Lib\Enums;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

enum HttpRedactionType: string {
    case Always   = '*';
    case Query    = 'query';
    case Header   = 'header';
    case Request  = 'request';
    case Response = 'response';
}
