<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\ConversionTracker;
use App\Services\AuditLog;

class DocumentController extends Controller
{
    public function index()
    {
        return view('cms.documents.index', ['documents' => Document::latest()->paginate(20)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(true));
        $file = $request->file('file');
        $document = Document::create([
            ...$validated,
            'file_path' => $file->store('documents'),
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'is_published' => $request->boolean('is_published'),
        ]);
        AuditLog::record('document.created', 'Dokumen '.$document->title.' diunggah.', $document);

        return back()->with('status', 'Dokumen berhasil diunggah.');
    }

    public function edit(Document $document)
    {
        return view('cms.documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate($this->rules(false));
        $data = [...$validated, 'is_published' => $request->boolean('is_published')];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $newPath = $file->store('documents');
            Storage::delete($document->file_path);
            $data += ['file_path' => $newPath, 'original_name' => $file->getClientOriginalName(), 'file_size' => $file->getSize()];
        }
        $document->update($data);
        AuditLog::record('document.updated', 'Dokumen '.$document->title.' diperbarui.', $document);

        return redirect()->route('cms.documents.index')->with('status', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Document $document)
    {
        Storage::delete($document->file_path);
        AuditLog::record('document.deleted', 'Dokumen '.$document->title.' dihapus.', $document); $document->delete();

        return back()->with('status', 'Dokumen berhasil dihapus.');
    }

    public function download(Request $request, Document $document, ConversionTracker $tracker)
    {
        abort_unless($document->is_published && Storage::exists($document->file_path), 404);
        $document->increment('downloads');
        $tracker->record($request, 'document_download', ['document_id'=>$document->id]);

        return Storage::download($document->file_path, $document->original_name, ['Content-Type' => 'application/pdf']);
    }

    private function rules(bool $fileRequired): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'in:coa,tds,msds,certificate,catalog,other'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
