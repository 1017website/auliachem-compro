<?php

namespace App\Http\Controllers;

use App\Mail\NewQuoteRequest;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;
use App\Services\ConversionTracker;

class QuoteRequestController extends Controller
{
    public function store(Request $request, ConversionTracker $tracker)
    {
        if ($request->filled('website')) {
            return $this->successRedirect($request->input('locale', 'id'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+() .-]+$/'],
            'interest' => ['required', 'in:chemical,industrial-salt,laboratory-chemical,laboratory-solution,other'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'locale' => ['required', 'in:id,en,zh'],
            'website' => ['nullable', 'max:0'],
        ]);
        unset($validated['website']);
        $quote = QuoteRequest::create($validated);
        $tracker->record($request, 'quote_submitted', ['interest'=>$quote->interest]);

        try {
            Mail::to(config('company.quote_recipient'))->send(new NewQuoteRequest($quote));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $this->successRedirect($quote->locale);
    }

    private function successRedirect(string $locale)
    {
        return redirect(route('home', ['lang' => in_array($locale, ['id', 'en', 'zh'], true) ? $locale : 'id']).'#quote-form')
            ->with('quote_status', 'received');
    }
}
