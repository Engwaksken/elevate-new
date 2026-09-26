@extends('layouts.admin')

@section('title', 'Appraisal KPI Score | ElevateHer360 Administration')

@section('content')
@php
    $appraisal = $appraisal ?? null;
    $template = $template ?? ($appraisal?->kpiTemplate ?? null);
    $items = collect($items ?? ($template?->items ?? []));
    $scores = collect($scores ?? ($appraisal?->kpiScores ?? []));
    $weekly = collect($weekly ?? ($appraisal?->kpiWeeklyUpdates ?? []));

    $employeeName = data_get($appraisal, 'employee.user.name')
        ?? data_get($appraisal, 'employee.name')
        ?? data_get($appraisal, 'user.name')
        ?? 'Staff Member';

    $templateName = data_get($template, 'name') ?? 'Performance Appraisal';

    $scoreForItem = function ($item) use ($scores) {
        $itemId = data_get($item, 'id');

        return $scores->get($itemId)
            ?? $scores->firstWhere('hr_kpi_template_item_id', $itemId);
    };

    $weeklyForItem = function ($item) use ($weekly) {
        $itemId = data_get($item, 'id');
        $rows = $weekly->get($itemId, collect());

        return collect($rows)->sortBy(function ($row) {
            return (int) data_get($row, 'week_number', 0);
        });
    };

    $overallPercent = data_get($appraisal, 'performance_percent');

    if ($overallPercent === null) {
        $overallPercent = data_get($appraisal, 'final_score')
            ?? data_get($appraisal, 'manager_score')
            ?? data_get($appraisal, 'self_score');
    }

    $overallPercent = $overallPercent !== null
        ? (float) $overallPercent
        : null;

    $overallLabel = app(\App\Services\HR\AppraisalRatingService::class)
        ->label($overallPercent);

    $saveRoute = Route::has('admin.hr.appraisals.kpi-score')
        ? 'admin.hr.appraisals.kpi-score'
        : null;

    $recalculateRoute = Route::has('admin.hr.appraisals.recalculate')
        ? 'admin.hr.appraisals.recalculate'
        : null;

    /*
     * Build KRA tabs from the exact imported order.
     * KPIs/OKRs after a KRA belong to that KRA until the next KRA.
     */
    $kraGroups = collect();
    $behaviouralItems = collect();
    $generalItems = collect();
    $currentKraKey = null;

    foreach ($items as $item) {
        $type = strtolower((string) data_get($item, 'item_type'));
        $title = data_get($item, 'title')
            ?? data_get($item, 'name')
            ?? 'Appraisal Item';

        if ($type === 'kra') {
            $currentKraKey = 'kra_' . data_get($item, 'id', $kraGroups->count() + 1);

            $kraGroups->put($currentKraKey, [
                'title' => $title,
                'item' => $item,
                'children' => collect(),
            ]);

            continue;
        }

        if (in_array($type, ['behavioral', 'behavioural'], true)) {
            if (! data_get($item, 'meta.group')) {
                $behaviouralItems->push($item);
            }

            continue;
        }

        if ($currentKraKey && $kraGroups->has($currentKraKey)) {
            $group = $kraGroups->get($currentKraKey);
            $group['children']->push($item);
            $kraGroups->put($currentKraKey, $group);
        } else {
            $generalItems->push($item);
        }
    }

    if ($generalItems->isNotEmpty()) {
        $kraGroups->prepend([
            'title' => 'General KPIs',
            'item' => null,
            'children' => $generalItems,
        ], 'general');
    }
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Human Resources</span>
        <h1>Performance Appraisal</h1>
        <p>{{ $employeeName }} · Source: {{ $templateName }}</p>
    </div>

    <div class="admin-page-actions">
        @if(Route::has('admin.hr.appraisals.index'))
            <a href="{{ route('admin.hr.appraisals.index') }}" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Appraisals
            </a>
        @endif

        @if($recalculateRoute)
            <form method="POST" action="{{ route($recalculateRoute, $appraisal) }}">
                @csrf
                <button type="submit" class="btn btn-outline">
                    <i class="fas fa-calculator"></i>
                    Recalculate
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="admin-stats-grid compact">
    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-layer-group"></i></span>
        <div>
            <small>Key Result Areas</small>
            <strong>{{ $kraGroups->count() }}</strong>
        </div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-clipboard-check"></i></span>
        <div>
            <small>Score Records</small>
            <strong>{{ $scores->count() }}</strong>
        </div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-chart-line"></i></span>
        <div>
            <small>Performance</small>
            <strong>{{ $overallPercent !== null ? number_format($overallPercent, 1).'%' : '—' }}</strong>
        </div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-award"></i></span>
        <div>
            <small>Rating</small>
            <strong>{{ $overallLabel }}</strong>
        </div>
    </div>
