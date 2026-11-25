@extends('layouts.dashboard')

@section('title', 'Tutor Profile')

@section('content')
<div class="px-gutter-mobile md:px-gutter-desktop max-w-3xl mx-auto py-12">
    <div class="clay-card p-5 md:p-8 lg:p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-outline-variant pb-6">
            <div class="w-16 h-16 bg-tertiary text-white rounded-full flex items-center justify-center font-bold text-2xl overflow-hidden relative group">
                <img src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=199C79&color=fff' }}" alt="Avatar" class="w-full h-full object-cover">
            </div>
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">Tutor Profile</h2>
                <p class="font-body-md text-on-surface-variant">Update your account details and password.</p>
            </div>
        </div>

        

        @if ($errors->any())
            <div class="mb-6 p-4 bg-error-container text-error rounded-xl font-medium">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tutor.profile.update') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            
            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Profile Photo</label>
                <input type="file" name="avatar" accept="image/*" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                <p class="text-xs text-on-surface-variant">Recommended size: 500x500 pixels. Max size 5MB.</p>
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Full Name</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-on-surface">Email Address</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
            </div>
            
            <div class="border-t border-outline-variant pt-6 mt-6">
                <h3 class="font-bold text-lg text-on-surface mb-4">Change Password</h3>
                <p class="text-sm text-on-surface-variant mb-4">Leave blank if you don't want to change your password.</p>
                
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block font-bold text-on-surface">New Password</label>
                        <input type="password" name="password" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block font-bold text-on-surface">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full p-4 bg-surface-container-low border border-outline-variant rounded-xl focus:border-tertiary focus:ring-1 focus:ring-tertiary outline-none transition-all">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant">
                <button type="submit" class="btn-primary">Update Profile</button>
            </div>
        </form>
        <div class="mt-12 pt-10 border-t border-outline-variant">
            <h3 class="font-headline-md text-headline-md text-on-surface mb-6">My Availability</h3>
            <p class="font-body-md text-on-surface-variant mb-8">Set your weekly availability commitment and choose the hours you are generally available.</p>
            
            <div x-data="availabilityGrid()">
                <form method="POST" action="{{ route('tutor.availability.store') }}">
                    @csrf

                    <!-- Availability Commitment -->
                    <div class="mb-10 max-w-md">
                        <label class="block text-lg font-bold text-on-surface mb-2">Availability Commitment <span class="text-sm text-on-surface-variant font-normal">(select one)</span></label>
                        <select name="availability_commitment" required class="w-full px-4 py-3 bg-white border border-outline-variant/30 rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            <option value="" disabled {{ !$user->availability_commitment ? 'selected' : '' }}>Select your weekly commitment...</option>
                            <option value="Under 10 hrs/wk" {{ $user->availability_commitment === 'Under 10 hrs/wk' ? 'selected' : '' }}>Under 10 hrs/wk</option>
                            <option value="10 - 20 hrs/wk" {{ $user->availability_commitment === '10 - 20 hrs/wk' ? 'selected' : '' }}>10 - 20 hrs/wk</option>
                            <option value="20 - 30 hrs/wk" {{ $user->availability_commitment === '20 - 30 hrs/wk' ? 'selected' : '' }}>20 - 30 hrs/wk</option>
                            <option value="30+ hrs/wk" {{ $user->availability_commitment === '30+ hrs/wk' ? 'selected' : '' }}>30+ hrs/wk</option>
                        </select>
                    </div>

                    <!-- Days/Hours Grid -->
                    <div class="mb-8">
                        <label class="block text-lg font-bold text-on-surface mb-2">Days/hours available (in your timezone) <span class="text-sm text-on-surface-variant font-normal">(click slots to turn green, select at least one)</span></label>
                        
                        <div class="overflow-x-auto pb-4">
                            <div class="min-w-[800px]">
                                <!-- Header Hours -->
                                <div class="flex ml-16 mb-2">
                                    @for($i = 0; $i < 24; $i++)
                                        <div class="flex-1 text-center text-xs text-on-surface-variant font-bold">{{ $i }}</div>
                                    @endfor
                                </div>

                                <!-- Rows for Days -->
                                <div class="space-y-1">
                                    @php
                                        $days = [
                                            1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 
                                            5 => 'Fri', 6 => 'Sat', 0 => 'Sun'
                                        ];
                                    @endphp
                                    @foreach($days as $dayIndex => $dayName)
                                        <div class="flex items-center">
                                            <div class="w-16 text-sm font-bold text-on-surface text-right pr-4">{{ $dayName }}</div>
                                            <div class="flex flex-1 gap-1">
                                                @for($hour = 0; $hour < 24; $hour++)
                                                    <div 
                                                        class="flex-1 h-8 rounded-sm cursor-pointer transition-colors border border-transparent"
                                                        :class="isSelected({{ $dayIndex }}, {{ $hour }}) ? 'bg-[#98D8C2] border-[#7CBBA6]' : 'bg-[#E7E9EE] hover:bg-[#D5D8DF]'"
                                                        @click="toggle({{ $dayIndex }}, {{ $hour }})"
                                                    >
                                                        <!-- Hidden input so the form submits the data naturally -->
                                                        <template x-if="isSelected({{ $dayIndex }}, {{ $hour }})">
                                                            <input type="hidden" name="slots[{{ $dayIndex }}][]" value="{{ $hour }}">
                                                        </template>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-start">
                        <button type="submit" class="btn-primary py-3 px-8 text-lg">Save Availability</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('availabilityGrid', () => ({
            slots: @json($grid ?? []),
            
            init() {
                // Initialize missing days as empty arrays
                [0, 1, 2, 3, 4, 5, 6].forEach(day => {
                    if (!this.slots[day]) this.slots[day] = [];
                });
            },

            isSelected(day, hour) {
                return this.slots[day] && this.slots[day].includes(hour);
            },

            toggle(day, hour) {
                if (!this.slots[day]) {
                    this.slots[day] = [];
                }
                
                const index = this.slots[day].indexOf(hour);
                if (index === -1) {
                    this.slots[day].push(hour);
                } else {
                    this.slots[day].splice(index, 1);
                }
            }
        }));
    });
</script>
@endsection
