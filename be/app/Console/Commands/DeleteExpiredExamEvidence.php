<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteExpiredExamEvidence extends Command
{
    protected $signature = 'exam-evidence:purge-expired';
    protected $description = 'Delete expired private AI webcam evidence while retaining violation records';

    public function handle(): int
    {
        $deleted = 0;
        DB::table('anh_man_hinh')
            ->where('nguon', 'webcam_ai')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($images) use (&$deleted) {
                foreach ($images as $image) {
                    Storage::disk($image->storage_disk ?: 'local')->delete($image->duong_dan);
                    if ($image->event_id) {
                        DB::table('canh_bao_camera')->where('event_id', $image->event_id)
                            ->update(['evidence_status' => 'expired', 'updated_at' => now()]);
                    }
                    DB::table('anh_man_hinh')->where('id', $image->id)->delete();
                    $deleted++;
                }
            });

        $this->info("Deleted {$deleted} expired evidence image(s).");
        return self::SUCCESS;
    }
}
