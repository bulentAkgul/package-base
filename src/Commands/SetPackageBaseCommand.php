<?php

namespace Bakgul\PackageBase\Commands;

use Illuminate\Console\Command;

class SetPackageBaseCommand extends Command
{
    protected $signature = 'set-package-base';
    protected $description = '';

    private $stubs;

    public function handle()
    {
        $this->stubs();

        $this->composer();

        $this->copy();

        $this->env();

        $this->addPackages();
    }

    private function stubs(): void
    {
        $this->stubs = implode(
            DIRECTORY_SEPARATOR,
            [__DIR__, '..', '..', 'stubs']
        );
    }

    private function copy(): void
    {
        copy("{$this->stubs}/phpunit.stub", base_path('phpunit.xml'));
        copy("{$this->stubs}/vite.stub", base_path('vite.config.js'));
        copy("{$this->stubs}/test-case.stub", base_path('package/tests/TestCase.php'));
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
        return str_replace('\/', '/', json_encode(
            $this->setComposerContent(),
            JSON_PRETTY_PRINT
        ));
    }

    private function setComposerContent(): array
    {
        $package = $this->packageName();

        $composer = [];

        foreach (json_decode(file_get_contents(
            base_path('composer.json')
        ), true) as $key => $value) {
            $composer[$key] = $value;

            if ($key == 'license') {
                $composer['repositories'] = [
                    $package => [
                        'type' => 'path',
                        'url' => 'package',
                        'options' => [
                            'symlink' => true
                        ]
                    ]
                ];
            }

            if ($key == 'require-dev') {
                $composer[$key]["bakgul/{$package}"] = '@dev';

                ksort($composer[$key]);
            }
        }

        return $composer;
    }

    private function packageName(): string
    {
        foreach (file(base_path('package/composer.json')) as $line) {
            if (str_contains($line, '"name": "bakgul')) {
                return trim(str_replace(['"', ','], '', explode('/', $line)[1]));
            }
        }

        return 'package-name';
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

    private function addPackages(): void
    {
        shell_exec('npm install');

        shell_exec('composer require bakgul/laravel-dump-server --dev');

        $this->call('vendor:publish', [
            '--provider' => 'Bakgul\LaravelDumpServer\LaravelDumpServerServiceProvider',
            '--tag' => 'config'
        ]);
    }
}
