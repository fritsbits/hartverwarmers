# AI Description Suggestion Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generate an AI description suggestion for fiches by renaming the unused `summary` field to `description` in the analysis pipeline and wiring it to the existing `aiDescription` Livewire property.

**Architecture:** The `AnalyzeFileContentAgent` already produces a `summary` that's only used as context for initiative matching. We rename it to `description`, enhance the prompt with criteria and examples, and populate `aiDescription` in the Livewire component. The existing suggestion panel UI renders automatically.

**Tech Stack:** Laravel AI agents, Livewire 4, PHPUnit

---

## Chunk 1: Rename summary → description through the pipeline

### Task 1: Update AnalyzeFileContentAgent schema and instructions

**Files:**
- Modify: `app/Ai/Agents/AnalyzeFileContentAgent.php`

- [ ] **Step 1: Update the schema field**

In `schema()`, replace:
```php
'summary' => $schema->string()->required()->description('Korte samenvatting van de activiteit (1-2 zinnen)'),
```
with:
```php
'description' => $schema->string()->required()->description('Beschrijving van de activiteit (1-3 zinnen). Beantwoord: wat is de activiteit, wat maakt het boeiend, voor wie is het?'),
```

- [ ] **Step 2: Add examples to the agent instructions**

In `instructions()`, after the line `Als informatie niet in de tekst staat, laat het veld leeg.`, add:

```php
        Voor het veld 'description', schrijf 1-3 zinnen die beantwoorden: wat is de activiteit, wat maakt het boeiend, en voor wie is het? De toon is warm en praktisch, als een collega die de activiteit beschrijft.

        Voorbeelden van goede beschrijvingen:
        - "Bak samen smoutebollen en breng de gezellige sfeer van de kermis naar het woonzorgcentrum. De geur en smaak roepen herinneringen op en brengen bewoners samen rond een gedeelde beleving."
        - "Laat bewoners inschatten hoeveel dagelijkse voorwerpen vandaag kosten en vergelijk met de prijzen van vroeger. Een leuke manier om herinneringen op te halen en gesprekken op gang te brengen over het dagelijks leven van toen."
        - "Tover de tuin om tot een lichtjesparadijs en trek er in de late namiddag samen op uit. Met een jenever, streepje muziek en braadworst aan het vuur wordt het een gezellig wintermoment voor alle bewoners."
```

- [ ] **Step 3: Commit**

```bash
git add app/Ai/Agents/AnalyzeFileContentAgent.php
git commit -m "feat: rename summary to description in AnalyzeFileContentAgent with examples"
```

### Task 2: Update FicheAiService

**Files:**
- Modify: `app/Services/FicheAiService.php`

- [ ] **Step 1: Update analyzeFiles return value**

On line 48, change:
```php
'summary' => $response['summary'] ?? '',
```
to:
```php
'description' => $response['description'] ?? '',
```

- [ ] **Step 2: Update PHPDoc @return type**

On line 23, change `summary` to `description` in the array shape:
```php
* @return array{description: string, preparation: string, inventory: string, process: string, duration_estimate: string, group_size_estimate: string, suggested_themes: array, suggested_goals: array, suggested_target_audience: array, _meta: array}|null
```

- [ ] **Step 3: Update matchInitiatives parameter**

On line 69, rename the parameter:
```php
public function matchInitiatives(string $title, string $description, ?string $aiDescription): ?array
```

On lines 92-93, update the usage:
```php
if ($aiDescription) {
    $prompt .= "Samenvatting bestanden: {$aiDescription}\n";
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Services/FicheAiService.php
git commit -m "refactor: rename summary to description in FicheAiService"
```

### Task 3: Update ProcessFicheUploads job

**Files:**
- Modify: `app/Jobs/ProcessFicheUploads.php`

- [ ] **Step 1: Update runAiAnalysis**

On lines 104-105, change:
```php
$summary = $analysis['summary'] ?? null;
$matchedInitiatives = $aiService->matchInitiatives($this->title, $this->description, $summary);
```
to:
```php
$aiDescription = $analysis['description'] ?? null;
$matchedInitiatives = $aiService->matchInitiatives($this->title, $this->description, $aiDescription);
```

- [ ] **Step 2: Commit**

```bash
git add app/Jobs/ProcessFicheUploads.php
git commit -m "refactor: rename summary to description in ProcessFicheUploads"
```

### Task 4: Update AnalyzeFileCommand

**Files:**
- Modify: `app/Console/Commands/AnalyzeFileCommand.php`

- [ ] **Step 1: Update all 3 references**

On lines 78-79, change:
```php
$summary = $analysis['summary'] ?? null;
$match = $aiService->matchInitiatives($title, $description, $summary);
```
to:
```php
$aiDescription = $analysis['description'] ?? null;
$match = $aiService->matchInitiatives($title, $description, $aiDescription);
```

On line 112, change:
```php
$this->info('Summary: '.($analysis['summary'] ?: '(empty)'));
```
to:
```php
$this->info('Description: '.($analysis['description'] ?: '(empty)'));
```

- [ ] **Step 2: Commit**

```bash
git add app/Console/Commands/AnalyzeFileCommand.php
git commit -m "refactor: rename summary to description in AnalyzeFileCommand"
```

## Chunk 2: Wire aiDescription and update tests

### Task 5: Populate aiDescription in FicheWizard

**Files:**
- Modify: `app/Livewire/FicheWizard.php:654-667`

- [ ] **Step 1: Add aiDescription loading**

In `loadAiSuggestions()`, after line 658 (`if ($analysis) {`), add as the first line inside the block:
```php
$this->aiDescription = self::markdownToHtml($analysis['description'] ?? null);
```

So it becomes:
```php
if ($analysis) {
    $this->aiDescription = self::markdownToHtml($analysis['description'] ?? null);
    $this->aiPreparation = self::markdownToHtml($analysis['preparation'] ?? null);
    ...
```

- [ ] **Step 2: Commit**

```bash
git add app/Livewire/FicheWizard.php
git commit -m "feat: populate aiDescription from analysis pipeline"
```

### Task 6: Update tests

**Files:**
- Modify: `tests/Feature/Fiches/FicheWizardTest.php`

- [ ] **Step 1: Update all 5 test references from `'summary'` to `'description'`**

Line 264: `'summary' => 'Test samenvatting'` → `'description' => 'Test samenvatting'`
Line 745: `'summary' => 'Samenvatting'` → `'description' => 'Samenvatting'`
Line 776: `'summary' => 'Samenvatting'` → `'description' => 'Samenvatting'`
Line 975: `'summary' => 'Samenvatting'` → `'description' => 'Samenvatting'`
Line 1071: `->set('aiAnalysis', ['summary' => 'test'])` → `->set('aiAnalysis', ['description' => 'test'])`

- [ ] **Step 2: Add assertion for aiDescription in the processing completion test**

In `test_check_processing_completes_on_done` (around line 280), after the existing assertion:
```php
$this->assertStringContainsString('AI voorbereiding', $component->get('aiPreparation'));
```

Add:
```php
$this->assertStringContainsString('Test samenvatting', $component->get('aiDescription'));
```

- [ ] **Step 3: Run the affected tests**

Run: `php artisan test --compact --filter=test_check_processing`
Expected: all pass

- [ ] **Step 4: Run all FicheWizard tests**

Run: `php artisan test --compact tests/Feature/Fiches/FicheWizardTest.php`
Expected: all pass

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: pass

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Fiches/FicheWizardTest.php
git commit -m "test: update tests for summary→description rename and aiDescription assertion"
```
