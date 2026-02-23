# Entity Relationship Diagram — 26hartverwarmers

> Gegenereerd op 2026-02-23 op basis van de actuele database schema (`hartverwarmers_local`) en Eloquent-modellen.

## ERD

```mermaid
erDiagram

    %% ============================================
    %% USERS & AUTHENTICATION
    %% ============================================

    users {
        int_unsigned id PK
        varchar name "unique"
        varchar email "unique"
        varchar password
        varchar remember_token "nullable"
        int_unsigned carehome_id "FK -> carehomes"
        int_unsigned role_id "FK -> roles"
        json states "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    roles {
        int_unsigned id PK
        varchar name "indexed"
    }

    sessions {
        varchar id PK
        bigint_unsigned user_id "FK -> users, nullable"
        varchar ip_address "nullable, max 45"
        text user_agent "nullable"
        longtext payload
        int last_activity "indexed"
    }

    password_resets {
        varchar email "indexed"
        varchar token
        timestamp created_at "nullable"
    }

    personal_access_tokens {
        bigint_unsigned id PK
        varchar tokenable_type "polymorphic"
        bigint_unsigned tokenable_id "polymorphic"
        varchar name
        varchar token "unique, 64"
        text abilities "nullable"
        timestamp last_used_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% CAREHOMES & DEPARTMENTS
    %% ============================================

    carehomes {
        int_unsigned id PK
        varchar name
        varchar city
        varchar address
        tinyint_unsigned isGDPRsigned
        tinyint inactive "indexed"
        json feature_flags "nullable"
        varchar slug "unique"
        varchar website "nullable"
        json states "nullable"
        timestamp expires_at "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    departments {
        int_unsigned id PK
        varchar name
        varchar group "nullable"
        int_unsigned carehome_id "FK -> carehomes"
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% PROFILES & RESIDENTS
    %% ============================================

    profiles {
        bigint_unsigned id PK
        varchar type "indexed"
        varchar first_name
        varchar last_name
        char gender "1 char"
        json states "nullable"
        varchar room "nullable"
        varchar nickname "nullable"
        varchar slug "unique"
        date birthday "nullable"
        json guidances "nullable"
        json groups "nullable"
        json public_info "nullable"
        int_unsigned carehome_id "FK -> carehomes"
        int_unsigned user_id "FK -> users"
        bigint_unsigned guidance_id "nullable, indexed"
        int_unsigned department_id "FK -> departments"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    residents {
        int_unsigned id PK
        varchar firstname
        varchar lastname
        varchar name_bycarehome "nullable"
        varchar name_byfamily "nullable"
        varchar gender
        varchar profile_image "nullable"
        tinyint isPaid
        tinyint isExample "indexed"
        json states "nullable"
        varchar room "nullable"
        int_unsigned carehome_id "FK -> carehomes"
        int_unsigned department_id "FK -> departments"
        bigint_unsigned guidance_id "nullable, indexed"
        int_unsigned profile_id "indexed"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    authors {
        int_unsigned id PK
        varchar slug "unique"
        varchar name
        varchar title "nullable"
        text description "nullable"
        varchar image "nullable"
        varchar company "nullable"
        varchar company_link "nullable"
        varchar linkedin "nullable"
        varchar email "nullable"
        tinyint is_coach
        varchar airtable_id "unique"
        int_unsigned user_id "FK -> users"
        int_unsigned carehome_id "FK -> carehomes"
        bigint_unsigned profile_id "FK -> profiles"
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% ACTIVITIES & TEMPLATES
    %% ============================================

    activities {
        int_unsigned id PK
        varchar title
        varchar slug "unique"
        longtext description "nullable, fulltext"
        json dimensions "nullable"
        json guidances "nullable"
        json fiche "nullable"
        json target_audience "nullable"
        tinyint published "default 0"
        tinyint shared "default 0"
        tinyint_unsigned quality_score "nullable"
        text quality_notes "nullable"
        tinyint_unsigned completeness_percentage "nullable"
        int_unsigned carehome_id "FK -> carehomes"
        int_unsigned template_id "FK -> activity_templates"
        int_unsigned origin_id "FK -> activities (self)"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    activity_templates {
        int_unsigned id PK
        varchar slug "unique, 100"
        varchar title
        text summary
        text content
        varchar image "nullable"
        varchar youtube_id "nullable"
        json links "nullable"
        json downloads "nullable"
        json examples "nullable"
        json category "nullable"
        json target_audience "nullable"
        json pictures "nullable"
        json fiche "nullable"
        tinyint dementia_tip
        tinyint published
        varchar airtable_id "unique, 100"
        int_unsigned activity_id "FK -> activities"
        int_unsigned migration_id "FK -> activities"
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% INTERESTS & CATEGORIES
    %% ============================================

    interest_categories {
        int_unsigned id PK
        varchar title
        varchar icon "nullable"
        varchar color "nullable"
    }

    interests {
        int_unsigned id PK
        varchar name "fulltext"
        varchar type "default interest"
        int_unsigned interest_category_id "FK -> interest_categories"
        int_unsigned parent_id "FK -> interests (self)"
        int_unsigned domain_id "nullable"
        varchar image "nullable"
        varchar icon "nullable"
        varchar airtable_id "unique, 100"
    }

    interestexamples {
        int_unsigned id PK
        int interest_id "FK -> interests (no constraint)"
        longtext title
    }

    %% ============================================
    %% THEMES
    %% ============================================

    themes {
        bigint_unsigned id PK
        varchar title
        text description "nullable"
        date start "nullable"
        date end "nullable"
        tinyint is_month "default 0"
        enum date_type "fixed/variable"
        varchar airtable_id "unique, nullable"
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% EVENTS
    %% ============================================

    events {
        bigint_unsigned id PK
        varchar title
        text description "nullable"
        text report "nullable"
        datetime start
        datetime end
        tinyint is_reported
        tinyint all_day
        tinyint is_public
        tinyint is_hidden
        text announcement "nullable"
        int satisfaction "nullable"
        int_unsigned carehome_id "FK -> carehomes"
        int_unsigned activity_id "FK -> activities"
        int_unsigned owner_id "FK -> users"
        int_unsigned reporter_id "FK -> users"
        bigint_unsigned location_id "FK -> event_locations"
        timestamp created_at
        timestamp updated_at
    }

    event_templates {
        bigint_unsigned id PK
        varchar title
        text description "nullable"
        tinyint is_public
        tinyint is_hidden
        text announcement "nullable"
        tinyint week
        tinyint day
        time start_time
        time end_time
        tinyint all_day
        int_unsigned carehome_id "FK -> carehomes"
        int_unsigned activity_id "FK -> activities"
        bigint_unsigned location_id "FK -> event_locations"
        timestamp created_at
        timestamp updated_at
    }

    event_locations {
        bigint_unsigned id PK
        varchar name "indexed"
        text description "nullable"
        int_unsigned carehome_id "FK -> carehomes"
    }

    %% ============================================
    %% STORIES
    %% ============================================

    stories {
        int_unsigned id PK
        text title "nullable, fulltext"
        text description "nullable, fulltext"
        varchar image "nullable"
        varchar youtube_id "nullable"
        varchar year "4 chars"
        varchar category "nullable"
        tinyint isPrivate
        tinyint isExample "indexed"
        int_unsigned resident_id "FK -> residents"
        int_unsigned user_id "FK -> users"
        bigint_unsigned profile_id "FK -> profiles"
        int_unsigned story_question_id "FK -> story_questions"
        timestamp highlighted_at "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    story_questions {
        int_unsigned id PK
        varchar title
        varchar theme "nullable"
        tinyint groupIndex "nullable"
        int_unsigned group_id "indexed"
        varchar label "nullable"
        text description "nullable"
        varchar type "nullable"
        json options "nullable"
        varchar airtable_id "unique, 100"
    }

    %% ============================================
    %% IDEAS
    %% ============================================

    ideas {
        int_unsigned id PK
        text title
        int_unsigned published
        int_unsigned user_id "FK -> users"
        bigint_unsigned theme_id "FK -> themes"
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% MEETUPS
    %% ============================================

    meetups {
        bigint_unsigned id PK
        varchar title
        varchar slug
        text description "nullable"
        timestamp starts_at
        timestamp stops_at
        varchar channel_name "nullable"
        varchar channel_url "nullable"
        varchar subscribeform_id "nullable"
        varchar guest_name "nullable"
        varchar guest_title "nullable"
        varchar guest_website "nullable"
        varchar guest_avatar "nullable"
        text guest_bio "nullable"
        longtext details "nullable"
        varchar youtube_id "nullable"
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% INTERACTIONS (polymorphic)
    %% ============================================

    likes {
        bigint_unsigned id PK
        int_unsigned user_id "FK -> users"
        int_unsigned likeable_id "polymorphic"
        varchar likeable_type "polymorphic"
        varchar type "like/bookmark, indexed"
        bigint_unsigned profile_id "FK -> profiles"
        timestamp created_at
        timestamp updated_at
    }

    comments {
        bigint_unsigned id PK
        varchar commentable_type "polymorphic"
        bigint_unsigned commentable_id "polymorphic"
        bigint_unsigned user_id "FK -> users"
        text comment
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    %% ============================================
    %% TASKS & INVITES
    %% ============================================

    tasks {
        bigint_unsigned id PK
        varchar title
        text description "nullable"
        int_unsigned user_id "FK -> users"
        datetime done_at "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "soft delete"
    }

    invites {
        bigint_unsigned id PK
        varchar email "indexed"
        varchar token "indexed"
        int numsent
        timestamp sent_at "nullable"
        int_unsigned user_id "FK -> users"
        int_unsigned inviter_id "FK -> users"
        int_unsigned resident_id "FK -> residents"
        bigint_unsigned related_profile_id "FK -> profiles"
    }

    %% ============================================
    %% NOTIFICATIONS & MEDIA
    %% ============================================

    notifications {
        char id PK "UUID, 36"
        varchar type
        varchar notifiable_type "polymorphic"
        bigint_unsigned notifiable_id "polymorphic"
        text data
        timestamp read_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    media {
        bigint_unsigned id PK
        varchar model_type "polymorphic"
        bigint_unsigned model_id "polymorphic"
        char uuid "unique, 36"
        varchar collection_name
        varchar name
        varchar file_name
        varchar mime_type
        varchar disk
        varchar conversions_disk
        bigint_unsigned size
        json manipulations
        json custom_properties
        json generated_conversions
        json responsive_images
        int_unsigned order_column
        timestamp created_at
        timestamp updated_at
    }

    maillog {
        int_unsigned id PK
        text recipient_email
        text subject
        text message
        timestamp created_at
        timestamp updated_at
    }

    %% ============================================
    %% PIVOT TABLES
    %% ============================================

    activity_interest {
        int_unsigned activity_id "PK, FK -> activities"
        int_unsigned interest_id "PK, FK -> interests"
    }

    activity_theme {
        int_unsigned activity_id "PK, FK -> activities"
        bigint_unsigned theme_id "PK, FK -> themes"
        tinyint featured
    }

    activity_department {
        int_unsigned activity_id "PK, FK -> activities"
        int_unsigned department_id "PK, FK -> departments"
    }

    activity_profile {
        int_unsigned activity_id "PK, FK -> activities"
        bigint_unsigned profile_id "PK, FK -> profiles"
        text description "nullable"
    }

    activity_author_profile {
        int_unsigned activity_id "PK, FK -> activities"
        bigint_unsigned profile_id "PK, FK -> profiles"
    }

    activity_template_interest {
        int_unsigned activity_id "PK, FK -> activity_templates"
        int_unsigned interest_id "PK, FK -> interests"
    }

    activity_template_theme {
        int_unsigned activity_template_id "PK, FK -> activity_templates"
        bigint_unsigned theme_id "PK, FK -> themes"
        tinyint featured
    }

    activity_template_author {
        int_unsigned activity_template_id "PK, FK -> activity_templates"
        int_unsigned author_id "PK, FK -> authors"
    }

    interest_profile {
        int_unsigned interest_id "PK, FK -> interests"
        bigint_unsigned profile_id "PK, FK -> profiles"
    }

    interest_resident {
        int_unsigned interest_id "PK, FK -> interests"
        int_unsigned resident_id "PK, FK -> residents"
    }

    interest_story {
        int_unsigned story_id "PK, FK -> stories"
        int_unsigned interest_id "PK, FK -> interests"
    }

    interest_story_question {
        int_unsigned story_question_id "PK, FK -> story_questions"
        int_unsigned interest_id "PK, FK -> interests"
    }

    interest_theme {
        int_unsigned interest_id "PK, FK -> interests"
        bigint_unsigned theme_id "PK, FK -> themes"
    }

    event_department {
        bigint_unsigned event_id "PK, FK -> events"
        int_unsigned department_id "PK, FK -> departments"
    }

    event_profile {
        bigint_unsigned event_id "PK, FK -> events"
        bigint_unsigned profile_id "PK, FK -> profiles"
        varchar status "indexed"
        text report "nullable"
        timestamp created_at
        timestamp updated_at
    }

    event_template_department {
        bigint_unsigned event_template_id "PK, FK -> event_templates"
        int_unsigned department_id "PK, FK -> departments"
    }

    profile_task {
        bigint_unsigned task_id "PK, FK -> tasks"
        bigint_unsigned profile_id "PK, FK -> profiles"
    }

    resident_user {
        int_unsigned id PK
        int_unsigned user_id "FK -> users"
        int_unsigned resident_id "FK -> residents"
        varchar relation_type "indexed"
        bigint_unsigned related_profile_id "FK -> profiles"
    }

    users_x_departments {
        int_unsigned user_id "FK -> users"
        int_unsigned department_id "FK -> departments"
    }

    %% ============================================
    %% SYSTEM / FRAMEWORK
    %% ============================================

    jobs {
        bigint_unsigned id PK
        varchar queue "indexed"
        longtext payload
        tinyint_unsigned attempts
        int_unsigned reserved_at "nullable"
        int_unsigned available_at
        int_unsigned created_at
    }

    failed_jobs {
        bigint_unsigned id PK
        varchar uuid "unique"
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }

    migrations {
        int_unsigned id PK
        varchar migration
        int batch
    }

    %% ============================================
    %% RELATIES: Users & Auth
    %% ============================================

    users ||--o| roles : "role_id"
    users }o--|| carehomes : "carehome_id"
    sessions }o--o| users : "user_id"

    %% ============================================
    %% RELATIES: Carehomes & Organisatie
    %% ============================================

    departments }o--|| carehomes : "carehome_id"

    %% ============================================
    %% RELATIES: Profiles & Residents
    %% ============================================

    profiles }o--|| carehomes : "carehome_id"
    profiles }o--o| users : "user_id"
    profiles }o--o| departments : "department_id"
    residents }o--|| carehomes : "carehome_id"
    residents }o--o| departments : "department_id"
    authors }o--o| users : "user_id"
    authors }o--o| carehomes : "carehome_id"
    authors }o--o| profiles : "profile_id"

    %% ============================================
    %% RELATIES: Activities
    %% ============================================

    activities }o--o| carehomes : "carehome_id"
    activities }o--o| activity_templates : "template_id"
    activities }o--o| activities : "origin_id (self)"
    activity_templates }o--o| activities : "activity_id"

    %% ============================================
    %% RELATIES: Events
    %% ============================================

    events }o--|| carehomes : "carehome_id"
    events }o--o| activities : "activity_id"
    events }o--o| users : "owner_id"
    events }o--o| users : "reporter_id"
    events }o--o| event_locations : "location_id"
    event_templates }o--|| carehomes : "carehome_id"
    event_templates }o--o| activities : "activity_id"
    event_templates }o--o| event_locations : "location_id"
    event_locations }o--|| carehomes : "carehome_id"

    %% ============================================
    %% RELATIES: Interests
    %% ============================================

    interests }o--o| interest_categories : "interest_category_id"
    interests }o--o| interests : "parent_id (self)"

    %% ============================================
    %% RELATIES: Stories
    %% ============================================

    stories }o--o| residents : "resident_id"
    stories }o--o| users : "user_id"
    stories }o--o| profiles : "profile_id"
    stories }o--o| story_questions : "story_question_id"

    %% ============================================
    %% RELATIES: Ideas
    %% ============================================

    ideas }o--o| users : "user_id"
    ideas }o--o| themes : "theme_id"

    %% ============================================
    %% RELATIES: Interactions
    %% ============================================

    likes }o--o| users : "user_id"
    likes }o--o| profiles : "profile_id"
    comments }o--o| users : "user_id"
    tasks }o--|| users : "user_id"

    %% ============================================
    %% RELATIES: Invites
    %% ============================================

    invites }o--|| users : "user_id"
    invites }o--|| users : "inviter_id"
    invites }o--o| residents : "resident_id"
    invites }o--o| profiles : "related_profile_id"

    %% ============================================
    %% RELATIES: Pivot many-to-many
    %% ============================================

    activities ||--o{ activity_interest : ""
    interests ||--o{ activity_interest : ""

    activities ||--o{ activity_theme : ""
    themes ||--o{ activity_theme : ""

    activities ||--o{ activity_department : ""
    departments ||--o{ activity_department : ""

    activities ||--o{ activity_profile : ""
    profiles ||--o{ activity_profile : ""

    activities ||--o{ activity_author_profile : ""
    profiles ||--o{ activity_author_profile : ""

    activity_templates ||--o{ activity_template_interest : ""
    interests ||--o{ activity_template_interest : ""

    activity_templates ||--o{ activity_template_theme : ""
    themes ||--o{ activity_template_theme : ""

    activity_templates ||--o{ activity_template_author : ""
    authors ||--o{ activity_template_author : ""

    interests ||--o{ interest_profile : ""
    profiles ||--o{ interest_profile : ""

    interests ||--o{ interest_resident : ""
    residents ||--o{ interest_resident : ""

    stories ||--o{ interest_story : ""
    interests ||--o{ interest_story : ""

    story_questions ||--o{ interest_story_question : ""
    interests ||--o{ interest_story_question : ""

    interests ||--o{ interest_theme : ""
    themes ||--o{ interest_theme : ""

    events ||--o{ event_department : ""
    departments ||--o{ event_department : ""

    events ||--o{ event_profile : ""
    profiles ||--o{ event_profile : ""

    event_templates ||--o{ event_template_department : ""
    departments ||--o{ event_template_department : ""

    tasks ||--o{ profile_task : ""
    profiles ||--o{ profile_task : ""

    users ||--o{ resident_user : ""
    residents ||--o{ resident_user : ""

    users ||--o{ users_x_departments : ""
    departments ||--o{ users_x_departments : ""
```

