<?php

namespace Scrippy\Services;

use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class GithubExecutor
{
    private string $scriptName;
    private string $branchName;
    private string $baseBranch;
    private string $tempDir;

    public function handle(string $scriptPath): void
    {
        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("Script not found at: $scriptPath");
        }

        $this->scriptName = basename($scriptPath);
        $this->baseBranch = config('scrippy.cleanup.github.branch_to_cleanup_against');
        $this->branchName = $this->generateBranchName();
        $this->tempDir = sys_get_temp_dir() . '/git-' . Str::random(10);

        try {
            $this->setupWorktree()
                ->removeScript($scriptPath)
                ->commitChanges()
                ->pushBranch()
                ->createPullRequest()
                ->cleanup();

        } catch (\Exception $e) {
            $this->cleanup();
            throw $e;
        }
    }


    private function setupWorktree(): self
    {
        // Create temporary directory
        if (!mkdir($this->tempDir)) {
            throw new \RuntimeException("Failed to create temporary directory: {$this->tempDir}");
        }

        // Debug the git status
        dump('Current git status:', Process::run('git status')->output());

        // Fetch latest from base branch without checking it out
        $fetchProcess = Process::run("git fetch origin {$this->baseBranch}");
        if (!$fetchProcess->successful()) {
            throw new \RuntimeException("Failed to fetch: " . $fetchProcess->errorOutput());
        }

        // Create a new worktree from base branch
        $worktreeProcess = Process::run("git worktree add {$this->tempDir} origin/{$this->baseBranch}");
        if (!$worktreeProcess->successful()) {
            throw new \RuntimeException("Failed to create worktree: " . $worktreeProcess->errorOutput());
        }

        // Create and checkout new branch in the worktree
        $checkoutProcess = Process::run("cd {$this->tempDir} && git checkout -b {$this->branchName}");
        if (!$checkoutProcess->successful()) {
            throw new \RuntimeException("Failed to create branch: " . $checkoutProcess->errorOutput());
        }

        // Debug the worktree content
        dump('Worktree files:', Process::run("cd {$this->tempDir} && ls -la")->output());

        return $this;
    }

    private function removeScript(string $scriptPath): self
    {
        // Get relative path from repository root
        $repoRoot = trim(Process::run('git rev-parse --show-toplevel')->output());

        // Ensure we have the real path of the script
        $realScriptPath = realpath($scriptPath);
        if (!$realScriptPath) {
            throw new \RuntimeException("Cannot resolve real path for: $scriptPath");
        }

        // Get relative path, ensuring we handle Windows/Unix path separators
        $relativeScriptPath = str_replace(
            [$repoRoot . '/', $repoRoot . '\\'],
            '',
            $realScriptPath
        );

        // Construct worktree path using directory separator
        $worktreeScriptPath = $this->tempDir . DIRECTORY_SEPARATOR . $relativeScriptPath;

        // Ensure directory exists
        $targetDir = dirname($worktreeScriptPath);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                throw new \RuntimeException("Failed to create directory: $targetDir");
            }
        }

        // Debug information
        dump([
            'repoRoot' => $repoRoot,
            'realScriptPath' => $realScriptPath,
            'relativeScriptPath' => $relativeScriptPath,
            'worktreeScriptPath' => $worktreeScriptPath,
            'fileExists' => file_exists($worktreeScriptPath),
            'isWritable' => is_writable(dirname($worktreeScriptPath))
        ]);

        if (!file_exists($worktreeScriptPath)) {
            throw new \RuntimeException("Script not found in worktree: $worktreeScriptPath");
        }

        if (!unlink($worktreeScriptPath)) {
            throw new \RuntimeException("Failed to unlink script: $worktreeScriptPath");
        }

        // Run git rm after successful unlink
        $process = Process::run("cd {$this->tempDir} && git rm {$relativeScriptPath}");

        if (!$process->successful()) {
            throw new \RuntimeException("Git rm failed: " . $process->errorOutput());
        }

        return $this;
    }

    private function commitChanges(): self
    {
        $message = "Remove deprecated script: {$this->scriptName}";
        Process::run("cd {$this->tempDir} && git commit -m \"$message\"");

        return $this;
    }

    private function pushBranch(): self
    {
        Process::run("cd {$this->tempDir} && git push origin {$this->branchName}");

        return $this;
    }

    private function createPullRequest(): self
    {
        $repoOwner = config('github.connections.main.owner');
        $repoName = config('github.connections.main.repo');

        $pr = GitHub::pullRequests()->create($repoOwner, $repoName, [
            'title' => "🐕 Scrippy Removed a deprecated script: {$this->scriptName}",
            'body' => $this->generatePrDescription(),
            'head' => $this->branchName,
            'base' => $this->baseBranch
        ]);

        // Then add reviewers to the PR
        GitHub::pullRequests()->reviewRequests()->create($repoOwner, $repoName, $pr['number'], config('scrippy.cleanup.github.reviewers'));

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

        This PR was automatically created and is now removed by Scrippy.
        EOT;
    }
}
