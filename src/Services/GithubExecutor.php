<?php

namespace Scrippy\Services;

use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Scrippy\Interfaces\Runnable;
use Scrippy\Models\ScrippyExecution;

class GithubExecutor
{
    private string $scriptName;
    private string $branchName;
    private string $tempDir;

    public function handle(string $scriptPath): void
    {
        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("Script not found at: $scriptPath");
        }


        $this->scriptName = basename($scriptPath);
        $this->branchName = $this->generateBranchName();
        $this->tempDir = sys_get_temp_dir() . '/git-' . Str::random(10);

        try {
            $this->setupWorktree()
                ->removeScript($scriptPath)
                ->commitChanges()
                ->pushBranch()
                ->createPullRequest()
                ->cleanup();

            dump('Process completed successfully!');
        } catch (\Exception $e) {
            $this->cleanup();
            throw $e;
        }
    }


    private function setupWorktree(): self
    {
        dump('Setting up temporary worktree...');

        // Create temporary directory
        if (!mkdir($this->tempDir)) {
            throw new \RuntimeException("Failed to create temporary directory: {$this->tempDir}");
        }

        // Fetch latest from master without checking it out
        Process::run('git fetch origin master:master');

        // Create a new worktree from master
        Process::run("git worktree add {$this->tempDir} master");

        // Create and checkout new branch in the worktree
        Process::run("cd {$this->tempDir} && git checkout -b {$this->branchName}");

        return $this;
    }

    private function removeScript(string $scriptPath): self
    {
        dump("Removing script: $scriptPath");

        // Get relative path from repository root
        $repoRoot = trim(Process::run('git rev-parse --show-toplevel')->output());
        $relativeScriptPath = str_replace($repoRoot . '/', '', realpath($scriptPath));

        // Remove the file in the worktree
        $worktreeScriptPath = "{$this->tempDir}/{$relativeScriptPath}";
        if (file_exists($worktreeScriptPath) && unlink($worktreeScriptPath)) {
            Process::run("cd {$this->tempDir} && git rm {$relativeScriptPath}");
        } else {
            throw new \RuntimeException("Failed to remove script: $worktreeScriptPath");
        }

        return $this;
    }

    private function commitChanges(): self
    {
        dump('Committing changes...');

        $message = "Remove deprecated script: {$this->scriptName}";
        Process::run("cd {$this->tempDir} && git commit -m \"$message\"");

        return $this;
    }

    private function pushBranch(): self
    {
        dump('Pushing branch to remote...');

        Process::run("cd {$this->tempDir} && git push origin {$this->branchName}");

        return $this;
    }

    private function createPullRequest(): self
    {
        dump('Creating pull request...');

        $repoOwner = 'floorbox';
        $repoName = 'bmb-app';

        $pr = GitHub::pullRequests()->create($repoOwner, $repoName, [
            'title' => "Remove deprecated script: {$this->scriptName}",
            'body' => $this->generatePrDescription(),
            'head' => $this->branchName,
            'base' => 'master'
        ]);

        dump("Pull request created: {$pr['html_url']}");

        return $this;
    }

    private function cleanup(): self
    {
        dump('Cleaning up temporary worktree...');

        // Remove the worktree
        Process::run("git worktree remove --force {$this->tempDir}");

        // Remove temporary directory if it still exists
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        return $this;
    }

    private function generateBranchName(): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $safeName = Str::slug(pathinfo($this->scriptName, PATHINFO_FILENAME));

        return "remove/{$safeName}_{$timestamp}";
    }

    private function generatePrDescription(): string
    {
        return <<<EOT
        This PR removes the deprecated script: {$this->scriptName}

        Please review the changes and ensure this script is no longer needed.

        This PR was automatically created by the script removal bot.
        EOT;
    }
}
