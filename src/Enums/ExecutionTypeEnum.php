<?php

namespace Scrippy\Enums;

enum ExecutionTypeEnum: string
{
    case SYNC = 'sync';
    case ASYNC = 'async';
}
