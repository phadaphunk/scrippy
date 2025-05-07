<?php

namespace Scrippy\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Scrippy\Enums\ExecutionTypeEnum;
use Scrippy\Models\ScrippyExecution;

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

    public function asJob(ScrippyExecution $scrippyExecution)
    {
        try {
            $this->handle();

            if (\config('scrippy.requires_proof') && ! $this->proof()) {
                throw new \RuntimeException("Script proof failed");
            }

            $scrippyExecution->recordRun();
        } catch (\Exception $e) {
            $scrippyExecution->recordFailure($e->getMessage());
            throw $e;
        }
    }
}