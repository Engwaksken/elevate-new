<?php
namespace App\Console\Commands;

use App\Models\PlatformBackup;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PlatformBackupCommand extends Command
{
    protected $signature='platform:backup {--destination=local}';
    protected $description='Create an ElevateHer360 database backup';

    public function handle(SettingsService $settings): int
    {
        $destination=(string)$this->option('destination');

        if ($destination !== 'local') {
            $this->error(
                'Cloud backup credentials can be configured in System Settings, '
                .'but a deployment-specific Google storage adapter must be installed before cloud upload.'
            );

            return self::FAILURE;
        }

        $record=PlatformBackup::create([
            'destination'=>'local',
            'filename'=>'database-'.now()->format('Ymd-His').'.sql',
            'status'=>'pending',
        ]);

        try {
            $db=config('database.connections.'.config('database.default'));
            $dir=storage_path('app/private/backups');
            File::ensureDirectoryExists($dir);

            $path=$dir.'/'.$record->filename;

            $cmd=sprintf(
                'mysqldump -h%s -P%s -u%s %s %s > %s',
                escapeshellarg($db['host'] ?? '127.0.0.1'),
                escapeshellarg((string)($db['port'] ?? '3306')),
                escapeshellarg($db['username'] ?? ''),
                ($db['password'] ?? '') !== ''
                    ? '-p'.escapeshellarg($db['password'])
                    : '',
                escapeshellarg($db['database'] ?? ''),
                escapeshellarg($path)
            );

            exec($cmd,$output,$code);

            if ($code !== 0 || ! File::exists($path)) {
                throw new \RuntimeException('mysqldump failed with exit code '.$code);
            }

            $record->update([
                'path'=>$path,
                'size_bytes'=>File::size($path),
                'status'=>'completed',
                'completed_at'=>now(),
            ]);

            $this->info('Backup created: '.$path);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $record->update([
                'status'=>'failed',
                'error_message'=>$e->getMessage(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