## Samenvattende tabel

### Core Content

| Tabel | Beschrijving | Kolommen | Relaties | Opmerkingen |
|---|---|---|---|---|
| **activities** | Activiteiten/belevingsactiviteiten | 17 | belongsToMany interests, themes; morphMany likes, comments; belongsTo carehomes, activity_templates, activities (self) | Soft deletes, fulltext search |
| **activity_templates** | Sjablonen/inspiratie voor activiteiten | 20 | belongsToMany interests, themes, authors; belongsTo activities | Fulltext search op title/summary/content |
| **interests** | Interesses en domeinen (type: interest/domain) | 9 | belongsTo interest_categories, interests (self-ref); belongsToMany activities, profiles, residents, stories, themes, story_questions | Self-referencing via parent_id, fulltext search |
| **interest_categories** | Categorisering van interesses | 4 | hasMany interests | Geen timestamps |
| **interestexamples** | Voorbeelden bij interesses | 3 | belongsTo interests (geen FK constraint) | Geen FK constraint |
| **themes** | Thema's/periodes voor activiteiten | 10 | belongsToMany activities, activity_templates, interests | date_type enum: fixed/variable |
| **stories** | Levensverhalen van bewoners | 16 | belongsTo residents, users, profiles, story_questions; belongsToMany interests | Soft deletes, fulltext search |
| **story_questions** | Vragen voor levensverhalen | 10 | hasMany stories; belongsToMany interests | |
| **ideas** | Ideeën van gebruikers | 6 | belongsTo users, themes | |
| **meetups** | Online bijeenkomsten/webinars | 17 | Geen relaties | Standalone tabel |

