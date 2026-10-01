<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Winkelinstellingen als geneste sleutels ("store.name", "loyalty.points_per_euro").
 * Alles staat in de tabel settings als JSON per groep en wordt gecachet.
 */
class Settings
{
    private ?array $values = null;

    public function all(): array
    {
        if ($this->values === null) {
            $stored = [];
            try {
                if (Schema::hasTable('settings')) {
                    $stored = Cache::rememberForever('settings', fn () => Setting::all()
                        ->mapWithKeys(fn (Setting $s) => [$s->key => json_decode((string) $s->value, true)])
                        ->all());
                }
            } catch (\Throwable) {
                $stored = [];
            }
            $this->values = array_replace_recursive(SettingDefaults::all(), array_filter($stored, fn ($v) => $v !== null));
            // Lijsten (zoals de startpagina-secties) niet samenvoegen maar vervangen
            foreach ($stored as $group => $value) {
                foreach ((array) $value as $k => $v) {
                    if (is_array($v) && array_is_list($v)) {
                        $this->values[$group][$k] = $v;
                    }
                }
            }
        }

        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    public function group(string $group): array
    {
        return (array) ($this->all()[$group] ?? []);
    }

    public function setGroup(string $group, array $values): void
    {
        Setting::updateOrCreate(['key' => $group], ['value' => json_encode($values, JSON_UNESCAPED_UNICODE)]);
        $this->flush();
    }

    public function set(string $key, mixed $value): void
    {
        [$group, $rest] = array_pad(explode('.', $key, 2), 2, null);
        $current = Setting::find($group);
        $values = $current ? (array) json_decode((string) $current->value, true) : [];
        if ($rest === null) {
            $values = $value;
        } else {
            Arr::set($values, $rest, $value);
        }
        $this->setGroup($group, $values);
    }

    public function flush(): void
    {
        Cache::forget('settings');
        $this->values = null;
    }
}
