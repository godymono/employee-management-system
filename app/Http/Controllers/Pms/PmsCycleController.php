<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Pms\PmsCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmsCycleController extends Controller
{
    public function edit(PmsCycle $cycle): View
    {
        return view('pms.cycles.edit', compact('cycle'));
    }

    public function update(Request $request, PmsCycle $cycle): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'h1_opens_at' => ['nullable', 'date'],
            'h1_closes_at' => ['nullable', 'date', 'after_or_equal:h1_opens_at'],
            'h2_opens_at' => ['nullable', 'date'],
            'h2_closes_at' => ['nullable', 'date', 'after_or_equal:h2_opens_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Active/closed is driven by the calendar year — not manually toggled.
        $data['status'] = $cycle->isCurrentCalendarYear() ? 'active' : 'closed';

        $cycle->update($data);

        return redirect()
            ->route('pms.index', ['year' => $cycle->year])
            ->with('success', $cycle->year.' year settings updated.');
    }
}
