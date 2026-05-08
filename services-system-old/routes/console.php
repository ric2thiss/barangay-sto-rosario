<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('db:cleanup {--force} {--dry-run}', function () {
    $connectionName = env('DB_CONNECTION', config('database.default'));
    $conn = DB::connection($connectionName);
    $driver = $conn->getDriverName();
    if ($driver !== 'mysql' && $driver !== 'mariadb') {
        $this->error('db:cleanup supports MySQL/MariaDB only. Current driver: '.$driver);
        return 1;
    }

    $dbName = $conn->getDatabaseName();
    if (! $dbName) {
        $this->error('Database is not configured or unreachable.');
        return 1;
    }

    $tables = collect($conn->select("
        SELECT table_name AS name
        FROM information_schema.tables
        WHERE table_schema = database()
          AND table_type IN ('BASE TABLE', 'SYSTEM VERSIONED')
    "))->pluck('name');

    $reserved = collect([
        'migrations',
        'failed_jobs',
        'job_batches',
        'personal_access_tokens',
        'password_reset_tokens',
        'sessions',
        'cache',
    ]);

    $migrationTables = collect();
    foreach (File::files(database_path('migrations')) as $file) {
        $contents = File::get($file->getPathname());
        preg_match_all("/Schema::create\\(['\\\"]([^'\\\"]+)['\\\"]/", $contents, $matchesCreate);
        preg_match_all("/Schema::table\\(['\\\"]([^'\\\"]+)['\\\"]/", $contents, $matchesTable);
        $names = collect($matchesCreate[1] ?? [])->merge($matchesTable[1] ?? []);
        $migrationTables = $migrationTables->merge($names);
    }
    $expected = $migrationTables->merge($reserved)->unique()->values();

    $unused = $tables->diff($expected)->values();

    if ($unused->isEmpty()) {
        $this->info('No unused tables found in database: '.$dbName);
        return 0;
    }

    $this->warn('Unused tables detected (will be dropped if --force provided):');
    foreach ($unused as $t) {
        $this->line('- '.$t);
    }

    $dryRun = (bool) $this->option('dry-run');
    $force = (bool) $this->option('force');

    if ($dryRun || ! $force) {
        $this->info('Dry run. Re-run with --force to drop these tables.');
        return 0;
    }

    foreach ($unused as $t) {
        try {
            $conn->statement('DROP TABLE `'.Str::of($t)->replace('`', '')->toString().'`');
            $this->info('Dropped: '.$t);
        } catch (\Throwable $e) {
            $this->error('Failed to drop '.$t.': '.$e->getMessage());
        }
    }

    $this->info('Cleanup complete.');
    return 0;
})->purpose('Drop database tables not referenced by migrations (safe with --dry-run)');
