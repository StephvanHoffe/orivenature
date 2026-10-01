<?php

namespace App\Filament\Pages\Settings;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Basis voor instellingenpagina's. Velden heten "groep.sleutel" (bijv. store.name)
 * en worden opgeslagen in de tabel settings.
 */
abstract class SettingsPage extends Page
{
    /** @var array<int, string> */
    protected static array $groups = [];

    protected static string|UnitEnum|null $navigationGroup = 'Instellingen';

    public ?array $data = [];

    abstract protected function fields(): array;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canManageSettings();
    }

    public function mount(): void
    {
        $values = [];
        foreach (static::$groups as $group) {
            $values[$group] = settings()->group($group);
        }
        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->fields())->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Opslaan')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky(),
                ]),
        ]);
    }

    protected function beforeSave(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        $data = $this->beforeSave($this->form->getState());
        foreach (static::$groups as $group) {
            $stored = (array) json_decode((string) Setting::find($group)?->value, true);
            settings()->setGroup($group, array_merge($stored, (array) ($data[$group] ?? [])));
        }
        Notification::make()->success()->title('Instellingen opgeslagen')->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected static function helpIcon(): Heroicon
    {
        return Heroicon::OutlinedInformationCircle;
    }
}
