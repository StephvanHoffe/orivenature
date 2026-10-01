<?php

namespace App\Filament\Pages\Settings;

use App\Models\Product;
use App\Support\SettingDefaults;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class HomepageSettings extends SettingsPage
{
    protected static array $groups = ['homepage'];

    protected static string|UnitEnum|null $navigationGroup = 'Webshop';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Startpagina';

    protected static ?string $title = 'Startpagina';

    protected static ?string $slug = 'webshop/startpagina';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_active;
    }

    private static function image(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)->label($label)->image()->disk('public')->directory('home')->imageEditor()->maxSize(10240);
    }

    /** Producten om uit te kiezen; een gekozen product dat niet meer bestaat blijft zichtbaar. */
    private static function products(mixed $current = null): array
    {
        $options = Product::orderBy('title')->pluck('title', 'handle')->all();
        foreach ((array) $current as $handle) {
            if (is_string($handle) && ! isset($options[$handle])) {
                $options[$handle] = $handle.' (niet gevonden)';
            }
        }

        return $options;
    }

    private static function heading(string $prefix = 'heading'): Grid
    {
        return Grid::make(3)->schema([
            TextInput::make($prefix.'_before')->label('Kop (begin)'),
            TextInput::make($prefix === 'heading' ? 'heading_highlight' : $prefix.'_italic')->label('Kop (accent)'),
            TextInput::make($prefix.'_after')->label('Kop (einde)'),
        ]);
    }

    private static function button(): Grid
    {
        return Grid::make(2)->schema([
            TextInput::make('button_label')->label('Knoptekst'),
            TextInput::make('button_link')->label('Knoplink')->placeholder('/collections/… of #essence'),
        ]);
    }

    protected function fields(): array
    {
        $icons = ThemeSettings::icons();

        return [
            Builder::make('homepage.sections')->hiddenLabel()->collapsible()->collapsed()->reorderableWithDragAndDrop()->cloneable()
                ->addActionLabel('Sectie toevoegen')->blockNumbers(false)
                ->blocks([
                    Block::make('hero')->label('Openingsbeeld (hero)')->icon(Heroicon::OutlinedSparkles)->schema([
                        TextInput::make('eyebrow')->label('Bovenregel'),
                        Grid::make(3)->schema([
                            TextInput::make('heading_before')->label('Kop (begin)'),
                            TextInput::make('heading_highlight')->label('Kop (onderstreept woord)'),
                            TextInput::make('heading_after')->label('Kop (einde)'),
                        ]),
                        Textarea::make('text')->label('Tekst')->rows(3),
                        self::button(),
                        Grid::make(2)->schema([
                            self::image('image_1', 'Foto links'),
                            self::image('image_2', 'Foto rechts'),
                            Select::make('image_1_position')->label('Uitsnede foto links')->options(['left' => 'Links', 'center' => 'Midden', 'right' => 'Rechts']),
                            Select::make('image_2_position')->label('Uitsnede foto rechts')->options(['left' => 'Links', 'center' => 'Midden', 'right' => 'Rechts']),
                        ]),
                        FileUpload::make('floaties')->label('Zwevende verpakkingen')->image()->multiple()->reorderable()->disk('public')->directory('home'),
                        Select::make('flavours')->label('"Kies je smaak"-knoppen')->options(fn ($state) => self::products($state))->multiple(),
                        Grid::make(2)->schema([
                            TextInput::make('flavour_label')->label('Tekst bij smaken'),
                            TextInput::make('badge_text')->label('Draaiende stickertekst'),
                        ]),
                        Repeater::make('trust')->label('Voordelen onder de knop')->grid(3)->schema([
                            Select::make('icon')->label('Icoon')->options($icons),
                            TextInput::make('text')->label('Tekst'),
                        ]),
                    ]),
                    Block::make('ribbons')->label('Bewegende linten')->icon(Heroicon::OutlinedArrowsRightLeft)->schema([
                        TagsInput::make('top')->label('Bovenste lint'),
                        TagsInput::make('bottom')->label('Onderste lint'),
                    ]),
                    Block::make('essences')->label('Uitgelichte producten')->icon(Heroicon::OutlinedSquares2x2)->schema([
                        Grid::make(2)->schema([
                            TextInput::make('heading')->label('Kop'),
                            TextInput::make('heading_italic')->label('Kop (cursief)'),
                        ]),
                        Select::make('products')->label('Producten')->options(fn ($state) => self::products($state))->multiple()->required(),
                        self::button(),
                        TagsInput::make('assurance')->label('Voordelenbalk'),
                        TextInput::make('sticky_title')->label('Titel van de plakkende balk (mobiel)'),
                    ]),
                    Block::make('launch')->label('Productlancering met video')->icon(Heroicon::OutlinedVideoCamera)->schema([
                        TextInput::make('eyebrow')->label('Label'),
                        Grid::make(3)->schema([
                            TextInput::make('heading_before')->label('Kop (begin)'),
                            TextInput::make('heading_italic')->label('Kop (cursief)'),
                            TextInput::make('heading_after')->label('Kop (einde)'),
                        ]),
                        Textarea::make('text')->label('Tekst')->rows(3),
                        Grid::make(2)->schema([
                            Select::make('product')->label('Product')->options(fn ($state) => self::products($state)),
                            TextInput::make('button_label')->label('Knoptekst'),
                        ]),
                        Grid::make(3)->schema([
                            FileUpload::make('video')->label('Video (mp4)')->disk('public')->directory('home')->acceptedFileTypes(['video/mp4'])->maxSize(51200),
                            self::image('poster', 'Afbeelding vóór de video'),
                            self::image('floaty', 'Zwevende verpakking'),
                        ]),
                        TextInput::make('badge_text')->label('Draaiende stickertekst'),
                    ]),
                    Block::make('tiles')->label('Categorietegels')->icon(Heroicon::OutlinedRectangleGroup)->schema([
                        Repeater::make('tiles')->hiddenLabel()->grid(2)->reorderable()->schema([
                            TextInput::make('title')->label('Titel')->required(),
                            self::image('image', 'Foto'),
                            TextInput::make('link')->label('Link'),
                            TextInput::make('cta')->label('Knoptekst'),
                        ]),
                    ]),
                    Block::make('story')->label('Ons verhaal (tekst)')->icon(Heroicon::OutlinedChatBubbleBottomCenterText)->schema([
                        TextInput::make('eyebrow')->label('Bovenregel'),
                        Textarea::make('text')->label('Tekst')->rows(3),
                        self::button(),
                    ]),
                    Block::make('origin')->label('Grote foto met tekst')->icon(Heroicon::OutlinedPhoto)->schema([
                        self::image('image', 'Foto'),
                        Grid::make(2)->schema([
                            TextInput::make('heading')->label('Kop'),
                            TextInput::make('heading_italic')->label('Kop (cursief)'),
                        ]),
                        Textarea::make('text')->label('Tekst')->rows(3),
                        self::button(),
                    ]),
                    Block::make('lovers')->label("Foto's van klanten")->icon(Heroicon::OutlinedHeart)->schema([
                        TextInput::make('heading')->label('Kop'),
                        self::button(),
                        FileUpload::make('photos')->label("Foto's")->image()->multiple()->reorderable()->disk('public')->directory('home'),
                    ]),
                    Block::make('loyalty')->label('Spaarprogramma')->icon(Heroicon::OutlinedGift)->schema([
                        TextInput::make('eyebrow')->label('Bovenregel'),
                        Grid::make(2)->schema([
                            TextInput::make('heading')->label('Kop'),
                            TextInput::make('heading_italic')->label('Kop (cursief)'),
                        ]),
                        Textarea::make('text')->label('Tekst')->rows(2),
                        Repeater::make('steps')->label('Stappen')->grid(3)->helperText('[bonus], [points] en [rate] worden automatisch ingevuld.')->schema([
                            Select::make('icon')->label('Icoon')->options($icons),
                            TextInput::make('title')->label('Titel'),
                            TextInput::make('text')->label('Tekst'),
                        ]),
                    ]),
                    Block::make('faq')->label('Veelgestelde vragen')->icon(Heroicon::OutlinedQuestionMarkCircle)->schema([
                        TextInput::make('heading')->label('Kop'),
                        Grid::make(2)->schema([
                            TextInput::make('help_title')->label('Kop hulpblok'),
                            TextInput::make('help_text')->label('Tekst hulpblok'),
                        ]),
                        Repeater::make('questions')->label('Vragen')->reorderableWithDragAndDrop()->collapsible()->collapsed()
                            ->itemLabel(fn (array $state) => $state['question'] ?? null)->schema([
                                TextInput::make('question')->label('Vraag')->required(),
                                RichEditor::make('answer')->label('Antwoord')->toolbarButtons([['bold', 'italic', 'link'], ['bulletList']]),
                            ]),
                    ]),
                    Block::make('blog')->label('Blogberichten')->icon(Heroicon::OutlinedNewspaper)->schema([
                        Grid::make(2)->schema([
                            TextInput::make('heading')->label('Kop'),
                            TextInput::make('heading_italic')->label('Kop (cursief)'),
                            TextInput::make('tag')->label('Alleen berichten met label')->placeholder('bijv. recept'),
                            TextInput::make('count')->label('Aantal')->integer()->minValue(1)->maxValue(12),
                            TextInput::make('button_label')->label('Knoptekst'),
                            TextInput::make('blog')->label('Blog')->default('news'),
                        ]),
                    ]),
                    Block::make('newsletter')->label('Nieuwsbrief')->icon(Heroicon::OutlinedEnvelope)->schema([
                        self::image('image', 'Foto'),
                        Grid::make(2)->schema([
                            TextInput::make('heading_italic')->label('Kop (cursief begin)'),
                            TextInput::make('heading')->label('Kop'),
                            TextInput::make('sticker_big')->label('Sticker groot'),
                            TextInput::make('sticker_small')->label('Sticker klein'),
                        ]),
                        Textarea::make('text')->label('Tekst')->rows(2),
                    ]),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label('Bekijk startpagina')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')->url(url('/'), shouldOpenInNewTab: true),
            Action::make('reset')->label('Standaard herstellen')->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->requiresConfirmation()->modalDescription('Alle secties worden teruggezet naar het originele ontwerp.')
                ->action(function () {
                    settings()->setGroup('homepage', ['sections' => SettingDefaults::homepageSections()]);
                    $this->mount();
                    Notification::make()->success()->title('Startpagina hersteld')->send();
                }),
        ];
    }
}
