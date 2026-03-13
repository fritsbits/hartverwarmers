<?php

namespace App\Livewire;

use App\Models\Fiche;
use App\Models\File;
use App\Models\Initiative;
use App\Models\Tag;
use App\Services\FileTextExtractor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class FicheEdit extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Fiche $fiche;

    public string $activeTab = 'praktische-informatie';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:5000')]
    public string $description = '';

    public string $preparation = '';

    public string $inventory = '';

    public string $process = '';

    public string $duration = '';

    public string $groupSize = '';

    /** @var array<int, string> */
    public array $targetAudience = [];

    /** @var array<int, int> */
    public array $selectedThemeTags = [];

    /** @var array<int, int> */
    public array $selectedGoalTags = [];

    public ?int $selectedInitiativeId = null;

    /** @var array<int, array{id: int, name: string, size: int, type: string}> */
    public array $existingFiles = [];

    #[Validate(
        ['newUploads.*' => 'file|max:51200|mimes:pdf,pptx,docx,doc,ppt,jpg,jpeg,png'],
        message: [
            'newUploads.*.max' => 'Dit bestand is te groot (max 50 MB). Probeer het bestand te verkleinen voor je het opnieuw uploadt.',
            'newUploads.*.mimes' => 'Dit bestandstype wordt niet ondersteund. Kies een PDF, PPTX, DOCX of afbeelding (JPG/PNG).',
            'newUploads.*.file' => 'Dit bestand kon niet worden gelezen. Probeer het opnieuw te uploaden.',
        ]
    )]
    public array $newUploads = [];

    public function mount(Fiche $fiche): void
    {
        $this->authorize('update', $fiche);

        $this->fiche = $fiche;
        $this->title = $fiche->title;
        $this->description = $fiche->description ?? '';
        $this->selectedInitiativeId = $fiche->initiative_id;

        $materials = $fiche->materials ?? [];
        $this->preparation = $materials['preparation'] ?? '';
        $this->inventory = $materials['inventory'] ?? '';
        $this->process = $materials['process'] ?? '';
        $this->duration = $materials['duration'] ?? '';
        $this->groupSize = $materials['group_size'] ?? '';
        $this->targetAudience = $fiche->target_audience ?? [];

        $this->selectedThemeTags = $fiche->tags()->where('type', 'theme')->pluck('tags.id')->toArray();
        $this->selectedGoalTags = $fiche->tags()->where('type', 'goal')->pluck('tags.id')->toArray();

        $this->existingFiles = $fiche->files->map(fn ($f) => [
            'id' => $f->id,
            'name' => $f->original_filename,
            'size' => $f->size_bytes,
            'type' => $f->typeLabel(),
        ])->toArray();
    }

    public function updatedNewUploads(): void
    {
        $this->validateOnly('newUploads.*', messages: [
            'newUploads.*.max' => 'Dit bestand is te groot (max 50 MB). Probeer het bestand te verkleinen voor je het opnieuw uploadt.',
            'newUploads.*.mimes' => 'Dit bestandstype wordt niet ondersteund. Kies een PDF, PPTX, DOCX of afbeelding (JPG/PNG).',
            'newUploads.*.file' => 'Dit bestand kon niet worden gelezen. Probeer het opnieuw te uploaden.',
        ]);

        foreach ($this->newUploads as $upload) {
            $path = $upload->store('files', 'public');

            $file = File::create([
                'fiche_id' => $this->fiche->id,
                'original_filename' => $upload->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $upload->getMimeType(),
                'size_bytes' => $upload->getSize(),
                'sort_order' => count($this->existingFiles),
            ]);

            $extractor = app(FileTextExtractor::class);
            $storagePath = Storage::disk('public')->path($path);
            $text = $extractor->extract($storagePath, $upload->getMimeType());

            if ($text) {
                $file->update(['extracted_text' => $text]);
            }

            $this->existingFiles[] = [
                'id' => $file->id,
                'name' => $file->original_filename,
                'size' => $file->size_bytes,
                'type' => $file->typeLabel(),
            ];
        }

        $this->newUploads = [];
    }

    public function removeFile(int $fileId): void
    {
        $file = File::where('id', $fileId)->where('fiche_id', $this->fiche->id)->first();

        if ($file) {
            Storage::disk('public')->delete($file->path);

            if ($file->preview_images) {
                foreach ($file->preview_images as $preview) {
                    Storage::disk('public')->delete($preview);
                }
            }

            $file->delete();

            $this->existingFiles = array_values(
                array_filter($this->existingFiles, fn ($f) => $f['id'] !== $fileId)
            );
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $materials = array_filter([
            'preparation' => $this->preparation,
            'inventory' => $this->inventory,
            'process' => $this->process,
            'duration' => $this->duration,
            'group_size' => $this->groupSize,
        ]);

        $this->fiche->update([
            'initiative_id' => $this->selectedInitiativeId,
            'title' => $this->title,
            'description' => $this->description,
            'materials' => ! empty($materials) ? $materials : null,
            'target_audience' => ! empty($this->targetAudience) ? $this->targetAudience : null,
        ]);

        if (Feature::active('diamant-goals')) {
            $tagIds = array_merge($this->selectedThemeTags, $this->selectedGoalTags);
        } else {
            $existingGoalTagIds = $this->fiche->tags()->where('type', 'goal')->pluck('tags.id')->toArray();
            $tagIds = array_merge($this->selectedThemeTags, $existingGoalTagIds);
        }
        $this->fiche->tags()->sync($tagIds);

        $route = $this->fiche->initiative
            ? route('fiches.show', [$this->fiche->initiative, $this->fiche])
            : route('home');

        session()->flash('toast', [
            'heading' => 'Fiche bijgewerkt',
            'text' => 'Je wijzigingen zijn opgeslagen.',
            'variant' => 'success',
        ]);
        $this->redirect($route, navigate: false);
    }

    public function render()
    {
        return view('livewire.fiche-edit', [
            'allThemeTags' => Tag::where('type', 'theme')->orderBy('name')->get(),
            'allGoalTags' => Feature::active('diamant-goals')
                ? Tag::where('type', 'goal')->orderBy('name')->get()
                : collect(),
            'allInitiatives' => Initiative::published()->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
