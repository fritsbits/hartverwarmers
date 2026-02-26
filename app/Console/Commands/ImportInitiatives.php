<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportInitiatives extends Command
{
    protected $signature = 'import:initiatives
                            {file : Path to the grouped JSON export file}
                            {--dry-run : Preview import without writing to the database}
                            {--fresh : Wipe imported data before re-importing}';

    protected $description = 'Import old Hartverwarmers activities as initiatives & fiches from a grouped JSON export';

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

    private const GUIDANCE_MAP = [
        'active-independent' => 'Actief zelfstandig',
        'active-participator' => 'Actief zelfstandig',
        'active-participator-dependent' => 'Passief begeleid',
        'passive-participator' => 'Passief begeleid',
        'passive-dependent' => 'Volledig begeleid',
    ];

    private const THEME_MAP = [
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
        if (! is_array($data) || ! isset($data['initiatives']) || empty($data['initiatives'])) {
            $this->error('Invalid JSON file: expected an object with an "initiatives" key');

            return self::FAILURE;
        }

        $initiatives = $data['initiatives'];
        $totalFiches = collect($initiatives)->sum(fn ($i) => count($i['elaborations'] ?? []));
        $this->info('Found '.count($initiatives)." initiatives with {$totalFiches} fiches");

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

    private function previewImport(array $initiatives): void
    {
        $authors = $this->collectUniqueAuthors($initiatives);

        $rows = [];
        foreach ($initiatives as $initiative) {
            $rows[] = [
                Str::slug($initiative['name']),
                Str::limit($initiative['name'], 50),
                count($initiative['elaborations'] ?? []),
                implode(', ', $initiative['diamant_aspects'] ?? []) ?: '—',
            ];
        }

        $this->table(['Slug', 'Name', 'Fiches', 'DIAMANT'], $rows);
        $this->newLine();
        $this->info("Unique authors: {$authors->count()}");
    }

    private function importData(array $initiatives): void
    {
        // 1. Load existing theme tags
        $themeTags = Tag::where('type', 'theme')->get()->keyBy('name');
        $this->info("Theme tags loaded: {$themeTags->count()}");

        // 2. Load existing guidance tags
        $guidanceTags = Tag::where('type', 'guidance')->get()->keyBy('name');
        $this->info("Guidance tags loaded: {$guidanceTags->count()}");

        // 3. Ensure goal tags exist
        $goalTags = $this->ensureGoalTags();
        $this->info("Goal tags loaded: {$goalTags->count()}");

        // 4. Create system user
        $systemUser = User::firstOrCreate(
            ['email' => self::SYSTEM_EMAIL],
            [
                'first_name' => 'Hartverwarmers',
                'last_name' => 'Import',
                'password' => Str::random(32),
                'role' => 'contributor',
                'organisation' => 'Import',
            ],
        );

        // 5. Create contributor users
        $authors = $this->collectUniqueAuthors($initiatives);
        $userMap = $this->createContributorUsers($authors);
        $userMap[self::SYSTEM_EMAIL] = $systemUser;
        $this->info("Created {$authors->count()} contributor users");

        // 6. Import initiatives & fiches in a transaction
        $stats = ['initiatives' => 0, 'fiches' => 0, 'skipped' => 0];

        DB::transaction(function () use ($initiatives, $themeTags, $guidanceTags, $goalTags, $userMap, $systemUser, &$stats) {
            $usedInitiativeSlugs = Initiative::withTrashed()->pluck('slug')->flip();
            $usedFicheSlugs = Fiche::withTrashed()->pluck('slug')->flip();

            foreach ($initiatives as $initiativeData) {
                $initiativeSlug = $this->uniqueSlug($initiativeData['name'], $usedInitiativeSlugs);

                // Idempotency: skip if initiative slug already existed before import
                if (Initiative::withTrashed()->where('slug', Str::slug($initiativeData['name']))->exists()) {
                    $this->warn("  Skipping existing initiative: {$initiativeData['name']}");
                    $stats['skipped']++;

                    continue;
                }

                $usedInitiativeSlugs[$initiativeSlug] = true;

                // Create initiative
                $initiative = Initiative::create([
                    'title' => $initiativeData['name'],
                    'slug' => $initiativeSlug,
                    'description' => $initiativeData['description'] ?? null,
                    'published' => true,
                    'created_by' => $systemUser->id,
                ]);

                // Attach goal tags to initiative
                $initiativeGoalTagIds = $this->resolveGoalTagIds($initiativeData['diamant_aspects'] ?? [], $goalTags);
                if ($initiativeGoalTagIds->isNotEmpty()) {
                    $initiative->tags()->attach($initiativeGoalTagIds);
                }

                $stats['initiatives']++;

                // Import fiches for this initiative
                foreach ($initiativeData['elaborations'] ?? [] as $elaboration) {
                    $ficheSlug = $this->uniqueSlug($elaboration['title'], $usedFicheSlugs);
                    $usedFicheSlugs[$ficheSlug] = true;

                    // Resolve author
                    $user = $this->resolveUser($elaboration['authors'] ?? null, $userMap, $systemUser);

                    // Parse fiche content from original_data
                    $originalData = $elaboration['original_data'] ?? [];
                    $materials = $this->parseFiche($originalData['fiche'] ?? null);
                    $practicalTips = $this->extractPracticalTips($originalData['fiche'] ?? null);
                    $targetAudience = $this->parseJsonString($elaboration['target_audience'] ?? null);
                    $hasDiamond = ($originalData['quality_score'] ?? null) === 1;

                    // Create fiche
                    $fiche = new Fiche([
                        'initiative_id' => $initiative->id,
                        'user_id' => $user->id,
                        'title' => $elaboration['title'],
                        'slug' => $ficheSlug,
                        'description' => $elaboration['description'] ?? null,
                        'practical_tips' => $practicalTips,
                        'materials' => $materials,
                        'target_audience' => $targetAudience,
                        'published' => true,
                        'has_diamond' => $hasDiamond,
                    ]);

                    if (! empty($originalData['created_at'])) {
                        $fiche->created_at = $originalData['created_at'];
                    }
                    if (! empty($originalData['updated_at'])) {
                        $fiche->updated_at = $originalData['updated_at'];
                    }

                    $fiche->save();

                    // Attach theme tags to fiche
                    $themeTagIds = $this->resolveThemeTagIds($elaboration['interests'] ?? '', $themeTags);
                    if ($themeTagIds->isNotEmpty()) {
                        $fiche->tags()->attach($themeTagIds);
                    }

                    // Attach guidance tags to fiche
                    $guidanceTagIds = $this->resolveGuidanceTagIds($originalData['guidances'] ?? null, $guidanceTags);
                    if ($guidanceTagIds->isNotEmpty()) {
                        $fiche->tags()->attach($guidanceTagIds);
                    }

                    // Attach goal tags to fiche
                    $ficheGoalTagIds = $this->resolveGoalTagIds($elaboration['diamant_aspects'] ?? [], $goalTags);
                    if ($ficheGoalTagIds->isNotEmpty()) {
                        $fiche->tags()->attach($ficheGoalTagIds);
                    }

                    $stats['fiches']++;
                }
            }
        });

        $this->newLine();
        $this->info('Import complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Initiatives created', $stats['initiatives']],
                ['Fiches created', $stats['fiches']],
                ['Initiatives skipped', $stats['skipped']],
                ['Theme tags', $themeTags->count()],
                ['Guidance tags', $guidanceTags->count()],
                ['Goal tags', $goalTags->count()],
                ['Contributor users', count($userMap) - 1],
            ],
        );
    }

    private function ensureGoalTags(): Collection
    {
        $facets = config('diamant.facets');
        $goalTags = collect();

        foreach ($facets as $facet) {
            $slug = 'doel-'.$facet['slug'];
            $tag = Tag::firstOrCreate(
                ['slug' => $slug, 'type' => 'goal'],
                ['name' => $facet['keyword']],
            );
            $goalTags[$slug] = $tag;
        }

        return $goalTags;
    }

    private function resolveGoalTagIds(array $aspects, Collection $goalTags): Collection
    {
        return collect($aspects)
            ->map(fn ($letter) => self::DIAMANT_MAP[$letter] ?? null)
            ->filter()
            ->unique()
            ->map(fn ($slug) => $goalTags->get($slug)?->id)
            ->filter()
            ->values();
    }

    private function collectUniqueAuthors(array $initiatives): Collection
    {
        return collect($initiatives)
            ->flatMap(fn ($i) => collect($i['elaborations'] ?? [])->pluck('authors'))
            ->filter()
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();
    }

    private function createContributorUsers(Collection $authors): array
    {
        $userMap = [];

        foreach ($authors as $authorName) {
            $email = Str::slug($authorName).'@import.hartverwarmers.be';
            $parts = explode(' ', $authorName, 2);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $parts[0],
                    'last_name' => $parts[1] ?? '',
                    'password' => Str::random(32),
                    'role' => 'contributor',
                    'organisation' => 'Import',
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

    private function uniqueSlug(string $title, Collection $usedSlugs): string
    {
        $slug = Str::slug($title);

        if (empty($slug)) {
            $slug = 'activiteit';
        }

        if (! $usedSlugs->has($slug)) {
            return $slug;
        }

        $counter = 2;
        while ($usedSlugs->has("{$slug}-{$counter}")) {
            $counter++;
        }

        return "{$slug}-{$counter}";
    }

    private function resolveThemeTagIds(string $interestsString, Collection $themeTags): Collection
    {
        if (empty($interestsString)) {
            return collect();
        }

        return collect(explode(',', $interestsString))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->map(fn ($name) => self::THEME_MAP[$name] ?? null)
            ->filter()
            ->unique()
            ->map(fn ($tagName) => $themeTags->get($tagName)?->id)
            ->filter()
            ->values();
    }

    private function resolveGuidanceTagIds(?string $guidancesJson, Collection $guidanceTags): Collection
    {
        if (empty($guidancesJson)) {
            return collect();
        }

        $guidances = json_decode($guidancesJson, true);
        if (! is_array($guidances)) {
            return collect();
        }

        return collect($guidances)
            ->map(fn ($slug) => self::GUIDANCE_MAP[$slug] ?? null)
            ->filter()
            ->unique()
            ->map(fn ($tagName) => $guidanceTags->get($tagName)?->id)
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

        // Keep process, inventory, preparation with HTML for display; filter empty fields
        $fields = ['process', 'inventory', 'preparation'];
        $materials = [];

        foreach ($fields as $field) {
            $value = trim($parsed[$field] ?? '');
            if ($value !== '') {
                $materials[$field] = $value;
            }
        }

        return $materials ?: null;
    }

    private function extractPracticalTips(?string $ficheJson): ?string
    {
        if (empty($ficheJson)) {
            return null;
        }

        $parsed = json_decode($ficheJson, true);
        if (! is_array($parsed)) {
            return null;
        }

        $preparation = trim($parsed['preparation'] ?? '');
        if ($preparation === '') {
            return null;
        }

        return trim(strip_tags($preparation)) ?: null;
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

        $importUserIds = User::where('organisation', 'Import')->pluck('id');

        if ($importUserIds->isEmpty()) {
            $this->info('No previous import found.');

            return;
        }

        // Delete fiches by import users
        $ficheIds = Fiche::whereIn('user_id', $importUserIds)->pluck('id');
        DB::table('taggables')
            ->where('taggable_type', Fiche::class)
            ->whereIn('taggable_id', $ficheIds)
            ->delete();
        Fiche::whereIn('id', $ficheIds)->forceDelete();

        // Delete initiatives created by import users
        $initiativeIds = Initiative::whereIn('created_by', $importUserIds)->pluck('id');
        DB::table('taggables')
            ->where('taggable_type', Initiative::class)
            ->whereIn('taggable_id', $initiativeIds)
            ->delete();
        Initiative::whereIn('id', $initiativeIds)->forceDelete();

        // Delete import users
        User::whereIn('id', $importUserIds)->forceDelete();

        $this->info('Previous import wiped.');
    }
}
