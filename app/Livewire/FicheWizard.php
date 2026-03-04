<?php

namespace App\Livewire;

use App\Jobs\GenerateFilePreview;
use App\Jobs\ProcessFicheUploads;
use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class FicheWizard extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;

    // Step 1 — Upload
    #[Validate(
        ['uploads.*' => 'file|max:51200|mimes:pdf,pptx,docx,doc,ppt,jpg,jpeg,png'],
        message: [
            'uploads.*.max' => 'Een bestand mag maximaal 50MB zijn.',
            'uploads.*.mimes' => 'Alleen PDF, PPTX, DOCX en afbeeldingen zijn toegestaan.',
            'uploads.*.file' => 'Upload een geldig bestand.',
        ]
    )]
    public array $uploads = [];

    /** @var array<int, array{id: int, name: string, size: int, type: string}> */
    public array $uploadedFiles = [];

    public ?int $previewFileId = null;

    public bool $showPreviewFileModal = false;

    // Processing state
    public string $processingKey = '';

    public string $processingStep = 'idle';

    public bool $processingComplete = false;

    public ?int $processingStartedAt = null;

    public bool $processingStale = false;

    // Step 2 — Details (user's original input, never overwritten by AI)
    #[Validate('required|string|max:255', message: [
        'required' => 'Geef je activiteit een titel.',
        'max' => 'De titel mag maximaal 255 tekens bevatten.',
    ])]
    public string $title = '';

    #[Validate('nullable|string|max:5000', message: [
        'max' => 'De beschrijving mag maximaal 5000 tekens bevatten.',
    ])]
    public string $description = '';

    public string $materialsText = '';

    public string $duration = '';

    public string $groupSize = '';

    // Step 3 — Review
    public ?int $selectedInitiativeId = null;

    /** @var array<int, int> */
    public array $selectedThemeTags = [];

    /** @var array<int, int> */
    public array $selectedGoalTags = [];

    /** @var array<int, int> AI-suggested tag IDs for "Aanbevolen" display */
    public array $suggestedThemeTagIds = [];

    /** @var array<int, int> */
    public array $suggestedGoalTagIds = [];

    public bool $showMoreThemeTags = false;

    public bool $showMoreGoalTags = false;

    // AI suggestions (populated when processing completes)
    public ?string $aiTitle = null;

    public ?string $aiDescription = null;

    public ?string $aiPreparation = null;

    public ?string $aiInventory = null;

    public ?string $aiProcess = null;

    public ?string $aiMaterials = null;

    public ?string $aiDuration = null;

    public ?string $aiGroupSize = null;

    /** @var array<int, array{id: int, title: string, reason: string}> */
    public array $matchedInitiatives = [];

    // User's final values for content fields (step 3)
    public string $preparation = '';

    public string $inventory = '';

    public string $process = '';

    /** @var array<string> Fields where the user dismissed the AI suggestion */
    public array $dismissedSuggestions = [];

    // Full AI results (stored for saveFiche)
    /** @var array<string, mixed>|null */
    public ?array $aiAnalysis = null;

    public function updatedUploads(): void
    {
        $this->validateOnly('uploads.*', messages: [
            'uploads.*.max' => 'Een bestand mag maximaal 50MB zijn.',
            'uploads.*.mimes' => 'Alleen PDF, PPTX, DOCX en afbeeldingen zijn toegestaan.',
            'uploads.*.file' => 'Upload een geldig bestand.',
        ]);

        $isFirstUploadBatch = $this->processingStep === 'idle';
        $newFileIds = [];

        foreach ($this->uploads as $upload) {
            $path = $upload->store('files', 'public');

            $file = File::create([
                'fiche_id' => null,
                'original_filename' => $upload->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $upload->getMimeType(),
                'size_bytes' => $upload->getSize(),
                'sort_order' => count($this->uploadedFiles),
            ]);

            $this->uploadedFiles[] = [
                'id' => $file->id,
                'name' => $file->original_filename,
                'size' => $file->size_bytes,
                'type' => $file->typeLabel(),
            ];

            $newFileIds[] = $file->id;
        }

        $this->uploads = [];

        if ($this->title === '' && ! empty($this->uploadedFiles)) {
            $name = pathinfo($this->uploadedFiles[0]['name'], PATHINFO_FILENAME);
            $this->title = ucfirst(trim(preg_replace('/\s+/', ' ', str_replace(['-', '_', '.'], ' ', $name))));
        }

        if ($isFirstUploadBatch && ! empty($newFileIds)) {
            $allFileIds = collect($this->uploadedFiles)->pluck('id')->toArray();

            if (count($allFileIds) === 1) {
                $this->previewFileId = $allFileIds[0];
            } else {
                $this->previewFileId = $allFileIds[0];
                $this->showPreviewFileModal = true;
            }

            $this->dispatchProcessing($allFileIds);
        } elseif (! empty($newFileIds)) {
            $allFileIds = collect($this->uploadedFiles)->pluck('id')->toArray();
            $this->clearAiSuggestions();
            $this->dispatchProcessing($allFileIds, skipPreview: true);

            if (count($this->uploadedFiles) >= 2) {
                $this->showPreviewFileModal = true;
            }
        }
    }

    public function confirmPreviewFile(): void
    {
        $previousPreviewId = $this->previewFileId;
        $this->showPreviewFileModal = false;

        if ($this->previewFileId !== $previousPreviewId || ! $this->hasPreviewGenerated($this->previewFileId)) {
            GenerateFilePreview::dispatch($this->previewFileId);
        }
    }

    public function removeFile(int $fileId): void
    {
        $file = File::find($fileId);

        if ($file && $file->fiche_id === null) {
            Storage::disk('public')->delete($file->path);
            $file->delete();
            $this->uploadedFiles = array_values(
                array_filter($this->uploadedFiles, fn ($f) => $f['id'] !== $fileId)
            );

            if ($fileId === $this->previewFileId) {
                $this->previewFileId = null;

                if (empty($this->uploadedFiles)) {
                    Cache::forget("fiche-processing:{$this->processingKey}");
                    $this->processingComplete = false;
                    $this->processingStep = 'idle';
                    $this->processingKey = '';
                    $this->processingStartedAt = null;
                    $this->processingStale = false;
                } elseif (count($this->uploadedFiles) === 1) {
                    $this->previewFileId = $this->uploadedFiles[0]['id'];
                    GenerateFilePreview::dispatch($this->previewFileId);
                } else {
                    $this->previewFileId = $this->uploadedFiles[0]['id'];
                    $this->showPreviewFileModal = true;
                }
            }
        }
    }

    public function submitStep1(): void
    {
        $this->currentStep = 2;
    }

    public function dispatchProcessing(array $fileIds, bool $skipPreview = false): void
    {
        $this->processingKey = Str::random(32);
        $this->processingStep = 'extracting';
        $this->processingStartedAt = now()->timestamp;
        $this->processingStale = false;
        $this->processingComplete = false;

        ProcessFicheUploads::dispatch(
            $fileIds,
            $skipPreview ? null : $this->previewFileId,
            $this->processingKey,
            $this->title,
            $this->description,
        );
    }

    public function checkProcessing(): void
    {
        if ($this->processingComplete || $this->processingStep === 'idle') {
            return;
        }

        $status = Cache::get("fiche-processing:{$this->processingKey}");

        if (! $status) {
            if ($this->processingStartedAt && (now()->timestamp - $this->processingStartedAt) >= 30) {
                $this->processingStale = true;
            }

            return;
        }

        $this->processingStep = $status['step'];

        if (isset($status['updated_at']) && (now()->timestamp - $status['updated_at']) >= 30) {
            $this->processingStale = true;
        }

        if ($status['step'] === 'done') {
            $this->processingComplete = true;
            $this->processingStale = false;
            $this->aiAnalysis = $status['analysis'] ?? null;
            $this->loadAiSuggestions($status);
            Cache::forget("fiche-processing:{$this->processingKey}");
        } elseif ($status['step'] === 'failed') {
            $this->processingComplete = true;
            Cache::forget("fiche-processing:{$this->processingKey}");
        }
    }

    public function skipProcessing(): void
    {
        $this->processingComplete = true;
        $this->processingStep = 'skipped';
        Cache::forget("fiche-processing:{$this->processingKey}");
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    public function submitStep2(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
        ], [
            'title.required' => 'Geef je activiteit een titel.',
            'title.max' => 'De titel mag maximaal 255 tekens bevatten.',
        ]);

        $this->currentStep = 3;
    }

    /** @deprecated Use submitStep2() — kept for backwards compatibility with tests */
    public function goToStep3(): void
    {
        $this->submitStep2();
    }

    public function applySuggestion(string $field): void
    {
        $map = [
            'description' => ['ai' => 'aiDescription', 'user' => 'description'],
            'preparation' => ['ai' => 'aiPreparation', 'user' => 'preparation'],
            'inventory' => ['ai' => 'aiInventory', 'user' => 'inventory'],
            'process' => ['ai' => 'aiProcess', 'user' => 'process'],
            'materials' => ['ai' => 'aiMaterials', 'user' => 'materialsText'],
        ];

        if (isset($map[$field]) && $this->{$map[$field]['ai']} !== null) {
            $this->{$map[$field]['user']} = $this->{$map[$field]['ai']};
        }

        if (! in_array($field, $this->dismissedSuggestions)) {
            $this->dismissedSuggestions[] = $field;
        }
    }

    public function dismissSuggestion(string $field): void
    {
        if (! in_array($field, $this->dismissedSuggestions)) {
            $this->dismissedSuggestions[] = $field;
        }
    }

    public function saveDraft(): void
    {
        $this->saveFiche(published: false);
    }

    public function publish(): void
    {
        $this->saveFiche(published: true);
    }

    public function render()
    {
        return view('livewire.fiche-wizard', [
            'allThemeTags' => Tag::where('type', 'theme')->orderBy('name')->get(),
            'allGoalTags' => Feature::active('diamant-goals')
                ? Tag::where('type', 'goal')->orderBy('name')->get()
                : collect(),
            'allInitiatives' => $this->currentStep >= 2
                ? Initiative::published()->orderBy('title')->get(['id', 'title', 'description'])
                : collect(),
            'contentFields' => $this->currentStep === 3 ? $this->getContentFields() : [],
        ]);
    }

    public function restoreUploadedFiles(array $fileIds, ?int $previewFileId = null): void
    {
        $this->uploadedFiles = File::whereIn('id', $fileIds)
            ->whereNull('fiche_id')
            ->get()
            ->map(fn (File $f) => [
                'id' => $f->id,
                'name' => $f->original_filename,
                'size' => $f->size_bytes,
                'type' => $f->typeLabel(),
            ])
            ->toArray();

        if (! empty($this->uploadedFiles)) {
            $restoredIds = collect($this->uploadedFiles)->pluck('id')->toArray();
            $this->previewFileId = ($previewFileId && in_array($previewFileId, $restoredIds))
                ? $previewFileId
                : $this->uploadedFiles[0]['id'];
        }
    }

    private function clearAiSuggestions(): void
    {
        $this->aiTitle = null;
        $this->aiDescription = null;
        $this->aiPreparation = null;
        $this->aiInventory = null;
        $this->aiProcess = null;
        $this->aiMaterials = null;
        $this->aiDuration = null;
        $this->aiGroupSize = null;
        $this->matchedInitiatives = [];
        $this->aiAnalysis = null;
        $this->suggestedThemeTagIds = [];
        $this->suggestedGoalTagIds = [];
        $this->dismissedSuggestions = [];
    }

    /**
     * Content fields for step 3 — editable with side-by-side AI suggestions.
     *
     * @return array<int, array{field: string, label: string, description: string, placeholder: string, userProp: string, aiProp: string, rows: int}>
     */
    private function getContentFields(): array
    {
        return [
            ['field' => 'description', 'label' => 'Beschrijving', 'description' => 'Wat is je bedoeling met deze activiteit? Voor wie is ze bedoeld?', 'placeholder' => 'bijv. Een interactieve quiz waarbij bewoners liedjes herkennen.', 'userProp' => 'description', 'aiProp' => 'aiDescription', 'rows' => 4],
            ['field' => 'preparation', 'label' => 'Voorbereiding', 'description' => 'Wat moet er klaargezet of voorbereid worden?', 'placeholder' => 'bijv. Print de bingokaarten uit en test het geluid.', 'userProp' => 'preparation', 'aiProp' => 'aiPreparation', 'rows' => 4],
            ['field' => 'inventory', 'label' => 'Benodigdheden', 'description' => 'Welke materialen heb je nodig?', 'placeholder' => 'bijv. Bingokaarten, stiften, muziekinstallatie.', 'userProp' => 'inventory', 'aiProp' => 'aiInventory', 'rows' => 4],
            ['field' => 'process', 'label' => 'Werkwijze', 'description' => 'Beschrijf stap voor stap hoe de activiteit verloopt.', 'placeholder' => 'bijv. 1. Verdeel de bingokaarten. 2. Speel het eerste fragment...', 'userProp' => 'process', 'aiProp' => 'aiProcess', 'rows' => 6],
            ['field' => 'materials', 'label' => 'Materiaal', 'description' => 'Welk materiaal lever je bij deze fiche?', 'placeholder' => 'bijv. Bingokaarten, muzieklijst', 'userProp' => 'materialsText', 'aiProp' => 'aiMaterials', 'rows' => 3],
        ];
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function loadAiSuggestions(array $status): void
    {
        $analysis = $status['analysis'] ?? null;

        if ($analysis) {
            $this->aiPreparation = self::markdownToHtml($analysis['preparation'] ?? null);
            $this->aiInventory = self::markdownToHtml($analysis['inventory'] ?? null);
            $this->aiProcess = self::markdownToHtml($analysis['process'] ?? null);
            $this->aiMaterials = $analysis['materials_list'] ?? null;
            $this->aiDuration = $analysis['duration_estimate'] ?? null;
            $this->aiGroupSize = $analysis['group_size_estimate'] ?? null;

            $this->matchGoalTags($analysis['suggested_goals'] ?? []);
            $this->matchThemeTags($analysis['suggested_themes'] ?? []);
        }

        // Auto-fill step 2 fields from AI when empty
        if ($this->duration === '' && $this->aiDuration) {
            $this->duration = $this->aiDuration;
        }
        if ($this->groupSize === '' && $this->aiGroupSize) {
            $this->groupSize = $this->aiGroupSize;
        }

        $matchedInitiativeData = $status['matched_initiatives'] ?? null;
        if ($matchedInitiativeData && ! empty($matchedInitiativeData['matched_initiative_ids'])) {
            $initiatives = Initiative::whereIn('id', $matchedInitiativeData['matched_initiative_ids'])->get();
            $this->matchedInitiatives = [];

            foreach ($matchedInitiativeData['matched_initiative_ids'] as $index => $id) {
                $initiative = $initiatives->firstWhere('id', $id);
                if ($initiative) {
                    $this->matchedInitiatives[] = [
                        'id' => $initiative->id,
                        'title' => $initiative->title,
                        'reason' => $matchedInitiativeData['match_reasons'][$index] ?? '',
                    ];
                }
            }

            if (! empty($this->matchedInitiatives)) {
                $this->selectedInitiativeId = $this->matchedInitiatives[0]['id'];
            }
        }
    }

    private function matchGoalTags(array $slugs): void
    {
        if (empty($slugs)) {
            return;
        }

        $goalSlugs = collect($slugs)->map(fn ($s) => "doel-{$s}")->toArray();
        $ids = Tag::where('type', 'goal')
            ->whereIn('slug', $goalSlugs)
            ->pluck('id')
            ->toArray();

        $this->suggestedGoalTagIds = $ids;
        $this->selectedGoalTags = $ids;
    }

    private function matchThemeTags(array $slugs): void
    {
        if (empty($slugs)) {
            return;
        }

        $ids = Tag::where('type', 'theme')
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->toArray();

        $this->suggestedThemeTagIds = $ids;
        $this->selectedThemeTags = $ids;
    }

    private static function markdownToHtml(?string $markdown): ?string
    {
        if ($markdown === null || trim($markdown) === '') {
            return $markdown;
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    private function saveFiche(bool $published): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ], [
            'title.required' => 'Geef je activiteit een titel.',
            'title.max' => 'De titel mag maximaal 255 tekens bevatten.',
            'description.max' => 'De beschrijving mag maximaal 5000 tekens bevatten.',
        ]);

        $slug = $this->generateUniqueSlug($this->title);

        $materials = array_filter([
            'preparation' => $this->preparation,
            'inventory' => $this->inventory,
            'process' => $this->process,
            'materials' => $this->materialsText,
            'duration' => $this->duration,
            'group_size' => $this->groupSize,
        ]);

        try {
            $fiche = DB::transaction(function () use ($slug, $materials, $published) {
                $fiche = Fiche::create([
                    'initiative_id' => $this->selectedInitiativeId,
                    'user_id' => auth()->id(),
                    'title' => $this->title,
                    'slug' => $slug,
                    'description' => $this->description,
                    'materials' => ! empty($materials) ? $materials : null,
                    'published' => $published,
                ]);

                $fileIds = collect($this->uploadedFiles)->pluck('id')->toArray();
                File::whereIn('id', $fileIds)->update(['fiche_id' => $fiche->id]);

                $tagIds = array_merge($this->selectedThemeTags, $this->selectedGoalTags);
                if (! empty($tagIds)) {
                    $fiche->tags()->sync($tagIds);
                }

                return $fiche;
            });
        } catch (\Throwable $e) {
            report($e);
            $this->addError('save', 'Er ging iets mis bij het opslaan. Probeer het opnieuw.');

            return;
        }

        $this->dispatch('fiche-saved');

        $route = $fiche->initiative
            ? route('fiches.show', [$fiche->initiative, $fiche])
            : route('home');

        $message = $published ? 'Fiche gepubliceerd!' : 'Fiche opgeslagen als concept.';

        $this->redirect($route, navigate: false);
        session()->flash('success', $message);
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title) ?: 'fiche';
        $slug = $baseSlug;
        $suffix = 1;

        while (Fiche::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function hasPreviewGenerated(?int $fileId): bool
    {
        if (! $fileId) {
            return false;
        }

        $file = File::find($fileId);

        return $file && $file->preview_path !== null;
    }
}
