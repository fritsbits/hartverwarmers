<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RelinkFicheAuthors extends Command
{
    protected $signature = 'app:relink-fiche-authors';

    protected $description = 'Re-link fiches to real imported users and update stub user organisations';

    public function handle(): int
    {
        // Build email → new user ID map
        $newUsersByEmail = DB::table('users')
            ->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [strtolower(trim($email)) => $id]);

        // Get all fiches with migration_id
        $fiches = DB::table('fiches')
            ->whereNotNull('migration_id')
            ->select('id', 'migration_id', 'user_id')
            ->get();

        $this->info("Processing {$fiches->count()} fiches with migration_id...");

        // Load author chain from old DB
        $authorData = DB::connection('soulcenter')
            ->table('activity_author_profile as aap')
            ->join('profiles as p', 'aap.profile_id', '=', 'p.id')
            ->join('authors as a', 'a.profile_id', '=', 'p.id')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->select(
                'aap.activity_id',
                'a.email as author_email',
                'a.user_id as author_user_id',
                'a.company',
                'u.email as user_email'
            )
            ->get()
            // Activities can have multiple authors; take the first one per activity
            ->groupBy('activity_id')
            ->map(fn ($group) => $group->first());

        $relinked = 0;
        $orgUpdated = 0;
        $kept = 0;

        foreach ($fiches as $fiche) {
            $author = $authorData->get($fiche->migration_id);
            if (! $author) {
                $kept++;

                continue;
            }

            // Try to find real user email
            $email = null;
            if ($author->user_email) {
                $email = strtolower(trim($author->user_email));
            } elseif ($author->author_email) {
                $email = strtolower(trim(explode(',', $author->author_email)[0]));
            }

            $newUserId = $email ? ($newUsersByEmail[$email] ?? null) : null;

            if ($newUserId && $newUserId !== $fiche->user_id) {
                DB::table('fiches')->where('id', $fiche->id)->update(['user_id' => $newUserId]);
                $relinked++;
            } else {
                $kept++;

                // Update stub user's organisation if applicable
                $currentUser = DB::table('users')->where('id', $fiche->user_id)->first();
                if ($currentUser
                    && str_ends_with($currentUser->email, '@import.hartverwarmers.be')
                    && $author->company
                    && $currentUser->organisation === 'Import'
                ) {
                    DB::table('users')
                        ->where('id', $currentUser->id)
                        ->update(['organisation' => trim($author->company)]);
                    $orgUpdated++;
                }
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Re-linked to real user', $relinked],
            ['Kept stub user', $kept],
            ['Stub orgs updated', $orgUpdated],
        ]);

        return self::SUCCESS;
    }
}
