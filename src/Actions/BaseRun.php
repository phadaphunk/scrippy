<?php

namespace Scrippy\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Scrippy\Enums\ExecutionTypeEnum;

class BaseRun
{
    use AsAction;

    protected ExecutionTypeEnum $executionType = ExecutionTypeEnum::SYNC;
    public string $jobQueue = 'high';


    public function handle()
    {
    }

    public function proof(): bool
    {
        return true;
    }

    public function getExecutionType(): ExecutionTypeEnum
    {
        return $this->executionType;
    }
}