<?php

use App\Console\Commands\ImportLegacyDotnetCommand;
use App\Console\Commands\InspectLegacyDotnetCommand;
use App\Console\Commands\PrepareLegacyAccountsCommand;
use App\Console\Commands\RepairLegacyAuditTypesCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        InspectLegacyDotnetCommand::class,
        ImportLegacyDotnetCommand::class,
        PrepareLegacyAccountsCommand::class,
        RepairLegacyAuditTypesCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
