<?php

namespace App\Http\Controllers;

use App\Features\DiamantGoals;
use App\Features\WizardDevMode;
use Illuminate\Http\RedirectResponse;
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
            'active' => Feature::for(null)->active($meta['class']),
        ]);

        return view('admin.features', ['features' => $features]);
    }

    public function toggle(string $feature): RedirectResponse
    {
        if (! isset(self::FEATURES[$feature])) {
            abort(404);
        }

        $class = self::FEATURES[$feature]['class'];

        if (Feature::for(null)->active($class)) {
            Feature::deactivateForEveryone($class);
            $status = 'uitgeschakeld';
        } else {
            Feature::activateForEveryone($class);
            $status = 'ingeschakeld';
        }

        return redirect()->route('admin.features')
            ->with('success', self::FEATURES[$feature]['label']." is {$status}.");
    }
}
