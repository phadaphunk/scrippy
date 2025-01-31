<?php

namespace Scrippy\Models;

use Illuminate\Database\Eloquent\Model;
use Scrippy\Services\GithubExecutor;

class ScrippyExecution extends Model
{
    protected $guarded = [];

    protected $casts = [
        'environments_ran' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('scrippy_status', 'pending');
    }

    public function scopeAllowedInEnvironment($query, $environment)
    {
        return $query->whereDoesntHave('environments_ran', function ($q) use ($environment) {
            $q->where('environment', $environment);
        });
    }

    public function scopeNotExceededMaxRuns($query)
    {
        return $query->whereRaw('run_count < max_runs');
    }

    public function recordRun(): void
    {
        $this->update([
            'run_count' => $this->run_count + 1,
            'last_run_at' => now(),
            'environments_ran' => array_merge($this->environments_ran ?? [], [app()->environment()]),
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
        // TODO: Implement shouldRun logic
        return true;
    }

    public function deleteScript()
    {
        $ex = new GithubExecutor();

        // Hardcoded for now
        $ex->handle("app/Actions/Address/CreateAddress.php");
    }
}
