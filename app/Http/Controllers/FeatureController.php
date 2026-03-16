<?php

namespace App\Http\Controllers;

use App\Features\DiamantGoals;
use App\Features\WizardDevMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class FeatureController extends Controller
{
    /**
     * Known features with display metadata.
     *
     * @var array<string, array{class: class-string, label: string, description: string}>
     */
    private const FEATURES = [
        'diamant-goals' => [
            'class' => DiamantGoals::class,
            'label' => 'DIAMANT-doelen',
            'description' => 'Toont de zeven DIAMANT-doelstellingen in navigatie, homepagina, fiches en formulieren.',
        ],
        'wizard-dev-mode' => [
            'class' => WizardDevMode::class,
            'label' => 'Wizard Dev Mode',
            'description' => 'Laat admins direct naar elke stap van de fiche-wizard springen met vooraf ingevulde testdata.',
        ],
    ];

    public function index(): View
    {
        $features = collect(self::FEATURES)->map(fn ($meta, $name) => [
            'name' => $name,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'globally_active' => $this->isGloballyActive($name),
            'active' => Feature::active($meta['class']),
        ]);

        return view('admin.features', ['features' => $features]);
    }

    public function toggle(string $feature): RedirectResponse
    {
        if (! isset(self::FEATURES[$feature])) {
            abort(404);
        }

        $class = self::FEATURES[$feature]['class'];

        if ($this->isGloballyActive($feature)) {
            // Back to beta: purge all stored values, resolver takes over
            Feature::purge($class);
            $status = 'uitgeschakeld';
        } else {
            // Go live: purge stale per-user values, then activate null scope
            Feature::purge($class);
            Feature::for(null)->activate($class);
            $status = 'ingeschakeld';
        }

        return redirect()->route('admin.features')
            ->with('success', self::FEATURES[$feature]['label']." is {$status}.");
    }

    /**
     * Check if a feature has been globally activated (null-scope stored true).
     * Queries DB directly to avoid triggering Pennant's resolve-and-store cycle.
     */
    private function isGloballyActive(string $featureName): bool
    {
        return DB::table('features')
            ->where('name', $featureName)
            ->where('scope', '__laravel_null')
            ->where('value', 'true')
            ->exists();
    }
}
