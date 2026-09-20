<?php

namespace App\Http\Controllers;
use App\Services\AuditLog;

use App\Models\QuoteRequest;
use Illuminate\Http\Request;

class QuoteManagementController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        abort_unless(in_array($status, ['all', 'new', 'contacted', 'closed'], true), 422);
        $quotes = QuoteRequest::query()->when($status !== 'all', fn ($query) => $query->where('status', $status))->latest()->paginate(20)->withQueryString();

        return view('cms.quotes.index', ['quotes' => $quotes, 'status' => $status]);
    }

    public function update(Request $request, QuoteRequest $quote)
    {
        $validated = $request->validate(['status' => ['required', 'in:new,contacted,closed']]);
        $quote->update([
            'status' => $validated['status'],
            'contacted_at' => $validated['status'] === 'contacted' && ! $quote->contacted_at ? now() : $quote->contacted_at,
        ]);
        AuditLog::record('quote.updated', 'Status permintaan '.$quote->email.' menjadi '.$quote->status.'.', $quote);

        return back()->with('status', 'Status permintaan diperbarui.');
    }

    public function destroy(QuoteRequest $quote)
    {
        AuditLog::record('quote.deleted', 'Permintaan '.$quote->email.' dihapus.', $quote); $quote->delete();

        return back()->with('status', 'Permintaan penawaran dihapus.');
    }
}
