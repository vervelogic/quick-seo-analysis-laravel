<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentRedirectResource\Pages;
use App\Models\ContentRedirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContentRedirectResource extends Resource
{
    protected static ?string $model = ContentRedirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Redirects';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('content_entry_id')->relationship('entry', 'title')->searchable()->preload(),
            Forms\Components\TextInput::make('from_path')->required()->maxLength(2048),
            Forms\Components\TextInput::make('to_path')->required()->maxLength(2048),
            Forms\Components\TextInput::make('status_code')->numeric()->default(301),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_path')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('to_path')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('status_code')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentRedirects::route('/'),
            'create' => Pages\CreateContentRedirect::route('/create'),
            'edit' => Pages\EditContentRedirect::route('/{record}/edit'),
        ];
    }
}
