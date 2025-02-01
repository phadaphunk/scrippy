<?php

namespace Scrippy\Services;

use Illuminate\Support\Facades\File;
use Scrippy\Interfaces\Runnable;
use Scrippy\Models\ScrippyExecution;

class ScrippyExecutor
{
    public function runPendingScripts(): void
    {
        if (!in_array(app()->environment(), config('scrippy.run_script_on'))) {
            return;
        }

        $scriptFiles = File::files(config('scrippy.script_path'));
        foreach ($scriptFiles as $file) {
            $className = config('scrippy.script_namespace') . '\\' . $file->getBasename('.php');

            if (!class_exists($className)) {
                continue;
            }

            $script = ScrippyExecution::firstOrCreate([
                'scrippy_name' => $file->getBasename('.php'),
                'scrippy_class' => $className,
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

            if (config('scrippy.requires_proof') && !$instance->proof()) {
                throw new \RuntimeException("Script proof failed");
            }

            $script->recordRun();
            $script->deleteScript();

        } catch (\Exception $e) {
            $script->recordFailure($e->getMessage());
            throw $e;
        }
    }
}
