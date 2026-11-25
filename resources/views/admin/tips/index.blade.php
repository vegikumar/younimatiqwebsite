@extends('layouts.dashboard')
@section('title', 'Tips & Tricks Management')
@section('content')
<div class="space-y-6 max-w-[1600px] mx-auto px-4 md:px-8">
    <!-- Header -->
    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-on-surface">Tips & Tricks</h1>
            <p class="text-sm text-on-surface-variant">Manage and assign tips to students</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="flex gap-4 mb-6" id="summary-cards">
        <div class="bg-white border border-outline-variant/30 rounded-xl p-4 min-w-[150px] shadow-sm">
            <p class="text-xs font-bold text-on-surface-variant uppercase">ALL FOLDERS</p>
            <h2 class="text-2xl font-bold mt-1">{{ $totalFolders }}</h2>
        </div>
        <div class="bg-white border border-outline-variant/30 rounded-xl p-4 min-w-[150px] shadow-sm">
            <p class="text-xs font-bold text-on-surface-variant uppercase">ALL FILES</p>
            <h2 class="text-2xl font-bold mt-1">{{ $totalTips }}</h2>
        </div>
        <div class="bg-white border border-outline-variant/30 rounded-xl p-4 min-w-[200px] shadow-sm">
            <p class="text-xs font-bold text-on-surface-variant uppercase">ROOT LEVEL</p>
            <h2 class="text-lg font-bold mt-1">{{ $rootFoldersCount }} folders &bull; {{ $rootTipsCount }} files</h2>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-tertiary-fixed text-tertiary rounded-xl border border-tertiary-fixed-dim font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-error-container text-on-error-container rounded-xl border border-error/20 font-medium">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <!-- Tab 1: Explorer -->
    <div id="tab-explorer">
        <form action="{{ route('admin.tips.bulk-move') }}" method="POST" id="explorer-form">
            @csrf
            <!-- File Explorer Area -->
            <div id="explorer-container" class="bg-white rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden flex flex-col min-h-[600px]">
                
                <!-- Breadcrumb & Search -->
                <div class="p-4 border-b border-outline-variant/30 bg-surface-container-lowest flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2 bg-surface-container-low px-4 py-2 rounded-xl flex-1 border border-outline-variant/50">
                        <span class="material-symbols-outlined text-warning">folder</span>
                        <div class="flex items-center gap-1 text-sm font-medium">
                            <a href="{{ route('admin.tips') }}" class="hover:text-primary transition-colors">Admin</a>
                            <span class="material-symbols-outlined text-[16px] text-outline">chevron_right</span>
                            <a href="{{ route('admin.tips') }}" class="hover:text-primary transition-colors">Explorer</a>
                            @foreach($breadcrumbs as $bc)
                                <span class="material-symbols-outlined text-[16px] text-outline">chevron_right</span>
                                <a href="{{ route('admin.tips', ['folder_id' => $bc->id]) }}" class="hover:text-primary transition-colors">{{ $bc->name }}</a>
                            @endforeach
                        </div>
                    </div>
                    <div class="relative w-64">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-sm">search</span>
                        <input type="text" id="explorer-search" placeholder="Search folders & files..." class="w-full pl-9 pr-4 py-2 bg-surface-container-lowest border border-outline-variant/50 rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="p-4 border-b border-outline-variant/30 flex items-center justify-between gap-2 bg-surface-container-lowest">
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('add-folder-modal').showModal()" class="px-4 py-2 bg-success text-white font-bold rounded-lg hover:bg-success transition-colors flex items-center gap-2 text-sm shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">create_new_folder</span> New Folder
                        </button>
                        <button type="button" onclick="document.getElementById('add-item-modal').showModal()" class="px-4 py-2 bg-success text-white font-bold rounded-lg hover:bg-success transition-colors flex items-center gap-2 text-sm shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">upload_file</span> Add File
                        </button>
                        <div class="w-px h-6 bg-outline-variant/50 mx-2"></div>
                        <button type="button" id="btn-move" onclick="openMoveModal()" class="px-4 py-2 bg-white border border-outline-variant/50 text-on-surface font-bold rounded-lg hover:bg-surface-container-low transition-colors flex items-center gap-2 text-sm shadow-sm opacity-50 cursor-not-allowed" disabled>
                            Move (<span class="sel-count">0</span>)
                        </button>
                        <button type="button" id="btn-multi-assign" onclick="openMultiAssignModal()" class="px-4 py-2 bg-white border border-outline-variant/50 text-primary font-bold rounded-lg hover:bg-primary/5 transition-colors flex items-center gap-2 text-sm shadow-sm opacity-50 cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-[18px]">group_add</span> Assign (<span class="sel-count">0</span>)
                        </button>
                        <button type="submit" id="btn-delete" formaction="{{ route('admin.tips.bulk-delete') }}" onclick="return confirm('Are you sure you want to delete selected items?')" class="px-4 py-2 bg-white border border-outline-variant/50 text-error font-bold rounded-lg hover:bg-error/10 transition-colors flex items-center gap-2 text-sm shadow-sm opacity-50 cursor-not-allowed" disabled>
                            Delete (<span class="sel-count">0</span>)
                        </button>
                    </div>
                </div>

                <!-- Grid View -->
                <div class="p-6 flex-1 overflow-y-auto bg-surface-container-lowest">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6" id="explorer-grid">
                        <!-- Folders -->
                        @foreach($folders as $folder)
                            <div class="explorer-item group relative flex flex-col items-center p-4 rounded-2xl hover:bg-surface-container-low transition-colors border border-transparent hover:border-outline-variant/30 cursor-pointer">
                                <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    <input type="checkbox" name="tip_folder_ids[]" value="{{ $folder->id }}" class="item-checkbox w-5 h-5 text-primary rounded border-outline focus:ring-primary shadow-sm" onclick="event.stopPropagation()">
                                </div>
                                <a href="{{ route('admin.tips', ['folder_id' => $folder->id]) }}" class="flex flex-col items-center w-full relative z-0">
                                    <div class="w-20 h-20 mb-3 flex items-center justify-center relative">
                                        <img src="https://img.icons8.com/?size=100&id=12160&format=png&color=000000" alt="Folder" class="w-full h-full object-contain filter drop-shadow-sm">
                                    </div>
                                    <h4 class="font-bold text-sm text-center text-on-surface w-full truncate item-title" title="{{ $folder->name }}">{{ $folder->name }}</h4>
                                    <p class="text-xs text-on-surface-variant text-center mt-1">{{ $folder->tips_count }} files &bull; {{ $folder->children_count }} folders</p>
                                </a>
                            </div>
                        @endforeach
                        
                        <!-- Files -->
                        @foreach($looseTips as $tip)
                            <div class="explorer-item group relative flex flex-col items-center p-4 rounded-2xl hover:bg-surface-container-low transition-colors border border-transparent hover:border-outline-variant/30 cursor-pointer">
                                <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    <input type="checkbox" name="tip_ids[]" value="{{ $tip->id }}" class="item-checkbox w-5 h-5 text-primary rounded border-outline focus:ring-primary shadow-sm" onclick="event.stopPropagation()">
                                </div>
                                <a href="javascript:void(0)" onclick="openSecureViewer('{{ addslashes($tip->title) }}', '{{ route('tips.stream', $tip->id) }}', '{{ $tip->type }}')" class="flex flex-col items-center w-full relative z-0">
                                    <div class="w-20 h-20 mb-3 flex items-center justify-center relative">
                                        @if($tip->type === 'video')
                                            <img src="https://img.icons8.com/?size=100&id=10260&format=png&color=000000" alt="Video" class="w-full h-full object-contain filter drop-shadow-sm">
                                        @else
                                            <img src="https://img.icons8.com/?size=100&id=11342&format=png&color=000000" alt="File" class="w-full h-full object-contain filter drop-shadow-sm">
                                        @endif
                                    </div>
                                    <h4 class="font-bold text-sm text-center text-on-surface w-full truncate item-title" title="{{ $tip->title }}">{{ $tip->title }}</h4>
                                    <p class="text-xs text-on-surface-variant text-center mt-1 uppercase">{{ $tip->type }}</p>
                                </a>
                            </div>
                        @endforeach
                    </div>
                    
                    @if(count($folders) == 0 && count($looseTips) == 0)
                        <div class="flex flex-col items-center justify-center h-64 opacity-50">
                            <span class="material-symbols-outlined text-6xl mb-4">folder_open</span>
                            <p class="text-lg font-medium">This folder is empty.</p>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    </div>

