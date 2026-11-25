@extends('layouts.dashboard')

@section('title', 'Course Approval')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-container-max mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-6 md:p-12 mb-10 border-t-8 border-secondary relative overflow-hidden">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Course Management</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Create courses and manage the syllabus.</p>
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-64 h-64 bg-secondary-fixed rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    </div>
    @if(session('success'))
        <div class="mb-6 p-4 bg-tertiary-fixed border border-tertiary-fixed-dim text-tertiary rounded-2xl flex items-center gap-3">
            <span class="material-symbols-outlined text-on-tertiary-container">check_circle</span>
            <div class="font-bold text-sm">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-error-container border border-error-container text-error rounded-2xl flex items-center gap-3">
            <span class="material-symbols-outlined text-error">error</span>
            <div class="font-bold text-sm">{{ session('error') }}</div>
        </div>
    @endif

    @if(session('import_errors'))
        <div class="mb-6 p-4 bg-error-container border border-error-container text-error rounded-2xl">
            <div class="flex items-center gap-3 mb-2">
                <span class="material-symbols-outlined text-error">error</span>
                <div class="font-bold text-sm">Import blocked — please fix the following and upload again:</div>
            </div>
            <ul class="list-disc pl-10 space-y-1 text-sm">
                @foreach(session('import_errors') as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('import_warnings') && count(session('import_warnings')))
        <div class="mb-6 p-4 bg-warning-container border border-warning-container text-warning rounded-2xl">
            <div class="flex items-center gap-3 mb-2">
                <span class="material-symbols-outlined text-warning">warning</span>
                <div class="font-bold text-sm">Import completed with {{ count(session('import_warnings')) }} warning(s):</div>
            </div>
            <ul class="list-disc pl-10 space-y-1 text-sm">
                @foreach(session('import_warnings') as $importWarning)
                    <li>{{ $importWarning }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.courses.sample-csv') }}" class="px-4 py-2.5 bg-surface-container-high hover:bg-surface-container-highest border border-outline-variant text-on-surface text-sm font-bold rounded-xl inline-flex items-center gap-2 transition-all shadow-sm" title="Download unified course + classes CSV template (24 sample classes with practical_video_url)">
                <span class="material-symbols-outlined text-base text-primary">download</span> Download Course CSV Template
            </a>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" onclick="openBulkUploadModal()" class="px-5 py-3 bg-secondary hover:bg-secondary/90 text-on-secondary font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all">
                <span class="material-symbols-outlined text-xl">upload_file</span> Bulk Upload (CSV)
            </button>
            <a href="{{ route('admin.courses.create') }}" class="btn-primary flex items-center gap-2 px-6 py-3">
                <span class="material-symbols-outlined">add</span> Create Course
            </a>
        </div>
    </div>

    <div class="clay-card p-6 mb-6">
        <form method="GET" action="{{ route('admin.courses') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-sm font-bold mb-2" for="admin-course-search">Search</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">search</span>
                    <input
                        id="admin-course-search"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by title, category, or tutor…"
                        class="w-full pl-10 pr-3 py-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none"
                    >
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Filter by Status</label>
                <select name="status" class="p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none">
                    <option value="">All Statuses</option>
                    @foreach(['draft', 'pending', 'published', 'rejected'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary btn-sm">Search</button>
            @if(request()->filled('status') || request()->filled('search'))
                <a href="{{ route('admin.courses') }}" class="px-6 py-3 bg-surface-container text-on-surface font-bold rounded-xl">Clear</a>
            @endif
        </form>
    </div>

    <div class="clay-card p-5 md:p-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-outline-variant">
                        <th class="py-4 px-4 font-bold text-on-surface">Course</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Category</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Price</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Created By</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Status</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Active</th>
                        <th class="py-4 px-4 font-bold text-on-surface">Submitted</th>
                        <th class="py-4 px-4 font-bold text-on-surface text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <td class="py-4 px-4 font-medium">{{ $course->title }}</td>
                            <td class="py-4 px-4 text-on-surface-variant">{{ $course->categories->count() ? $course->categories->pluck('name')->join(', ') : 'Uncategorized' }}</td>
                            <td class="py-4 px-4 font-bold">${{ number_format($course->price_usd ?? 18, 2) }} / class</td>
                            <td class="py-4 px-4">
                                @if($course->tutor)
                                    <span class="font-bold text-tertiary text-sm">Tutor: {{ $course->tutor->name }}</span>
                                @else
                                    <span class="font-bold text-primary text-sm">Admin</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <form method="POST" action="{{ route('admin.courses.status', $course) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="p-2 text-sm font-bold rounded-lg border border-outline-variant bg-surface-container-low
                                        {{ $course->status === 'published' ? 'text-on-tertiary-container' : ($course->status === 'pending' ? 'text-warning' : ($course->status === 'rejected' ? 'text-error' : 'text-on-surface-variant')) }}">
                                        @foreach(['draft', 'pending', 'published', 'rejected'] as $status)
                                            <option value="{{ $status }}" {{ $course->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <form method="POST" action="{{ route('admin.courses.toggle-active', $course) }}">
                                    @csrf @method('PATCH')
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" onchange="this.form.submit()" class="sr-only peer" {{ $course->is_active ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-surface-container-high peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-outline-variant after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-success"></div>
                                    </label>
                                </form>
                            </td>
                            <td class="py-4 px-4 text-on-surface-variant text-sm"><x-local-time :date="$course->created_at" format="date" /></td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <button
                                  type="button"
                                  onclick="copyReviewLink('{{ route('courses.review.create', $course) }}')"
                                  class="text-on-tertiary-container font-bold hover:underline mr-3 text-sm inline-flex items-center gap-1"
                                  title="Copy parent review link"
                                  id="copy-btn-{{ $course->id }}"
                                >
                                  📋 Review Link
                                </button>
                                <button
                                  type="button"
                                  onclick="openCustomLinkModal('{{ $course->slug }}')"
                                  class="text-on-secondary-container font-bold hover:underline mr-3 text-sm inline-flex items-center gap-1"
                                  title="Generate custom enrollment link"
                                >
                                  🔗 Custom Link
                                </button>
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-primary font-bold hover:underline mr-3">Edit</a>
                                <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-error font-bold hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-on-surface-variant">No courses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
  #copy-toast {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #0f172a;
    color: #fff;
    padding: 12px 24px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 600;
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 9999;
    pointer-events: none;
    white-space: nowrap;
  }
  #copy-toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
</style>
<div id="copy-toast">✅ Review link copied to clipboard!</div>
<script>
function copyReviewLink(url) {
  navigator.clipboard.writeText(url).then(() => {
    const toast = document.getElementById('copy-toast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
  }).catch(() => {
    // Fallback for older browsers
    const tmp = document.createElement('textarea');
    tmp.value = url;
    document.body.appendChild(tmp);
    tmp.select();
    document.execCommand('copy');
    document.body.removeChild(tmp);
    const toast = document.getElementById('copy-toast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
  });
}
</script>

<div id="custom-link-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-surface p-6 rounded-2xl shadow-xl w-full max-w-md">
        <h2 class="text-xl font-bold mb-4 text-on-surface">Generate Custom Link</h2>
        <form id="custom-link-form" onsubmit="generateCustomLink(event)">
            @csrf
            <input type="hidden" id="custom-link-course-id" name="course_id">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Price</label>
                <input type="number" step="0.01" id="custom-link-price" name="price" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Currency</label>
                <select id="custom-link-currency" name="currency" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" required>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                    <option value="AUD">AUD</option>
                    <option value="CAD">CAD</option>
                    <option value="INR">INR</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Number of Classes</label>
                <input type="number" id="custom-link-classes" name="classes" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl focus:border-primary outline-none" min="1" value="1">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeCustomLinkModal()" class="px-4 py-2 font-bold text-on-surface-variant hover:bg-surface-container rounded-xl">Done</button>
                <button type="submit" class="btn-primary">Generate</button>
            </div>
        </form>
        <div id="custom-link-result" class="mt-4 hidden">
            <p class="text-sm font-bold mb-2 text-on-surface">Generated Link:</p>
            <div class="flex gap-2">
                <input type="text" id="custom-link-url" class="w-full p-2 bg-surface-container-low border border-outline-variant rounded-lg text-sm" readonly>
                <button type="button" onclick="copyCustomLink()" class="btn-primary btn-sm whitespace-nowrap">Copy</button>
            </div>
        </div>
    </div>
</div>

<script>
function openCustomLinkModal(courseId) {
    document.getElementById('custom-link-result').classList.add('hidden');
    document.getElementById('custom-link-form').reset();
    document.getElementById('custom-link-course-id').value = courseId;
    document.getElementById('custom-link-modal').classList.remove('hidden');
}

function closeCustomLinkModal() {
    document.getElementById('custom-link-modal').classList.add('hidden');
}

function generateCustomLink(e) {
    e.preventDefault();
    const courseId = document.getElementById('custom-link-course-id').value;
    const price = document.getElementById('custom-link-price').value;
    const currency = document.getElementById('custom-link-currency').value;
    const classes = document.getElementById('custom-link-classes').value;
    const token = document.querySelector('input[name="_token"]').value;

    fetch(`/admin/courses/${courseId}/custom-link`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ price, currency, classes })
    })
    .then(async res => {
        if (!res.ok) {
            const text = await res.text();
            throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.url) {
            document.getElementById('custom-link-url').value = data.url;
            document.getElementById('custom-link-result').classList.remove('hidden');
        } else {
            alert('Failed to generate link');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error generating link: ' + err.message);
    });
}

function copyCustomLink() {
    const url = document.getElementById('custom-link-url').value;
    navigator.clipboard.writeText(url).then(() => {
        const toast = document.getElementById('copy-toast');
        toast.innerText = '✅ Custom link copied to clipboard!';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2800);
    });
}
</script>

<div id="bulk-upload-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-surface border border-outline-variant rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="p-6 bg-gradient-to-r from-secondary-container/50 to-primary-container/30 border-b border-outline-variant flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-secondary/20 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-2xl">cloud_upload</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-on-surface">Bulk Upload Courses (CSV)</h2>
                    <p class="text-xs text-on-surface-variant">Upload single or multiple AI courses with 24 to 100 classes in one go.</p>
                </div>
            </div>
            <button type="button" onclick="closeBulkUploadModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-lg hover:bg-black/5 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('admin.courses.bulk-preview') }}" method="POST" enctype="multipart/form-data" id="bulk-upload-form" class="p-6 space-y-5" onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Validating CSV...';">
            @csrf
            
            <div class="p-4 bg-primary/5 border border-primary/20 rounded-2xl text-xs space-y-2 text-on-surface-variant">
                <div class="flex items-center gap-2 font-bold text-primary text-sm">
                    <span class="material-symbols-outlined text-base">info</span>
                    <span>Supported CSV Features</span>
                </div>
                <ul class="list-disc pl-5 space-y-1">
                    <li>One unified template — <strong>course info + all classes</strong> in a single CSV file.</li>
                    <li>Supports media (<code>thumbnail_url</code>, <code>video_url</code>, per-class <code>practical_video_url</code>), <strong>learning outcomes</strong> and <strong>highlights</strong> (separate with <code>|</code>), <strong>SEO</strong> title/description, and <strong>tutor assignment</strong> by <code>tutor_email</code>.</li>
                    <li>Explicit <code>grade_min</code>/<code>grade_max</code>, <code>number_of_classes</code>, <code>per_class_price</code>, <code>is_camp</code>, <code>is_top_course</code>, and a per-row <code>status</code>.</li>
                    <li>The <code>category</code> column must match an <strong>existing website category name exactly</strong> (row 2 of the template lists all valid names). The <code>grade_band</code> must match an age group (row 3 lists them).</li>
                    <li>You can also <strong>override the category below</strong> — selected chips overrule the CSV column for every course in this upload.</li>
                    <li>You'll see a <strong>validation preview</strong> before anything is saved — fix any blocking errors first.</li>
                </ul>
                <div class="pt-2 border-t border-primary/10 flex items-center justify-between">
                    <span class="font-bold text-on-surface">Need the exact CSV format?</span>
                    <a href="{{ route('admin.courses.sample-csv') }}" class="text-primary font-bold hover:underline inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">download</span> Download Course Template
                    </a>
                </div>
            </div>

            {{-- Category override picker — optional, overrides CSV category column --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-bold text-on-surface">
                        Override Category
                        <span class="text-xs font-normal text-on-surface-variant ml-1">(optional — overrides the <code>category</code> column in the CSV)</span>
                    </label>
                    <button type="button" onclick="loadBulkCategories()" id="bulk-cat-refresh-btn" class="text-xs text-primary font-bold hover:underline inline-flex items-center gap-1 hidden">
                        <span class="material-symbols-outlined text-xs">refresh</span> Refresh
                    </button>
                </div>

                {{-- Loading state --}}
                <div id="bulk-category-loading" class="text-xs text-on-surface-variant py-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Loading categories from website...
                </div>

                {{-- Chip grid --}}
                <div id="bulk-category-chips" class="hidden flex-wrap gap-2 flex"></div>

                {{-- Hidden inputs injected by JS --}}
                <div id="bulk-category-inputs"></div>

                <p class="text-xs text-on-surface-variant mt-2">
                    Categories are loaded live. If you just
                    <a href="{{ route('admin.categories.index') }}" target="_blank" class="text-primary hover:underline">added a new category</a>,
                    click Refresh above to see it.
                </p>
            </div>

            <div>
                <label class="block text-sm font-bold text-on-surface mb-2">Select CSV File *</label>
                <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" required class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary/90 transition-colors cursor-pointer">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Default Status</label>
                    <select name="default_status" class="w-full p-3 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold outline-none focus:border-primary">
                        <option value="draft" selected>Draft (Recommended)</option>
                        <option value="published">Published</option>
                    </select>
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-on-surface">
                        <input type="checkbox" name="update_existing" value="1" checked class="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary">
                        Update existing course if slug matches
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant flex justify-end gap-3">
                <button type="button" onclick="closeBulkUploadModal()" class="px-5 py-2.5 font-bold text-on-surface-variant hover:bg-surface-container rounded-xl transition-colors text-sm">Cancel</button>
                <button type="submit" id="bulk-upload-submit" class="btn-primary px-6 py-2.5 text-sm font-bold shadow-md inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">fact_check</span> Validate &amp; Preview
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openBulkUploadModal() {
    document.getElementById('bulk-upload-modal').classList.remove('hidden');
    loadBulkCategories();
}

function closeBulkUploadModal() {
    document.getElementById('bulk-upload-modal').classList.add('hidden');
}

function loadBulkCategories() {
    const loading  = document.getElementById('bulk-category-loading');
    const chips    = document.getElementById('bulk-category-chips');
    const refresh  = document.getElementById('bulk-cat-refresh-btn');

    loading.classList.remove('hidden');
    chips.classList.add('hidden');
    chips.innerHTML = '';
    document.getElementById('bulk-category-inputs').innerHTML = '';

    fetch('{{ route("admin.categories.json") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(categories => {
        chips.innerHTML = '';
        document.getElementById('bulk-category-inputs').innerHTML = '';

        categories.forEach(cat => {
            // Chip button (toggle)
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.id = cat.id;
            btn.dataset.name = cat.name;
            btn.textContent = cat.name;
            btn.className = 'category-chip px-3 py-1.5 rounded-full border text-xs font-bold transition-all cursor-pointer select-none border-outline-variant bg-surface-container text-on-surface hover:border-primary hover:text-primary';
            btn.onclick = () => toggleChip(btn);
            chips.appendChild(btn);
        });

        loading.classList.add('hidden');
        chips.classList.remove('hidden');
        refresh.classList.remove('hidden');
    })
    .catch(() => {
        loading.innerHTML = '<span class="text-error">⚠️ Failed to load categories. <button type="button" onclick="loadBulkCategories()" class="underline font-bold">Retry</button></span>';
    });
}

function toggleChip(btn) {
    const isSelected = btn.classList.contains('chip-selected');
    const inputsDiv  = document.getElementById('bulk-category-inputs');
    const catId      = btn.dataset.id;

    if (isSelected) {
        // Deselect
        btn.classList.remove('chip-selected', 'bg-primary', 'text-white', 'border-primary');
        btn.classList.add('border-outline-variant', 'bg-surface-container', 'text-on-surface');
        const existing = inputsDiv.querySelector(`input[value="${catId}"]`);
        if (existing) existing.remove();
    } else {
        // Select
        btn.classList.add('chip-selected', 'bg-primary', 'text-white', 'border-primary');
        btn.classList.remove('border-outline-variant', 'bg-surface-container', 'text-on-surface');
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'category_ids[]';
        inp.value = catId;
        inputsDiv.appendChild(inp);
    }
}
</script>
@if(session('import_errors') || session('import_open_modal'))
<script>
    document.addEventListener('DOMContentLoaded', openBulkUploadModal);
</script>
@endif
@endpush
