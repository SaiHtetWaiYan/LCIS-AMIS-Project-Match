<?php

namespace App\Services\Sync;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Exceptions\SourceApi\SourceApiException;
use App\Models\SyncRun;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Wraps a sync in a sync_runs row: running → succeeded, or failed with the error the admin sees.
 */
class SyncRunRecorder
{
    /**
     * A run still "running" after this long was killed (worker crash, timeout) and is closed as failed.
     */
    private const STALE_AFTER_MINUTES = 120;

    /**
     * @param  Closure(SyncRun): void  $sync
     */
    public function record(SyncSource $source, ?int $triggeredBy, Closure $sync): SyncRun
    {
        $this->closeStaleRuns($source);

        $run = SyncRun::create([
            'source' => $source,
            'status' => SyncStatus::Running,
            'triggered_by' => $triggeredBy,
            'started_at' => now(),
        ]);

        try {
            $sync($run);
            $run->fill(['status' => SyncStatus::Succeeded, 'finished_at' => now()])->save();
        } catch (SourceApiException $exception) {
            Log::warning("{$source->label()} sync failed: {$exception->getMessage()}", $exception->context());
            $this->fail($run, $exception->errorCode, $exception->getMessage());
        } catch (Throwable $exception) {
            $this->fail($run, 'INTERNAL_ERROR', $exception->getMessage());

            throw $exception;
        }

        return $run;
    }

    /**
     * Marks the newest still-running run of a source as failed, for a job that died without finishing.
     */
    public function failRunning(SyncSource $source, string $message): void
    {
        $run = SyncRun::query()->forSource($source)->where('status', SyncStatus::Running)->latest('started_at')->first();

        if ($run !== null) {
            $this->fail($run, 'JOB_FAILED', $message);
        }
    }

    private function closeStaleRuns(SyncSource $source): void
    {
        SyncRun::query()
            ->forSource($source)
            ->where('status', SyncStatus::Running)
            ->where('started_at', '<', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->each(fn (SyncRun $run) => $this->fail($run, 'STALE', 'The sync stopped without finishing (worker stopped or timed out).'));
    }

    private function fail(SyncRun $run, string $code, string $message): void
    {
        $run->forceFill([
            'status' => SyncStatus::Failed,
            'finished_at' => now(),
            'error_code' => $code,
            'error_message' => mb_substr($message, 0, 2000),
        ])->save();
    }
}
