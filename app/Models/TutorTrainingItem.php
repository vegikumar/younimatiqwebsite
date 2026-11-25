<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorTrainingItem extends Model
{
    protected $fillable = [
        'module_id', 'type', 'title', 'instructions', 'video_url', 'video_path',
        'document_url', 'document_path', 'documents', 'quiz_questions', 'pass_score', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quiz_questions' => 'array',
            'documents' => 'array',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TutorTrainingModule::class, 'module_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TutorTrainingProgress::class, 'item_id');
    }

    /**
     * Normalized list of docs (files + links), including legacy single-doc columns.
     *
     * @return list<array{kind:string,label:string,url:string}>
     */
    public function resolvedDocuments(): array
    {
        $out = [];

        foreach ($this->documents ?? [] as $doc) {
            if (! is_array($doc)) {
                continue;
            }
            $kind = ($doc['kind'] ?? null) === 'url' ? 'url' : 'file';
            $label = trim((string) ($doc['label'] ?? ''));
            if ($kind === 'url') {
                $url = trim((string) ($doc['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $out[] = [
                    'kind' => 'url',
                    'label' => $label !== '' ? $label : 'Open link',
                    'url' => $url,
                ];
                continue;
            }

            $path = trim((string) ($doc['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $out[] = [
                'kind' => 'file',
                'label' => $label !== '' ? $label : basename($path),
                'url' => asset('storage/'.$path),
            ];
        }

        // Legacy single columns (older rows created before multi-doc support).
        if ($this->document_url) {
            $already = collect($out)->contains(fn ($d) => ($d['url'] ?? '') === $this->document_url);
            if (! $already) {
                $out[] = [
                    'kind' => 'url',
                    'label' => 'Open link',
                    'url' => $this->document_url,
                ];
            }
        }
        if ($this->document_path) {
            $legacyUrl = asset('storage/'.$this->document_path);
            $already = collect($out)->contains(fn ($d) => ($d['url'] ?? '') === $legacyUrl);
            if (! $already) {
                $out[] = [
                    'kind' => 'file',
                    'label' => basename($this->document_path),
                    'url' => $legacyUrl,
                ];
            }
        }

        return $out;
    }
}
