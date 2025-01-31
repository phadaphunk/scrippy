<?php

namespace Scrippy\Services;

use Illuminate\Support\Facades\File;
use Scrippy\Interfaces\Runnable;
use Scrippy\Models\ScrippyExecution;

class ScrippyExecutor
{
    public function runPendingScripts(): void
    {
        $scriptFiles = File::files(config('scrippy.scripts_path'));


        foreach ($scriptFiles as $file) {
            $className = config('scrippy.namespace') . '\\' . $file->getBasename('.php');

            if (!class_exists($className)) {
                continue;
            }

            $script = ScrippyExecution::firstOrCreate([
                'scrippy_name' => $file->getBasename('.php'),
                'scrippy_class' => $className,
                'environment' => app()->environment(),
            ]);

            if ($script->shouldRun()) {
                $this->runScript($script);
            }
        }
    }

    private function runScript(ScrippyExecution $script): void
    {
        try {
            $instance = app($script->scrippy_class);

            if (!$instance instanceof Runnable) {
                throw new \RuntimeException("Script must implement Runnable interface");
            }

            $instance->run();
            $script->recordRun();

            if ($instance->proof()) {
                $script->deleteScript();
            }

        } catch (\Exception $e) {
            $script->recordFailure($e->getMessage());
            throw $e;
        }
    }

    private function removeScript(ScrippyExecution $script): void
    {

    }
}
