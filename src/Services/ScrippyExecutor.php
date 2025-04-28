<?php

namespace Scrippy\Services;

use Illuminate\Support\Facades\File;
use Scrippy\Enums\ExecutionTypeEnum;
use Scrippy\Interfaces\Runnable;
use Scrippy\Models\ScrippyExecution;

class ScrippyExecutor
{
    public function runPendingScripts(): void
    {
        echo 'Scrippy looking for scripts to run '.PHP_EOL;

        if (! in_array(app()->environment(), config('scrippy.run_script_on') ?? [])) {
            return;
        }

        $scriptDirectory = config('scrippy.script_path');

        if (! File::isDirectory($scriptDirectory)) {
            echo 'Scrippy script directory does not exist. Please create it before running scrippy: '.$scriptDirectory.PHP_EOL;
            return;
        }

        $scriptFiles = File::files($scriptDirectory);

        foreach ($scriptFiles as $file) {

            $className = config('scrippy.script_namespace').'\\'.$file->getBasename('.php');

            if (! class_exists($className)) {
                continue;
            }

            $script = ScrippyExecution::firstOrCreate([
                'scrippy_name' => $file->getBasename('.php'),
                'scrippy_class' => $className,
                'execution_type' => $className::getExecutionType(),
            ]);

            if ($script->shouldRun()) {

                echo 'Scrippy will now run : '.$script->scrippy_name.PHP_EOL;

                $script->update([
                    'run_count' => $script->run_count + 1,
                    'last_run_at' => now(),
                ]);

                $script->save();
                $this->runScript($script);

                echo 'Scrippy has completed running : '.$script->scrippy_name.PHP_EOL;
            }
        }
    }

    private function runScript(ScrippyExecution $script): void
    {
        try {
            $instance = app($script->scrippy_class);

            if ($instance instanceof Runnable) {
                $this->handleOldScript($instance);
            } else if ($instance instanceof BaseRun) {
                $this->handleNewScript($instance);
            } else {
                throw new \RuntimeException("Script must implement Runnable or BaseRun interface");
            }


            if (config('scrippy.requires_proof') && ! $instance->proof()) {
                throw new \RuntimeException("Script proof failed");
            }

            $script->recordRun();
            //$script->deleteScript();

        } catch (\Exception $e) {
            $script->recordFailure($e->getMessage());
            throw $e;
        }
    }

    private function handleOldScript($classInstance): void
    {
        $classInstance->run();
    }

    private function handleNewScript($classInstance): void
    {
        switch ($classInstance::executionType) {
            case ExecutionTypeEnum::SYNC:
                $classInstance->run();
                break;
            case ExecutionTypeEnum::ASYNC:
                $classInstance->dispatch();
                break;
            default:
                throw new \RuntimeException("Invalid execution type");
        }
    }
}
