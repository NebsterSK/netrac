<?php

use Illuminate\Foundation\DevCommands;

it('runs npm watch as the vite process of artisan dev', function () {
    $viteCommand = collect(DevCommands::commands())->firstWhere('name', 'vite');

    expect($viteCommand['command'])->toBe('npm run watch');
});
