<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrackPageVisit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $agent = $request->userAgent() ?? '';
        if ($request->isMethod('GET') && $response->getStatusCode() === 200 && ! $request->user()?->is_admin
            && ! preg_match('/bot|crawler|spider|headless|preview/i', $agent)) {
            $visitor = $request->session()->get('analytics_visitor');
            if (! $visitor) {
                $visitor = (string) Str::uuid();
                $request->session()->put('analytics_visitor', $visitor);
            }
            $host = parse_url($request->header('referer', ''), PHP_URL_HOST);
            try {
                DB::table('page_visits')->insert([
                    'visitor_hash' => hash_hmac('sha256', $visitor, config('app.key')),
                    'locale' => $request->query('lang', 'id'),
                    'device' => preg_match('/tablet|ipad/i', $agent) ? 'Tablet' : (preg_match('/mobile|android|iphone/i', $agent) ? 'Mobile' : 'Desktop'),
                    'referrer' => $host && $host !== $request->getHost() ? mb_substr(strtolower($host), 0, 255) : null,
                    'visited_at' => now(),
                ]);
            } catch (QueryException $exception) {
                report($exception);
            }
        }

        return $response;
    }
}
