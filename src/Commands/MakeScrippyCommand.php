<?php

namespace Scrippy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeScrippyCommand extends Command
{
    protected $signature = 'make:scrippy {name : The name of the script}';
    protected $description = 'Create a new Scrippy script';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $className = Str::studly($name);
        $path = config('scrippy.scripts_path') . "/{$className}.php";

        if (!$files->isDirectory(config('scrippy.scripts_path'))) {
            $files->makeDirectory(config('scrippy.scripts_path'), 0755, true);
        }

        $stub = $this->getStub($className);
        $files->put($path, $stub);

        $this->info("Script created: {$path}");
    }

    private function getStub(string $className): string
    {
        return str_replace(
            ['{{className}}', '{{namespace}}'],
            [$className, config('scrippy.namespace')],
            <<<'STUB'
<?php

namespace {{namespace}};

use Scrippy\Interfaces\Runnable;

class {{className}} implements Runnable
{
    public function run(): void
    {
        // Your script logic here
    }

    public function proof(): bool
    {
        return true;
    }
}
STUB
        );
    }
}
