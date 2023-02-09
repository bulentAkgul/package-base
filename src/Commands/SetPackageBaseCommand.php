<?php

namespace Bakgul\PackageBase\Commands;

use Illuminate\Console\Command;

class SetPackageBaseCommand extends Command
{
    protected $signature = 'set-package-base';
    protected $description = '';

    public function handle()
    {
        $this->composer();

        $this->copy();

        $this->env();
    }

    private function composer(): void
    {
        file_put_contents(
            base_path('composer.json'),
            $this->composerContent()
        );
    }

    private function composerContent(): string
    {
        return str_replace(
            '{{ package }}',
            $this->packageName(),
            file_get_contents(base_path('composer.json'))
        );
    }

    private function packageName(): string
    {
        foreach (file(base_path('package/composer.json')) as $line) {
            if (str_contains($line, '"name": "bakgul')) {
                return str_replace(['"', ','], '', explode('/', $line)[1]);
            }
        }

        return 'package-name';
    }

    private function copy(): void
    {
        $base  = __DIR__ . '/../..';

        copy("{$base}/stubs/phpunit.stub", base_path('phpunit.xml'));
        copy("{$base}/stubs/vite.stub", base_path('vite.config.js'));
        copy("{$base}/stubs/test-case.stub", base_path('package/tests/TestCase.php'));
    }

    private function env(): void
    {
        $env = base_path('.env');
        $content = [];

        foreach (file($env) as $line) {
            $content[] = str_contains($line, 'APP_URL')
                ? 'APP_URL=http://package.test' . PHP_EOL
                : $line;
        }

        file_put_contents($env, implode('', $content));
    }
}
