# Fiche-bestanden naar Hetzner Object Storage

Status: plan, nog niet gereviewd.
Datum: 2026-11-03.

## Probleem

De productieserver (DigitalOcean, beheerd via Forge) raakt vol.
Elke fiche-upload blijft op de lokale schijf staan, samen met een omgezette pdf, slide-previews en soms een vooraf gebouwde zip.
Het doel is dat zowel de bestaande als de toekomstige fiche-bestanden bij een Europese objectopslag staan.

## Huidige toestand

Groottes in de lokale kopie (productie is vergelijkbaar of groter):

| map in `storage/app/public` | grootte | inhoud |
|---|---|---|
| `files/` | 5,4 GB | originelen en omgezette pdf's |
| `fiche-zips/` | 1,0 GB | vooraf gebouwde zips |
| `file-previews/` | 0,8 GB | slide-jpg's en thumbnails |
| `avatars/` | 7 MB | profielfoto's |

Vaststellingen in de code:

- Alles gebruikt de schijf `public`.
- Zo'n vijftien klassen roepen `Storage::disk('public')->path()` aan, omdat soffice (`PdfConverter`), `FileTextExtractor`, `GenerateFilePreviewsCommand` en `ZipArchive` een lokaal bestand nodig hebben.
- De database bewaart relatieve paden (`files/<uuid>.pdf`, `file-previews/<id>/slide-001.jpg`, `fiches.zip_path`), dus er is geen schemawijziging nodig.
- Originelen zijn rechtstreeks publiek bereikbaar via `/storage/files/<uuid>`, waardoor de login vóór de download niets afschermt.
- Alleen het manuele `app:generate-zips` maakt zips; nieuwe fiches krijgen al een zip on-the-fly in `FicheController::download`.
- `league/flysystem-aws-s3-v3` is nog niet geïnstalleerd.
- Achttien testbestanden gebruiken `Storage::fake('public')`.

Betrokken code (niet volledig, grep op `disk('public')` voor de rest):

- `app/Livewire/FicheWizard.php`, `app/Livewire/FicheEdit.php`
- `app/Http/Controllers/FicheController.php` (download)
- `app/Jobs/ProcessFicheUploads.php`, `app/Jobs/ExtractFileText.php`, `app/Jobs/GenerateFilePreview.php`
- `app/Services/PdfConverter.php`, `app/Services/FichePurger.php`
- `app/Models/Fiche.php` (`cardPreviewImages`), `app/Models/File.php`
- `resources/views/components/file-preview-carousel.blade.php`
- Commando's: `GenerateFilePreviewsCommand`, `ExtractFileTextCommand`, `GenerateFicheZips`, `CleanupMedia`, `CleanupOrphanedFilesCommand`, `ImportFiles`, `BackfillFileProcessing`, `PurgeTrashedFichesCommand`

## Keuze van dienst: Hetzner Object Storage

- €6,49/maand excl. btw, met 1 TB opslag en 1 TB egress inbegrepen; API-calls en ingress zijn gratis.
- Met ongeveer 7 GB blijven we ruim binnen het vaste bedrag.
- S3-compatibel, met presigned URLs, public-read per bucket, CORS, versioning en object lock.
- Locatie: Falkenstein (`fsn1`). Neurenberg heeft momenteel throttling met 503-fouten.

Beperkingen waar het ontwerp rekening mee houdt:

- De opslag draait op HDD en is geen CDN.
- Data staat in één datacenter, zonder replicatie.
- Een eigen domein via CNAME is niet mogelijk.
- Nieuwere AWS SDK's sturen standaard CRC32-checksums mee; Hetzner documenteert niet of dat werkt.

## Keuze van pakket: `league/flysystem-aws-s3-v3`

Dit is de route die Laravel zelf documenteert voor de `s3`-driver, ook voor S3-compatibele diensten.
`temporaryUrl()`, `url()` en `Storage::fake()` werken zonder extra code.
De meegeleverde `aws/aws-sdk-php` is groot; we snoeien alles behalve S3 weg via `Aws\Script\Composer\Composer::removeUnusedServices`.

Overwogen en afgewezen:

- `league/flysystem-async-aws-s3`: lichter, maar vraagt een eigen driver en waarschijnlijk eigen code voor presigned URLs.
- De bucket mounten met rclone of s3fs: geen codewijziging, maar breekbaar, en het lek via `/storage/files` blijft open.
- Hetzner Storage Box via SFTP: geen presigned of publieke URLs, dus alle verkeer loopt nog via de server.
- DigitalOcean Spaces of een DO-volume: geen Europees bedrijf, en een volume lost het structurele probleem niet op.
- `spatie/laravel-medialibrary`: vervangt onze eigen `files`-tabel en previewpipeline, een grote migratie op gecureerde data.

