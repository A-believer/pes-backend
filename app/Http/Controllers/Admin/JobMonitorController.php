<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobMonitorController extends Controller
{
    /**
     * GET /api/admin/jobs/failed
     * Returns paginated failed jobs with parsed payload.
     */
    public function failed(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $search  = $request->query('search', '');

        $query = DB::table('failed_jobs')->orderByDesc('failed_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('exception', 'like', "%{$search}%")
                  ->orWhere('payload',    'like', "%{$search}%")
                  ->orWhere('queue',      'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(function ($job) {
            $payload = json_decode($job->payload, true);
            return [
                'id'          => $job->id,
                'uuid'        => $job->uuid,
                'connection'  => $job->connection,
                'queue'       => $job->queue,
                'job_class'   => $payload['displayName'] ?? ($payload['job'] ?? 'Unknown'),
                'attempts'    => $payload['attempts'] ?? null,
                'failed_at'   => $job->failed_at,
                'exception'   => $this->truncateException($job->exception),
                'payload_raw' => $payload,
            ];
        });

        return response()->json([
            'data'         => $items,
            'total'        => $paginated->total(),
            'per_page'     => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    /**
     * DELETE /api/admin/jobs/failed/{id}
     * Delete (forget) a single failed job.
     */
    public function deleteFailed(int $id): JsonResponse
    {
        $deleted = DB::table('failed_jobs')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Failed job not found.'], 404);
        }

        return response()->json(['message' => 'Failed job removed.']);
    }

    /**
     * DELETE /api/admin/jobs/failed
     * Flush all failed jobs.
     */
    public function flushFailed(): JsonResponse
    {
        $count = DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->truncate();

        return response()->json(['message' => "Flushed {$count} failed job(s)."]);
    }

    /**
     * GET /api/admin/jobs/queue
     * Returns pending/processing jobs still in the queue.
     */
    public function queue(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $paginated = DB::table('jobs')->orderByDesc('created_at')->paginate($perPage);

        $items = collect($paginated->items())->map(function ($job) {
            $payload = json_decode($job->payload, true);
            return [
                'id'            => $job->id,
                'queue'         => $job->queue,
                'job_class'     => $payload['displayName'] ?? ($payload['job'] ?? 'Unknown'),
                'attempts'      => $job->attempts,
                'available_at'  => date('Y-m-d H:i:s', $job->available_at),
                'created_at'    => date('Y-m-d H:i:s', $job->created_at),
                'reserved_at'   => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
            ];
        });

        return response()->json([
            'data'         => $items,
            'total'        => $paginated->total(),
            'per_page'     => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    /**
     * GET /api/admin/jobs/stats
     * Quick summary counts.
     */
    public function stats(): JsonResponse
    {
        $pendingCount = DB::table('jobs')->count();
        $failedCount  = DB::table('failed_jobs')->count();

        $recentFailed = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'id'        => $job->id,
                    'job_class' => $payload['displayName'] ?? 'Unknown',
                    'failed_at' => $job->failed_at,
                    'exception' => $this->truncateException($job->exception, 120),
                ];
            });

        return response()->json([
            'pending_jobs'  => $pendingCount,
            'failed_jobs'   => $failedCount,
            'recent_failed' => $recentFailed,
        ]);
    }

    private function truncateException(string $exception, int $length = 400): string
    {
        $firstLine = strtok($exception, "\n");
        return mb_substr($firstLine ?: $exception, 0, $length);
    }
}
