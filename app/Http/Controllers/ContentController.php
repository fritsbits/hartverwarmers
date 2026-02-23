<?php

namespace App\Http\Controllers;

use App\Services\JsonContent;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function content(string $slug): View
    {
        $slug = trim($slug, '/');
        $content = JsonContent::getContent($slug);

        if ($content === false) {
            abort(404);
        }

        $view = config('content.viewsPath').(isset($content['_template'])
            ? 'templates/'.$content['_template']
            : str_replace('/', '.', $slug));

        if (! view()->exists($view)) {
            abort(404);
        }

        $parent = null;

        if ($content['_type'] === 'overview') {
            $overviewSlug = $slug;
        } else {
            $overviewSlug = explode('/', $slug);
            array_pop($overviewSlug);
            $overviewSlug = implode('/', $overviewSlug);
            $parent = JsonContent::getContent($overviewSlug);

            if ($parent) {
                $parent['slug'] = $overviewSlug;
            }
        }

        $pages = [];
        $disk = JsonContent::disk();

        foreach ($disk->files($overviewSlug) as $filename) {
            $filename = basename($filename, JsonContent::CONTENT_SUFFIX);
            $pageSlug = $overviewSlug.'/'.$filename;
            $page = JsonContent::getContent($pageSlug);

            if ($page) {
                $pages[] = [
                    'slug' => $pageSlug,
                    'url' => route('content', ['slug' => $pageSlug]),
                    'label' => $page['title'] ?? null,
                    '_page' => $page,
                ];
            }
        }

        $content['slug'] = $slug;
        $content['_parent'] = $parent;
        $content['_pages'] = collect($pages)->sortBy('label');

        return view($view, compact('content', 'slug', 'overviewSlug', 'parent'));
    }

    public function roadmap(): View
    {
        return view('content.roadmap');
    }
}