<!-- Modals -->


<!-- New Folder Modal -->
<dialog id="add-folder-modal" class="m-auto p-0 rounded-2xl shadow-xl border-0 backdrop:bg-black/50 w-full max-w-md bg-white">
    <div class="p-6">
        <h3 class="font-bold text-xl mb-4">New Folder</h3>
        <form action="{{ route('admin.tips.folder.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="parent_id" value="{{ request('folder_id') }}">
            <div>
                <label class="block text-sm font-medium mb-1">Folder Name</label>
                <input type="text" name="name" required class="w-full p-2 border border-outline rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="document.getElementById('add-folder-modal').close()" class="px-4 py-2 bg-surface-container-high rounded-xl hover:bg-surface-container-highest transition-colors font-bold">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-success text-white rounded-xl hover:bg-success transition-colors font-bold">Create</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Upload File Modal -->
<dialog id="add-item-modal" class="m-auto p-0 rounded-2xl shadow-xl border-0 backdrop:bg-black/50 w-full max-w-md bg-white">
    <div class="p-6">
        <h3 class="font-bold text-xl mb-4">Upload File(s)</h3>
        <form action="{{ route('admin.tips.item.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="tip_folder_id" value="{{ request('folder_id') }}">
            <div>
                <label class="block text-sm font-medium mb-1">Title (Used if uploading single file or URL)</label>
                <input type="text" name="title" required class="w-full p-2 border border-outline rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Type</label>
                <select name="type" id="upload-type" required class="w-full p-2 border border-outline rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <option value="video">Video</option>
                    <option value="file">Document/File</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Content URL (e.g. YouTube embed)</label>
                <input type="text" name="content_url" class="w-full p-2 border border-outline rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
            </div>
            <div class="text-center text-sm font-bold text-outline">OR</div>
            <div>
                <label class="block text-sm font-medium mb-1">Upload File(s)</label>
                <input type="file" name="content_files[]" id="upload-file-input" multiple accept="video/*" class="w-full p-2 border border-outline rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="document.getElementById('add-item-modal').close()" class="px-4 py-2 bg-surface-container-high rounded-xl hover:bg-surface-container-highest transition-colors font-bold">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-success text-white rounded-xl hover:bg-success transition-colors font-bold">Save</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Move Modal -->
