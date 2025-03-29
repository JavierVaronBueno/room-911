<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class CleanOldPdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-old-pdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete PDF files older than 7 days from access_histories directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of old PDF files...');

        $files = Storage::disk('public')->files('access_histories');
        $threshold = Carbon::now()->subDays(7)->timestamp;

        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            if ($lastModified < $threshold) {
                Storage::disk('public')->delete($file);
                $this->info("Deleted: {$file}");
                $deletedCount++;
            }
        }

        if ($deletedCount === 0) {
            $this->info('No old PDF files found to delete.');
        } else {
            $this->info("Cleanup completed. Deleted {$deletedCount} file(s).");
        }
    }
}
