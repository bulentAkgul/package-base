<?php

namespace Bakgul\PackageBase\Commands;

use Illuminate\Console\Command;

class SetPackageBaseCommand extends Command
{
    protected $signature = 'set-package-base';
    protected $description = '';

    private $stubs;
    private $package;

    public function handle()
    {
        $this->props();

        $this->composer();

        $this->copy();

        $this->env();

        $this->addPackages();
    }

    private function props(): void
    {
        $this->stubs();

        $this->package();
    }

    private function stubs(): void
    {
        $this->stubs = implode(
            DIRECTORY_SEPARATOR,
            [__DIR__, '..', '..', 'stubs']
        );
    }

    private function package(): void
    {
        foreach (file(base_path('package/composer.json')) as $line) {
            if (str_contains($line, '"name": "bakgul')) {
                $name = trim(str_replace(['"', ','], '', explode('/', $line)[1]));
            }
        }

        $this->package = $name ?? 'package-name';
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


        $composer = [];

        foreach (json_decode(file_get_contents(
            base_path('composer.json')
        ), true) as $key => $value) {
            $composer[$key] = $value;

            if ($key == 'license') {
                $composer['repositories'] = [
                    $this->package => [
                        'type' => 'path',
                        'url' => 'package',
                        'options' => [
                            'symlink' => true
                        ]
                    ]
                ];
            }

            if ($key == 'require-dev') {
                $composer[$key]["bakgul/{$this->package}"] = '@dev';

                ksort($composer[$key]);
            }
        }

        return $composer;
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

        if ($this->package == 'laravel-dump-server') return;

        shell_exec('composer require bakgul/laravel-dump-server --dev');

        $this->call('vendor:publish', [
            '--provider' => 'Bakgul\LaravelDumpServer\LaravelDumpServerServiceProvider',
            '--tag' => 'config'
        ]);
    }
}
