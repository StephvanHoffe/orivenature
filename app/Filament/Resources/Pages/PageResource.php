<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Webshop';

    protected static ?string $navigationLabel = "Pagina's";

    protected static ?string $modelLabel = 'pagina';

    protected static ?string $pluralModelLabel = "pagina's";

    protected static ?string $slug = 'paginas';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function editor(string $name, string $directory): RichEditor
    {
        return RichEditor::make($name)->fileAttachmentsDisk('public')->fileAttachmentsDirectory($directory)
            ->toolbarButtons([['bold', 'italic', 'underline', 'strike', 'link'], ['h2', 'h3'], ['alignStart', 'alignCenter'], ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'], ['attachFiles', 'table'], ['undo', 'redo']]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make([
                    Section::make()->schema([
                        TextInput::make('title')->label('Titel')->required()->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, string $operation) => $operation === 'create' && blank($get('handle')) ? $set('handle', Str::slug((string) $state)) : null),
                        self::editor('body', 'pages')->label('Inhoud'),
                    ]),
                    Section::make('Zoekmachines (SEO)')->schema([
                        TextInput::make('handle')->label('URL')->prefix(url('/pages').'/')->required()->unique(ignoreRecord: true)->rule('regex:/^[a-z0-9-]+$/'),
                        TextInput::make('seo_title')->label('Paginatitel')->maxLength(70),
                        Textarea::make('seo_description')->label('Meta-omschrijving')->rows(2)->maxLength(320),
                    ])->collapsible()->collapsed(),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Zichtbaarheid')->schema([
                        Toggle::make('is_published')->label('Gepubliceerd')->default(true),
                    ]),
                    Section::make('Sjabloon')->schema([
                        Select::make('template')->hiddenLabel()->options(Page::TEMPLATES)->default('default')->required()
                            ->helperText('Met een formulier komen inzendingen binnen bij Klanten > Berichten.'),
                    ]),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')->label('Titel')->searchable()->sortable()->weight('bold')->description(fn (Page $record) => $record->url()),
                TextColumn::make('template')->label('Sjabloon')->formatStateUsing(fn ($state) => Page::TEMPLATES[$state] ?? $state)->toggleable(),
                IconColumn::make('is_published')->label('Zichtbaar')->boolean(),
                TextColumn::make('updated_at')->label('Bijgewerkt')->since()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')->label('Bekijk')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')->url(fn (Page $record) => url($record->url()), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/nieuw'),
            'edit' => EditPage::route('/{record}'),
        ];
    }
}
