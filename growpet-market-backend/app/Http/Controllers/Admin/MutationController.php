<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mutation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MutationController extends Controller
{
    public function index(): View
    {
        return view('admin.mutations.index', [
            'mutations' => Mutation::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Mutation::query()->create($this->validatedData($request));

        return back()->with('status', 'Mutasi berhasil dibuat.');
    }

    public function update(Request $request, Mutation $mutation): RedirectResponse
    {
        $mutation->update($this->validatedData($request, $mutation));

        return back()->with('status', 'Mutasi berhasil diupdate.');
    }

    public function destroy(Mutation $mutation): RedirectResponse
    {
        $mutation->update(['active' => false]);

        return back()->with('status', 'Mutasi dinonaktifkan.');
    }

    private function validatedData(Request $request, ?Mutation $mutation = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('mutations')->ignore($mutation)],
            'price_modifier' => ['required', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]) + ['active' => false];
    }
}
