# Fiche-bestanden naar Hetzner Object Storage: bediening via Forge

Dit document hoort bij het plan in `docs/plans/2026-11-03-fiche-bestanden-object-storage.md`.
Het beschrijft wat je in Forge instelt en in welke volgorde je de fasen doorloopt.
Alle opdrachten start je via Forge, Site, Commands.
Ze zijn kort of plannen werk in op de bestaande queue; je hoeft geen terminal open te houden.
De voortgang volg je op `/admin/gezondheid`, in de kaart "Fiche-opslag".

## Eenmalig in Forge

### Deployscript

Voeg deze regel toe na `config:cache` (of `optimize`) en vóór `$ACTIVATE_RELEASE()`:

```bash
$FORGE_PHP artisan files:storage-check
```

Zonder `--target` controleert het commando de modus die in `FICHE_STORAGE_MODE` staat.
Blijft de modus gelijk, dan controleert het alleen of de configuratie volledig is.
Verandert de modus, dan eist het de bewijzen voor die stap; anders faalt de deploy en blijft de vorige release actief.
Laat de queue-restart van Forge na `$ACTIVATE_RELEASE()` staan.

### Queue-workers

Pas de worker één keer aan in Forge, Site, Queue:

| Instelling | Waarde |
|---|---|
| Maximum seconds per job (`--timeout`) | 300 |
| Graceful shutdown (supervisor `stopwaitsecs`) | 420 |

`retry_after` staat in de code nu standaard op 420 voor de database- en Redis-queue.
Staat `DB_QUEUE_RETRY_AFTER` of `REDIS_QUEUE_RETRY_AFTER` in Forge Environment, zet die dan ook op 420 of verwijder de regel.
`files:storage-prepare` controleert deze waarde.

### Scheduler

De scheduler van Forge moet draaien: `files:storage-supervise` loopt elke minuut.
Die noteert de actieve modus, ziet wanneer oude processen zijn uitgelopen, hervat vastgelopen runs en ruimt verlaten werkmappen op.

### Environment

Vul in Forge, Site, Environment deze regels in.
Key en secret haal je uit de wachtwoordmanager (key pair `hartverwarmers-forge`).

```dotenv
FICHE_STORAGE_MODE=local
FICHE_STORAGE_ACCESS_KEY_ID=
FICHE_STORAGE_SECRET_ACCESS_KEY=
FICHE_STORAGE_ENDPOINT=https://fsn1.your-objectstorage.com
FICHE_STORAGE_REGION=fsn1
FICHE_STORAGE_FILES_BUCKET=hartverwarmers-files
FICHE_STORAGE_PREVIEWS_BUCKET=hartverwarmers-previews
```

Na een wijziging deploy je opnieuw, zodat de gecachte configuratie meegaat.
Daarna verander je alleen nog `FICHE_STORAGE_MODE`, telkens gevolgd door een deploy.

## Fasen

### Fase 2: eerste deploy, nog lokaal

1. Deploy met `FICHE_STORAGE_MODE=local`.
2. Controleer op `/admin/gezondheid` dat de kaart "Fiche-opslag" modus `local` toont en dat de workers met de nieuwe release verschijnen.

### Fase 3: buckets testen

1. Zet versioning aan op `hartverwarmers-files` (AWS CLI op je Mac) en controleer `Status=Enabled` met `get-bucket-versioning`.
2. Vul de environment in en deploy.
3. Start `php artisan files:storage-prepare` via Commands.
   Het commando toont een tabel met controles en plant de buckettest in.
   Een nieuwe bucket kan tot vijftien minuten nodig hebben; bij een fout probeer je het later opnieuw.
4. Wacht tot "Buckettest" op het gezondheidsscherm `passed` toont.
   De test uploadt een bestand zo groot als de grootste upload, controleert privé en publiek gedrag, een presigned download met Unicode-naam, Range, het verval van een link en het bewaren van een vorige versie.
   Daarna verwijdert hij alle testversies.

### Fase 4: dubbel schrijven en kopiëren

1. Zet `FICHE_STORAGE_MODE=mirror` en deploy.
2. Start `php artisan files:copy-to-object-storage --queued`.
   Het commando geeft meteen een run-id terug; nog eens starten hervat dezelfde run.
3. Volg "Kopie naar de bucket" op het gezondheidsscherm.
   Status `waiting` betekent dat oude processen nog niet zijn uitgelopen; de run gaat daarna vanzelf verder met een nieuwe inventarisronde.
4. Klaar is pas `verified`.
   Bij `blocked` toont het scherm welke paden ontbreken of afwijken.
   Ontbrekende previews worden automatisch opnieuw gemaakt; ontbrekende originelen moet je zelf uitzoeken.

### Fase 5: s3 met terugval

1. Zet `FICHE_STORAGE_MODE=s3-fallback` en deploy.
   De deploycheck weigert zonder geslaagde run, met onbevestigde objecten of met een onbereikbare bucket.
2. Test op productie een nieuwe upload, de verwerking, een download van één bestand, een zip, een kaartpreview en het definitief wissen van eigen testmateriaal.
3. Blokkeer de oude publieke paden in Forge, Site, Nginx.
   Zet deze regels in het `server`-blok, vóór `location /`:

   ```nginx
   location ^~ /storage/files/ {
       return 404;
   }

   location ^~ /storage/fiche-zips/ {
       return 404;
   }
   ```

   Forge test de configuratie bij het opslaan en laadt nginx alleen opnieuw als die geldig is.
   Previews en avatars onder `/storage/` blijven bereikbaar; downloads lopen via de controller.
4. "Lokaal gelezen na omschakeling" hoort op 0 te blijven.
   Een hoger getal betekent dat een object in de bucket ontbrak; zoek uit waarom voor je verdergaat.

### Fase 6: alleen nog s3, dan lokaal opruimen

1. Wacht minstens veertien dagen na de eerste omschakeling; het gezondheidsscherm toont de vroegste opruimdatum.
2. Start `php artisan files:copy-to-object-storage --queued` opnieuw en wacht op `verified`.
3. Zet `FICHE_STORAGE_MODE=s3` en deploy.
4. Start opnieuw `php artisan files:copy-to-object-storage --queued` en wacht op `verified`.
   Opruimen vraagt een verificatie in modus `s3`.
5. Start `php artisan files:cleanup-local --dry-run`.
   Het commando toont een plan-id, het aantal bestanden, de ruimte die vrijkomt en de onbekende bestanden die blijven staan.
6. Start `php artisan files:cleanup-local --execute --run=<plan-id>`.
   Elk bestand wordt vlak voor het verwijderen opnieuw tegen de bucket gecontroleerd.
   Avatars, Livewire-uploads, het migratieregister en onbekende bestanden blijven staan.

## Terug naar lokaal

- Vanuit `mirror`, zolang nooit in `s3-fallback` of `s3` gewerkt is: zet de modus op `local` en deploy.
- Na `s3-fallback` of `s3`: ga eerst terug naar `mirror` en deploy.
  Start `php artisan files:copy-to-local --queued` en wacht tot "Herstel naar lokaal" `verified` toont.
  Daarna laat de deploycheck `local` toe.
- Laat de nginx-blokkade op `/storage/files/` staan; downloads hebben die paden niet nodig.

## Waar de stand staat

Het migratieregister is een SQLite-bestand in `storage/app/private/fiche-storage-migration/`, in de gedeelde storage van Forge.
Het bevat checksums, voortgang, verwijderingen en de modusgeschiedenis, maar geen secrets of presigned URL's.
`php artisan files:storage-status` toont hetzelfde rapport als het gezondheidsscherm.
