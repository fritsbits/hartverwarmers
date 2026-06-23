CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "organisations"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "city" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "initiatives"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "slug" varchar not null,
  "description" text,
  "content" text,
  "image" varchar,
  "published" tinyint(1) not null default '0',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "diamant_guidance" text,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "initiatives_slug_unique" on "initiatives"("slug");
CREATE TABLE IF NOT EXISTS "fiches"(
  "id" integer primary key autoincrement not null,
  "initiative_id" integer,
  "user_id" integer not null,
  "title" varchar not null,
  "slug" varchar not null,
  "description" text,
  "practical_tips" text,
  "materials" text,
  "target_audience" text,
  "published" tinyint(1) not null default '0',
  "has_diamond" tinyint(1) not null default '0',
  "download_count" integer not null default '0',
  "kudos_count" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "icon" varchar,
  "migration_id" integer,
  "zip_path" varchar,
  "quality_score" integer,
  "quality_justification" text,
  "quality_assessed_at" datetime,
  "completeness_score" integer,
  "presentation_score" integer,
  "presentation_justification" text,
  "ai_suggestions" text,
  "aanleiding" text,
  "diamond_awarded_at" datetime,
  foreign key("initiative_id") references "initiatives"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "fiches_slug_unique" on "fiches"("slug");
CREATE TABLE IF NOT EXISTS "tags"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "type" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "tags_slug_unique" on "tags"("slug");
CREATE TABLE IF NOT EXISTS "taggables"(
  "tag_id" integer not null,
  "taggable_type" varchar not null,
  "taggable_id" integer not null,
  foreign key("tag_id") references "tags"("id") on delete cascade,
  primary key("tag_id", "taggable_type", "taggable_id")
);
CREATE INDEX "taggables_taggable_type_taggable_id_index" on "taggables"(
  "taggable_type",
  "taggable_id"
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "comments"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "commentable_type" varchar not null,
  "commentable_id" integer not null,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "parent_id" integer,
  foreign key("user_id") references users("id") on delete cascade on update no action,
  foreign key("parent_id") references "comments"("id") on delete cascade
);
CREATE INDEX "comments_commentable_type_commentable_id_index" on "comments"(
  "commentable_type",
  "commentable_id"
);
CREATE TABLE IF NOT EXISTS "agent_conversations"(
  "id" varchar not null,
  "user_id" integer,
  "title" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "agent_conversations_user_id_updated_at_index" on "agent_conversations"(
  "user_id",
  "updated_at"
);
CREATE TABLE IF NOT EXISTS "agent_conversation_messages"(
  "id" varchar not null,
  "conversation_id" varchar not null,
  "user_id" integer,
  "agent" varchar not null,
  "role" varchar not null,
  "content" text not null,
  "attachments" text not null,
  "tool_calls" text not null,
  "tool_results" text not null,
  "usage" text not null,
  "meta" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "conversation_index" on "agent_conversation_messages"(
  "conversation_id",
  "user_id",
  "updated_at"
);
CREATE INDEX "agent_conversation_messages_user_id_index" on "agent_conversation_messages"(
  "user_id"
);
CREATE INDEX "agent_conversation_messages_conversation_id_index" on "agent_conversation_messages"(
  "conversation_id"
);
CREATE TABLE IF NOT EXISTS "features"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "scope" varchar not null,
  "value" text not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "features_name_scope_unique" on "features"(
  "name",
  "scope"
);
CREATE INDEX "fiches_published_index" on "fiches"("published");
CREATE INDEX "initiatives_published_index" on "initiatives"("published");
CREATE INDEX "tags_type_index" on "tags"("type");
CREATE TABLE IF NOT EXISTS "likes"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "likeable_type" varchar not null,
  "likeable_id" integer not null,
  "type" varchar not null default('like'),
  "created_at" datetime,
  "updated_at" datetime,
  "count" integer not null default('1'),
  "session_id" varchar,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "likes_likeable_type_likeable_id_index" on "likes"(
  "likeable_type",
  "likeable_id"
);
CREATE UNIQUE INDEX "likes_user_id_likeable_type_likeable_id_type_unique" on "likes"(
  "user_id",
  "likeable_type",
  "likeable_id",
  "type"
);
CREATE INDEX "likes_session_lookup" on "likes"(
  "session_id",
  "likeable_type",
  "likeable_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "files"(
  "id" integer primary key autoincrement not null,
  "fiche_id" integer,
  "original_filename" varchar not null,
  "path" varchar not null,
  "mime_type" varchar not null,
  "size_bytes" integer not null,
  "sort_order" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "preview_images" text,
  "extracted_text" text,
  "total_slides" integer,
  "source_file_id" integer,
  foreign key("fiche_id") references fiches("id") on delete cascade on update no action,
  foreign key("source_file_id") references "files"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "file_uploads"(
  "id" integer primary key autoincrement not null,
  "file_id" integer not null,
  "user_id" integer not null,
  "ip_address" varchar not null,
  "file_hash" varchar not null,
  "original_filename" varchar not null,
  "disclaimer_accepted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("file_id") references "files"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "file_uploads_user_id_index" on "file_uploads"("user_id");
CREATE INDEX "file_uploads_file_hash_index" on "file_uploads"("file_hash");
CREATE TABLE IF NOT EXISTS "user_interactions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "interactable_type" varchar not null,
  "interactable_id" integer not null,
  "type" varchar not null,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "user_interactions_interactable_type_interactable_id_index" on "user_interactions"(
  "interactable_type",
  "interactable_id"
);
CREATE UNIQUE INDEX "user_interactions_unique" on "user_interactions"(
  "user_id",
  "interactable_type",
  "interactable_id",
  "type"
);
CREATE INDEX "user_interactions_user_id_interactable_type_type_index" on "user_interactions"(
  "user_id",
  "interactable_type",
  "type"
);
CREATE INDEX "fiches_migration_id_index" on "fiches"("migration_id");
CREATE TABLE IF NOT EXISTS "onboarding_email_log"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "mail_key" varchar not null,
  "sent_at" datetime not null,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "onboarding_email_log_user_id_mail_key_unique" on "onboarding_email_log"(
  "user_id",
  "mail_key"
);
CREATE TABLE IF NOT EXISTS "themes"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "slug" varchar not null,
  "description" text,
  "is_month" tinyint(1) not null default '0',
  "recurrence_rule" varchar not null,
  "recurrence_detail" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "themes_slug_unique" on "themes"("slug");
CREATE TABLE IF NOT EXISTS "theme_occurrences"(
  "id" integer primary key autoincrement not null,
  "theme_id" integer not null,
  "year" integer not null,
  "start_date" date not null,
  "end_date" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("theme_id") references "themes"("id") on delete cascade
);
CREATE UNIQUE INDEX "theme_occurrences_theme_id_year_unique" on "theme_occurrences"(
  "theme_id",
  "year"
);
CREATE INDEX "theme_occurrences_start_date_end_date_index" on "theme_occurrences"(
  "start_date",
  "end_date"
);
CREATE TABLE IF NOT EXISTS "fiche_theme"(
  "fiche_id" integer not null,
  "theme_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("fiche_id") references "fiches"("id") on delete cascade,
  foreign key("theme_id") references "themes"("id") on delete cascade,
  primary key("fiche_id", "theme_id")
);
CREATE INDEX "fiche_theme_theme_id_index" on "fiche_theme"("theme_id");
CREATE TABLE IF NOT EXISTS "pending_notifications"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "type" varchar not null,
  "fiche_id" integer,
  "payload" text not null,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("fiche_id") references "fiches"("id") on delete set null
);
CREATE INDEX "pending_notifications_user_id_fiche_id_type_index" on "pending_notifications"(
  "user_id",
  "fiche_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "email" varchar not null,
  "password" varchar not null,
  "role" varchar not null default('member'),
  "function_title" varchar,
  "avatar_path" varchar,
  "bio" text,
  "remember_token" varchar,
  "email_verified_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "first_name" varchar not null default(''),
  "last_name" varchar not null default(''),
  "organisation" varchar,
  "website" varchar,
  "linkedin" varchar,
  "fiches_comments_seen_at" datetime,
  "terms_accepted_at" datetime,
  "onboarded_at" datetime,
  "contributor_onboarded_at" datetime,
  "last_visited_at" datetime,
  "notify_on_onboarding_emails" tinyint(1) not null default('1'),
  "first_return_at" datetime,
  "notification_frequency" varchar not null default 'weekly',
  "notify_on_kudos_milestones" tinyint(1) not null default('1'),
  "newsletter_unsubscribed_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE INDEX "onboarding_email_log_sent_at_index" on "onboarding_email_log"(
  "sent_at"
);
CREATE INDEX "users_created_at_index" on "users"("created_at");
CREATE TABLE IF NOT EXISTS "okr_objectives"(
  "id" integer primary key autoincrement not null,
  "slug" varchar not null,
  "title" varchar not null,
  "description" text,
  "status" varchar not null default 'on_track',
  "position" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "okr_objectives_slug_unique" on "okr_objectives"("slug");
CREATE TABLE IF NOT EXISTS "okr_initiatives"(
  "id" integer primary key autoincrement not null,
  "objective_id" integer not null,
  "slug" varchar not null,
  "label" varchar not null,
  "status" varchar not null default 'in_progress',
  "description" text,
  "position" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "started_at" date,
  foreign key("objective_id") references "okr_objectives"("id") on delete cascade
);
CREATE UNIQUE INDEX "okr_initiatives_objective_id_slug_unique" on "okr_initiatives"(
  "objective_id",
  "slug"
);
CREATE TABLE IF NOT EXISTS "okr_key_results"(
  "id" integer primary key autoincrement not null,
  "objective_id" integer not null,
  "label" varchar not null,
  "metric_key" varchar,
  "target_value" integer,
  "target_unit" varchar not null default '',
  "position" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("objective_id") references "okr_objectives"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "okr_initiative_baselines"(
  "id" integer primary key autoincrement not null,
  "initiative_id" integer not null,
  "key_result_id" integer not null,
  "baseline_value" numeric,
  "baseline_unit" varchar not null default '',
  "baseline_at" datetime not null,
  "low_data" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("initiative_id") references "okr_initiatives"("id") on delete cascade,
  foreign key("key_result_id") references "okr_key_results"("id") on delete cascade
);
CREATE UNIQUE INDEX "okr_initiative_baselines_initiative_id_key_result_id_unique" on "okr_initiative_baselines"(
  "initiative_id",
  "key_result_id"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000001_create_organisations_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000002_create_users_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000003_create_initiatives_table',1);
INSERT INTO migrations VALUES(4,'0001_01_01_000004_create_fiches_table',1);
INSERT INTO migrations VALUES(5,'0001_01_01_000005_create_files_table',1);
INSERT INTO migrations VALUES(6,'0001_01_01_000006_create_tags_table',1);
INSERT INTO migrations VALUES(7,'0001_01_01_000007_create_comments_table',1);
INSERT INTO migrations VALUES(8,'0001_01_01_000008_create_likes_table',1);
INSERT INTO migrations VALUES(9,'0001_01_01_000009_create_laravel_standard_tables',1);
INSERT INTO migrations VALUES(10,'2026_02_23_000001_add_diamant_guidance_to_initiatives_table',1);
INSERT INTO migrations VALUES(11,'2026_02_26_111359_restructure_users_table',1);
INSERT INTO migrations VALUES(12,'2026_02_27_164942_add_count_to_likes_table',1);
INSERT INTO migrations VALUES(13,'2026_02_27_164942_add_parent_id_to_comments_table',1);
INSERT INTO migrations VALUES(14,'2026_02_28_153556_add_preview_images_to_files_table',1);
INSERT INTO migrations VALUES(15,'2026_02_28_174829_add_extracted_text_to_files_table',1);
INSERT INTO migrations VALUES(16,'2026_02_28_232941_add_total_slides_to_files_table',1);
INSERT INTO migrations VALUES(17,'2026_03_01_004442_create_agent_conversations_table',1);
INSERT INTO migrations VALUES(18,'2026_03_01_004514_make_files_fiche_id_nullable',1);
INSERT INTO migrations VALUES(19,'2026_03_03_121056_create_features_table',1);
INSERT INTO migrations VALUES(20,'2026_03_03_150448_remove_seeded_demo_initiatives',1);
INSERT INTO migrations VALUES(21,'2026_03_04_080318_reorganize_diverse_initiative_fiches',1);
INSERT INTO migrations VALUES(22,'2026_03_04_114006_update_initiative_image_paths',1);
INSERT INTO migrations VALUES(23,'2026_03_04_120823_fix_initiative_image_paths',1);
INSERT INTO migrations VALUES(24,'2026_03_04_160440_set_initiative_image_paths_by_slug',1);
INSERT INTO migrations VALUES(25,'2026_03_05_221530_add_performance_indexes',1);
INSERT INTO migrations VALUES(26,'2026_03_05_232748_add_fiches_comments_seen_at_to_users_table',1);
INSERT INTO migrations VALUES(27,'2026_03_07_092121_add_featured_month_to_fiches_table',1);
INSERT INTO migrations VALUES(28,'2026_03_07_141100_add_terms_accepted_at_to_users_table',1);
INSERT INTO migrations VALUES(29,'2026_03_10_224856_allow_anonymous_kudos',1);
INSERT INTO migrations VALUES(30,'2026_03_11_120314_change_default_user_role_to_member',1);
INSERT INTO migrations VALUES(31,'2026_03_13_094154_add_onboarding_fields_to_users_table',1);
INSERT INTO migrations VALUES(32,'2026_03_13_151742_add_source_file_id_to_files_table',1);
INSERT INTO migrations VALUES(33,'2026_03_14_144323_create_file_uploads_table',1);
INSERT INTO migrations VALUES(34,'2026_03_14_231318_create_user_interactions_table',1);
INSERT INTO migrations VALUES(35,'2026_03_14_232445_add_icon_to_fiches_table',1);
INSERT INTO migrations VALUES(36,'2026_03_15_145124_add_migration_id_to_fiches_table',1);
INSERT INTO migrations VALUES(37,'2026_03_15_205137_add_zip_path_to_fiches_table',1);
INSERT INTO migrations VALUES(38,'2026_03_16_084328_clean_import_organisation_placeholder',1);
INSERT INTO migrations VALUES(39,'2026_03_16_152108_move_fiches_from_diverse_initiative',1);
INSERT INTO migrations VALUES(40,'2026_03_17_155549_add_scoring_columns_to_fiches_table',1);
INSERT INTO migrations VALUES(41,'2026_03_18_091745_add_presentation_score_to_fiches_table',1);
INSERT INTO migrations VALUES(42,'2026_03_18_122143_reassign_import_fiches_to_admin',1);
INSERT INTO migrations VALUES(43,'2026_03_18_122554_add_ai_suggestions_to_fiches_table',1);
INSERT INTO migrations VALUES(44,'2026_03_18_193013_drop_featured_month_from_fiches_table',1);
INSERT INTO migrations VALUES(45,'2026_03_19_092646_add_last_visited_at_to_users_table',1);
INSERT INTO migrations VALUES(46,'2026_03_19_120142_drop_pulse_tables',1);
INSERT INTO migrations VALUES(47,'2026_03_19_153105_add_notify_on_fiche_comments_to_users',1);
INSERT INTO migrations VALUES(48,'2026_04_01_205636_create_onboarding_email_log_table',1);
INSERT INTO migrations VALUES(49,'2026_04_01_205637_add_notify_on_onboarding_emails_to_users_table',1);
INSERT INTO migrations VALUES(50,'2026_04_03_070823_add_first_return_at_to_users_table',1);
INSERT INTO migrations VALUES(51,'2026_04_06_212451_add_aanleiding_to_fiches_table',1);
INSERT INTO migrations VALUES(52,'2026_05_12_110853_create_themes_table',1);
INSERT INTO migrations VALUES(53,'2026_05_12_111331_create_theme_occurrences_table',1);
INSERT INTO migrations VALUES(54,'2026_05_12_111707_create_fiche_theme_table',1);
INSERT INTO migrations VALUES(55,'2026_05_13_152553_replace_notify_on_fiche_comments_with_notification_frequency',1);
INSERT INTO migrations VALUES(56,'2026_05_13_152554_add_notify_on_kudos_milestones_to_users',1);
INSERT INTO migrations VALUES(57,'2026_05_13_152555_create_pending_notifications_table',1);
INSERT INTO migrations VALUES(58,'2026_05_13_164220_add_newsletter_unsubscribed_at_to_users_table',1);
INSERT INTO migrations VALUES(59,'2026_05_13_195032_add_diamond_awarded_at_to_fiches_table',1);
INSERT INTO migrations VALUES(60,'2026_05_14_090030_default_notification_frequency_to_weekly',1);
INSERT INTO migrations VALUES(61,'2026_05_14_131522_add_perf_indexes_to_admin_dashboard_tables',1);
INSERT INTO migrations VALUES(62,'2026_05_14_142709_create_okr_objectives_table',1);
INSERT INTO migrations VALUES(63,'2026_05_14_142711_create_okr_initiatives_table',1);
INSERT INTO migrations VALUES(64,'2026_05_14_142711_create_okr_key_results_table',1);
INSERT INTO migrations VALUES(65,'2026_05_15_000001_add_started_at_to_okr_initiatives_table',1);
INSERT INTO migrations VALUES(66,'2026_05_15_000002_create_okr_initiative_baselines_table',1);
INSERT INTO migrations VALUES(67,'2026_05_15_000003_backfill_okr_initiative_started_at',1);