</div>

@if($items->isEmpty())
    <div class="admin-panel">
        <div class="admin-empty">
            <i class="fas fa-clipboard-list"></i>
            <h3>No KPI template items found</h3>
            <p>This appraisal has no imported KPI items to score.</p>
        </div>
    </div>
@else
    <form
        method="POST"
        action="{{ $saveRoute ? route($saveRoute, $appraisal) : '#' }}"
        class="appraisal-form appraisal-admin-kra-workflow"
        id="admin-appraisal-kra-form"
    >
        @csrf

        <div class="appraisal-admin-kra-tabs" role="tablist" aria-label="Key Result Areas">
            @foreach($kraGroups as $kraKey => $group)
                <button
                    type="button"
                    class="appraisal-admin-kra-tab {{ $loop->first ? 'active' : '' }}"
                    data-admin-kra-tab="{{ $kraKey }}"
                    role="tab"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                >
                    <i class="fas fa-bullseye"></i>
                    <span>{{ $group['title'] }}</span>
                    <small>{{ collect($group['children'])->count() }}</small>
                </button>
            @endforeach

            @if($behaviouralItems->isNotEmpty())
                <button
                    type="button"
                    class="appraisal-admin-kra-tab {{ $kraGroups->isEmpty() ? 'active' : '' }}"
                    data-admin-kra-tab="behavioural"
                    role="tab"
                    aria-selected="{{ $kraGroups->isEmpty() ? 'true' : 'false' }}"
                >
                    <i class="fas fa-user-check"></i>
                    <span>Behavioural</span>
                    <small>{{ $behaviouralItems->count() }}</small>
                </button>
            @endif

            <button
                type="button"
                class="appraisal-admin-kra-tab"
                data-admin-kra-tab="overall"
                role="tab"
                aria-selected="false"
            >
                <i class="fas fa-comments"></i>
                <span>Overall Comments</span>
            </button>
        </div>

        <div class="appraisal-admin-kra-panels">
            @foreach($kraGroups as $kraKey => $group)
                <section
                    class="appraisal-admin-kra-panel {{ $loop->first ? 'active' : '' }}"
                    data-admin-kra-panel="{{ $kraKey }}"
                    role="tabpanel"
                >
                    <div class="admin-panel appraisal-kra-card">
                        <div class="appraisal-section-head">
                            <div>
                                <span class="admin-eyebrow">Key Result Area</span>
                                <h2>{{ $group['title'] }}</h2>
                            </div>

                            @if(data_get($group, 'item.weight') !== null)
                                <span class="appraisal-weight">
                                    {{ number_format((float) data_get($group, 'item.weight'), 1) }}%
                                </span>
                            @endif
                        </div>
                    </div>

                    @forelse($group['children'] as $item)
                        @php
                            $itemId = data_get($item, 'id');
                            $itemType = strtolower((string) data_get($item, 'item_type'));
                            $itemTitle = data_get($item, 'title')
                                ?? data_get($item, 'name')
                                ?? 'Appraisal Item';
                            $itemWeight = data_get($item, 'weight');
                            $scoreRecord = $scoreForItem($item);
                            $weeklyRows = $weeklyForItem($item);
                        @endphp

                        <div class="admin-panel appraisal-item-card">
                            <div class="appraisal-section-head">
                                <div>
                                    <span class="admin-eyebrow">
                                        {{ strtoupper($itemType ?: 'KPI') }}
                                    </span>
                                    <h3>{{ $itemTitle }}</h3>
                                </div>

                                @if($itemWeight !== null)
                                    <span class="appraisal-weight">
                                        {{ number_format((float) $itemWeight, 1) }}%
                                    </span>
                                @endif
                            </div>

                            @if($itemType === 'okr')
                                <div class="appraisal-rating-grid">
                                    <div class="form-group">
                                        <label>Employee Achievement %</label>
                                        <input
                                            type="number"
                                            value="{{ data_get($scoreRecord, 'okr_percent') }}"
                                            placeholder="Employee achievement %"
                                            readonly
                                        >
                                    </div>

                                    <div class="form-group">
                                        <label>Manager Rating</label>
                                        <select name="scores[{{ $itemId }}][manager_rating]">
                                            <option value="">Select manager rating</option>
                                            @foreach(range(1, 5) as $rating)
                                                <option
                                                    value="{{ $rating }}"
                                                    @selected((string) old("scores.$itemId.manager_rating", data_get($scoreRecord, 'manager_rating')) === (string) $rating)
                                                >
                                                    {{ $rating }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Agreed Rating</label>
                                        <select name="scores[{{ $itemId }}][agreed_rating]">
                                            <option value="">Select agreed rating</option>
                                            @foreach(range(1, 5) as $rating)
                                                <option
                                                    value="{{ $rating }}"
                                                    @selected((string) old("scores.$itemId.agreed_rating", data_get($scoreRecord, 'agreed_rating')) === (string) $rating)
                                                >
                                                    {{ $rating }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="appraisal-admin-weekly">
                                    <div class="appraisal-admin-weekly__title">
                                        <strong>Weekly Progress</strong>
                                        <span>Weeks 1–13</span>
                                    </div>

                                    <div class="admin-table-wrap">
                                        <table class="admin-table appraisal-week-table">
                                            <thead>
                                                <tr>
                                                    <th>Week</th>
                                                    <th>Actual / Target</th>
                                                    <th>Comment</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(range(1, 13) as $weekNumber)
                                                    @php
                                                        $weekRecord = $weeklyRows->get($weekNumber)
                                                            ?? $weeklyRows->firstWhere('week_number', $weekNumber);
                                                    @endphp

                                                    <tr>
                                                        <td><strong>Week {{ $weekNumber }}</strong></td>
                                                        <td>{{ data_get($weekRecord, 'actual_target') ?: '—' }}</td>
                                                        <td>{{ data_get($weekRecord, 'comment') ?: '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="appraisal-rating-grid">
                                    <div class="form-group">
                                        <label>Employee Rating</label>
                                        <input
                                            type="text"
                                            value="{{ data_get($scoreRecord, 'employee_rating') ?? '—' }}"
                                            placeholder="Employee rating"
                                            readonly
                                        >
                                    </div>

                                    <div class="form-group">
                                        <label>Manager Rating</label>
                                        <select name="scores[{{ $itemId }}][manager_rating]">
                                            <option value="">Select manager rating</option>
                                            @foreach(range(1, 5) as $rating)
                                                <option
                                                    value="{{ $rating }}"
                                                    @selected((string) old("scores.$itemId.manager_rating", data_get($scoreRecord, 'manager_rating')) === (string) $rating)
                                                >
                                                    {{ $rating }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Agreed Rating</label>
                                        <select name="scores[{{ $itemId }}][agreed_rating]">
                                            <option value="">Select agreed rating</option>
                                            @foreach(range(1, 5) as $rating)
                                                <option
                                                    value="{{ $rating }}"
                                                    @selected((string) old("scores.$itemId.agreed_rating", data_get($scoreRecord, 'agreed_rating')) === (string) $rating)
                                                >
                                                    {{ $rating }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="appraisal-comment-grid">
                                <div class="form-group">
                                    <label>Employee Comment</label>
                                    <textarea
                                        rows="3"
                                        placeholder="Employee comment"
                                        readonly
                                    >{{ data_get($scoreRecord, 'employee_comment') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label>Manager Comment</label>
                                    <textarea
                                        name="scores[{{ $itemId }}][manager_comment]"
                                        rows="3"
                                        placeholder="Add manager comment"
                                    >{{ old("scores.$itemId.manager_comment", data_get($scoreRecord, 'manager_comment')) }}</textarea>
                                </div>
                            </div>

                            <div class="appraisal-comment-grid">
                                <div class="form-group">
                                    <label>Evidence / Supporting Notes</label>
                                    <textarea
                                        rows="2"
                                        placeholder="Employee evidence or supporting notes"
                                        readonly
                                    >{{ data_get($scoreRecord, 'evidence_note') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label>Evidence URL</label>
                                    <input
                                        type="url"
                                        value="{{ data_get($scoreRecord, 'evidence_url') }}"
                                        placeholder="No evidence URL provided"
                                        readonly
                                    >
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="admin-panel">
                            <div class="admin-empty compact">
                                <i class="fas fa-circle-info"></i>
                                <p>No KPI or OKR items are linked to this Key Result Area.</p>
                            </div>
                        </div>
                    @endforelse

                    <div class="admin-panel appraisal-admin-tab-actions">
                        <button type="button" class="btn btn-outline" data-admin-kra-prev>
                            <i class="fas fa-arrow-left"></i>
                            Previous KRA
                        </button>

                        @if($saveRoute)
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-floppy-disk"></i>
                                Save Review
                            </button>
                        @endif

                        <button type="button" class="btn btn-outline" data-admin-kra-next>
                            Next KRA
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </section>
            @endforeach

            @if($behaviouralItems->isNotEmpty())
                <section
                    class="appraisal-admin-kra-panel {{ $kraGroups->isEmpty() ? 'active' : '' }}"
                    data-admin-kra-panel="behavioural"
                    role="tabpanel"
                >
                    <div class="admin-panel appraisal-kra-card">
                        <div class="appraisal-section-head">
                            <div>
                                <span class="admin-eyebrow">Behavioural Assessment</span>
                                <h2>Behavioural Competencies</h2>
                            </div>
                        </div>
                    </div>

                    @foreach($behaviouralItems as $item)
                        @php
                            $itemId = data_get($item, 'id');
                            $scoreRecord = $scoreForItem($item);
                        @endphp

                        <div class="admin-panel appraisal-item-card">
                            <h3>{{ data_get($item, 'title') }}</h3>

                            <div class="appraisal-rating-grid">
                                <div class="form-group">
                                    <label>Employee Rating</label>
                                    <input
                                        type="text"
                                        value="{{ data_get($scoreRecord, 'employee_rating') ?? '—' }}"
                                        placeholder="Employee rating"
                                        readonly
                                    >
                                </div>

                                <div class="form-group">
                                    <label>Manager Rating</label>
                                    <select name="scores[{{ $itemId }}][manager_rating]">
                                        <option value="">Select manager rating</option>
                                        @foreach([1 => 'Never', 2 => 'Occasionally', 3 => 'Always'] as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected((string) old("scores.$itemId.manager_rating", data_get($scoreRecord, 'manager_rating')) === (string) $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Agreed Rating</label>
                                    <select name="scores[{{ $itemId }}][agreed_rating]">
                                        <option value="">Select agreed rating</option>
                                        @foreach([1 => 'Never', 2 => 'Occasionally', 3 => 'Always'] as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected((string) old("scores.$itemId.agreed_rating", data_get($scoreRecord, 'agreed_rating')) === (string) $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="appraisal-comment-grid">
                                <div class="form-group">
                                    <label>Employee Comment</label>
                                    <textarea
                                        rows="3"
                                        placeholder="Employee comment"
                                        readonly
                                    >{{ data_get($scoreRecord, 'employee_comment') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label>Manager Comment</label>
                                    <textarea
                                        name="scores[{{ $itemId }}][manager_comment]"
                                        rows="3"
                                        placeholder="Add manager comment"
                                    >{{ old("scores.$itemId.manager_comment", data_get($scoreRecord, 'manager_comment')) }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="admin-panel appraisal-admin-tab-actions">
                        <button type="button" class="btn btn-outline" data-admin-kra-prev>
                            <i class="fas fa-arrow-left"></i>
                            Previous
                        </button>

                        @if($saveRoute)
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-floppy-disk"></i>
                                Save Review
                            </button>
                        @endif

                        <button type="button" class="btn btn-outline" data-admin-kra-next>
                            Next
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </section>
            @endif

            <section
                class="appraisal-admin-kra-panel {{ $kraGroups->isEmpty() && $behaviouralItems->isEmpty() ? 'active' : '' }}"
                data-admin-kra-panel="overall"
                role="tabpanel"
            >
                <div class="admin-panel appraisal-kra-card">
                    <div class="appraisal-section-head">
                        <div>
                            <span class="admin-eyebrow">Summary</span>
                            <h2>Overall Comments & Development Plan</h2>
                        </div>
                    </div>
                </div>

                <div class="admin-panel">
                    <div class="appraisal-comment-grid">
                        <div class="form-group">
                            <label>Overall Employee Comments</label>
                            <textarea
                                rows="5"
                                placeholder="Overall employee comments"
                                readonly
                            >{{ $appraisal?->employee_comments }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Overall Manager Comments</label>
                            <textarea
                                name="overall_manager_comments"
                                rows="5"
                                placeholder="Add overall manager comments"
                            >{{ old('overall_manager_comments', $appraisal?->manager_comments) }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Development Plan</label>
                        <textarea
                            name="development_plan"
                            rows="5"
                            placeholder="Add development actions, support required and follow-up plans"
                        >{{ old('development_plan', $appraisal?->development_plan) }}</textarea>
                    </div>
                </div>

                @if($saveRoute)
                    <div class="admin-panel appraisal-form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i>
                            Save Appraisal Review
                        </button>
                    </div>
                @endif
            </section>
        </div>
    </form>
@endif
@endsection
