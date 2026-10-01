<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Webshop';

    protected static ?string $navigationLabel = 'Blog';

    protected static ?string $modelLabel = 'blogbericht';

    protected static ?string $pluralModelLabel = 'blogberichten';

    protected static ?string $slug = 'blog';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make([
                    Section::make()->schema([
                        TextInput::make('title')->label('Titel')->required()->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, string $operation) => $operation === 'create' && blank($get('handle')) ? $set('handle', Str::slug((string) $state)) : null),
                        PageResource::editor('body', 'articles')->label('Inhoud'),
                        Textarea::make('excerpt')->label('Samenvatting')->rows(3)->helperText('Wordt getoond in het blogoverzicht. Laat leeg om automatisch de eerste zinnen te gebruiken.'),
                    ]),
                    Section::make('Zoekmachines (SEO)')->schema([
                        TextInput::make('handle')->label('URL')->prefix(fn (Get $get) => url('/blogs/'.($get('blog') ?: 'news')).'/')->required()->rule('regex:/^[a-z0-9-]+$/')
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, Get $get) => $rule->where('blog', $get('blog') ?: 'news')),
                        TextInput::make('seo_title')->label('Paginatitel')->maxLength(70),
                        Textarea::make('seo_description')->label('Meta-omschrijving')->rows(2)->maxLength(320),
                    ])->collapsible()->collapsed(),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Zichtbaarheid')->schema([
                        Toggle::make('is_published')->label('Gepubliceerd')->default(true),
                        DateTimePicker::make('published_at')->label('Publicatiedatum')->default(now())->native(false)->seconds(false),
                    ]),
                    Section::make('Afbeelding')->schema([
                        FileUpload::make('image')->hiddenLabel()->image()->disk('public')->directory('articles')->imageEditor(),
                    ]),
                    Section::make('Organisatie')->schema([
                        TextInput::make('author')->label('Auteur'),
                        TagsInput::make('tags')->label('Labels')->helperText('Het label "recept" toont het bericht op de startpagina.'),
                        TextInput::make('blog')->label('Blog')->default('news')->required()->helperText('Standaard "news" (de URL /blogs/news).'),
                    ]),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->imageSize(44),
                TextColumn::make('title')->label('Titel')->searchable()->weight('bold')->description(fn (Article $record) => $record->url()),
                TextColumn::make('tags')->label('Labels')->badge()->color('gray'),
                TextColumn::make('published_at')->label('Gepubliceerd')->date('j M Y')->sortable(),
                IconColumn::make('is_published')->label('Zichtbaar')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')->label('Bekijk')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')->url(fn (Article $record) => url($record->url()), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/nieuw'),
            'edit' => EditArticle::route('/{record}'),
        ];
    }
}
