<?php

namespace App\Http\Controllers;

use App\Models\LibraryItem;
use Illuminate\Http\Request;

class LibraryItemsController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', LibraryItem::class);

        $query = LibraryItem::with('createdBy')->orderByDesc('updated_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('approved')) {
            $query->where('is_approved', (bool) $request->approved);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('content', 'like', "%{$term}%");
            });
        }

        $items      = $query->paginate(20)->withQueryString();
        $categories = LibraryItem::categories();

        return view('library.index', compact('items', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', LibraryItem::class);

        $categories = LibraryItem::categories();
        return view('library.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', LibraryItem::class);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'category'         => 'required|in:' . implode(',', array_keys(LibraryItem::categories())),
            'content'          => 'required|string|min:20',
            'usage_notes'      => 'nullable|string',
            'tags'             => 'nullable|string',
            'funder_relevance' => 'nullable|string|max:255',
        ]);

        $data['created_by']  = auth()->id();
        $data['tags']        = $this->parseTags($data['tags'] ?? '');
        $data['is_approved'] = auth()->user()->hasRole('coordinator');

        LibraryItem::create($data);

        return redirect()->route('library.index')
            ->with('success', 'Library item added' . ($data['is_approved'] ? ' and published.' : '. Awaiting coordinator approval.'));
    }

    public function show(LibraryItem $library)
    {
        $this->authorize('view', $library);

        $library->load('createdBy', 'updatedBy');
        return view('library.show', compact('library'));
    }

    public function edit(LibraryItem $library)
    {
        $this->authorize('update', $library);

        $categories = LibraryItem::categories();
        return view('library.edit', compact('library', 'categories'));
    }

    public function update(Request $request, LibraryItem $library)
    {
        $this->authorize('update', $library);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'category'         => 'required|in:' . implode(',', array_keys(LibraryItem::categories())),
            'content'          => 'required|string|min:20',
            'usage_notes'      => 'nullable|string',
            'tags'             => 'nullable|string',
            'funder_relevance' => 'nullable|string|max:255',
        ]);

        $data['updated_by'] = auth()->id();
        $data['tags']       = $this->parseTags($data['tags'] ?? '');

        // Non-coordinators lose approval when they edit
        if (!auth()->user()->hasRole('coordinator')) {
            $data['is_approved'] = false;
        }

        $library->update($data);

        return redirect()->route('library.show', $library)
            ->with('success', 'Library item updated.');
    }

    public function destroy(LibraryItem $library)
    {
        $this->authorize('delete', $library);

        $library->delete();
        return redirect()->route('library.index')->with('success', 'Library item removed.');
    }

    public function toggleApproval(LibraryItem $library)
    {
        $this->authorize('approve', $library);

        $library->update(['is_approved' => !$library->is_approved, 'updated_by' => auth()->id()]);
        $state = $library->is_approved ? 'published' : 'unpublished';
        return back()->with('success', "Item {$state}.");
    }

    public function copy(LibraryItem $library)
    {
        $this->authorize('view', $library);

        $library->incrementUseCount();
        return response()->json(['content' => $library->content, 'title' => $library->title]);
    }

    private function parseTags(string $raw): ?array
    {
        $tags = array_filter(array_map('trim', explode(',', $raw)));
        return empty($tags) ? null : array_values($tags);
    }
}
