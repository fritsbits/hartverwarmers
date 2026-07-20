<?php

namespace App\Livewire;

use App\Services\ProductUpdates;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AdminProductUpdates extends Component
{
    public bool $showForm = false;

    public ?string $originalUid = null;

    public string $uid = '';

    public string $publishedAt = '';

    public string $title = '';

    public string $body = '';

    public string $content = '';

    public string $linkUrl = '';

    public string $linkLabel = '';

    public string $imageSrc = '';

    public string $imageAlt = '';

    public function create(): void
    {
        $this->resetForm();
        $this->publishedAt = now()->format('Y-m-d');
        $this->showForm = true;
    }

    public function edit(string $uid): void
    {
        $update = ProductUpdates::find($uid);

        if ($update === null) {
            return;
        }

        $this->resetForm();
        $this->originalUid = $update['uid'];
        $this->uid = $update['uid'];
        $this->publishedAt = $update['published_at'];
        $this->title = $update['title'];
        $this->body = $update['body'];
        $this->content = $update['content'] ?? '';
        $this->linkUrl = $update['link']['url'] ?? '';
        $this->linkLabel = $update['link']['label'] ?? '';
        $this->imageSrc = $update['image']['src'] ?? '';
        $this->imageAlt = $update['image']['alt'] ?? '';
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $rules = [
            'publishedAt' => 'required|date_format:Y-m-d',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:2000',
            'content' => 'nullable|string|max:20000',
            'linkUrl' => 'nullable|string|max:255|required_with:linkLabel',
            'linkLabel' => 'nullable|string|max:255|required_with:linkUrl',
            'imageSrc' => 'nullable|string|max:255|required_with:imageAlt',
            'imageAlt' => 'nullable|string|max:255|required_with:imageSrc',
        ];

        if ($this->uid !== '') {
            $rules['uid'] = 'string|max:100|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/';
        }

        $this->validate($rules, [
            'uid.regex' => 'Een uid bevat enkel kleine letters, cijfers en streepjes.',
        ], [
            'publishedAt' => 'publicatiedatum',
            'title' => 'titel',
            'body' => 'korte tekst',
            'content' => 'uitgebreide tekst',
            'linkUrl' => 'link-URL',
            'linkLabel' => 'linktekst',
            'imageSrc' => 'afbeeldingspad',
            'imageAlt' => 'alt-tekst',
        ]);

        $uid = $this->originalUid ?? ($this->uid !== '' ? $this->uid : $this->generateUid());

        if ($this->originalUid === null && ProductUpdates::find($uid) !== null) {
            $this->addError('uid', "Er bestaat al een update met uid \"{$uid}\".");

            return;
        }

        ProductUpdates::save(array_filter([
            'uid' => $uid,
            'published_at' => $this->publishedAt,
            'title' => trim($this->title),
            'body' => trim($this->body),
            'content' => trim($this->content) !== '' ? trim($this->content) : null,
            'image' => trim($this->imageSrc) !== ''
                ? ['src' => trim($this->imageSrc), 'alt' => trim($this->imageAlt)]
                : null,
            'link' => trim($this->linkUrl) !== ''
                ? ['url' => trim($this->linkUrl), 'label' => trim($this->linkLabel)]
                : null,
        ]));

        Flux::toast($this->originalUid !== null ? 'Update aangepast.' : 'Update toegevoegd.', variant: 'success');
        $this->resetForm();
    }

    public function delete(string $uid): void
    {
        ProductUpdates::delete($uid);

        if ($this->originalUid === $uid) {
            $this->resetForm();
        }

        Flux::toast('Update verwijderd.', variant: 'success');
    }

    /**
     * All product updates from the content disk, newest first.
     *
     * @return Collection<int, array{uid: string, published_at: string, title: string, body: string, content?: string, image?: array{src: string, alt: string}, link?: array{url: string, label: string}}>
     */
    #[Computed]
    public function updates(): Collection
    {
        return ProductUpdates::all();
    }

    public function isFresh(string $publishedAt): bool
    {
        return Carbon::parse($publishedAt)->gte(now()->subDays(ProductUpdates::FRESH_DAYS));
    }

    private function generateUid(): string
    {
        return Carbon::parse($this->publishedAt)->format('Y-m').'-'.Str::slug($this->title);
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm', 'originalUid', 'uid', 'publishedAt', 'title', 'body',
            'content', 'linkUrl', 'linkLabel', 'imageSrc', 'imageAlt',
        ]);
        $this->resetErrorBag();
        unset($this->updates);
    }

    public function render(): View
    {
        return view('livewire.admin-product-updates');
    }
}
