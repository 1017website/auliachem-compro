<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request)
    {
        $days = (int) $request->query('days', 30);
        abort_unless(in_array($days, [7, 30, 90]), 422);
        $start = now()->startOfDay()->subDays($days - 1);
        $query = DB::table('page_visits')->where('visited_at', '>=', $start)->where('visited_at', '<=', now());
        $conversions = DB::table('conversion_events')->where('occurred_at','>=',$start)->where('occurred_at','<=',now());
        $daily = (clone $query)->selectRaw('DATE(visited_at) as date, COUNT(*) as total')->groupByRaw('DATE(visited_at)')->pluck('total', 'date');
        $series = collect(range(0, $days - 1))->map(fn ($day) => [
            'date' => $start->copy()->addDays($day)->format('d M'),
            'total' => (int) ($daily[$start->copy()->addDays($day)->format('Y-m-d')] ?? 0),
        ]);

        return view('cms.analytics', [
            'days' => $days, 'series' => $series, 'total' => (clone $query)->count(),
            'visitors' => (clone $query)->distinct()->count('visitor_hash'),
            'today' => DB::table('page_visits')->whereDate('visited_at', today())->count(),
            'devices' => (clone $query)->selectRaw('device as label, COUNT(*) as total')->groupBy('device')->orderByDesc('total')->get(),
            'languages' => (clone $query)->selectRaw('locale as label, COUNT(*) as total')->groupBy('locale')->orderByDesc('total')->get(),
            'sources' => (clone $query)->selectRaw('referrer as label, COUNT(*) as total')->groupBy('referrer')->orderByDesc('total')->limit(8)->get(),
            'conversionTotal' => (clone $conversions)->count(),
            'conversionTypes' => (clone $conversions)->selectRaw('type as label, COUNT(*) as total')->groupBy('type')->orderByDesc('total')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $days=(int)$request->query('days',30); abort_unless(in_array($days,[7,30,90]),422); $start=now()->startOfDay()->subDays($days-1);
        $visits=DB::table('page_visits')->where('visited_at','>=',$start)->selectRaw('DATE(visited_at) date, COUNT(*) total, COUNT(DISTINCT visitor_hash) visitors')->groupByRaw('DATE(visited_at)')->get()->keyBy('date');
        $events=DB::table('conversion_events')->where('occurred_at','>=',$start)->selectRaw('DATE(occurred_at) date, COUNT(*) total')->groupByRaw('DATE(occurred_at)')->pluck('total','date');
        return response()->streamDownload(function()use($days,$start,$visits,$events){$out=fopen('php://output','w');fputcsv($out,['Tanggal','Page views','Pengunjung unik','Konversi']);foreach(range(0,$days-1) as $i){$date=$start->copy()->addDays($i)->format('Y-m-d');fputcsv($out,[$date,$visits[$date]->total??0,$visits[$date]->visitors??0,$events[$date]??0]);}fclose($out);},'auliachem-analytics-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv']);
    }
}
