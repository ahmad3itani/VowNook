<?php

namespace App\Http\Controllers;

use App\Models\WeddingPartyMember;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Couple-side editor for the wedding party (bridal party, groomsmen, family). */
class WeddingPartyController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function store(Request $request): RedirectResponse
    {
        $this->validateMember($request);
        $wedding = $this->current->get();

        $data = $request->only(['name', 'role', 'side', 'bio']);
        $data['wedding_id'] = $wedding->id;
        $data['sort_order'] = (int) WeddingPartyMember::forWedding($wedding->id)->max('sort_order') + 1;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = ImageOptimizer::store($request->file('photo'), "websites/{$wedding->id}/party", 800);
        }

        WeddingPartyMember::create($data);

        return back()->with('status', 'party-saved');
    }

    public function update(Request $request, WeddingPartyMember $member): RedirectResponse
    {
        $this->authorizeOwn($member);
        $this->validateMember($request);

        $data = $request->only(['name', 'role', 'side', 'bio']);

        if ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::delete($member->photo_path);
            }
            $data['photo_path'] = ImageOptimizer::store($request->file('photo'), "websites/{$member->wedding_id}/party", 800);
        }

        $member->update($data);

        return back()->with('status', 'party-saved');
    }

    public function destroy(WeddingPartyMember $member): RedirectResponse
    {
        $this->authorizeOwn($member);

        if ($member->photo_path) {
            Storage::delete($member->photo_path);
        }

        $member->delete();

        return back()->with('status', 'party-deleted');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $owned = WeddingPartyMember::forWedding($this->current->id())->pluck('id')->all();

        foreach ($data['ids'] as $position => $id) {
            if (in_array((int) $id, $owned, true)) {
                WeddingPartyMember::where('id', $id)->update(['sort_order' => $position]);
            }
        }

        return back()->with('status', 'party-saved');
    }

    private function validateMember(Request $request): void
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:80'],
            'side' => ['required', Rule::in(WeddingPartyMember::SIDES)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);
    }

    private function authorizeOwn(WeddingPartyMember $member): void
    {
        abort_unless($member->wedding_id === $this->current->id(), 404);
    }
}
