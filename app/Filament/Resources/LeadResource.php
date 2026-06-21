<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('scan_id')->relationship('scan', 'normalized_url')->searchable()->required(),
            Forms\Components\Select::make('status')->options(self::statusOptions())->default('new')->required(),
            Forms\Components\Select::make('assigned_user_id')->label('Assigned user')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))->searchable()->preload(),
            Forms\Components\TextInput::make('name')->maxLength(120),
            Forms\Components\TextInput::make('email')->email()->required()->maxLength(190),
            Forms\Components\TextInput::make('phone')->maxLength(60),
            Forms\Components\TextInput::make('company_name')->maxLength(160),
            Forms\Components\DateTimePicker::make('last_contacted_at')->seconds(false),
            Forms\Components\TextInput::make('source_report_uuid')->maxLength(36),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('company_name')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'new' => 'gray',
                    'contacted' => 'info',
                    'qualified' => 'warning',
                    'proposal' => 'primary',
                    'won' => 'success',
                    'lost' => 'danger',
                    default => 'gray',
                })->sortable(),
                Tables\Columns\TextColumn::make('assignedUser.name')->label('Owner')->sortable(),
                Tables\Columns\TextColumn::make('scan.normalized_url')->label('Scanned URL')->limit(42),
                Tables\Columns\TextColumn::make('last_contacted_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('assigned_user_id')->label('Assigned user')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    private static function statusOptions(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
    }
}
