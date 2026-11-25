@php
    $rewardPresets = \App\Services\ClassRewardService::XP_PRESETS;
    $rewardBadges = ($badgeCatalog ?? collect())
        ->map(fn ($b) => ['id' => $b->id, 'title' => $b->title, 'icon' => $b->badge_icon, 'is_system' => (bool) $b->is_system])
        ->values();
@endphp

<div x-data="rewardModal()" @open-reward.window="open($event.detail)" x-cloak>
    <template x-if="show">
        <div class="fixed inset-0 z-[120] flex items-start justify-center overflow-y-auto bg-black/60 p-4 py-10">
            <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-outline-variant/20 flex items-center justify-between bg-gradient-to-r from-primary-container/50 to-tertiary-container/40">
                    <div>
                        <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">celebration</span>
                            Celebrate <span class="text-primary" x-text="studentName"></span>
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Give XP compliments, one level-up tip and award a badge.</p>
                    </div>
                    <button type="button" @click="show = false" class="text-on-surface-variant hover:text-error text-2xl leading-none">&times;</button>
                </div>

                <form method="POST" action="{{ route('tutor.rewards.store') }}" class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                    @csrf
                    <input type="hidden" name="student_id" :value="studentId">
                    <input type="hidden" name="course_id" :value="courseId ?? ''">
                    <input type="hidden" name="booking_id" :value="bookingId ?? ''">
                    <input type="hidden" name="is_trial" :value="isTrial ? 1 : 0">
                    <template x-for="(c, i) in chosen" :key="i">
                        <div class="hidden">
                            <input type="hidden" :name="`compliments[${i}][label]`" :value="c.label">
                            <input type="hidden" :name="`compliments[${i}][xp]`" :value="c.xp">
                        </div>
                    </template>

                    <!-- XP compliments -->
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-primary">thumb_up</span> XP Compliments
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="(p, i) in presets" :key="i">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-colors"
                                       :class="selected[i] ? 'border-primary bg-primary/10' : 'border-outline-variant/30 hover:bg-surface-container-low'">
                                    <input type="checkbox" class="rounded" :checked="selected[i]" @change="selected[i] = !selected[i]">
                                    <span class="material-symbols-outlined text-base text-on-surface-variant" x-text="p.icon"></span>
                                    <span class="flex-1 text-xs font-medium text-on-surface" x-text="p.label"></span>
                                    <span class="text-xs font-bold text-primary" x-text="'+' + p.xp + ' XP'"></span>
                                </label>
                            </template>
                        </div>

                        <div class="mt-3 flex gap-2 items-center">
                            <input type="text" x-model="customLabel" placeholder="Custom compliment (optional)" class="flex-1 px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                            <input type="number" x-model.number="customXp" min="0" max="1000" placeholder="XP" class="w-20 px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                        </div>
                    </div>

                    <!-- Level-up tip -->
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-tertiary">lightbulb</span> Level-Up Tip
                        </label>
                        <textarea name="level_up_tip" x-model="tip" rows="2" maxlength="500" placeholder="One specific thing they can do to level up next time..." class="w-full px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary"></textarea>
                    </div>

                    <!-- Badge -->
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-warning">military_tech</span> Award a Badge
                        </label>
                        <div class="flex gap-2">
                            <select name="badge_id" x-model="badgeId" class="flex-1 px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                                <option value="">— No badge —</option>
                                @foreach($rewardBadges as $badge)
                                    <option value="{{ $badge['id'] }}">{{ $badge['title'] }}{{ $badge['is_system'] ? '' : ' (custom)' }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="showNewBadge = !showNewBadge" class="text-xs font-bold text-primary bg-primary/10 px-3 py-2 rounded-xl whitespace-nowrap">+ New badge</button>
                        </div>

                        <div x-show="showNewBadge" x-cloak class="mt-3 p-3 rounded-2xl border border-outline-variant/30 bg-surface-container-low space-y-2">
                            <p class="text-[11px] text-on-surface-variant font-bold uppercase tracking-wider">Create a custom badge</p>
                            <input type="text" form="newBadgeForm" name="title" required maxlength="80" placeholder="Badge name (e.g. Comic Creator)" class="w-full px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                            <input type="text" form="newBadgeForm" name="description" maxlength="240" placeholder="Short description (optional)" class="w-full px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                            <div class="flex gap-2">
                                <input type="text" form="newBadgeForm" name="badge_icon" value="military_tech" placeholder="Icon name" class="flex-1 px-3 py-2 rounded-xl border border-outline-variant/30 text-xs outline-none focus:border-primary">
                                <input type="hidden" form="newBadgeForm" name="course_id" :value="courseId ?? ''">
                                <button type="submit" form="newBadgeForm" class="text-xs font-bold text-white bg-primary px-4 py-2 rounded-xl whitespace-nowrap">Create badge</button>
                            </div>
                            <p class="text-[10px] text-on-surface-variant">Tip: use Material Symbols names like <code>auto_stories</code>, <code>emoji_events</code>, <code>star</code>.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-outline-variant/20">
                        <div class="text-sm font-bold text-on-surface">
                            Total XP: <span class="text-tertiary text-lg" x-text="total"></span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="show = false" class="px-4 py-2 rounded-xl border border-outline-variant/30 text-sm font-bold text-on-surface-variant">Cancel</button>
                            <button type="submit" :disabled="total <= 0 && !tip && !badgeId" class="btn-enroll px-6 py-2 text-sm disabled:opacity-50">Award</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<form id="newBadgeForm" method="POST" action="{{ route('tutor.rewards.badges.store') }}">
    @csrf
</form>

@once
<script>
    function rewardModal() {
        return {
            show: false,
            studentId: null,
            studentName: '',
            courseId: null,
            bookingId: null,
            isTrial: false,
            presets: @json($rewardPresets),
            selected: {},
            customLabel: '',
            customXp: 0,
            tip: '',
            badgeId: '',
            showNewBadge: false,

            open(detail) {
                detail = detail || {};
                this.studentId = detail.id;
                this.studentName = detail.name || 'this student';
                this.courseId = detail.courseId ?? null;
                this.bookingId = detail.bookingId ?? null;
                this.isTrial = !!detail.isTrial;
                this.selected = {};
                this.customLabel = '';
                this.customXp = 0;
                this.tip = '';
                this.badgeId = '';
                this.showNewBadge = false;
                this.show = true;
            },

            get chosen() {
                const out = [];
                this.presets.forEach((p, i) => {
                    if (this.selected[i]) out.push({ label: p.label, xp: p.xp });
                });
                if ((this.customLabel && this.customLabel.trim() !== '') || +this.customXp > 0) {
                    out.push({ label: this.customLabel.trim() || 'Great work', xp: +this.customXp || 0 });
                }
                return out;
            },

            get total() {
                return this.chosen.reduce((sum, c) => sum + (+c.xp || 0), 0);
            }
        };
    }
</script>
@endonce
