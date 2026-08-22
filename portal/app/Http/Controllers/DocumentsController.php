<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('uploadedBy');

        if (!auth()->user()->hasAnyRole(['coordinator', 'planning_officer'])) {
            $query->where(function ($q) {
                $q->where('is_public', true)->orWhere('uploaded_by', auth()->id());
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        return view('documents.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|in:sop,template,policy,contract,report,audit,other',
            'description' => 'nullable|string',
            'version'     => 'required|integer|min:1',
            'is_public'   => 'nullable|boolean',
            'file'        => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'uploaded_by' => auth()->id(),
            'category'    => $data['category'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getMimeType(),
            'version'     => $data['version'],
            'is_public'   => $request->boolean('is_public'),
        ]);

        return redirect()->route('documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    public function show(Document $document)
    {
        return redirect()->away(Storage::url($document->file_path));
    }

    public function edit(Document $document)
    {
        return view('documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|in:sop,template,policy,contract,report,audit,other',
            'description' => 'nullable|string',
            'version'     => 'required|integer|min:1',
            'is_public'   => 'nullable|boolean',
        ]);

        $document->update([
            'title'       => $data['title'],
            'category'    => $data['category'],
            'description' => $data['description'] ?? null,
            'version'     => $data['version'],
            'is_public'   => $request->boolean('is_public'),
        ]);

        return redirect()->route('documents.index')
            ->with('success', 'Document updated.');
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return redirect()->route('documents.index')
            ->with('success', 'Document removed.');
    }
}
