<?php

namespace App\Services;

use App\Models\ContentField;
use App\Models\Document;
use App\Models\Product;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class CompanyPage
{
    public const SECTIONS = [
        'branding' => 'Logo & favicon', 'settings' => 'SEO website', 'navbar' => 'Navigasi', 'home' => 'Hero',
        'solution-strip' => 'Ringkasan solusi', 'core' => 'Produk utama',
        'industrial-salt' => 'Garam industri', 'applications' => 'Aplikasi industri',
        'labsolution' => 'Solusi laboratorium', 'handling' => 'Fasilitas',
        'quality' => 'Kualitas & dokumen', 'contact' => 'Kontak', 'footer' => 'Footer',
    ];

    public const BACKGROUNDS = [
        ['home', 'Foto utama hero', '.hero-photo'],
        ['industrial-salt', 'Foto garam industri', '.salt-visual'],
        ['applications', 'Foto aplikasi industri', '.app-photo'],
        ['labsolution', 'Foto solusi laboratorium', '.lab-photo'],
        ['handling', 'Foto utama fasilitas', '.handling-main'],
        ['handling', 'Foto distribusi', '.handling-small.one'],
        ['handling', 'Foto penyimpanan', '.handling-small.two'],
        ['quality', 'Foto pengujian laboratorium', '.quality-photo'],
    ];

    private function document(): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.file_get_contents(resource_path('templates/company.html')));
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $doc;
    }

    private function section(DOMNode $node): string
    {
        for ($parent = $node instanceof DOMElement ? $node : $node->parentNode; $parent; $parent = $parent->parentNode) {
            if ($parent instanceof DOMElement) {
                if ($parent->tagName === 'footer') {
                    return 'footer';
                }
                if (in_array($parent->tagName, ['section', 'header'])) {
                    return $parent->getAttribute('id') ?: (explode(' ', $parent->getAttribute('class'))[0] ?: 'settings');
                }
            }
        }

        return 'settings';
    }

    public function fields(?DOMDocument $doc = null): array
    {
        $doc ??= $this->document();
        $xpath = new DOMXPath($doc);
        $source = file_get_contents(resource_path('templates/company.html'));
        preg_match('/const TRANSLATIONS\s*=\s*(\{.*?\});/s', $source, $match);
        $translations = json_decode($match[1] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $fields = [];
        foreach ($xpath->query('//body//text()[normalize-space() and not(ancestor::script) and not(ancestor::style) and not(ancestor::svg) and not(ancestor::*[contains(@class,"lang-switch")])] | //head/title/text()') as $node) {
            $original = trim($node->nodeValue);
            $section = $this->section($node);
            $key = 'text_'.count($fields);
            $fields[$key] = ['key' => $key, 'section' => $section, 'type' => 'text', 'label' => mb_substr($original, 0, 110),
                'values' => ['en' => $original, 'id' => $translations[$original]['id'] ?? $original, 'zh' => $translations[$original]['zh'] ?? $original], 'node' => $node, 'attribute' => null];
        }
        foreach ($xpath->query('//img | //a[@href] | //meta[@name="description"]') as $node) {
            $attribute = match ($node->tagName) {
                'img' => 'src', 'a' => 'href', default => 'content'
            };
            $type = match ($node->tagName) {
                'img' => 'image', 'a' => 'link', default => 'text'
            };
            $key = $type.'_'.count($fields);
            $value = $node->getAttribute($attribute);
            $label = $node->getAttribute('alt') ?: trim($node->textContent) ?: 'Deskripsi mesin pencari';
            $fields[$key] = ['key' => $key, 'section' => $this->section($node), 'type' => $type, 'label' => mb_substr($label, 0, 110),
                'values' => ['id' => $value, 'en' => $value, 'zh' => $value], 'node' => $node, 'attribute' => $attribute];
            if ($node->tagName === 'img' && $node->getAttribute('alt') === 'Auliachem Perkasa') {
                $fields[$key]['section'] = 'branding';
                $fields[$key]['label'] = $this->section($node) === 'footer' ? 'Logo footer' : 'Logo utama / header';
            }
        }
        foreach ($xpath->query('//style') as $node) {
            preg_match_all('/url\([\'"]?(https:\/\/[^\)\'\"]+)[\'"]?\)/', $node->textContent, $matches);
            foreach (array_unique($matches[1]) as $index => $url) {
                $key = 'background_'.$index;
                [$section, $label, $selector] = self::BACKGROUNDS[$index];
                $fields[$key] = ['key' => $key, 'section' => $section, 'type' => 'image', 'label' => $label,
                    'values' => ['id' => $url, 'en' => $url, 'zh' => $url], 'node' => $node, 'attribute' => 'background', 'original' => $url, 'selector' => $selector];
            }
        }

        $favicon = $doc->createElement('link');
        $favicon->setAttribute('rel', 'icon');
        $doc->getElementsByTagName('head')->item(0)->appendChild($favicon);
        $fields['brand_favicon'] = ['key' => 'brand_favicon', 'section' => 'branding', 'type' => 'image', 'label' => 'Favicon / ikon tab browser',
            'values' => array_fill_keys(['id', 'en', 'zh'], '/images/auliachem-logo.webp'), 'node' => $favicon, 'attribute' => 'href'];

        return $fields;
    }

    public function import(): int
    {
        $count = 0;
        foreach ($this->fields() as $field) {
            unset($field['node'], $field['attribute'], $field['original'], $field['selector']);
            $record = ContentField::firstOrCreate(['key' => $field['key']], $field);
            $count += $record->wasRecentlyCreated ? 1 : 0;
            $record->update(['section' => $field['section'], 'label' => $field['label']]);
        }

        return $count;
    }

    public function render(string $locale, bool $preview = false): string
    {
        $doc = $this->document();
        $stored = ContentField::all()->keyBy('key');
        $bindings = [];
        $mobileStyles = [];
        foreach ($this->fields($doc) as $key => $field) {
            $record = $stored->get($key);
            $values = $record?->values ?? $field['values'];
            $value = $values[$locale] ?? $values['id'] ?? '';
            if ($field['attribute'] === 'background') {
                $style = str_replace($field['original'], $value, $field['node']->textContent);
                while ($field['node']->firstChild) {
                    $field['node']->removeChild($field['node']->firstChild);
                }
                $field['node']->appendChild($doc->createTextNode($style));
            } elseif ($field['attribute']) {
                $field['node']->setAttribute($field['attribute'], $value);
                if ($field['type'] === 'image' && $field['node'] instanceof DOMElement && $field['node']->tagName === 'img') {
                    $field['node']->setAttribute('alt', $record?->alt_values[$locale] ?? $record?->alt_values['id'] ?? $field['label']);
                    $field['node']->setAttribute('style', trim($field['node']->getAttribute('style').';object-position:'.($record?->image_position ?? 'center')));
                    if ($record?->mobile_value) {$field['node']->setAttribute('srcset', $record->mobile_value.' 650w, '.$value.' 1600w');$field['node']->setAttribute('sizes','(max-width:650px) 100vw, 1600px');}
                }
            } else {
                preg_match('/^(\s*)/u', $field['node']->nodeValue, $leading);
                preg_match('/(\s*)$/u', $field['node']->nodeValue, $trailing);
                $field['node']->nodeValue = $leading[1].$value.$trailing[1];
            }
            $bindings[$key] = [
                'path' => $field['node']->getNodePath(), 'attribute' => $field['attribute'],
                'selector' => $field['selector'] ?? null, 'value' => $value,
                'section' => $field['section'],
            ];
            if ($field['attribute'] === 'background' && $record?->mobile_value) $mobileStyles[] = $field['selector'].'{background-image:url("'.$record->mobile_value.'")!important;background-position:'.($record->image_position ?? 'center').'!important}';
        }
        $xpath = new DOMXPath($doc);
        foreach (iterator_to_array($xpath->query('//script')) as $script) {
            $script->parentNode->removeChild($script);
        }
        foreach ($xpath->query('//button[@data-lang]') as $button) {
            $language = $button->getAttribute('data-lang');
            $button->setAttribute('class', $language === $locale ? 'active' : '');
            $button->setAttribute('aria-pressed', $language === $locale ? 'true' : 'false');
        }
        $doc->documentElement->setAttribute('lang', $locale === 'zh' ? 'zh-CN' : $locale);
        $button = $doc->getElementById('menuBtn');
        $button?->setAttribute('aria-label', 'Menu');
        $button?->setAttribute('aria-expanded', 'false');
        $button?->setAttribute('aria-controls', 'menu');
        $css = $doc->createElement('link');
        $css->setAttribute('rel', 'stylesheet');
        $css->setAttribute('href', asset('css/company.css'));
        $doc->getElementsByTagName('head')->item(0)->appendChild($css);
        if ($mobileStyles) {$mobileCss=$doc->createElement('style');$mobileCss->appendChild($doc->createTextNode('@media(max-width:650px){'.implode('', $mobileStyles).'}'));$doc->getElementsByTagName('head')->item(0)->appendChild($mobileCss);}
        $head = $doc->getElementsByTagName('head')->item(0);
        $title = trim($doc->getElementsByTagName('title')->item(0)?->textContent ?? 'Auliachem Perkasa');
        $description = '';
        foreach ($doc->getElementsByTagName('meta') as $candidate) {
            if ($candidate->getAttribute('name') === 'description') $description = $candidate->getAttribute('content');
        }
        $canonicalUrl = route('home', ['lang' => $locale]);
        foreach ([['link',['rel'=>'canonical','href'=>$canonicalUrl]],['meta',['property'=>'og:title','content'=>$title]],['meta',['property'=>'og:description','content'=>$description]],['meta',['property'=>'og:type','content'=>'website']],['meta',['property'=>'og:url','content'=>$canonicalUrl]],['meta',['name'=>'twitter:card','content'=>'summary_large_image']]] as [$tag,$attributes]) {
            $element = $doc->createElement($tag); foreach ($attributes as $name=>$content) $element->setAttribute($name, $content); $head->appendChild($element);
        }
        foreach (['id'=>'id','en'=>'en','zh'=>'zh-CN'] as $language=>$hreflang) {
            $alternate = $doc->createElement('link'); $alternate->setAttribute('rel','alternate'); $alternate->setAttribute('hreflang',$hreflang); $alternate->setAttribute('href',route('home',['lang'=>$language])); $head->appendChild($alternate);
        }
        $schema = $doc->createElement('script'); $schema->setAttribute('type','application/ld+json');
        $schema->appendChild($doc->createTextNode(json_encode(['@context'=>'https://schema.org','@type'=>'Organization','name'=>'Auliachem Perkasa','url'=>url('/'),'logo'=>asset('images/auliachem-logo.webp'),'description'=>$description], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))); $head->appendChild($schema);
        $script = $doc->createElement('script');
        $script->setAttribute('src', asset('js/company.js'));
        $script->setAttribute('defer', 'defer');
        $doc->getElementsByTagName('body')->item(0)->appendChild($script);
        $quality = $doc->getElementById('quality');
        $documents = Document::where('is_published', true)->latest()->get();
        if ($quality && $documents->isNotEmpty()) {
            $this->appendHtml($doc, $quality, view('site.documents', compact('documents', 'locale'))->render());
        }
        $core = $doc->getElementById('core');
        $products = Product::where('is_published', true)->orderBy('sort_order')->orderBy('name_id')->get();
        if ($core && $products->isNotEmpty()) {
            $this->appendHtml($doc, $core, view('site.products', compact('products', 'locale'))->render());
        }
        $contact = $doc->getElementById('contact');
        if ($contact) {
            $this->appendHtml($doc, $contact, view('site.quote-form', compact('locale'))->render());
        }
        if ($preview) {
            $meta = $doc->createElement('meta');
            $meta->setAttribute('name', 'robots');
            $meta->setAttribute('content', 'noindex,nofollow');
            $doc->getElementsByTagName('head')->item(0)->appendChild($meta);
            $data = $doc->createElement('script');
            $data->setAttribute('type', 'application/json');
            $data->setAttribute('id', 'cms-bindings');
            $data->appendChild($doc->createTextNode(json_encode($bindings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)));
            $doc->getElementsByTagName('body')->item(0)->appendChild($data);
            $previewScript = $doc->createElement('script');
            $previewScript->setAttribute('src', asset('js/cms-preview.js').'?v='.filemtime(public_path('js/cms-preview.js')));
            $previewScript->setAttribute('defer', 'defer');
            $doc->getElementsByTagName('body')->item(0)->appendChild($previewScript);
        }

        return '<!doctype html>'."\n".$doc->saveHTML($doc->documentElement);
    }

    private function appendHtml(DOMDocument $document, DOMNode $target, string $html): void
    {
        $fragmentDocument = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $fragmentDocument->loadHTML('<?xml encoding="UTF-8"><div id="fragment-root">'.$html.'</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = (new DOMXPath($fragmentDocument))->query('//*[@id="fragment-root"]')->item(0);
        foreach (iterator_to_array($root->childNodes) as $child) {
            $target->appendChild($document->importNode($child, true));
        }
    }
}