<dialog id="move-modal" class="m-auto p-0 rounded-2xl shadow-xl border-0 backdrop:bg-black/50 w-full max-w-md bg-white">
    <div class="p-6">
        <h3 class="font-bold text-xl mb-4">Move Selected Items</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Select Destination Folder</label>
                <select id="move-target-folder" class="w-full p-2 border border-outline rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <option value="">[Root Directory]</option>
                    @foreach($allFolders as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="document.getElementById('move-modal').close()" class="px-4 py-2 bg-surface-container-high rounded-xl hover:bg-surface-container-highest transition-colors font-bold">Cancel</button>
                <button type="button" onclick="submitMoveForm()" class="px-4 py-2 bg-success text-white rounded-xl hover:bg-success transition-colors font-bold">Move</button>
            </div>
        </div>
    </div>
</dialog>

<!-- Multi-Assign Modal -->
<dialog id="multi-assign-modal" class="m-auto bg-white p-0 rounded-2xl shadow-xl border-0 backdrop:bg-black/50 w-full max-w-2xl max-h-[80vh] overflow-hidden">
    <div class="flex flex-col h-full max-h-[80vh]">
        <div class="p-4 border-b flex justify-between items-center bg-surface-container-lowest shrink-0">
            <h3 class="font-bold text-xl flex items-center gap-2"><span class="material-symbols-outlined text-primary">group_add</span> Assign Selected to Students</h3>
            <button type="button" onclick="document.getElementById('multi-assign-modal').close()" class="text-outline hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form action="{{ route('admin.tips.multi-assign') }}" method="POST" class="flex flex-col flex-1 overflow-hidden" id="multi-assign-form">
            @csrf
        <div id="multi-assign-items-container"></div> <!-- Hidden inputs for selected folders/tips -->
        <div class="p-4 flex-1 overflow-y-auto bg-white">
            <div class="relative mb-4">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-sm">search</span>
                <input type="text" id="multi-assign-student-search" placeholder="Search students..." class="w-full pl-9 pr-4 py-2 bg-surface-container-lowest border border-outline-variant/50 rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
            </div>
            <p class="text-sm text-on-surface-variant mb-4">Select the students you want to assign the selected items to.</p>
            <div class="border rounded-xl p-4 bg-surface-container-lowest grid grid-cols-1 sm:grid-cols-2 gap-2" id="multi-assign-student-list">
                @foreach($students as $student)
                    <label class="flex items-center gap-3 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors border border-transparent hover:border-outline-variant/50 multi-student-item">
                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="w-5 h-5 text-primary rounded border-outline focus:ring-primary">
                        <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-xs shrink-0">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-sm truncate student-name">{{ $student->name }}</div>
                            <div class="text-xs text-on-surface-variant truncate student-email">{{ $student->email }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="p-4 border-t bg-surface-container-low shrink-0 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('multi-assign-modal').close()" class="px-4 py-2 bg-white border rounded-xl hover:bg-surface-container-low transition-colors font-bold text-sm">Cancel</button>
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors font-bold text-sm">Assign to Selected</button>
        </div>
    </form>
    </div>
</dialog>

<script>
    // Modal logic
    function openMoveModal() {
        document.getElementById('move-modal').showModal();
    }
    
    function submitMoveForm() {
        const targetId = document.getElementById('move-target-folder').value;
        const form = document.getElementById('explorer-form');
        
        let hiddenInput = document.getElementById('hidden-target-folder');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = 'hidden-target-folder';
            hiddenInput.name = 'target_folder_id';
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = targetId;
        
        form.submit();
    }

    function openMultiAssignModal() {
        const container = document.getElementById('multi-assign-items-container');
        container.innerHTML = '';
        
        // Clone all checked items into the modal form
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = cb.name; // tip_folder_ids[] or tip_ids[]
            hidden.value = cb.value;
            container.appendChild(hidden);
        });

        document.querySelectorAll('#multi-assign-modal input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.getElementById('multi-assign-student-search').value = '';
        document.querySelectorAll('.multi-student-item').forEach(item => item.style.display = 'flex');

        document.getElementById('multi-assign-modal').showModal();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Selection Logic for Explorer
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const selCountSpans = document.querySelectorAll('.sel-count');
        const btnMove = document.getElementById('btn-move');
        const btnDelete = document.getElementById('btn-delete');
        const btnMultiAssign = document.getElementById('btn-multi-assign');
        
        function updateSelection() {
            let count = 0;
            itemCheckboxes.forEach(cb => {
                if (cb.checked) count++;
                const parent = cb.closest('.explorer-item');
                if (cb.checked) {
                    parent.classList.add('bg-surface-container', 'border-primary/50');
                    parent.classList.remove('border-transparent');
                    cb.parentElement.classList.remove('opacity-0');
                    cb.parentElement.classList.add('opacity-100');
                } else {
                    parent.classList.remove('bg-surface-container', 'border-primary/50');
                    parent.classList.add('border-transparent');
                    cb.parentElement.classList.add('opacity-0');
                    cb.parentElement.classList.remove('opacity-100');
                }
            });
            
            selCountSpans.forEach(span => span.textContent = count);
            
            if (count > 0) {
                btnMove.disabled = false;
                btnDelete.disabled = false;
                btnMultiAssign.disabled = false;
                btnMove.classList.remove('opacity-50', 'cursor-not-allowed');
                btnDelete.classList.remove('opacity-50', 'cursor-not-allowed');
                btnMultiAssign.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btnMove.disabled = true;
                btnDelete.disabled = true;
                btnMultiAssign.disabled = true;
                btnMove.classList.add('opacity-50', 'cursor-not-allowed');
                btnDelete.classList.add('opacity-50', 'cursor-not-allowed');
                btnMultiAssign.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        
        itemCheckboxes.forEach(cb => cb.addEventListener('change', updateSelection));

        // Search Explorer
        const explorerSearch = document.getElementById('explorer-search');
        const explorerItems = document.querySelectorAll('.explorer-item');
        if(explorerSearch) {
            explorerSearch.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                explorerItems.forEach(item => {
                    const title = item.querySelector('.item-title').textContent.toLowerCase();
                    if(title.includes(term)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        // Search Students (Multi Assign Modal)
        const multiStudentSearch = document.getElementById('multi-assign-student-search');
        const multiStudentItems = document.querySelectorAll('.multi-student-item');
        if(multiStudentSearch) {
            multiStudentSearch.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                multiStudentItems.forEach(item => {
                    const name = item.querySelector('.student-name').textContent.toLowerCase();
                    const email = item.querySelector('.student-email').textContent.toLowerCase();
                    if(name.includes(term) || email.includes(term)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
        
        // Upload File Type Logic
        const uploadType = document.getElementById('upload-type');
        const fileInput = document.getElementById('upload-file-input');
        if(uploadType && fileInput) {
            uploadType.addEventListener('change', function() {
                if(this.value === 'video') {
                    fileInput.accept = "video/*";
                } else {
                    fileInput.accept = ".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip";
                }
            });
            // trigger change to set initial value
            uploadType.dispatchEvent(new Event('change'));
        }
    });
</script>

<x-secure-viewer />
@endsection
