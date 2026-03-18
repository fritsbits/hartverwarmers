<?php

namespace App\Models;

use App\Observers\FicheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([FicheObserver::class])]
class Fiche extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'initiative_id',
        'user_id',
        'title',
        'slug',
        'description',
        'practical_tips',
        'materials',
        'target_audience',
        'published',
        'has_diamond',
        'download_count',
        'kudos_count',
        'featured_month',
        'icon',
        'migration_id',
        'zip_path',
        'quality_score',
        'quality_justification',
        'quality_assessed_at',
        'completeness_score',
        'presentation_score',
        'presentation_justification',
        'ai_suggestions',
    ];

    protected function casts(): array
    {
        return [
            'materials' => 'array',
            'target_audience' => 'array',
            'published' => 'boolean',
            'has_diamond' => 'boolean',
            'quality_assessed_at' => 'datetime',
            'ai_suggestions' => 'array',
        ];
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class)->orderBy('sort_order');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function kudos(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable')->where('type', 'kudos');
    }

    public function totalKudosCount(): int
    {
        return (int) $this->kudos()->sum('count');
    }

    /**
     * Structured practical sections built from the materials JSON,
     * falling back to the practical_tips text field.
     *
     * @return Attribute<array<int, array{title: string, content: string}>, never>
     */
    protected function practicalSections(): Attribute
    {
        return Attribute::get(function (): array {
            $materials = $this->materials ?? [];
            $map = [
                'preparation' => 'Voorbereiding',
                'inventory' => 'Benodigdheden',
                'process' => 'Werkwijze',
            ];

            $sections = [];
            foreach ($map as $key => $title) {
                $content = trim(strip_tags($materials[$key] ?? ''));
                if ($content !== '') {
                    $sections[] = ['title' => $title, 'content' => self::autoLinkUrls($materials[$key])];
                }
            }

            if ($sections === [] && $this->practical_tips && trim(strip_tags($this->practical_tips)) !== '') {
                $sections[] = ['title' => 'Praktische tips', 'content' => self::autoLinkUrls($this->practical_tips)];
            }

            return $sections;
        });
    }

    /**
     * Wrap bare URLs in anchor tags, skipping URLs already inside href="...".
     */
    private static function autoLinkUrls(string $html): string
    {
        return preg_replace(
            '~(?<!href=["\'])(?<!["\'>])(https?://[^\s<>"\']+)~i',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $html,
        );
    }

    /**
     * @return array<int, string> Up to $limit preview image URLs from attached files.
     */
    public function cardPreviewImages(int $limit = 3): array
    {
        $urls = [];

        foreach ($this->files as $file) {
            if (! $file->hasPreviewImages()) {
                continue;
            }
            $thumbPaths = $file->thumbnailPaths();
            foreach ($file->preview_images as $index => $path) {
                $thumbPath = $thumbPaths[$index];
                $usePath = Storage::disk('public')->exists($thumbPath) ? $thumbPath : $path;
                $urls[] = Storage::url($usePath);
                if (count($urls) >= $limit) {
                    return $urls;
                }
            }
        }

        return $urls;
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeFicheOfMonth($query)
    {
        return $query->whereNotNull('featured_month');
    }

    public function calculateCompletenessScore(): int
    {
        $score = 0;
        $description = trim(strip_tags($this->description ?? ''));

        if (mb_strlen($description) >= 100) {
            $score += 25;
        }

        $materials = $this->materials ?? [];

        foreach (['preparation', 'inventory', 'process'] as $field) {
            if (trim(strip_tags($materials[$field] ?? '')) !== '') {
                $score += 25;
            }
        }

        return $score;
    }

    public function hasUnusedSuggestions(): bool
    {
        if (! is_array($this->ai_suggestions)) {
            return false;
        }

        $applied = $this->ai_suggestions['applied'] ?? [];
        $fields = ['title', 'description', 'preparation', 'inventory', 'process'];

        return collect($fields)->contains(function ($field) use ($applied) {
            return ! empty($this->ai_suggestions[$field]) && ! in_array($field, $applied);
        });
    }

    public function shouldShowSuggestionNudge(): bool
    {
        return $this->presentation_score !== null
            && $this->presentation_score < 60
            && $this->hasUnusedSuggestions();
    }
}
