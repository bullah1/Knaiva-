<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\HeroBanner;
use App\Models\TrustPoint;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomepageController extends Controller
{
    private const MODELS = ['banner' => HeroBanner::class, 'trust' => TrustPoint::class, 'certificate' => Certificate::class];

    public function index()
    {
        return view('admin.homepage', [
            'banners' => HeroBanner::orderBy('sort_order')->get(),
            'trustPoints' => TrustPoint::orderBy('sort_order')->get(),
            'certificates' => Certificate::orderBy('sort_order')->get(),
        ]);
    }

    private function rules(string $type): array
    {
        return match ($type) {
            'banner' => [
                'title' => ['required', 'string', 'max:255'], 'title_bn' => ['nullable', 'string', 'max:255'],
                'subtitle' => ['nullable', 'string', 'max:500'], 'subtitle_bn' => ['nullable', 'string', 'max:500'],
                'cta_label' => ['nullable', 'string', 'max:50'], 'cta_link' => ['nullable', 'string', 'max:255'],
                'sort_order' => ['nullable', 'integer'], 'image' => Uploads::RULE,
            ],
            'trust' => [
                'icon' => ['required', Rule::in(array_keys(TrustPoint::ICONS))], 'title' => ['required', 'string', 'max:255'],
                'title_bn' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:500'],
                'description_bn' => ['nullable', 'string', 'max:500'], 'sort_order' => ['nullable', 'integer'],
            ],
            'certificate' => ['title' => ['required', 'string', 'max:255'], 'sort_order' => ['nullable', 'integer'], 'image' => Uploads::RULE],
        };
    }

    private function fill(Request $request, string $type, $model)
    {
        $data = $request->validate($this->rules($type));
        unset($data['image']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        if ($path = Uploads::store($request->file('image'), 'homepage')) {
            Uploads::delete($model->image_url);
            $data['image_url'] = $path;
        }
        if ($request->boolean('remove_image')) {
            Uploads::delete($model->image_url);
            $data['image_url'] = null;
        }
        $model->fill($data)->save();
    }

    public function store(Request $request, string $type)
    {
        $class = self::MODELS[$type];
        $this->fill($request, $type, new $class);

        return back()->with('success', 'Added');
    }

    public function update(Request $request, string $type, int $id)
    {
        $this->fill($request, $type, self::MODELS[$type]::findOrFail($id));

        return back()->with('success', 'Saved');
    }

    public function destroy(string $type, int $id)
    {
        $model = self::MODELS[$type]::findOrFail($id);
        Uploads::delete($model->image_url ?? null);
        $model->delete();

        return back()->with('success', 'Deleted');
    }
}
