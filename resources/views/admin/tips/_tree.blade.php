@foreach($folders as $folder)
    <div class="ml-4 border-l border-outline-variant/30 pl-4 py-1">
        <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-container-lowest p-1 rounded">
            <input type="checkbox" name="tip_folder_ids[]" value="{{ $folder->id }}" class="w-4 h-4 text-primary rounded border-outline">
            <span class="material-symbols-outlined text-warning text-lg">folder</span>
            <span class="font-medium text-sm">{{ $folder->name }}</span>
        </label>
        @if($folder->children->isNotEmpty() || $folder->tips->isNotEmpty())
            @include('admin.tips._tree', ['folders' => $folder->children, 'tips' => $folder->tips])
        @endif
    </div>
@endforeach

@foreach($tips as $tip)
    <div class="ml-4 border-l border-outline-variant/30 pl-4 py-1">
        <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-container-lowest p-1 rounded">
            <input type="checkbox" name="tip_ids[]" value="{{ $tip->id }}" class="w-4 h-4 text-primary rounded border-outline">
            @if($tip->type === 'video')
                <span class="material-symbols-outlined text-secondary text-lg">play_circle</span>
            @else
                <span class="material-symbols-outlined text-on-tertiary-container text-lg">description</span>
            @endif
            <span class="font-medium text-sm">{{ $tip->title }}</span>
        </label>
    </div>
@endforeach