### Users, Profiles & Organisatie

| Tabel | Beschrijving | Kolommen | Relaties | Opmerkingen |
|---|---|---|---|---|
| **users** | Gebruikersaccounts | 11 | belongsTo carehomes, roles; hasMany likes, comments, tasks, stories, ideas, invites | Soft deletes |
| **roles** | Gebruikersrollen | 2 | hasMany users | |
| **carehomes** | Woonzorgcentra | 13 | hasMany users, departments, profiles, residents, activities, events, authors | Soft deletes |
| **departments** | Afdelingen binnen woonzorgcentra | 5 | belongsTo carehomes; belongsToMany users, activities, events | |
| **profiles** | Profielen (bewoners/medewerkers/family) | 19 | belongsTo carehomes, users, departments; belongsToMany interests, activities, events, tasks | Soft deletes, type-discriminated, fulltext search |
| **residents** | Bewoners (legacy tabel) | 16 | belongsTo carehomes, departments; belongsToMany users, interests | Soft deletes |
| **authors** | Auteurs van content/templates | 16 | belongsTo users, carehomes, profiles; belongsToMany activity_templates | |

### Events

| Tabel | Beschrijving | Kolommen | Relaties | Opmerkingen |
|---|---|---|---|---|
| **events** | Agenda-events/activiteiten | 18 | belongsTo carehomes, activities, users (owner+reporter), event_locations; belongsToMany departments, profiles | Fulltext search |
| **event_templates** | Terugkerende event-sjablonen | 15 | belongsTo carehomes, activities, event_locations; belongsToMany departments | |
| **event_locations** | Locaties voor events | 4 | belongsTo carehomes; hasMany events, event_templates | |

