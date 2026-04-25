<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SettingsService
{
    private const FILE = 'settings.json';

    public function getSettings(): array
    {
        if (!Storage::exists(self::FILE)) {
            return [
                'modules' => [
                    'rifas' => true,
                    'torneos' => true,
                ]
            ];
        }

        $content = Storage::get(self::FILE);
        return json_decode($content, true) ?: [];
    }

    public function updateSettings(array $settings): void
    {
        $current = $this->getSettings();
        $merged = array_merge($current, $settings);
        Storage::put(self::FILE, json_encode($merged, JSON_PRETTY_PRINT));
    }
}
