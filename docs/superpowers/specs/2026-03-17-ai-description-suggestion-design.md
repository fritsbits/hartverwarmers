# AI Description Suggestion for Fiche Wizard

## Problem

The fiche wizard generates AI suggestions for preparation, inventory, and process — but not for the description field. The description is the most visible field (shown on cards, search results) and users often write low-quality descriptions ("Quiz", "Zie document") or leave it minimal. The infrastructure for `aiDescription` already exists in the Livewire component but is never populated.

## Approach

Rename the existing `summary` field in `AnalyzeFileContentAgent` to `description` and enhance its prompt with specific criteria and examples. The `summary` field is currently only used as context for `matchInitiatives()` — it's never shown to users. By replacing it with a purpose-built description, we get both a user-facing suggestion and initiative-matching context from a single field.

## Description Criteria

A good description answers three questions in 1-3 sentences:
- **What** is this activity?
- **What makes it engaging** for residents?
- **Who** is it for?

The tone should be warm, direct, and practical — like a supportive colleague describing the activity to a peer.

## Examples

These examples are included in the agent prompt as few-shot guidance:

1. *"Bak samen smoutebollen en breng de gezellige sfeer van de kermis naar het woonzorgcentrum. De geur en smaak roepen herinneringen op en brengen bewoners samen rond een gedeelde beleving."*

2. *"Laat bewoners inschatten hoeveel dagelijkse voorwerpen vandaag kosten en vergelijk met de prijzen van vroeger. Een leuke manier om herinneringen op te halen en gesprekken op gang te brengen over het dagelijks leven van toen."*

3. *"Tover de tuin om tot een lichtjesparadijs en trek er in de late namiddag samen op uit. Met een jenever, streepje muziek en braadworst aan het vuur wordt het een gezellig wintermoment voor alle bewoners."*

## Changes

### 1. `AnalyzeFileContentAgent` — rename field, improve prompt

- Schema: rename `summary` → `description`
- Update field description: `'Beschrijving van de activiteit (1-3 zinnen). Beantwoord: wat is de activiteit, wat maakt het boeiend, voor wie is het?'`
- Add the three examples above to the agent instructions

### 2. `FicheAiService` — update references

- `analyzeFiles()` return: `$response['summary']` → `$response['description']`
- `matchInitiatives()` parameter: rename `$summary` → `$aiDescription`
- Update PHPDoc `@return` type hint

### 3. `ProcessFicheUploads` job — update data flow

- `$summary = $analysis['summary']` → `$aiDescription = $analysis['description']`
- Pass `$aiDescription` to `matchInitiatives()`
- Include `description` in the cached analysis result (already passed through as part of `$analysis`)

### 4. `FicheWizard::loadAiSuggestions()` — wire it up

- Add: `$this->aiDescription = self::markdownToHtml($analysis['description'] ?? null);`

### 5. `AnalyzeFileCommand` — update all 3 references

- Line 78: `$summary = $analysis['summary']` → `$aiDescription = $analysis['description']`
- Line 79: pass `$aiDescription` to `matchInitiatives()`
- Line 112: debug label `'Summary: '` → `'Description: '`

### 6. Tests — update assertions

- Update any tests asserting on the `summary` key to use `description`

## What doesn't change

- Blade template: already handles `aiDescription` via `getContentFields()` — the suggestion panel renders automatically
- No new Livewire properties (already declared)
- No new API calls
- No new components or views

## Files touched

| File | Change |
|------|--------|
| `app/Ai/Agents/AnalyzeFileContentAgent.php` | Rename field, add examples to instructions |
| `app/Services/FicheAiService.php` | `summary` → `description` in return + param |
| `app/Jobs/ProcessFicheUploads.php` | Update variable name |
| `app/Livewire/FicheWizard.php` | Add `aiDescription` in `loadAiSuggestions()` |
| `app/Console/Commands/AnalyzeFileCommand.php` | Update debug output |
| Tests referencing `summary` | Update key names |
