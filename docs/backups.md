# Back-ups en herstel van Hartverwarmers

Status: code voorbereid, productie-inrichting gestart, nog niet operationeel bewezen. De niet-geheime productie-instellingen zijn voorbereid met back-ups uitgeschakeld. Er is nog geen echte back-up of hersteltest uitgevoerd. De [Notion-taak](https://app.notion.com/p/420d3ecc475c818c9bf5f82091ce4c81) blijft Bezig tot een echte back-up en volledige hersteltest zijn vastgelegd. Zie ook [Back-ups, herstel en objectopslag](https://app.notion.com/p/420d3ecc475c81489dadd5de3a8844bd).

## Productievoorbereiding op 22 november 2026

Na de codereview zijn de volgende controles rechtstreeks op productie uitgevoerd. Er is nog geen back-up gemaakt, hersteltest uitgevoerd of nieuwe code gedeployd; de actieve release is `2849e89`.

| Controle | Uitkomst |
| --- | --- |
| PHP en ZIP | PHP 8.4.28; AES-256 beschikbaar |
| Databaseclient | MySQL `mysqldump` 8.4.13 in `/usr/bin` |
| Database | 35 InnoDB-tabellen, circa 40,8 MB inclusief indexen; geen routines of events |
| Werkruimte | Circa 11 GB vrij; gedeelde storage circa 1,56 GiB; werkelijke dump-/ZIP-grootte nog niet gemeten |
| Scheduler | Cron actief; `/etc/crontab` start iedere minuut `php8.4 …/current/artisan schedule:run` als `forge` |
| Bestemming | Bucketpolicy toegepast en teruggelezen voor `p16891953:MZWRIZDMKUGTM853RZZZ`; versioning niet ingeschakeld |
| S3-bestanden | Live inventaris: 1.092 objecten, 4.721.069.488 bytes; grootste object 100.391.280 bytes |
| S3-previews | Live inventaris: 9.545 objecten, 804.775.650 bytes; grootste object 807.622 bytes |

De gedeelde productie-`.env` bevat nu de niet-geheime voorbereiding: `BACKUPS_ENABLED=false`, endpoint `https://nbg1.your-objectstorage.com`, regio `nbg1`, bucket `hartverwarmers-prod-backups`, dumppad `/usr/bin`, `BACKUP_MIN_FREE_BYTES=4294967296` (4 GiB) en de door Frederik opgegeven publieke access-key-ID. Andere instellingen zijn behouden. De back-upcode staat nog op de voorbereidingsbranch; configuratiecache en scheduler zijn niet geactiveerd voor back-ups.

Het lege Hetzner-project [Hartverwarmers backup credentials](https://console.hetzner.com/projects/16891953/security) is aangemaakt voor de aparte back-upsleutel. Maak in dit project geen buckets of servers aan. De bestaande bucket blijft in project `16644050`.

De [bucketpolicy-template](backups-policy.template.json) is toegepast met principal `p16891953:MZWRIZDMKUGTM853RZZZ`, nadat Frederik deze publieke access-key-ID heeft opgegeven. De volledige policy is teruggelezen en inhoudelijk gelijk bevonden aan de ingevulde template. Het bucketproject blijft `16644050`; de principal gebruikt het sleutelproject `16891953`. De template bevat geen secrets. Controleer bij toekomstige wijzigingen de actuele bucketpolicy opnieuw en behoud bestaande statements. Deze policy beperkt andere sleutels uit het bucketproject niet.

De rechtenproeven met de nieuwe sleutel zijn geslaagd: bucketmetadata/lijst lezen, private archiefupload/lezen/verwijderen, private objectupload/lezen, conditioneel overschrijven geweigerd (412), verwijderen van objectkopie geweigerd (403), en lijst-/schrijf-/verwijdertoegang tot beide bronbuckets geweigerd (403). Er zijn uitsluitend unieke niet-gevoelige testobjecten gebruikt; de overgebleven objectfixture is daarna met de beheersleutel verwijderd. De back-upsleutel is dus geen beheer- of opruimsleutel voor het objectprefix. Anonieme toegang tot de fixture gaf 403. De bucketproject-sleutel kon de private fixture van het sleutelproject niet lezen of zijn ACL opvragen; bevestig daarom afzonderlijk de bedoelde herstelcredentials voordat je een herstelprocedure daarop baseert.

De publieke `BACKUP_S3_ACCESS_KEY_ID` is ingesteld. Frederik heeft inmiddels ook `BACKUP_S3_SECRET_ACCESS_KEY`, `BACKUP_ARCHIVE_PASSWORD` en `BACKUP_NOTIFICATION_EMAIL` in de [Forge-site](https://forge.laravel.com/impact-studio/hartverwarmers-jubilant-dust/3060314) opgeslagen. Aanwezigheid, overeenkomst van de publieke ID, wachtwoordlengte van minstens 32 tekens en geldig e-mailformaat zijn gecontroleerd zonder geheime waarden te tonen. Bewaar het archiefwachtwoord ook in de externe kluis; dat is niet technisch gecontroleerd. Deel nooit de secret of het wachtwoord. Laat `BACKUPS_ENABLED=false` staan.

Daarna: rechten testen, code naar de productiebranch brengen en deployen, inventaris uitvoeren, eerste back-up en geïsoleerd herstel bewijzen. Activeer de dagelijkse planning pas na die controles.

## Dekking en bewaring

`backups:daily` maakt eerst een Spatie-archief van de Laravel-app en de MySQL-verbinding `mysql`. Het archief bevat code, Composer/npm-lockfiles, gebouwde publieke assets, `.env` en gedeelde storage. De expliciete `.env`- en storagepaden dekken Forge-symlinks. Runtimecache, logs, vendor, node_modules, Composer-authenticatie, tijdelijke werkmappen en het operationele SQLite-migratieregister worden overgeslagen. Dependencies worden bij herstel met de lockfiles geïnstalleerd. Het archief is geen serverimage: Nginx, PHP-extensies, Forge-instellingen, cron en externe dienstconfiguratie moet je opnieuw inrichten.

Spatie versleutelt ieder bestand in de ZIP met AES-256. `backup:run` wordt vóór de dump gestopt zonder een apart wachtwoord van minstens 32 tekens, AES-256-ondersteuning, leesbare `.env`, meldadres, mysqldump of vrije werkruimte. Ook geneste commando-aanroepen gebruiken deze controle. Vóór upload controleert de app de encryptiemethode van elke ZIP-entry. ZIP-bestandsnamen blijven zichtbaar; inhoud is versleuteld. Spatie's ZIP-controle controleert aantallen, geen volledig herstel.

Bewaar het willekeurig gegenereerde archiefwachtwoord ook in een externe wachtwoordkluis, los van Forge, server en bucket. Zonder dat wachtwoord kun je het versleutelde `.env`-bestand niet openen, ook al bevat dat bestand zelf een kopie van het wachtwoord. Bewaar oude wachtwoorden zolang bijbehorende archieven bestaan. Gebruik nooit `APP_KEY` of de S3-secret als archiefwachtwoord. Zet geheimen rechtstreeks in Forge en de kluis, nooit in Git, Notion, chat, terminalargumenten of logboeken. Beveilig ook Forge's gedeelde `.env` en gecachete configuratie.

Archieven staan onder `hartverwarmers-app/`. Spatie bewaart alle archieven 7 dagen, daarna één per dag gedurende 23 dagen, daarna één per week gedurende 8 weken en daarna één per maand gedurende 12 maanden. Deze perioden volgen op elkaar. De nieuwste back-up blijft behouden. Er is geen bytequota dat de bewaartermijn stilzwijgend inkort. De monitor waarschuwt boven 50 GiB aan app-archieven of bij een nieuwste archief ouder dan 36 uur.

Daarna kopieert `backups:copy-objects` alle huidige objecten uit `fiche-files-s3` en `fiche-previews-s3`, onafhankelijk van `FICHE_STORAGE_MODE`. De broncredentials voeren uitsluitend `ListObjectsV2` en `GetObject` uit. De aparte bestemmingscredentials schrijven private objecten naar `s3-objects/data/`. Objectnaam, ETag, grootte en wijzigingstijd bepalen een versiepad. Ongewijzigde versies worden hergebruikt. Gewijzigde versies krijgen een nieuw pad; bronverwijderingen verwijderen geen back-up. Conditionele uploads (`If-None-Match: *`) voorkomen overschrijven. Een afgebroken run kan nieuwe data achterlaten; die wordt bij de volgende run hergebruikt.

Een JSON-manifest onder `s3-objects/manifests/` verschijnt pas nadat beide broninventarissen volledig zijn gekopieerd en alle bestemmingsgroottes en SHA-256-metadata zijn gecontroleerd. Het manifest koppelt oorspronkelijke objectnamen aan versiepaden, controlesommen, groottes en contenttypes. De upload krijgt ook een Content-MD5 voor overdrachtscontrole. De monitor controleert het laatste volledige manifest, met een grens van 36 uur vanaf de start van de inventaris. Hij downloadt niet ieder uur alle objectinhoud; controle van de werkelijke bytes hoort bij de hersteltest.

Objectdata en manifesten hebben voorlopig geen automatische verwijdering. Zo blijven eerdere versies herstelbaar en raakt Spatie-opruiming deze gegevens niet. Dit vraagt later een afzonderlijk bewaarbeleid en capaciteitsbewaking op de bucket, met aandacht voor persoonsgegevens. Plaats geen algemene bucket-lifecycle die deze prefix opruimt. De S3-kopie is via HTTPS beveiligd en private opgeslagen, maar heeft geen extra versleuteling aan de clientkant. Geheimen uit `.env` en de database staan uitsluitend in de versleutelde ZIP.

Beperkingen: dit kopieert huidige objecten, geen oude bronversies, delete markers, ACL's, tags of bucketpolicy. Versies van vóór de eerste kopie worden niet achteraf veiliggesteld. De inventaris is geen atomische momentopname met de MySQL-dump; een bestand dat tussen dump en inventaris wordt gewist kan ontbreken. Bij een gecontroleerde hersteltest pauzeer je schrijvers. Controleer bij werkelijk herstel alle databaseverwijzingen, ook tegen eerdere manifesten. ETag-wijzigingen tijdens een download stoppen de run. Eén object mag maximaal 5 GiB zijn; grotere objecten vereisen eerst multipartondersteuning. Er wordt per nieuw object tijdelijk één bestand op de server opgeslagen. Een harde processtop kan dat bestand laten liggen: verwijder uitsluitend herkenbare `object-*`-werkbestanden nadat je hebt vastgesteld dat geen back-up actief is.

## Later in Forge instellen

De niet-geheime instellingen hieronder zijn voorbereid; de secret, het archiefwachtwoord en het meldadres moeten nog worden ingesteld. Productiebranch is `fritsbits/bering-strait`; deze voorbereiding staat op een aparte branch. De code is beoordeeld; de deploy volgt na de resterende productie-inrichting.

| Variabele | Waarde of betekenis |
| --- | --- |
| `BACKUPS_ENABLED` | Eerst `false`; pas na controles en meldconfiguratie `true` |
| `BACKUP_S3_ACCESS_KEY_ID` | Nieuwe sleutel die alleen de bestemmingsbucket mag gebruiken |
| `BACKUP_S3_SECRET_ACCESS_KEY` | Frederik bewaart de secret rechtstreeks in Forge |
| `BACKUP_S3_ENDPOINT` | `https://nbg1.your-objectstorage.com` |
| `BACKUP_S3_REGION` | `nbg1` |
| `BACKUP_S3_BUCKET` | `hartverwarmers-prod-backups` |
| `BACKUP_ARCHIVE_PASSWORD` | Apart willekeurig wachtwoord van minstens 32 tekens, ook extern bewaard |
| `BACKUP_NOTIFICATION_EMAIL` | Werkelijk bereikbaar beheermeldadres, geen voorbeeldadres |
| `BACKUP_MYSQLDUMP_PATH` | Directory met MySQL 8.4-client, gewoonlijk `/usr/bin`; geen bestandsnaam |
| `BACKUP_MIN_FREE_BYTES` | Ondergrens voor vrije werkruimte, standaard `2147483648`; verhogen na meting |

Behoud de bestaande `DB_*`- en `FICHE_STORAGE_*`-variabelen. Bronnen blijven `hartverwarmers-files` en `hartverwarmers-previews` op `fsn1`. Bestemmingscredentials mogen nooit als `FICHE_STORAGE_*` worden ingesteld. Mail gebruikt de bestaande `MAIL_*`/Resend-configuratie, synchronisch buiten de queue. Als `TELEGRAM_BOT_TOKEN` en `TELEGRAM_CHAT_ID` al ingesteld zijn, gaan waarschuwingen ook naar Telegram. Er gaan uitsluitend vaste foutmeldingen naar deze kanalen, geen ruwe exceptions met exports of credentials. Veilige foutcodes en lokale controles staan in de serverlogs.

De planning draait dagelijks om 01:00 UTC; monitoring elk uur om minuut 30. De gewone Forge-scheduler moet iedere minuut `schedule:run` uitvoeren. Beide taken worden alleen actief met `BACKUPS_ENABLED=true`. Gebruik één schedulerhost en een gedeelde cache voor de sloten. Archiveer- en objectcommando's hebben ieder een eigen slot van 24 uur. Stop een vastgelopen proces voordat je specifiek dat slot vrijgeeft; wis nooit alle applicatiecache om een back-upslot te omzeilen. Laat geen run langer dan 24 uur doorlopen.

## Sleutel en bucketpolicy

[Hetzner geeft een nieuwe sleutel standaard toegang tot iedere bestaande en toekomstige bucket in hetzelfde project](https://docs.hetzner.com/storage/object-storage/faq/s3-credentials/). Een Allow op alleen de bestemming trekt die rechten niet in. Een policy op alleen `hartverwarmers-prod-backups` kan de sleutel dus niet automatisch van de bronbuckets uitsluiten.

Dit rechtenmodel gaat uit van een back-upsleutel in een afzonderlijk project zonder buckets. Verleen die sleutel via de policy van de bestaande bestemmingsbucket uitsluitend de onderstaande rechten. Hetzner ondersteunt hiervoor een principal in de vorm `arn:aws:iam:::user/p<credential-project-id>:<access-key-id>`. Dit vraagt geen verplaatsing van de bestaande bucket. Houd een aparte beheersleutel in het bucketproject beschikbaar om policies te beheren.

Een sleutel uit het bestaande bucketproject vraagt eerst een afzonderlijk beoordeeld Deny-beleid. Je moet dan zowel toegang tot alle andere buckets blokkeren als de geërfde volledige rechten op de bestemming intrekken, waaronder verwijderen van objectkopieën en policybeheer. Een Allow-lijst alleen doet dat niet. Dit alternatief is hier niet uitgewerkt. Hetzner ondersteunt `NotAction` niet.

| Resource | Benodigde rechten voor de back-upsleutel |
| --- | --- |
| `arn:aws:s3:::hartverwarmers-prod-backups` | `s3:ListBucket`, `s3:GetBucketLocation`, `s3:GetBucketVersioning` |
| `arn:aws:s3:::hartverwarmers-prod-backups/hartverwarmers-app/*` | `s3:GetObject`, `s3:PutObject`, `s3:DeleteObject` voor archiefbewaring |
| `arn:aws:s3:::hartverwarmers-prod-backups/s3-objects/*` | `s3:GetObject`, `s3:PutObject`; geen verwijderrecht |
| Beide bovenstaande objectprefixen | `s3:PutObjectAcl` uitsluitend met conditie `s3:x-amz-acl=private` |

De uploads zetten expliciet de private ACL. [S3 vraagt daarvoor ook PutObjectAcl](https://docs.aws.amazon.com/AmazonS3/latest/userguide/using-with-s3-policy-actions.html). Beperk de afzonderlijke Allow voor `PutObjectAcl` met `StringEquals: {"s3:x-amz-acl": "private"}` en bevestig deze conditie bij Hetzner. Geef geen bucketpolicy-/bucket-ACL-beheer, `DeleteObjectVersion` of toegang tot bronbuckets aan de back-upsleutel. Voor een archive-upload die multipart gebruikt zijn ook `s3:AbortMultipartUpload` en `s3:ListMultipartUploadParts` op het archiefprefix nodig. Beperk andere bestaande projectsleutels ook op de back-upbucket; zonder zulke policies kunnen ze standaard nog aan back-ups.

Schakel op deze bestemming geen versioning of Object Lock in: [Hetzner ondersteunt geen conditionele PUT/DELETE op versioned buckets](https://docs.hetzner.com/storage/object-storage/supported-actions/). De objectkopie controleert dit vóór elke inventaris en stopt bij status Enabled of Suspended. Het herstelvoorbeeld hieronder vereist om dezelfde reden nieuwe, niet-versioned testbuckets. Bescherming tegen een gecompromitteerde server vraagt een afzonderlijk ontwerp, bijvoorbeeld een onafhankelijke kopie met eigen credentials. Deze code biedt zelf geen onveranderlijke opslaggarantie.

Verifieer later met de nieuwe sleutel: bestemming list/read/write en verwijderen van een tijdelijk archiefobject werken; verwijderen van een object onder `s3-objects/` wordt geweigerd; list/read/write/delete van de bronbuckets en andere buckets worden geweigerd. Gebruik voor schrijf-/verwijderproeven uitsluitend unieke testobjecten, nooit bestaande bronobjecten. Leg geen credentialwaarden vast in de resultaten. Een publiek previewobject blijft eventueel anoniem leesbaar; dat is los van de rechten van de sleutel.

## Eerste ingebruikname en verificatie

Nog uit te voeren, met Frederik's toestemming voor productiehandelingen:

1. Controleer PHP 8.4.1 of hoger, ZIP/libzip met AES-256, MySQL 8.4 `mysqldump --version`, InnoDB-tabellen en exportrechten. De dump gebruikt een single transaction, `--quick`, geen table locks/tablespaces en GTID-uitvoer uit. Pauzeer schemawijzigingen tijdens een dump. Controleer of routines/events bestaan; die worden niet standaard geëxporteerd en vragen dan extra dumpopties en herstelcontrole.
2. Meet database, app/storage en het grootste object. Houd voldoende ruimte vrij voor ongecomprimeerde SQL plus ZIP plus marge. De ingestelde ondergrens van 2 GiB is geen schatting van het werkelijke archief. Werkmap is `storage/app/backup-temp`, rechten 0700, tijdelijke SQL/bestanden via umask 0077. Controleer eigenaar, gedeelde Forge-storage en uitgesloten webtoegang.
3. Configureer sleutel/policy, variabelen en echt meldtransport. Vernieuw gecachete configuratie met Forge's gebruikelijke deploystappen. Controleer dat de CLI naar de actieve release en gedeelde storage wijst. Zet geen configuratie-uitvoer met geheimen in logs of Notion.
4. Inventariseer zonder S3-writes met `php artisan backups:copy-objects --dry-run`. Vergelijk aantallen en bytes met beide bronbuckets. Controleer geen objecten groter dan 5 GiB. De dry-run gebruikt wel een cacheslot en valideert de overdracht of encryptie niet.
5. Voer later afzonderlijk `php artisan backup:run --isolated=1` en `php artisan backups:copy-objects` uit. Controleer upload-/downloadprecondities op Hetzner, private ACL, archief, volledige manifesten en checksums. Bij een mislukte preconditie wordt niet teruggevallen op onvoorwaardelijk overschrijven. De eerste objectkopie kan lang duren door circa 10.640 objecten en 5,2 GB data; hergebruik vraagt nog een HEAD per object.
6. Voer de herstelprocedure hieronder volledig uit. Controleer `.env`, oorspronkelijke `APP_KEY`, MySQL-aantallen, foreign keys, steekproeven en alle bestandsverwijzingen. Controleer een tweede incrementele run, gewijzigde en gewiste wegwerpobjecten, onderbroken kopie, foutmelding en ouderdomsmelding. Probeer bewaarbeleid op wegwerparchieven in een aparte testbestemming. Draai PHPUnit pas wanneer Frederik dat expliciet vraagt; in deze thread zijn geen tests uitgevoerd.
7. Zet na deze controles `BACKUPS_ENABLED=true`, controleer `php artisan schedule:list` en daarna een werkelijk geplande `backups:daily`. Controleer `php artisan backups:monitor`, beide meldkanalen en bezorging bij een defecte bestemming. Bewaak de scheduler ook buiten deze server: een gestopte cron kan zichzelf geen alarm sturen. Noteer datum, archiefpad, manifestpad, aantallen en hersteluitkomst zonder geheimen. Markeer de Notion-taak pas klaar na een echte back-up en geslaagde hersteltest.

## Herstellen

Werk op een geïsoleerde herstelmachine met afgeschermde tijdelijke schijf en een lege testdatabase. Gebruik aparte herstelcredentials en nieuwe private testbuckets. Pauzeer scheduler, queues, webschrijvers en uitgaande mail/webhooks. Gebruik nooit een bestaande lokale of productiedatabase als testdoel. Een daadwerkelijke productieovername vereist later een apart besluit.

1. Download een passend app-archief en het volledige objectmanifest rond hetzelfde tijdstip. Controleer begin-/eindtijden; er is geen garantie dat de twee momentopnamen atomisch zijn. Bewaar de originelen. Maak een directory met rechten 0700 en gebruik `umask 077`. Voer `7z t archive.zip` en `7z x archive.zip -o<lege-herstelmap>` uit en geef het wachtwoord interactief op. Gebruik geen wachtwoordargument. Controleer of `.env`, SQL en gedeelde storage volledig aanwezig zijn. Kopieer nooit absolute archiefpaden rechtstreeks over een bestaande installatie.
2. Herstel de code uit het archief of de bijbehorende Git-revisie. Controleer paden en symlinks vóór je storage terugzet. Bewaar het uitgepakte productie-`.env` afgeschermd als referentie. Maak eerst een actieve herstel-`.env` met de oorspronkelijke `APP_KEY`, geïsoleerde DB-/S3-waarden, `BACKUPS_ENABLED=false`, maildriver `log` en uitgeschakelde Telegram-, Sentry- en andere productie-integraties. Maak geen nieuwe app-key. Maak de uitgesloten runtimepaden opnieuw aan met `mkdir -p bootstrap/cache storage/framework/{cache/data,sessions,views} storage/logs`. Geef alleen de herstelgebruiker de nodige schrijfrechten. Installeer pas daarna dependencies met `composer install --no-dev --prefer-dist --no-interaction`, want Composer-scripts starten Laravel. Herstel gebouwde assets of voer de bestaande npm-build uit.
3. Maak een nieuwe lege MySQL 8.4-testdatabase en importeer de gevonden `db-dumps/mysql-*.sql`. Stel `SQL_PATH` in op dat bestand en gebruik bijvoorbeeld `mysql --login-path=hw-restore --database=hartverwarmers_restore < "$SQL_PATH"`. Het loginpad moet naar het geïsoleerde testdoel wijzen. Controleer die verbinding vooraf. Vergelijk tabel-/rijaantallen en relaties met de export. Voer geen `migrate:fresh`, reset of wipe uit. Controleer eerst de applicatie met de herstelde schema-/codeversie voordat je eventuele nieuwere forward migrations overweegt.
4. Herstel elk object uit het manifest naar de bijbehorende lege testbucket, met de oorspronkelijke `source_key` en `content_type`. Download uit `backup_key`, vergelijk grootte en SHA-256, upload pas daarna. Gebruik nooit het bronbucketveld uit het manifest als schrijfdoel. Onderstaand voorbeeld is voor een Ubuntu-herstelmachine met AWS CLI, `jq` en `sha256sum`. Profielen bevatten rechtstreeks lokaal ingestelde credentials; er staan geen sleutels in het script. Maak beide genoemde testbuckets vooraf en controleer de profielen. De testbuckets blijven private.

```bash
set -euo pipefail
umask 077
RECOVERY_DIR=$(mktemp -d)
# Download het gekozen manifest naar "$RECOVERY_DIR/manifest.json".
# hw-backup: alleen lezen uit nbg1; hw-restore: alleen schrijven naar de testbuckets.
RESTORE_FILES_BUCKET=hartverwarmers-restore-files
RESTORE_PREVIEWS_BUCKET=hartverwarmers-restore-previews

jq -c '.sources[] | .disk as $disk | .objects[] | . + {source_disk: $disk}' \
  "$RECOVERY_DIR/manifest.json" | while IFS= read -r entry; do
  role=$(jq -er '.source_disk' <<< "$entry")
  case "$role" in
    fiche-files-s3) target=$RESTORE_FILES_BUCKET ;;
    fiche-previews-s3) target=$RESTORE_PREVIEWS_BUCKET ;;
    *) exit 1 ;;
  esac
  source_key=$(jq -er '.source_key' <<< "$entry")
  backup_key=$(jq -er '.backup_key' <<< "$entry")
  expected_sha=$(jq -er '.sha256' <<< "$entry")
  expected_size=$(jq -er '.size' <<< "$entry")
  content_type=$(jq -er '.content_type' <<< "$entry")
  aws --profile hw-backup --endpoint-url https://nbg1.your-objectstorage.com \
    s3api get-object --bucket hartverwarmers-prod-backups "--key=$backup_key" \
    "$RECOVERY_DIR/object" >/dev/null
  actual_sha=$(sha256sum "$RECOVERY_DIR/object")
  test "${actual_sha%% *}" = "$expected_sha"
  test "$(wc -c < "$RECOVERY_DIR/object" | tr -d ' ')" = "$expected_size"
  aws --profile hw-restore --endpoint-url https://fsn1.your-objectstorage.com \
    s3api put-object --bucket "$target" "--key=$source_key" \
    --body "$RECOVERY_DIR/object" --content-type "$content_type" \
    --acl private --if-none-match '*' >/dev/null
done
```

5. Controleer het aantal en de bytes van elke herstelde bucket tegen het manifest en download steekproeven terug voor checksumcontrole. Controleer ook databaseverwijzingen die niet in dit manifest voorkomen: zoek ze in eerdere manifesten of bronversies. Laat de herstelapp uitsluitend naar de testbuckets wijzen. Controleer login, afgeschermde originele downloads, ficheweergave, previews en thumbnails. Voor publieke previewweergave is later de juiste preview-policy nodig; die wordt niet uit het manifest hersteld.
6. Leg herstelduur, gebruikte archief-/manifestpaden, rij-/objectaantallen en eventuele ontbrekende bestanden vast. Verwijder pas daarna uitsluitend de geïsoleerde testdata en ontsleutelde werkbestanden volgens het afgesproken bewaarbeleid. Productie, oorspronkelijke buckets en back-ups blijven behouden.

De encryptiekeuze volgt [Spatie v10](https://spatie.be/docs/laravel-backup/v10/advanced-usage/encrypt-backup-archives). Vereisten en dumpconfiguratie staan in de [packagehandleiding](https://spatie.be/docs/laravel-backup/v10/installation-and-setup). Bucketrechten en conditionele S3-verzoeken moeten nog tegen Hetzner worden bevestigd; de [AWS SDK-interface](https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html) alleen bewijst die providercompatibiliteit niet.
