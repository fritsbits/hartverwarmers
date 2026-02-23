<?php

namespace App\Console\Commands;

use App\Models\Elaboration;
use App\Models\Initiative;
use App\Models\Organisation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportInitiatives extends Command
{
    protected $signature = 'import:initiatives
                            {file : Path to the initiatives_import.json file}
                            {--dry-run : Preview import without writing to the database}
                            {--fresh : Wipe imported data before re-importing}';

    protected $description = 'Import old Hartverwarmers activities as initiatives & elaborations from a JSON file';

    private const SYSTEM_EMAIL = 'import@hartverwarmers.be';

    private const DIAMANT_MAP = [
        'D' => 'doel-doen',
        'I' => 'doel-inclusief',
        'AU' => 'doel-autonomie',
        'M' => 'doel-mensgericht',
        'A' => 'doel-anderen',
        'N' => 'doel-normalisatie',
        'T' => 'doel-talent',
    ];

    private const INTEREST_MAP = [
        // Exact matches to existing tags
        'Muziek' => 'Muziek',
        'Sport & Bewegen' => 'Sport & Bewegen',
        'Uitstappen' => 'Uitstappen',
        'Gezelschap' => 'Gezelschap',
        'Zelfzorg' => 'Zelfzorg',
        'Technologie' => 'Technologie',
        'Huishouden' => 'Huishouden',
        'Beheren & organiseren' => 'Beheren & organiseren',
        'Geloof & tradities' => 'Geloof & tradities',
        'Klussen & creatief' => 'Klussen & creatief',
        'Kunst & cultuur' => 'Kunst & cultuur',
        'Lezen & schrijven' => 'Lezen & schrijven',
        'Natuur & dieren' => 'Natuur & dieren',
        'Nieuws & actualiteit' => 'Nieuws & actualiteit',
        'Spelletjes' => 'Spelletjes',
        'Tv & film' => 'Tv & film',
        // Mapped: Spelletjes
        'Groepsspelletjes' => 'Spelletjes',
        'Quiz' => 'Spelletjes',
        'Kaartspelen' => 'Spelletjes',
        'Kaartspellen' => 'Spelletjes',
        'Bordspelen' => 'Spelletjes',
        'Bordspellen' => 'Spelletjes',
        'Puzzelen' => 'Spelletjes',
        'Woord- & cijferspelen' => 'Spelletjes',
        // Mapped: Huishouden
        'Koken & bakken' => 'Huishouden',
        'Boodschappen' => 'Huishouden',
        'Lekker eten' => 'Huishouden',
        // Mapped: Natuur & dieren
        'Tuinieren' => 'Natuur & dieren',
        'Natuur' => 'Natuur & dieren',
        'Dieren' => 'Natuur & dieren',
        'Wandelen' => 'Natuur & dieren',
        'Bloemen & planten' => 'Natuur & dieren',
        'Planten & bloemen' => 'Natuur & dieren',
        'Huisdieren' => 'Natuur & dieren',
        'Boerderijdieren' => 'Natuur & dieren',
        'Wilde dieren' => 'Natuur & dieren',
        'Bos' => 'Natuur & dieren',
        "Natuurprogramma's" => 'Natuur & dieren',
        // Mapped: Klussen & creatief
        'Knutselen' => 'Klussen & creatief',
        'Creatief' => 'Klussen & creatief',
        'Handwerken' => 'Klussen & creatief',
        'Tekenen' => 'Klussen & creatief',
        'Schilderen' => 'Klussen & creatief',
        'Decoratie' => 'Klussen & creatief',
        'Tekenen & schilderen' => 'Klussen & creatief',
        'Boetseren' => 'Klussen & creatief',
        'Haken' => 'Klussen & creatief',
        'Breien' => 'Klussen & creatief',
        'Naaien' => 'Klussen & creatief',
        'Repareren' => 'Klussen & creatief',
        '(Ver)bouwen' => 'Klussen & creatief',
        'Fotografie' => 'Klussen & creatief',
        // Mapped: Lezen & schrijven
        'Lezen' => 'Lezen & schrijven',
        'Voorlezen' => 'Lezen & schrijven',
        'Verhalen' => 'Lezen & schrijven',
        'Poëzie' => 'Lezen & schrijven',
        'Taal & voordracht' => 'Lezen & schrijven',
        // Mapped: Tv & film
        'Film' => 'Tv & film',
        'Films vroeger/recent' => 'Tv & film',
        'Tv-series' => 'Tv & film',
        'Radio' => 'Tv & film',
        // Mapped: Kunst & cultuur
        'Theater' => 'Kunst & cultuur',
        'Theater & dans' => 'Kunst & cultuur',
        'Schilderijen & beeldhouwwerk' => 'Kunst & cultuur',
        // Mapped: Gezelschap
        'Feestdagen' => 'Gezelschap',
        'Feesten' => 'Gezelschap',
        'Reminiscentie' => 'Gezelschap',
        'Gesprekken' => 'Gezelschap',
        'Gesprekken voeren' => 'Gezelschap',
        'Koffie en theekrans' => 'Gezelschap',
        // Mapped: Geloof & tradities
        'Geloof' => 'Geloof & tradities',
        'Religie' => 'Geloof & tradities',
        // Mapped: Sport & Bewegen
        'Beweging' => 'Sport & Bewegen',
        'Dans' => 'Sport & Bewegen',
        'Dansen' => 'Sport & Bewegen',
        'Yoga' => 'Sport & Bewegen',
        'Fietsen' => 'Sport & Bewegen',
        'Turnen' => 'Sport & Bewegen',
        'Wielrennen' => 'Sport & Bewegen',
        'Balsport' => 'Sport & Bewegen',
        // Mapped: Beheren & organiseren
        'Organisatie' => 'Beheren & organiseren',
        'Vergaderen' => 'Beheren & organiseren',
        // Mapped: Nieuws & actualiteit
        'Nieuws' => 'Nieuws & actualiteit',
        'Sportnieuws' => 'Nieuws & actualiteit',
        'Koningshuis' => 'Nieuws & actualiteit',
        'Bekendheden' => 'Nieuws & actualiteit',
        'Politiek' => 'Nieuws & actualiteit',
        // Mapped: Uitstappen
        'Daguitstappen' => 'Uitstappen',
        'Groepsuitstappen' => 'Uitstappen',
        'Reizen' => 'Uitstappen',
        // Mapped: Muziek
        'Zingen' => 'Muziek',
        'Klassiek & Jazz' => 'Muziek',
        // Mapped: Technologie
        'Computer/tablet/smartphone' => 'Technologie',
        'Digitaal' => 'Technologie',
        'Sociale media' => 'Technologie',
        'Camera' => 'Technologie',
        // Mapped: Zelfzorg
        'Therapie' => 'Zelfzorg',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        $dryRun = $this->option('dry-run');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        if (! $data || ! isset($data['initiatives'])) {
            $this->error('Invalid JSON file: missing "initiatives" key');

            return self::FAILURE;
        }

        $initiatives = $data['initiatives'];
        $totalElaborations = array_sum(array_column($initiatives, 'elaborations_count'));

        $this->info("Found {$this->countInitiatives($initiatives)} initiatives with {$totalElaborations} elaborations");

        if ($dryRun) {
            $this->warn('DRY RUN — no data will be written');
            $this->previewImport($initiatives);

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->wipePreviousImport();
        }

        $this->importData($initiatives);

        return self::SUCCESS;
    }

    private function countInitiatives(array $initiatives): int
    {
        return count($initiatives);
    }

    private function previewImport(array $initiatives): void
    {
        $authors = $this->collectUniqueAuthors($initiatives);

        $rows = [];
        foreach ($initiatives as $init) {
            $rows[] = [
                Str::slug($init['name']),
                $init['name'],
                $init['elaborations_count'],
                implode(', ', $init['diamant_aspects'] ?? []),
            ];
        }

        $this->table(['Slug', 'Initiative', 'Elaborations', 'DIAMANT'], $rows);
        $this->newLine();
        $this->info("Unique authors: {$authors->count()}");
    }

    private function importData(array $initiatives): void
    {
        // 1. Ensure goal tags exist
        $goalTags = $this->ensureGoalTags();
        $this->info("Goal tags: {$goalTags->count()}");

        // 2. Load existing interest tags
        $interestTags = Tag::where('type', 'interest')->get()->keyBy('name');
        $this->info("Interest tags loaded: {$interestTags->count()}");

        // 3. Create import organisation + system user
        $organisation = Organisation::firstOrCreate(
            ['name' => 'Import'],
            ['city' => null],
        );

        $systemUser = User::firstOrCreate(
            ['email' => self::SYSTEM_EMAIL],
            [
                'name' => 'Hartverwarmers Import',
                'password' => Str::random(32),
                'role' => 'contributor',
                'organisation_id' => $organisation->id,
            ],
        );

        // 4. Create contributor users
        $authors = $this->collectUniqueAuthors($initiatives);
        $userMap = $this->createContributorUsers($authors, $organisation);
        $userMap[self::SYSTEM_EMAIL] = $systemUser;
        $this->info("Created {$authors->count()} contributor users");

        // 5. Import initiatives & elaborations in a transaction
        $stats = ['initiatives' => 0, 'elaborations' => 0, 'skipped_initiatives' => 0, 'skipped_elaborations' => 0];

        DB::transaction(function () use ($initiatives, $goalTags, $interestTags, $userMap, $systemUser, &$stats) {
            $usedSlugs = Elaboration::withTrashed()->pluck('slug')->flip();

            foreach ($initiatives as $initData) {
                $slug = Str::slug($initData['name']);

                // Idempotency: skip if already exists
                if (Initiative::withTrashed()->where('slug', $slug)->exists()) {
                    $this->warn("  Skipping existing initiative: {$initData['name']}");
                    $stats['skipped_initiatives']++;

                    continue;
                }

                $initiative = Initiative::create([
                    'title' => $initData['name'],
                    'slug' => $slug,
                    'description' => $initData['description'] ?? null,
                    'published' => true,
                    'created_by' => $systemUser->id,
                ]);

                // Attach goal tags to initiative
                $initGoalTagIds = $this->resolveGoalTagIds($initData['diamant_aspects'] ?? [], $goalTags);
                if ($initGoalTagIds->isNotEmpty()) {
                    $initiative->tags()->attach($initGoalTagIds);
                }

                $stats['initiatives']++;

                // Import elaborations
                foreach ($initData['elaborations'] as $elabData) {
                    $user = $this->resolveUser($elabData['authors'] ?? null, $userMap, $systemUser);
                    $elabSlug = $this->uniqueSlug($elabData['title'], $slug, $usedSlugs);
                    $usedSlugs[$elabSlug] = true;

                    $originalData = $elabData['original_data'] ?? [];
                    $fiche = $this->parseFiche($originalData['fiche'] ?? null);
                    $targetAudience = $this->parseJsonString($elabData['target_audience'] ?? null);
                    $hasDiamond = ! empty($elabData['diamant_aspects']);

                    $elaboration = new Elaboration([
                        'initiative_id' => $initiative->id,
                        'user_id' => $user->id,
                        'title' => $elabData['title'],
                        'slug' => $elabSlug,
                        'description' => $elabData['description'] ?? null,
                        'fiche' => $fiche,
                        'target_audience' => $targetAudience,
                        'published' => true,
                        'has_diamond' => $hasDiamond,
                    ]);

                    // Preserve timestamps
                    if (! empty($originalData['created_at'])) {
                        $elaboration->created_at = $originalData['created_at'];
                    }
                    if (! empty($originalData['updated_at'])) {
                        $elaboration->updated_at = $originalData['updated_at'];
                    }

                    $elaboration->save();

                    // Attach goal tags to elaboration
                    $elabGoalTagIds = $this->resolveGoalTagIds($elabData['diamant_aspects'] ?? [], $goalTags);

                    // Attach interest tags to elaboration
                    $elabInterestTagIds = $this->resolveInterestTagIds($elabData['interests'] ?? '', $interestTags);

                    $allTagIds = $elabGoalTagIds->merge($elabInterestTagIds)->unique();
                    if ($allTagIds->isNotEmpty()) {
                        $elaboration->tags()->attach($allTagIds);
                    }

                    $stats['elaborations']++;
                }
            }
        });

        $this->newLine();
        $this->info('Import complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Initiatives created', $stats['initiatives']],
                ['Initiatives skipped', $stats['skipped_initiatives']],
                ['Elaborations created', $stats['elaborations']],
                ['Goal tags', $goalTags->count()],
                ['Interest tags', $interestTags->count()],
                ['Contributor users', count($userMap) - 1],
            ],
        );
    }

    private function ensureGoalTags(): Collection
    {
        $facets = config('diamant.facets');
        $tags = collect();

        foreach ($facets as $facet) {
            $tags->push(Tag::firstOrCreate(
                ['slug' => "doel-{$facet['slug']}"],
                ['name' => $facet['keyword'], 'type' => 'goal'],
            ));
        }

        return $tags;
    }

    private function collectUniqueAuthors(array $initiatives): Collection
    {
        $authors = collect();

        foreach ($initiatives as $init) {
            foreach ($init['elaborations'] as $elab) {
                if (! empty($elab['authors'])) {
                    $authors->push(trim($elab['authors']));
                }
            }
        }

        return $authors->unique();
    }

    private function createContributorUsers(Collection $authors, Organisation $organisation): array
    {
        $userMap = [];

        foreach ($authors as $authorName) {
            $email = Str::slug($authorName).'@import.hartverwarmers.be';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $authorName,
                    'password' => Str::random(32),
                    'role' => 'contributor',
                    'organisation_id' => $organisation->id,
                ],
            );

            $userMap[$authorName] = $user;
        }

        return $userMap;
    }

    private function resolveUser(?string $authorName, array $userMap, User $systemUser): User
    {
        if (empty($authorName)) {
            return $systemUser;
        }

        return $userMap[trim($authorName)] ?? $systemUser;
    }

    private function uniqueSlug(string $title, string $initiativeSlug, Collection $usedSlugs): string
    {
        $slug = Str::slug($title);

        if (empty($slug)) {
            $slug = 'uitwerking';
        }

        if (! $usedSlugs->has($slug)) {
            return $slug;
        }

        // Append initiative slug as suffix
        $suffixed = "{$slug}-{$initiativeSlug}";
        if (! $usedSlugs->has($suffixed)) {
            return $suffixed;
        }

        // Final fallback: append a counter
        $counter = 2;
        while ($usedSlugs->has("{$suffixed}-{$counter}")) {
            $counter++;
        }

        return "{$suffixed}-{$counter}";
    }

    private function resolveGoalTagIds(array $aspects, Collection $goalTags): Collection
    {
        $tagsBySlug = $goalTags->keyBy('slug');

        return collect($aspects)
            ->map(fn ($aspect) => self::DIAMANT_MAP[$aspect] ?? null)
            ->filter()
            ->map(fn ($slug) => $tagsBySlug->get($slug)?->id)
            ->filter()
            ->values();
    }

    private function resolveInterestTagIds(string $interestsString, Collection $interestTags): Collection
    {
        if (empty($interestsString)) {
            return collect();
        }

        return collect(explode(',', $interestsString))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->map(fn ($name) => self::INTEREST_MAP[$name] ?? null)
            ->filter()
            ->unique()
            ->map(fn ($tagName) => $interestTags->get($tagName)?->id)
            ->filter()
            ->values();
    }

    private function parseFiche(?string $ficheJson): ?array
    {
        if (empty($ficheJson)) {
            return null;
        }

        $parsed = json_decode($ficheJson, true);
        if (! is_array($parsed)) {
            return null;
        }

        // Strip HTML tags from fiche values and filter empty ones
        $cleaned = array_map(fn ($value) => trim(strip_tags((string) $value)), $parsed);
        $nonEmpty = array_filter($cleaned, fn ($value) => $value !== '');

        return $nonEmpty ?: null;
    }

    private function parseJsonString(?string $jsonString): ?array
    {
        if (empty($jsonString)) {
            return null;
        }

        $parsed = json_decode($jsonString, true);

        return is_array($parsed) && ! empty($parsed) ? $parsed : null;
    }

    private function wipePreviousImport(): void
    {
        $this->warn('Wiping previous import data...');

        $organisation = Organisation::where('name', 'Import')->first();
        if (! $organisation) {
            $this->info('No previous import found.');

            return;
        }

        $importUserIds = User::where('organisation_id', $organisation->id)->pluck('id');

        // Delete elaborations by import users
        $elaborationIds = Elaboration::whereIn('user_id', $importUserIds)->pluck('id');
        DB::table('taggables')
            ->where('taggable_type', Elaboration::class)
            ->whereIn('taggable_id', $elaborationIds)
            ->delete();
        Elaboration::whereIn('id', $elaborationIds)->forceDelete();

        // Delete initiatives created by import users
        $initiativeIds = Initiative::whereIn('created_by', $importUserIds)->pluck('id');
        DB::table('taggables')
            ->where('taggable_type', Initiative::class)
            ->whereIn('taggable_id', $initiativeIds)
            ->delete();
        Initiative::whereIn('id', $initiativeIds)->forceDelete();

        // Delete import users and organisation
        User::whereIn('id', $importUserIds)->forceDelete();
        $organisation->delete();

        $this->info('Previous import wiped.');
    }
}
