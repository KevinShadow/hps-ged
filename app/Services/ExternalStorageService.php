<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ExternalStorageService
{
    protected string $diskPath;

    public function __construct()
    {
        $this->diskPath = config('filesystems.disks.ged_external.root');
    }

    public function isAvailable(): bool
    {
        $directory = $this->diskPath;

        while (! is_dir($directory)) {
            $parent = dirname($directory);

            if ($parent === $directory) {
                return false;
            }

            $directory = $parent;
        }

        return is_writable($directory);
    }

    public function ensureAvailable(): bool
    {
        if (! is_dir($this->diskPath)) {
            File::makeDirectory($this->diskPath, 0755, true, true);
        }

        return is_dir($this->diskPath)
            && is_writable($this->diskPath);
    }
}
