<?php

namespace App\Livewire;

use App\Features\WizardDevMode;
use App\Jobs\GenerateFilePreview;
use App\Jobs\ProcessFicheUploads;
use App\Models\Fiche;
use App\Models\File;
use App\Models\FileUpload;
use App\Models\Initiative;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Session;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class FicheWizard extends Component
{
    use WithFileUploads;

    /** Dutch stop words — filler words that don't help topic matching */
    private const STOP_WORDS = [
        // Articles & demonstratives
        'een', 'het', 'die', 'dat', 'deze',
        // Prepositions
        'met', 'van', 'voor', 'aan', 'uit', 'naar', 'bij', 'over', 'door', 'via', 'tot',
        // Conjunctions
        'maar',
        // Pronouns & possessives
        'jouw', 'mijn', 'ons', 'onze', 'zijn', 'haar', 'hun',
        // Adverbs & particles
        'ook', 'nog', 'wel', 'niet', 'meer', 'alle',
        // Common adjectives (noise in filenames)
        'nieuwe', 'eigen', 'grote',
    ];

    public bool $devMode = false;

    public int $currentStep = 1;

    // Step 1 — Upload
    #[Validate(
        ['uploads.*' => 'file|max:51200|mimes:pdf,pptx,docx,doc,ppt,jpg,jpeg,png'],
        message: [
            'uploads.*.max' => 'Dit bestand is te groot (max 50 MB). Probeer het bestand te verkleinen voor je het opnieuw uploadt.',
            'uploads.*.mimes' => 'Dit bestandstype wordt niet ondersteund. Kies een PDF, PPTX, DOCX of afbeelding (JPG/PNG).',
            'uploads.*.file' => 'Dit bestand kon niet worden gelezen. Probeer het opnieuw te uploaden.',
        ]
    )]
    public array $uploads = [];

    #[Session(key: 'fiche-wizard.disclaimerAccepted')]
    public bool $disclaimerAccepted = false;

    /** @var array<int, array{id: int, name: string, size: int, type: string}> */
    #[Session(key: 'fiche-wizard.uploadedFiles')]
    public array $uploadedFiles = [];

    #[Session(key: 'fiche-wizard.previewFileId')]
    public ?int $previewFileId = null;

    public bool $showPreviewFileModal = false;

    // Processing state
    public string $processingKey = '';

    public string $processingStep = 'idle';

    public bool $processingComplete = false;

    public ?int $processingStartedAt = null;

    public bool $processingStale = false;

    // Step 4 — Celebration (after publish)
    public ?int $publishedFicheId = null;

    public ?string $publishedFicheUrl = null;

    // Step 2 — Details (user's original input, never overwritten by AI)
    #[Session(key: 'fiche-wizard.title')]
    #[Validate('required|string|max:255', message: [
        'required' => 'Geef je activiteit een titel.',
        'max' => 'De titel mag maximaal 255 tekens bevatten.',
    ])]
    public string $title = '';

    #[Session(key: 'fiche-wizard.description')]
    #[Validate('required|string|max:5000', message: [
        'required' => 'Geef een beschrijving van je activiteit.',
        'max' => 'De beschrijving mag maximaal 5000 tekens bevatten.',
    ])]
    public string $description = '';

    #[Session(key: 'fiche-wizard.duration')]
    public string $duration = '';

    #[Session(key: 'fiche-wizard.groupSize')]
    public string $groupSize = '';

    // Step 3 — Review
    public ?int $selectedInitiativeId = null;

    /** Separate model for the manual dropdown — decoupled from AI radio cards */
    public ?int $manualInitiativeId = null;

    /** @var array<int, int> */
    #[Session(key: 'fiche-wizard.selectedThemeTags')]
    public array $selectedThemeTags = [];

    /** @var array<int, int> */
    #[Session(key: 'fiche-wizard.selectedGoalTags')]
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

    public ?string $aiDuration = null;

    public ?string $aiGroupSize = null;

    /** @var array<int, array{id: int, title: string, reason: string}> */
    public array $matchedInitiatives = [];

    // User's final values for content fields (step 3)
    #[Session(key: 'fiche-wizard.preparation')]
    public string $preparation = '';

    #[Session(key: 'fiche-wizard.inventory')]
    public string $inventory = '';

    #[Session(key: 'fiche-wizard.process')]
    public string $process = '';

    /** @var array<string> Fields where the user dismissed the AI suggestion */
    public array $dismissedSuggestions = [];

    /** @var array<string> Fields where the user applied the AI suggestion */
    public array $appliedSuggestions = [];

    /** @var array{count: int, keyword: string, examples: array<string>}|array{} */
    public array $similarFiches = [];

    // Full AI results (stored for saveFiche)
    /** @var array<string, mixed>|null */
    public ?array $aiAnalysis = null;

    public function mount(): void
    {
        $this->devMode = auth()->user()?->isAdmin() && Feature::for(null)->active(WizardDevMode::class);

        // Validate restored file references from session (files may have been deleted or claimed)
        if (! empty($this->uploadedFiles)) {
            $validFileIds = File::whereIn('id', collect($this->uploadedFiles)->pluck('id'))
                ->whereNull('fiche_id')
                ->pluck('id')
                ->toArray();

            $this->uploadedFiles = array_values(
                array_filter($this->uploadedFiles, fn ($f) => in_array($f['id'], $validFileIds))
            );

            if ($this->previewFileId && ! in_array($this->previewFileId, $validFileIds)) {
                $this->previewFileId = ! empty($this->uploadedFiles) ? $this->uploadedFiles[0]['id'] : null;
            }
        }
    }

    public function updatedTitle(): void
    {
        $this->findSimilarFiches();
    }

    public function updatedManualInitiativeId(): void
    {
        $this->selectedInitiativeId = $this->manualInitiativeId;
    }

    public function updatedSelectedInitiativeId(): void
    {
        // When a radio card is selected, clear the manual dropdown so it shows the placeholder
        $matchedIds = collect($this->matchedInitiatives)->pluck('id')->all();

        if (in_array($this->selectedInitiativeId, $matchedIds)) {
            $this->manualInitiativeId = null;
        }
    }

    public function findSimilarFiches(): void
    {
        $words = collect(explode(' ', Str::lower(trim($this->title))))
            ->map(fn (string $w) => trim($w))
            ->filter(fn (string $w) => Str::length($w) >= 3 && ! in_array($w, self::STOP_WORDS))
            ->values();

        if ($words->isEmpty()) {
            $this->similarFiches = [];

            return;
        }

        // Find the word with the most matching fiches so keyword, count, and examples stay consistent
        $best = null;

        foreach ($words as $word) {
            $query = Fiche::published()->where('title', 'LIKE', "%{$word}%");
            $count = $query->count();

            if ($count > 0 && ($best === null || $count > $best['count'])) {
                $best = [
                    'count' => $count,
                    'keyword' => $word,
                    'examples' => (clone $query)->take(3)->pluck('title')->all(),
                ];
            }
        }

        if ($best === null) {
            $this->similarFiches = [];

            return;
        }

        $this->similarFiches = $best;
    }

    public function updatedUploads(): void
    {
        try {
            $this->validateOnly('uploads.*', messages: [
                'uploads.*.max' => 'Dit bestand is te groot (max 50 MB). Probeer het bestand te verkleinen voor je het opnieuw uploadt.',
                'uploads.*.mimes' => 'Dit bestandstype wordt niet ondersteund. Kies een PDF, PPTX, DOCX of afbeelding (JPG/PNG).',
                'uploads.*.file' => 'Dit bestand kon niet worden gelezen. Probeer het opnieuw te uploaden.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->uploads = [];
            $this->dispatch('upload-rejected');

            throw $e;
        }

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

            FileUpload::create([
                'file_id' => $file->id,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'file_hash' => hash_file('sha256', $upload->getRealPath()),
                'original_filename' => $upload->getClientOriginalName(),
                'disclaimer_accepted_at' => $this->disclaimerAccepted ? now() : null,
            ]);
        }

        $this->uploads = [];

        if ($this->title === '' && ! empty($this->uploadedFiles)) {
            $name = pathinfo($this->uploadedFiles[0]['name'], PATHINFO_FILENAME);
            $this->title = ucfirst(trim(preg_replace('/\s+/', ' ', str_replace(['-', '_', '.'], ' ', $name))));
            $this->findSimilarFiches();
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
            $pdfVersion = File::where('source_file_id', $fileId)->first();
            if ($pdfVersion) {
                Storage::disk('public')->delete($pdfVersion->path);
                $pdfVersion->delete();
            }

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
        if (! empty($this->uploadedFiles) && ! $this->disclaimerAccepted) {
            $this->addError('disclaimerAccepted', 'Bevestig dat je deze bestanden mag delen.');

            return;
        }

        // Backfill disclaimer_accepted_at on audit records created before checkbox was ticked
        if ($this->disclaimerAccepted && ! empty($this->uploadedFiles)) {
            $fileIds = collect($this->uploadedFiles)->pluck('id')->toArray();
            FileUpload::whereIn('file_id', $fileIds)
                ->whereNull('disclaimer_accepted_at')
                ->update(['disclaimer_accepted_at' => now()]);
        }

        $this->findSimilarFiches();
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

            return;
        }

        if ($this->devMode && $step > $this->currentStep) {
            $this->fillDevData($step);
            if ($step >= 2) {
                $this->findSimilarFiches();
            }
            $this->currentStep = $step;
        }
    }

    public function submitStep2(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'selectedInitiativeId' => 'required|exists:initiatives,id',
        ], [
            'title.required' => 'Geef je activiteit een titel.',
            'title.max' => 'De titel mag maximaal 255 tekens bevatten.',
            'selectedInitiativeId.required' => 'Kies een initiatief waar deze fiche bij hoort.',
            'selectedInitiativeId.exists' => 'Het gekozen initiatief bestaat niet.',
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
        ];

        if (isset($map[$field]) && $this->{$map[$field]['ai']} !== null) {
            $currentValue = trim($this->{$map[$field]['user']});
            $aiValue = $this->{$map[$field]['ai']};

            $this->{$map[$field]['user']} = $currentValue !== ''
                ? $currentValue."\n".$aiValue
                : $aiValue;
        }

        if (! in_array($field, $this->appliedSuggestions)) {
            $this->appliedSuggestions[] = $field;
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

    /**
     * Fill dummy data for dev mode when jumping forward to a step.
     */
    private function fillDevData(int $targetStep): void
    {
        $this->processingComplete = true;
        $this->processingStep = 'done';

        if ($targetStep >= 2) {
            if ($this->title === '') {
                $this->title = "Muziekbingo met schlagers uit de jaren '60";
            }
            if ($this->description === '') {
                $this->description = 'Een gezellige muziekbingo waarbij bewoners bekende schlagers herkennen. Ideaal voor groepen met verschillende niveaus van cognitieve mogelijkheden.';
            }
            if ($this->duration === '') {
                $this->duration = '45 min';
            }
            if ($this->groupSize === '') {
                $this->groupSize = '6-12';
            }

            if (empty($this->selectedThemeTags)) {
                $this->selectedThemeTags = Tag::where('type', 'theme')
                    ->whereIn('slug', ['muziek', 'spelletjes', 'gezelschap'])
                    ->pluck('id')
                    ->toArray();
            }

            if (empty($this->selectedGoalTags)) {
                $this->selectedGoalTags = Tag::where('type', 'goal')
                    ->whereIn('slug', ['doel-doen', 'doel-inclusief'])
                    ->pluck('id')
                    ->toArray();
            }

            if (empty($this->matchedInitiatives)) {
                $initiatives = Initiative::published()
                    ->orderBy('title')
                    ->limit(3)
                    ->get(['id', 'title']);

                $this->matchedInitiatives = $initiatives->map(fn ($i) => [
                    'id' => $i->id,
                    'title' => $i->title,
                    'reason' => 'Dev mode — voorbeeld',
                ])->toArray();

                if ($this->selectedInitiativeId === null && $this->matchedInitiatives) {
                    $this->selectedInitiativeId = $this->matchedInitiatives[0]['id'];
                }
            }
        }

        if ($targetStep >= 3) {
            if ($this->aiPreparation === null) {
                $this->aiPreparation = '<ul><li>Print de bingokaarten uit op A4-formaat (grote letters).</li><li>Test de muziekinstallatie en controleer het volume.</li><li>Leg stiften of fiches klaar bij elke plaats.</li><li>Zet de stoelen in een halve cirkel zodat iedereen de quizmaster kan zien.</li></ul>';
            }
            if ($this->aiInventory === null) {
                $this->aiInventory = '<ul><li>Bingokaarten (1 per deelnemer + reserve)</li><li>Stiften of bingofiches</li><li>Muziekinstallatie met speaker</li><li>Playlist met 30 schlagers uit de jaren \'60</li><li>Prijsjes voor de winnaars</li></ul>';
            }
            if ($this->aiProcess === null) {
                $this->aiProcess = '<ol><li>Verwelkom de deelnemers en leg de spelregels uit.</li><li>Deel de bingokaarten en stiften uit.</li><li>Speel telkens een fragment van 15-20 seconden.</li><li>Geef bewoners tijd om het lied te herkennen en af te vinken.</li><li>Wie een rij vol heeft roept "Bingo!" — controleer de kaart.</li><li>Speel door tot er een winnaar is, eventueel meerdere rondes.</li><li>Sluit af met een favoriet lied dat iedereen meezingt.</li></ol>';
            }
            if ($this->aiDescription === null) {
                $this->aiDescription = '<p>Een interactieve muziekbingo waarbij bewoners bekende schlagers uit de jaren \'60 herkennen. De activiteit stimuleert het langetermijngeheugen en brengt veel gezelligheid.</p>';
            }
        }
    }

    private function clearAiSuggestions(): void
    {
        $this->aiTitle = null;
        $this->aiDescription = null;
        $this->aiPreparation = null;
        $this->aiInventory = null;
        $this->aiProcess = null;
        $this->aiDuration = null;
        $this->aiGroupSize = null;
        $this->matchedInitiatives = [];
        $this->manualInitiativeId = null;
        $this->aiAnalysis = null;
        $this->suggestedThemeTagIds = [];
        $this->suggestedGoalTagIds = [];
        $this->dismissedSuggestions = [];
        $this->appliedSuggestions = [];
    }

    /**
     * Content fields for step 3 — editable with side-by-side AI suggestions.
     *
     * @return array<int, array{field: string, label: string, description: string, placeholder: string, userProp: string, aiProp: string, rows: int, required: bool}>
     */
    private function getContentFields(): array
    {
        return [
            ['field' => 'description', 'label' => 'Beschrijving', 'description' => 'Wat is je bedoeling met deze activiteit? Voor wie is ze bedoeld?', 'placeholder' => 'bijv. Een interactieve quiz waarbij bewoners liedjes herkennen.', 'userProp' => 'description', 'aiProp' => 'aiDescription', 'rows' => 4, 'required' => true],
            ['field' => 'preparation', 'label' => 'Voorbereiding', 'description' => 'Wat moet er klaargezet of voorbereid worden?', 'placeholder' => 'bijv. Print de bingokaarten uit en test het geluid.', 'userProp' => 'preparation', 'aiProp' => 'aiPreparation', 'rows' => 4, 'required' => false],
            ['field' => 'inventory', 'label' => 'Benodigdheden', 'description' => 'Welke materialen heb je nodig?', 'placeholder' => 'bijv. Bingokaarten, stiften, muziekinstallatie.', 'userProp' => 'inventory', 'aiProp' => 'aiInventory', 'rows' => 4, 'required' => false],
            ['field' => 'process', 'label' => 'Werkwijze', 'description' => 'Beschrijf stap voor stap hoe de activiteit verloopt.', 'placeholder' => 'bijv. 1. Verdeel de bingokaarten. 2. Speel het eerste fragment...', 'userProp' => 'process', 'aiProp' => 'aiProcess', 'rows' => 6, 'required' => false],
        ];
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function loadAiSuggestions(array $status): void
    {
        $analysis = $status['analysis'] ?? null;

        if ($analysis) {
            $this->aiDescription = self::markdownToHtml($analysis['description'] ?? null);
            $this->aiPreparation = self::markdownToHtml($analysis['preparation'] ?? null);
            $this->aiInventory = self::markdownToHtml($analysis['inventory'] ?? null);
            $this->aiProcess = self::markdownToHtml($analysis['process'] ?? null);
            $this->aiDuration = $analysis['duration_estimate'] ?? null;
            $this->aiGroupSize = $analysis['group_size_estimate'] ?? null;

            $this->matchGoalTags((array) ($analysis['suggested_goals'] ?? []));
            $this->matchThemeTags((array) ($analysis['suggested_themes'] ?? []));
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
            'description' => 'required|string|max:5000',
            'selectedInitiativeId' => 'required|exists:initiatives,id',
        ], [
            'title.required' => 'Geef je activiteit een titel.',
            'title.max' => 'De titel mag maximaal 255 tekens bevatten.',
            'description.required' => 'Geef een beschrijving van je activiteit.',
            'description.max' => 'De beschrijving mag maximaal 5000 tekens bevatten.',
            'selectedInitiativeId.required' => 'Kies een initiatief waar deze fiche bij hoort.',
            'selectedInitiativeId.exists' => 'Het gekozen initiatief bestaat niet.',
        ]);

        $slug = $this->generateUniqueSlug($this->title);

        $materials = array_filter([
            'preparation' => $this->preparation,
            'inventory' => $this->inventory,
            'process' => $this->process,
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

                // Also assign generated PDF versions to this fiche
                File::whereIn('source_file_id', $fileIds)->update(['fiche_id' => $fiche->id]);

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

        if ($published && auth()->user()->isMember()) {
            auth()->user()->update(['role' => 'contributor']);
        }

        $this->clearWizardSession();

        $route = $fiche->initiative
            ? route('fiches.show', [$fiche->initiative, $fiche])
            : route('home');

        if ($published) {
            $this->publishedFicheId = $fiche->id;
            $this->publishedFicheUrl = $route;
            $this->currentStep = 4;

            return;
        }

        $this->redirect($route, navigate: false);
        session()->flash('toast', [
            'heading' => 'Concept opgeslagen',
            'text' => 'Je kunt later verder werken aan je fiche.',
            'variant' => 'success',
        ]);
    }

    private function clearWizardSession(): void
    {
        $this->title = '';
        $this->description = '';
        $this->preparation = '';
        $this->inventory = '';
        $this->process = '';
        $this->duration = '';
        $this->groupSize = '';
        $this->selectedInitiativeId = null;
        $this->manualInitiativeId = null;
        $this->selectedThemeTags = [];
        $this->selectedGoalTags = [];
        $this->uploadedFiles = [];
        $this->previewFileId = null;
        $this->disclaimerAccepted = false;
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
