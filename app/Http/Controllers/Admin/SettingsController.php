<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['s' => site_settings()]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'delivery.inside_dhaka' => ['required', 'numeric', 'min:0'],
            'delivery.outside_dhaka' => ['required', 'numeric', 'min:0'],
            'delivery.free_delivery_threshold' => ['required', 'numeric', 'min:0'],
            'payments.bkash_number' => ['nullable', 'string', 'max:20'],
            'payments.nagad_number' => ['nullable', 'string', 'max:20'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
            'contact.whatsapp' => ['nullable', 'string', 'max:30'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.address' => ['nullable', 'string', 'max:255'],
            'contact.facebook' => ['nullable', 'url', 'max:255'],
            'contact.instagram' => ['nullable', 'url', 'max:255'],
            'contact.youtube' => ['nullable', 'url', 'max:255'],
            'seo.site_name' => ['nullable', 'string', 'max:100'],
            'seo.default_title' => ['nullable', 'string', 'max:255'],
            'seo.default_description' => ['nullable', 'string', 'max:500'],
            'seo.canonical_base' => ['nullable', 'url', 'max:255'],
        ]);

        $payments = $d['payments'] ?? [];
        foreach (['cod', 'bkash', 'nagad', 'card'] as $method) {
            $payments[$method] = $request->boolean("payments.{$method}");
        }
        if (! in_array(true, array_intersect_key($payments, array_flip(['cod', 'bkash', 'nagad', 'card'])), true)) {
            return back()->withErrors(['payments' => 'Turn on at least one payment method.'])->withInput();
        }

        SiteSetting::put('delivery', array_map('floatval', $d['delivery']));
        SiteSetting::put('payments', array_map(fn ($v) => is_string($v) ? trim($v) : $v, $payments + ['bkash_number' => '', 'nagad_number' => '']));
        SiteSetting::put('contact', array_map(fn ($v) => trim((string) $v), $d['contact'] ?? []));
        SiteSetting::put('seo', array_map(fn ($v) => trim((string) $v), $d['seo'] ?? []));

        return back()->with('success', 'Settings saved');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password changed');
    }
}
