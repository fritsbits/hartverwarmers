<?php

namespace App\Http\Controllers;

use App\Features\DiamantGoals;
use App\Features\WizardDevMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
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
            'cache_key' => DiamantGoals::CACHE_KEY,
        ],
        'wizard-dev-mode' => [
            'class' => WizardDevMode::class,
            'label' => 'Wizard Dev Mode',
            'description' => 'Laat admins direct naar elke stap van de fiche-wizard springen met vooraf ingevulde testdata.',
            'cache_key' => null,
        ],
    ];

    public function index(): View
    {
        $features = collect(self::FEATURES)->map(fn ($meta, $name) => [
            'name' => $name,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'globally_active' => $meta['cache_key'] ? Cache::get($meta['cache_key'], false) : false,
            'active' => Feature::active($meta['class']),
        ]);

        return view('admin.features', ['features' => $features]);
    }

    public function toggle(string $feature): RedirectResponse
    {
        if (! isset(self::FEATURES[$feature])) {
            abort(404);
        }

        $meta = self::FEATURES[$feature];

        if ($meta['cache_key'] && Cache::get($meta['cache_key'])) {
            // Back to beta: remove cache flag, purge stored values so resolver re-runs
            Cache::forget($meta['cache_key']);
            Feature::purge($meta['class']);
            $status = 'uitgeschakeld';
        } else {
            // Go live: set cache flag, purge stored values so resolver re-runs with flag active
            if ($meta['cache_key']) {
                Cache::forever($meta['cache_key'], true);
            }
            Feature::purge($meta['class']);
            $status = 'ingeschakeld';
        }

        return redirect()->route('admin.features')
            ->with('success', $meta['label']." is {$status}.");
    }
}
