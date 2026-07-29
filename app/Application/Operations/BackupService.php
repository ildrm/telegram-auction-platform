<?php

declare(strict_types=1);

namespace App\Application\Operations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final class BackupService
{
    public function create(): string
    {
        $key = $this->encryptionKey();
        $filename = 'telegram-auction-'.now()->utc()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.zip';
        $backupDisk = Storage::disk((string) config('backup.disk'));
        $path = $backupDisk->path($filename);
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('Unable to create backup archive.');
        }

        $temporaryFiles = [];
        $manifest = [
            'format_version' => 1,
            'created_at' => now()->utc()->toIso8601String(),
            'application' => (string) config('app.name'),
            'tables' => [],
            'media' => [],
        ];

        try {
            foreach ((array) config('backup.tables') as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $temporary = tempnam(sys_get_temp_dir(), 'auction-backup-');

                if ($temporary === false) {
                    throw new RuntimeException('Unable to allocate a temporary backup file.');
                }

                $temporaryFiles[] = $temporary;
                $handle = fopen($temporary, 'wb');

                if ($handle === false) {
                    throw new RuntimeException('Unable to open a temporary backup file.');
                }

                $count = 0;
                DB::table($table)->orderBy($this->orderingColumn($table))->chunk(500, function ($rows) use ($handle, &$count): void {
                    foreach ($rows as $row) {
                        fwrite($handle, json_encode((array) $row, JSON_THROW_ON_ERROR).PHP_EOL);
                        $count++;
                    }
                });
                fclose($handle);
                $entry = "database/{$table}.jsonl";
                $zip->addFile($temporary, $entry);
                $zip->setEncryptionName($entry, ZipArchive::EM_AES_256, $key);
                $manifest['tables'][$table] = ['rows' => $count, 'sha256' => hash_file('sha256', $temporary)];
            }

            $mediaDisk = Storage::disk((string) config('media.disk'));

            foreach ($mediaDisk->allFiles() as $mediaPath) {
                $entry = 'media/'.$mediaPath;
                $zip->addFile($mediaDisk->path($mediaPath), $entry);
                $zip->setEncryptionName($entry, ZipArchive::EM_AES_256, $key);
                $manifest['media'][$mediaPath] = hash_file('sha256', $mediaDisk->path($mediaPath));
            }

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $zip->setEncryptionName('manifest.json', ZipArchive::EM_AES_256, $key);
        } finally {
            $zip->close();

            foreach ($temporaryFiles as $temporary) {
                @unlink($temporary);
            }
        }

        $this->prune();

        return $filename;
    }

    /** @return array<string, mixed> */
    public function verify(string $filename): array
    {
        [$zip, $manifest] = $this->open($filename);

        try {
            foreach ($manifest['tables'] ?? [] as $table => $metadata) {
                $contents = $zip->getFromName("database/{$table}.jsonl");

                if (! is_string($contents) || hash('sha256', $contents) !== $metadata['sha256']) {
                    throw new RuntimeException("Backup table checksum failed: {$table}");
                }
            }

            foreach ($manifest['media'] ?? [] as $path => $checksum) {
                $contents = $zip->getFromName('media/'.$path);

                if (! is_string($contents) || hash('sha256', $contents) !== $checksum) {
                    throw new RuntimeException("Backup media checksum failed: {$path}");
                }
            }
        } finally {
            $zip->close();
        }

        return $manifest;
    }

    public function restore(string $filename): void
    {
        $this->verify($filename);
        [$zip, $manifest] = $this->open($filename);
        $tables = array_keys($manifest['tables'] ?? []);

        try {
            Schema::disableForeignKeyConstraints();
            DB::transaction(function () use ($zip, $tables): void {
                foreach (array_reverse($tables) as $table) {
                    DB::table($table)->delete();
                }

                foreach ($tables as $table) {
                    $contents = $zip->getFromName("database/{$table}.jsonl");
                    $batch = [];

                    foreach (preg_split('/\R/', trim((string) $contents)) ?: [] as $line) {
                        if ($line === '') {
                            continue;
                        }

                        $batch[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                        if (count($batch) === 250) {
                            DB::table($table)->insert($batch);
                            $batch = [];
                        }
                    }

                    if ($batch !== []) {
                        DB::table($table)->insert($batch);
                    }
                }
            }, 3);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $mediaDisk = Storage::disk((string) config('media.disk'));
        $mediaDisk->deleteDirectory('auctions');

        foreach (array_keys($manifest['media'] ?? []) as $path) {
            if (str_contains($path, '..') || str_starts_with($path, '/')) {
                throw new RuntimeException('Unsafe media path in backup.');
            }

            $mediaDisk->put($path, (string) $zip->getFromName('media/'.$path));
        }

        $zip->close();
    }

    /** @return array{ZipArchive, array<string, mixed>} */
    private function open(string $filename): array
    {
        if (basename($filename) !== $filename) {
            throw new RuntimeException('Backup filename must not contain a path.');
        }

        $path = Storage::disk((string) config('backup.disk'))->path($filename);
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Unable to open backup archive.');
        }

        $zip->setPassword($this->encryptionKey());
        $manifest = $zip->getFromName('manifest.json');

        if (! is_string($manifest)) {
            $zip->close();
            throw new RuntimeException('Backup manifest is missing or cannot be decrypted.');
        }

        return [$zip, json_decode($manifest, true, 512, JSON_THROW_ON_ERROR)];
    }

    private function encryptionKey(): string
    {
        $key = (string) config('backup.encryption_key');

        if (strlen($key) < 32) {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY must contain at least 32 characters.');
        }

        return $key;
    }

    private function orderingColumn(string $table): string
    {
        return Schema::hasColumn($table, 'id') ? 'id' : collect(Schema::getColumnListing($table))->first();
    }

    private function prune(): void
    {
        $disk = Storage::disk((string) config('backup.disk'));
        $cutoff = now()->subDays((int) config('backup.retention_days'))->getTimestamp();

        foreach ($disk->files() as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
            }
        }
    }
}