## Ontwerp

### Buckets en schijven

- Bucket `hartverwarmers-files`, privé: originelen en omgezette pdf's.
- Bucket `hartverwarmers-previews`, public-read: slide-jpg's en thumbnails.
- Versioning aan op `hartverwarmers-files`, zodat een foute delete terug te draaien is.
- Twee Laravel-schijven, `fiche-files` en `fiche-previews`, met de driver via env te kiezen (`local` of `s3`).
- In de S3-config staan `request_checksum_calculation` en `response_checksum_validation` op `when_required`.
- Avatars blijven op de lokale schijf `public`.
- Livewire-uploads blijven tijdelijk lokaal (`livewire-tmp`).

### Downloads

- De controller blijft de download tellen en loggen.
- Bij één bestand stuurt hij door naar een presigned URL van vijf minuten, met `ResponseContentDisposition` zodat de oorspronkelijke bestandsnaam behouden blijft.
- Bij meerdere bestanden bouwt hij de zip on-the-fly: bestanden ophalen naar een tijdelijke map, zippen, versturen, opruimen.

### Verwerking

- Eén helper, bv. `FicheStorage::withLocalCopy(File $file, Closure $callback)`, haalt het bestand naar een tijdelijke map, roept de callback aan met het lokale pad en ruimt daarna op, ook bij een fout.
- Alle huidige `->path()`-aanroepen op fiche-bestanden gaan via die helper.
- Resultaten (pdf, jpg's) gaan met `put()` naar de juiste schijf.
- Previews en kaartjes gebruiken `Storage::disk('fiche-previews')->url()`.

### Zips

- De vooraf gebouwde zips vervallen, want on-the-fly bestaat al.
- `GenerateFicheZips` en zijn test verdwijnen.
- De kolom `fiches.zip_path` blijft staan (geen schemawijziging op gecureerde tabellen) en wordt niet meer gelezen.

### Gezondheid

- De schijfmeting op `/admin/gezondheid` blijft.
- Er komt een bereikbaarheidscheck voor de buckets (HEAD op een vast sentinel-object), die alleen alarmeert bij een fout.

## Migratie

1. **Deploy 1, gedrag ongewijzigd.** Code met de twee schijven en de helper, beide schijven standaard lokaal op de huidige paden. Tests gebruiken `Storage::fake('fiche-files')` en `Storage::fake('fiche-previews')`.
2. **Hetzner klaarzetten.** Buckets en keys aanmaken in `fsn1`, versioning aanzetten, minstens vijftien minuten wachten (Hetzner past bucketinstellingen vertraagd toe), env in Forge invullen.
3. **Testbucket.** Eén upload, download en presigned URL proberen, om het checksumrisico uit te sluiten.
4. **Kopiëren.** Een commando `files:copy-to-object-storage` kopieert elk pad uit `files.path` en `files.preview_images` (plus thumbnails). Het is idempotent en hervatbaar, vergelijkt de grootte, slaat bestaande objecten over en meldt wat ontbreekt.
5. **Omschakelen.** Env naar `s3`, queue herstarten, het kopieercommando opnieuw draaien voor uploads die tussendoor binnenkwamen.
6. **Opruimen na twee weken.** Een commando met `--dry-run` verwijdert de lokale `files/`, `fiche-zips/` en `file-previews/`. Dat geeft ongeveer 7 GB terug.

## Buiten scope

- Backups. Er is nu nergens een backup. `spatie/laravel-backup` naar een tweede bucket in Helsinki is de aanbevolen vervolgstap, als apart werk.
- Een CDN voor previews. Pas nodig als thumbnails traag aanvoelen; Bunny CDN is dan de eerste kandidaat.

## Open vragen voor de review

- Is één helper rond tijdelijke kopieën genoeg, of heeft de previewgenerator (veel jpg's per bestand) een eigen aanpak nodig?
- Moeten de previews echt naar de bucket, of is lokaal houden (0,8 GB, snel) het betere compromis?
- Hoe gaan we om met omgezette pdf's: blijven die bewaard, of maken we ze opnieuw aan als dat nodig is?
- Is vijf minuten geldigheid voor de presigned URL goed bij trage verbindingen in woonzorgcentra?

## Bronnen

- [Hetzner Object Storage](https://www.hetzner.com/storage/object-storage/)
- [Hetzner docs: overview](https://docs.hetzner.com/storage/object-storage/overview)
- [Hetzner docs: supported actions](https://docs.hetzner.com/storage/object-storage/supported-actions)
- [Hetzner docs: FAQ](https://docs.hetzner.com/storage/object-storage/faq/general/)
- [AWS SDK for PHP: checksums](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-checksums.html)
