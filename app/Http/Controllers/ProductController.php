<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\AuditLog;

class ProductController extends Controller
{
    public function index()
    {
        return view('cms.products.index', ['products' => Product::orderBy('sort_order')->orderBy('name_id')->paginate(20)]);
    }

    public function create()
    {
        return view('cms.products.form', ['product' => new Product]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->uniqueSlug($data['name_id']);
        $data['is_published'] = $request->boolean('is_published');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product = Product::create($data); AuditLog::record('product.created', 'Produk '.$product->name_id.' dibuat.', $product);

        return redirect()->route('cms.products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('cms.products.form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate($this->rules($product));
        $data['is_published'] = $request->boolean('is_published');
        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('products', 'public');
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $newPath;
        }
        $product->update($data);
        AuditLog::record('product.updated', 'Produk '.$product->name_id.' diperbarui.', $product);

        return redirect()->route('cms.products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        AuditLog::record('product.deleted', 'Produk '.$product->name_id.' dihapus.', $product); $product->delete();

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    private function rules(?Product $product = null): array
    {
        return [
            'name_id' => ['required', 'string', 'max:180'],
            'name_en' => ['nullable', 'string', 'max:180'],
            'name_zh' => ['nullable', 'string', 'max:180'],
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'sku' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:160'],
            'packaging' => ['nullable', 'string', 'max:160'],
            'application' => ['nullable', 'string', 'max:255'],
            'summary_id' => ['nullable', 'string', 'max:1500'],
            'summary_en' => ['nullable', 'string', 'max:1500'],
            'summary_zh' => ['nullable', 'string', 'max:1500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $index = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$index++;
        }

        return $slug;
    }
}