### Interacties (Polymorphic)

| Tabel | Beschrijving | Kolommen | Relaties | Opmerkingen |
|---|---|---|---|---|
| **likes** | Likes & bookmarks | 8 | morphTo likeable (activities, ...); belongsTo users, profiles | Polymorphic: `likeable_type` + `likeable_id`; type: like/bookmark |
| **comments** | Reacties/opmerkingen | 8 | morphTo commentable (activities, ...); belongsTo users | Polymorphic: `commentable_type` + `commentable_id`; soft deletes |
| **notifications** | Laravel notifications | 8 | morphTo notifiable (users, ...) | Polymorphic: `notifiable_type` + `notifiable_id` |
| **media** | Spatie Media Library bestanden | 17 | morphTo model (any) | Polymorphic: `model_type` + `model_id` |

### Overig

| Tabel | Beschrijving | Kolommen | Relaties | Opmerkingen |
|---|---|---|---|---|
| **tasks** | Taken voor gebruikers | 7 | belongsTo users; belongsToMany profiles | Soft deletes |
| **invites** | Uitnodigingen voor platform | 8 | belongsTo users (2x), residents, profiles | |
| **maillog** | Log van verzonden e-mails | 5 | Geen relaties | |

### Pivot-tabellen

| Tabel | Beschrijving | Koppelt | Extra kolommen |
|---|---|---|---|
| **activity_interest** | Activiteit-interesse koppeling | activities <-> interests | - |
| **activity_theme** | Activiteit-thema koppeling | activities <-> themes | `featured` |
| **activity_department** | Activiteit-afdeling koppeling | activities <-> departments | - |
| **activity_profile** | Activiteit-profiel koppeling | activities <-> profiles | `description` |
| **activity_author_profile** | Activiteit-auteurprofiel koppeling | activities <-> profiles | - |
| **activity_template_interest** | Template-interesse koppeling | activity_templates <-> interests | - |
| **activity_template_theme** | Template-thema koppeling | activity_templates <-> themes | `featured` |
| **activity_template_author** | Template-auteur koppeling | activity_templates <-> authors | - |
| **interest_profile** | Interesse-profiel koppeling | interests <-> profiles | - |
| **interest_resident** | Interesse-bewoner koppeling | interests <-> residents | - |
| **interest_story** | Interesse-verhaal koppeling | stories <-> interests | - |
| **interest_story_question** | Interesse-vraag koppeling | story_questions <-> interests | - |
| **interest_theme** | Interesse-thema koppeling | interests <-> themes | - |
| **event_department** | Event-afdeling koppeling | events <-> departments | - |
| **event_profile** | Event-profiel koppeling (deelname) | events <-> profiles | `status`, `report` |
| **event_template_department** | Event template-afdeling koppeling | event_templates <-> departments | - |
| **profile_task** | Profiel-taak koppeling | profiles <-> tasks | - |
| **resident_user** | Bewoner-gebruiker relatie | users <-> residents | `relation_type`, `related_profile_id` (FK -> profiles) |
| **users_x_departments** | Gebruiker-afdeling koppeling | users <-> departments | - |

