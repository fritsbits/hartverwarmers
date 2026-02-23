<?php

namespace App\Http\Controllers;

use App\Services\JsonContent;
use Illuminate\View\View;

class ToolsInspirationController extends Controller
{
    public function index(): View
    {
        $tools = [];
        $disk = JsonContent::disk();

        foreach ($disk->files('tools') as $filename) {
            $slug = basename($filename, '.json');
            $tool = JsonContent::getContent('tools/'.$slug);

            if ($tool) {
                $tools[] = $tool;
            }
        }

        return view('tools.index', compact('tools'));
    }

    public function workshops(): View
    {
        $workshopsOverview = JsonContent::getContent('workshops');

        $workshopsVisie = [];
        foreach ($workshopsOverview['categories']['visie']['items'] as $uid) {
            $workshop = JsonContent::getContent('workshops/'.$uid);

            if ($workshop) {
                $workshopsVisie[] = $workshop;
            }
        }

        $workshopsProces = [];
        foreach ($workshopsOverview['categories']['proces']['items'] as $uid) {
            $workshop = JsonContent::getContent('workshops/'.$uid);

            if ($workshop) {
                $workshopsProces[] = $workshop;
            }
        }

        $workshopsActiviteiten = [];
        foreach ($workshopsOverview['categories']['activiteiten']['items'] as $uid) {
            $workshop = JsonContent::getContent('workshops/'.$uid);

            if ($workshop) {
                $workshopsActiviteiten[] = $workshop;
            }
        }

        return view('tools.workshops', compact('workshopsVisie', 'workshopsProces', 'workshopsActiviteiten'));
    }

    public function videoLessons(): View
    {
        return view('tools.videolessen');
    }

    public function showTool(string $uid): View
    {
        $tool = JsonContent::getContent('tools/'.$uid);
        abort_if($tool === false, 404);

        return view('tools.show', compact('tool'));
    }

    public function showWorkshop(string $uid): View
    {
        $workshop = JsonContent::getContent('workshops/'.$uid);
        abort_if($workshop === false, 404);

        return view('tools.workshop', compact('workshop'));
    }
}
