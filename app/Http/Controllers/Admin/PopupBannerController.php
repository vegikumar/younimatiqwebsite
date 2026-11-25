<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopupBanner;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class PopupBannerController extends Controller
{
    public function __construct(private ImageUploadService $images) {}

    public function index()
    {
        $banners = PopupBanner::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PopupBanner $banner) => $this->adminPayload($banner))
            ->values()
            ->all();

        return \App\Support\InertiaPage::react('Admin/PopupBanners/Index', [
            'banners' => $banners,
            'specs' => [
                'recommended' => '800 × 1000 px (portrait popup)',
                'landscape' => '1080 × 608 px (wide offer)',
                'square' => '800 × 800 px',
                'formats' => 'JPG, PNG, or WebP',
                'max_size' => '2 MB',
                'safe_zone' => 'Keep text and logos at least 40 px from the edges',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, creating: true);
        $path = $this->images->storePath($request->file('image'), 'popup-banners', 'public');

        PopupBanner::create([
            ...$data,
            'image_path' => $path,
            'coupon_code' => $this->normalizeCoupon($data['coupon_code'] ?? null),
            'link_url' => $this->normalizeLink($data['link_url'] ?? null),
        ]);

        $this->forgetCache();

        return back()->with('success', 'Popup banner created.');
    }

    public function update(Request $request, PopupBanner $popupBanner)
    {
        $data = $this->validated($request, creating: false);

        if ($request->hasFile('image')) {
            $popupBanner->deleteImage();
            $popupBanner->image_path = $this->images->storePath($request->file('image'), 'popup-banners', 'public');
        }

        $popupBanner->fill([
            ...$data,
            'coupon_code' => $this->normalizeCoupon($data['coupon_code'] ?? null),
            'link_url' => $this->normalizeLink($data['link_url'] ?? null),
        ])->save();

        $this->forgetCache();

        return back()->with('success', 'Popup banner updated.');
    }

    public function destroy(PopupBanner $popupBanner)
    {
        $popupBanner->deleteImage();
        $popupBanner->delete();
        $this->forgetCache();

        return back()->with('success', 'Popup banner deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'image' => [
                Rule::requiredIf($creating),
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'link_url' => ['nullable', 'string', 'max:500', 'url'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
            'delay_seconds' => ['nullable', 'integer', 'min:0', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        return [
            'title' => (string) $request->input('title'),
            'link_url' => $request->input('link_url'),
            'coupon_code' => $request->input('coupon_code'),
            'delay_seconds' => (int) $request->input('delay_seconds', 2),
            'show_once' => $request->boolean('show_once'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'starts_at' => $request->input('starts_at') ?: null,
            'ends_at' => $request->input('ends_at') ?: null,
        ];
    }

    private function normalizeCoupon(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code !== '' ? $code : null;
    }

    private function normalizeLink(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' ? $url : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function adminPayload(PopupBanner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'image_url' => $banner->imageUrl(),
            'link_url' => $banner->link_url,
            'coupon_code' => $banner->coupon_code,
            'delay_seconds' => (int) $banner->delay_seconds,
            'show_once' => (bool) $banner->show_once,
            'is_active' => (bool) $banner->is_active,
            'is_live' => $banner->isCurrentlyActive(),
            'sort_order' => (int) $banner->sort_order,
            'starts_at' => optional($banner->starts_at)->format('Y-m-d\TH:i'),
            'ends_at' => optional($banner->ends_at)->format('Y-m-d\TH:i'),
            'updated_at' => optional($banner->updated_at)->timezone(config('app.timezone'))->format('d M Y, H:i'),
        ];
    }

    private function forgetCache(): void
    {
        Cache::forget('public_popup_banner_v1');
    }
}
