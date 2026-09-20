<?php

namespace App\Http\Controllers;

use App\Models\ContentField;

class SeoController extends Controller
{
    public function sitemap()
    {
        $updated = ContentField::max('updated_at') ?? now();
        return response()->view('site.sitemap', compact('updated'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
