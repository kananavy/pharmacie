<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /**
     * Display a listing of the settings.
     */
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->all();

        // Provide default values if settings are not found
        $defaultSettings = [
            'pharmacy_name' => 'Pharmacie Pro',
            'pharmacy_address' => '123 Rue de la Santé, Ville',
            'pharmacy_phone' => '+261 32 00 000 00',
            'pharmacy_email' => 'contact@pharmaciepro.mg',
            'pharmacy_tax_id' => 'NIF: 1234567890 - STAT: 12345 67890 00012',
            'currency_symbol' => 'Ar',
            'app_logo' => null, // Path to the logo file
        ];

        return response()->json(array_merge($defaultSettings, $settings));
    }

    /**
     * Update the specified settings in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'pharmacy_name' => 'nullable|string|max:255',
            'pharmacy_address' => 'nullable|string|max:255',
            'pharmacy_phone' => 'nullable|string|max:255',
            'pharmacy_email' => 'nullable|email|max:255',
            'pharmacy_tax_id' => 'nullable|string|max:255',
            'currency_symbol' => 'nullable|string|max:10',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB Max
        ]);

        return DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                if ($key === 'app_logo') {
                    // Handle logo upload
                    if ($value) {
                        // Delete old logo if exists
                        $oldLogoPath = Setting::where('key', 'app_logo')->value('value');
                        if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                            Storage::disk('public')->delete($oldLogoPath);
                        }
                        $path = $value->store('logos', 'public');
                        Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $path]);
                    }
                    // If logo is null, it means keep existing or delete if specifically requested (not handled here)
                } else {
                    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            }

            // If the request contains 'remove_logo' = true, delete the logo
            if ($request->boolean('remove_logo')) {
                $oldLogoPath = Setting::where('key', 'app_logo')->value('value');
                if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                    Storage::disk('public')->delete($oldLogoPath);
                }
                Setting::where('key', 'app_logo')->delete();
            }

            return response()->json(Setting::pluck('value', 'key')->all());
        });
    }
}
