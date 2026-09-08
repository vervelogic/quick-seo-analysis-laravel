<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentEntryResource\Pages;
use App\Models\ContentEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class ContentEntryResource extends Resource
{
    protected static ?string $model = ContentEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Blog Posts';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Content')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Article')
                        ->schema([
                            Forms\Components\TextInput::make('title')->required()->maxLength(255),
                            Forms\Components\TextInput::make('slug')->required()->maxLength(255),
                            Forms\Components\TextInput::make('legacy_url')->url()->maxLength(2048)->label('Old / Legacy URL'),
                            Forms\Components\Select::make('content_category_id')->relationship('category', 'name')->searchable()->preload()->label('Category'),
                            Forms\Components\Select::make('tags')->relationship('tags', 'name')->multiple()->searchable()->preload(),
                            Forms\Components\TextInput::make('author_name')->maxLength(255),
                            Forms\Components\Textarea::make('excerpt')->rows(4)->columnSpanFull(),
                            Forms\Components\RichEditor::make('content')->columnSpanFull()->fileAttachmentsDisk('public')->fileAttachmentsDirectory('blog-body'),
                            Forms\Components\FileUpload::make('featured_image')->image()->disk('public')->directory('blog-images')->visibility('public'),
                            Forms\Components\TextInput::make('featured_image_alt')->maxLength(255),
                            Forms\Components\Toggle::make('is_featured'),
                            Forms\Components\Select::make('status')->options([
                                ContentEntry::STATUS_DRAFT => 'Draft',
                                ContentEntry::STATUS_PUBLISHED => 'Published',
                                ContentEntry::STATUS_UNPUBLISHED => 'Unpublished',
                                ContentEntry::STATUS_SCHEDULED => 'Scheduled',
                            ])->required(),
                            Forms\Components\DateTimePicker::make('scheduled_for'),
                            Forms\Components\DateTimePicker::make('published_at'),
                            Forms\Components\DateTimePicker::make('modified_at'),
                        ])->columns(2),
                    Forms\Components\Tabs\Tab::make('SEO')
                        ->schema([
                            Forms\Components\TextInput::make('seo_title')->maxLength(255),
                            Forms\Components\Textarea::make('meta_description')->rows(3)->columnSpanFull(),
                            Forms\Components\Textarea::make('meta_keywords')->rows(2)->columnSpanFull(),
                            Forms\Components\TextInput::make('canonical_url')->url()->maxLength(2048),
                            Forms\Components\TextInput::make('robots')->maxLength(255),
                            Forms\Components\TextInput::make('og_title')->maxLength(255),
                            Forms\Components\Textarea::make('og_description')->rows(3)->columnSpanFull(),
                            Forms\Components\TextInput::make('og_image')->maxLength(2048),
                            Forms\Components\TextInput::make('twitter_title')->maxLength(255),
                            Forms\Components\Textarea::make('twitter_description')->rows(3)->columnSpanFull(),
                            Forms\Components\TextInput::make('twitter_image')->maxLength(2048),
                        ])->columns(2),
                    Forms\Components\Tabs\Tab::make('Schema')
                        ->schema([
                            Forms\Components\Select::make('schema_type')->options([
                                'BlogPosting' => 'BlogPosting',
                                'Article' => 'Article',
                                'FAQPage' => 'FAQPage',
                                'HowTo' => 'HowTo',
                                'BreadcrumbList' => 'BreadcrumbList',
                            ])->required(),
                            Forms\Components\Textarea::make('generated_json_ld')->rows(18)->columnSpanFull()->readOnly(),
                            Forms\Components\Textarea::make('custom_json_ld')->rows(18)->columnSpanFull()->label('Custom JSON-LD override'),
                        ])->columns(1),
                    Forms\Components\Tabs\Tab::make('Import')
                        ->schema([
                            Forms\Components\KeyValue::make('import_metadata')->columnSpanFull(),
                            Forms\Components\KeyValue::make('legacy_metadata')->columnSpanFull(),
                            Forms\Components\DateTimePicker::make('imported_at'),
                            Forms\Components\DateTimePicker::make('last_crawled_at'),
                        ])->columns(2),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'blog'))
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(60),
                Tables\Columns\TextColumn::make('slug')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->badge(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\IconColumn::make('is_featured')->boolean(),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('modified_at')->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('word_count')->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('reading_time_minutes')->label('Read')->suffix(' min')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    ContentEntry::STATUS_DRAFT => 'Draft',
                    ContentEntry::STATUS_PUBLISHED => 'Published',
                    ContentEntry::STATUS_UNPUBLISHED => 'Unpublished',
                    ContentEntry::STATUS_SCHEDULED => 'Scheduled',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->url(fn (ContentEntry $record): string => URL::signedRoute('blog.show', ['slug' => $record->slug, 'preview' => 1]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('duplicate')
                    ->action(function (ContentEntry $record): void {
                        $clone = $record->replicate(['slug', 'published_at', 'modified_at', 'imported_at', 'last_crawled_at']);
                        $clone->title = $record->title.' (Copy)';
                        $clone->slug = ContentEntry::uniqueSlug($clone->title, 'blog');
                        $clone->status = ContentEntry::STATUS_DRAFT;
                        $clone->legacy_url = null;
                        $clone->save();
                        $clone->tags()->sync($record->tags->modelKeys());
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentEntries::route('/'),
            'create' => Pages\CreateContentEntry::route('/create'),
            'view' => Pages\ViewContentEntry::route('/{record}'),
            'edit' => Pages\EditContentEntry::route('/{record}/edit'),
        ];
    }
}