### Systeem/Framework

| Tabel | Beschrijving | Kolommen |
|---|---|---|
| **sessions** | Actieve gebruikerssessies | 6 |
| **password_resets** | Wachtwoord-reset tokens | 3 |
| **personal_access_tokens** | Sanctum API tokens (polymorphic) | 9 |
| **jobs** | Queue jobs | 7 |
| **failed_jobs** | Gefaalde queue jobs | 7 |
| **migrations** | Migratie-tracking | 3 |
| **notifications_backup** | Backup van notifications | 8 |

## Opmerkingen & Inconsistenties

### Migraties vs. database

De lokale `database/migrations/` directory bevat slechts 9 migraties die een subset van de tabellen aanmaken. De database bevat **47 tabellen** in totaal. Dit wijst erop dat het project gemigreerd is vanuit een ouder project en dat de meeste tabellen al bestonden vóór de huidige codebase.

**Tabellen in de database zonder lokale migratie:**
`activity_author_profile`, `activity_department`, `activity_profile`, `activity_template_author`, `activity_template_interest`, `activity_template_theme`, `activity_templates`, `authors`, `carehomes`, `departments`, `event_department`, `event_locations`, `event_profile`, `event_template_department`, `event_templates`, `events`, `ideas`, `interest_profile`, `interest_resident`, `interest_story`, `interest_story_question`, `interest_theme`, `interestexamples`, `invites`, `maillog`, `media`, `meetups`, `notifications`, `notifications_backup`, `password_resets`, `personal_access_tokens`, `profile_task`, `profiles`, `resident_user`, `residents`, `roles`, `stories`, `story_questions`, `tasks`, `users_x_departments`

