<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Applies database-backed admin settings on top of file/.env config so a
 * non-technical operator can manage the app from the admin panel. Runs
 * defensively: if the settings table does not yet exist (fresh install,
 * pre-migration), it silently no-ops.
 */
class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Map of setting key => config path it overrides.
     */
    protected array $configMap = [
        'app_name' => 'app.name',
        'brand_primary' => 'branding.primary',
        'brand_logo' => 'branding.logo',
        // Email / storage / OAuth / Stripe overrides are added in later phases.
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole() && ! $this->settingsTableReady()) {
            return;
        }

        try {
            $settings = Setting::all();
        } catch (Throwable) {
            return; // table not migrated yet
        }

        foreach ($this->configMap as $key => $configPath) {
            if (array_key_exists($key, $settings) && $settings[$key] !== null) {
                config([$configPath => $settings[$key]]);
            }
        }
    }

    protected function settingsTableReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }
}
