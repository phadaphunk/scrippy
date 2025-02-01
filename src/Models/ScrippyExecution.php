<?php

namespace Scrippy\Models;

use Illuminate\Database\Eloquent\Model;
use Scrippy\Services\GithubExecutor;

class ScrippyExecution extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_run_at' => 'datetime',
    ];

    public function recordRun(): void
    {
        $this->update([
            'run_count' => $this->run_count + 1,
            'last_run_at' => now(),
            'scrippy_status' => $this->run_count + 1 >= $this->max_runs ? 'completed' : 'pending'
        ]);
    }

    public function recordFailure(string $message): void
    {
        $this->update([
            'scrippy_status' => 'failed',
            'failure_message' => $message
        ]);
    }

    public function shouldRun(): bool
    {
        return $this->run_count < $this->max_runs;
    }

    public function deleteScript()
    {
        $ex = new GithubExecutor();
        $ex->handle(config('scrippy.script_path') . '/' . $this->scrippy_name . '.php');
    }
}
