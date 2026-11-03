# Fiche-bestanden naar Hetzner: uitrol fase 2 tot en met 6

Handoff voor de thread die Frederik door de productiefasen begeleidt.
Datum: 2026-11-03.

Lees eerst:

- `docs/plans/2026-11-03-fiche-bestanden-object-storage.md`, het plan.
- `docs/fiche-storage-forge.md`, de bediening via Forge per fase.
- `config/fiche-storage.php` en `app/Services/FicheStorage/`.

## Stand

- Fase 1 is gebouwd en lokaal getest in commit `33b3010` op `fritsbits/bering-strait`, nog niet gepusht.
  De volledige suite slaagt (2109 tests).
- Op productie is nog niets gebeurd.
  Er is geen deploy geweest, de Forge-environment is niet ingevuld en versioning staat nog niet aan.
- Hetzner is klaar: project `Hartverwarmers`, `fsn1`, en een key pair `hartverwarmers-forge` in Frederiks wachtwoordmanager.
  `hartverwarmers-files` is privé, `hartverwarmers-previews` is publiek, Object Lock staat op beide uit.
- De echte buckettest (`files:storage-prepare` met de job `RunStorageSmokeTest`) is nog nooit tegen Hetzner gelopen.
  Lokaal waren de buckets alleen gefaket.

## Stappen

1. Fase 1 afvinken in het plandoc, daarna pushen.
2. Fase 2:
   - [ ] In het Forge-deployscript na `config:cache` en vóór `$ACTIVATE_RELEASE()` deze regel toevoegen: `$FORGE_PHP artisan files:storage-check`.
   - [ ] Worker op timeout 300 en `stopwaitsecs` 420 zetten.
     Controleer hoe die velden in Forge heten; dat is onzeker.
   - [ ] Een eventuele `DB_QUEUE_RETRY_AFTER` op 420 zetten of weghalen.
   - [ ] Deployen met `FICHE_STORAGE_MODE=local`.
   - [ ] Op `/admin/gezondheid` controleren dat de kaart "Fiche-opslag" modus `local` en de workers met hun release toont.
3. Fase 3:
   - [ ] Versioning aanzetten via de AWS CLI op Frederiks Mac, met `AWS_REQUEST_CHECKSUM_CALCULATION=when_required` en `AWS_RESPONSE_CHECKSUM_VALIDATION=when_required`.
     Bevestigen met `get-bucket-versioning`.
   - [ ] De `FICHE_STORAGE_*`-variabelen in Forge invullen en deployen.
   - [ ] `files:storage-prepare` starten via Forge Commands en wachten tot de buckettest `passed` toont.
   - Als hij faalt, samen de details op het gezondheidsscherm lezen.
     Let vooral op de URL-vorm (virtual-hosted), de ACL op publieke previews, multipart-uploads en checksums.
4. Fase 4:
   - [ ] Modus `mirror` en deployen.
   - [ ] `files:copy-to-object-storage --queued` starten en wachten op `verified`.
5. Fase 5:
   - [ ] Modus `s3-fallback` en deployen.
   - [ ] Op productie testen: upload, verwerking, download, zip, kaartpreview en het wissen van eigen testmateriaal.
   - [ ] De nginx-blokkade uit de handleiding activeren.
6. Fase 6, ten vroegste 14 dagen na de omschakeling:
   - [ ] Opnieuw kopiëren en verifiëren, dan modus `s3`.
   - [ ] Nog eens verifiëren, dan `files:cleanup-local --dry-run`.
   - [ ] Daarna `files:cleanup-local --execute --run=<id>`.

## Werkwijze

- Leid Frederik per stap: wat hij in Forge invult of klikt, en wat hij daarna op `/admin/gezondheid` moet zien.
- Een stap is pas klaar als het scherm het bevestigt.
- Elke wisseling van modus is een Forge-environmentwijziging plus een deploy.
  De deploycheck weigert een wissel zonder bewijs; de redenen staan in de deploylog.
- Geen destructieve DB-commando's, geen push zonder akkoord, geen cleanup zonder dry-run.
- Code die faalt op productie: eerst reproduceren, dan pas fixen, met een test en één commit per fix.