### Modellen vs. database

- **Author model** (`app/Models/Author.php`) bestaat, maar er is geen migratie in de lokale codebase. De tabel bestaat wel in de database.
- De database bevat veel tabellen waarvoor geen Eloquent-model bestaat in `app/Models/` (bijv. `events`, `event_templates`, `profiles`, `residents`, `stories`, etc.) — deze modellen bestaan waarschijnlijk in het oorspronkelijke project.

### Polymorphic relaties

| Tabel | Type + ID kolommen | Gebruikt door |
|---|---|---|
| **likes** | `likeable_type` + `likeable_id` | Activities (en mogelijk andere) |
| **comments** | `commentable_type` + `commentable_id` | Activities (en mogelijk andere) |
| **notifications** | `notifiable_type` + `notifiable_id` | Users (en mogelijk andere) |
| **media** | `model_type` + `model_id` | Spatie Media Library (any model) |
| **personal_access_tokens** | `tokenable_type` + `tokenable_id` | Laravel Sanctum |

### Overige observaties

- **`interestexamples`**: heeft geen foreign key constraint op `interest_id`, en het column type is `int` i.p.v. `int unsigned`
- **`residents`** lijkt een legacy tabel; `profiles` met `type` discriminator vervangt deze functionaliteit
- **`notifications_backup`**: exacte kopie van de `notifications` tabel structuur — waarschijnlijk een eenmalige backup
- **Soft deletes** worden gebruikt bij: `users`, `carehomes`, `activities`, `comments`, `stories`, `profiles`, `residents`, `tasks`
- **`users`** tabel in de database heeft extra kolommen (`carehome_id`, `role_id`, `states`, `deleted_at`) die niet in de lokale migratie staan
