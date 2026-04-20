<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Subscriber;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __invoke(Request $request): JsonResponse
    {
        $org   = $request->user()->activeOrganization();
        $period = $request->query('period', '1m');

        [$start, $groupBy] = match ($period) {
            '1w'    => [now()->subWeek(),   'day'],
            '6m'    => [now()->subMonths(6), 'week'],
            '1y'    => [now()->subYear(),    'month'],
            default => [now()->subMonth(),   'day'],   // 1m
        };

        // --- Stats ---
        $totalSubscribers = Subscriber::where('organization_id', $org->id)->count();

        $optIns = Subscriber::where('organization_id', $org->id)
            ->where('status', 'opted_in')
            ->where('created_at', '>=', $start)
            ->count();

        $optOuts = Subscriber::where('organization_id', $org->id)
            ->where('status', 'opted_out')
            ->where('updated_at', '>=', $start)
            ->count();

        $outgoingTexts = Message::where('organization_id', $org->id)
            ->where('status', 'sent')
            ->where('sent_at', '>=', $start)
            ->count();

        $incomingTexts = Conversation::where('organization_id', $org->id)
            ->where('created_at', '>=', $start)
            ->count();

        // --- Chart data ---
        $chartData = $this->buildChartData($org->id, $start, $groupBy);

        // --- Subscriber sources ---
        $sources = Subscriber::where('organization_id', $org->id)
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source');

        // --- Recently sent messages ---
        $recentlySent = Message::where('organization_id', $org->id)
            ->where('status', 'sent')
            ->orderByDesc('sent_at')
            ->limit(5)
            ->get(['id', 'name', 'recipient_count', 'sent_at']);

        return response()->json([
            'stats' => [
                'total_subscribers' => $totalSubscribers,
                'opt_ins'           => $optIns,
                'opt_outs'          => $optOuts,
                'outgoing_texts'    => $outgoingTexts,
                'incoming_texts'    => $incomingTexts,
            ],
            'chart_data'       => $chartData,
            'subscriber_sources' => $sources,
            'recently_sent'    => $recentlySent,
        ]);
    }

    private function buildChartData(int $orgId, Carbon $start, string $groupBy): array
    {
        $end = now();

        // Build all bucket labels
        $labels  = [];
        $buckets = [];

        if ($groupBy === 'day') {
            $period = CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay());
            foreach ($period as $date) {
                $key           = $date->format('Y-m-d');
                $labels[]      = $key;
                $buckets[$key] = ['opt_ins' => 0, 'opt_outs' => 0, 'outgoing' => 0, 'incoming' => 0];
            }
        } elseif ($groupBy === 'week') {
            $cursor = $start->copy()->startOfWeek();
            while ($cursor->lte($end)) {
                $key           = $cursor->format('Y-\WW');
                $labels[]      = $cursor->format('Y-m-d');
                $buckets[$key] = ['opt_ins' => 0, 'opt_outs' => 0, 'outgoing' => 0, 'incoming' => 0];
                $cursor->addWeek();
            }
        } else { // month
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $key           = $cursor->format('Y-m');
                $labels[]      = $cursor->format('Y-m-01');
                $buckets[$key] = ['opt_ins' => 0, 'opt_outs' => 0, 'outgoing' => 0, 'incoming' => 0];
                $cursor->addMonth();
            }
        }

        // Opt-ins per bucket
        $optIns = Subscriber::where('organization_id', $orgId)
            ->where('status', 'opted_in')
            ->where('created_at', '>=', $start)
            ->selectRaw($this->dateTrunc($groupBy, 'created_at') . ' as bucket, count(*) as cnt')
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        foreach ($optIns as $bucket => $cnt) {
            if (isset($buckets[$bucket])) {
                $buckets[$bucket]['opt_ins'] = (int) $cnt;
            }
        }

        // Opt-outs per bucket
        $optOuts = Subscriber::where('organization_id', $orgId)
            ->where('status', 'opted_out')
            ->where('updated_at', '>=', $start)
            ->selectRaw($this->dateTrunc($groupBy, 'updated_at') . ' as bucket, count(*) as cnt')
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        foreach ($optOuts as $bucket => $cnt) {
            if (isset($buckets[$bucket])) {
                $buckets[$bucket]['opt_outs'] = (int) $cnt;
            }
        }

        // Outgoing per bucket
        $outgoing = Message::where('organization_id', $orgId)
            ->where('status', 'sent')
            ->where('sent_at', '>=', $start)
            ->selectRaw($this->dateTrunc($groupBy, 'sent_at') . ' as bucket, count(*) as cnt')
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        foreach ($outgoing as $bucket => $cnt) {
            if (isset($buckets[$bucket])) {
                $buckets[$bucket]['outgoing'] = (int) $cnt;
            }
        }

        // Incoming per bucket
        $incoming = Conversation::where('organization_id', $orgId)
            ->where('created_at', '>=', $start)
            ->selectRaw($this->dateTrunc($groupBy, 'created_at') . ' as bucket, count(*) as cnt')
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        foreach ($incoming as $bucket => $cnt) {
            if (isset($buckets[$bucket])) {
                $buckets[$bucket]['incoming'] = (int) $cnt;
            }
        }

        return [
            'labels'  => $labels,
            'buckets' => array_values($buckets),
        ];
    }

    private function dateTrunc(string $groupBy, string $column): string
    {
        return match ($groupBy) {
            'day'   => "DATE_FORMAT({$column}, '%Y-%m-%d')",
            'week'  => "DATE_FORMAT({$column}, '%Y-W%u')",
            'month' => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
