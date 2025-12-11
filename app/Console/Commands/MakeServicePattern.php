<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServicePattern extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service
                            {name : The name of the service (e.g., UserService)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = $this->argument('name');
        $this->createServices($name);
    }

    private function createServices(string $name): void
    {
        $path = app_path("Services/{$name}.php");
        if (File::exists($path)) {
            $this->warn("File {$name} already exists.");

            return;
        }

        $nameParts = explode('/', $name);
        $serviceName = array_pop($nameParts);
        $namespacePart = empty($nameParts) ? '' : '\\'.implode('\\', $nameParts);

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [
                "App\\Services{$namespacePart}",
                $serviceName,
            ],
            $this->getStubContent()
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        $this->info("Service {$serviceName} created.");
    }

    private function getStubContent(): string
    {
        return <<<'STUB'
<?php

namespace {{namespace}};

class {{class}}
{

}
STUB;
    }
}
