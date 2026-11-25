@extends('layouts.dashboard')

@section('title', 'My Profile')

@section('content')
<div class="p-5 md:p-8 space-y-8">
    <div>
        <h1 class="font-headline-lg text-headline-lg font-bold mb-2">My Profile 👤</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Manage your account information.</p>
    </div>

    

    <div class="bg-white rounded-2xl border border-surface-container-high shadow-sm overflow-hidden">
        <div class="p-5 md:p-8 border-b border-surface-container-high flex items-center gap-5 md:p-8">
            <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=046bd2&color=fff&size=128' }}" class="w-32 h-32 rounded-full border-4 border-surface object-cover" alt="Avatar">
            <div>
                <h2 class="font-headline-md font-bold text-2xl">{{ $user->name }}</h2>
                <p class="text-on-surface-variant mb-2">{{ $user->email }}</p>
                <p class="text-sm text-on-surface-variant">Member since {{ $user->created_at->format('M Y') }}</p>
            </div>
        </div>
        <div class="p-5 md:p-8">
            <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-2xl">
                @csrf
                @if($errors->any())
                    <div class="p-4 bg-error-container text-on-error-container rounded-xl text-sm">
                        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold mb-2">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Timezone</label>
                    <select name="timezone" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors">
                        @foreach(\App\Services\TimezoneService::allTimezones() as $tzId => $tzLabel)
                            <option value="{{ $tzId }}" @selected(old('timezone', $user->timezone ?? 'UTC') === $tzId)>{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Profile Photo (Optional)</label>
                    <input type="file" name="avatar" accept="image/*" class="w-full bg-surface-container-low border border-surface-container-high rounded-xl px-4 py-3 focus:outline-none focus:border-primary transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                </div>
                <button type="submit" class="btn-primary">Save Profile</button>
            </form>
        </div>
    </div>
</div>
@endsection
