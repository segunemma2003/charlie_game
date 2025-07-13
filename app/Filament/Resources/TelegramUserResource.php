<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramUserResource\Pages;
use App\Models\TelegramUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TelegramUserResource extends Resource
{
    protected static ?string $model = TelegramUser::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Telegram Information')
                    ->schema([
                        Forms\Components\TextInput::make('telegram_id')
                            ->label('Telegram ID')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('username')
                            ->label('Telegram Username')
                            ->nullable(),

                        Forms\Components\TextInput::make('first_name')
                            ->required(),

                        Forms\Components\TextInput::make('last_name')
                            ->nullable(),

                        Forms\Components\TextInput::make('wallet_address')
                            ->label('Crypto Wallet Address')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Game Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('charlie_points')
                            ->label('Charlie Points')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('moon_pot_points')
                            ->label('Moon Pot Points')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('total_wins')
                            ->label('Total Wins')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('total_losses')
                            ->label('Total Losses')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Select::make('skill_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert'
                            ])
                            ->default('beginner')
                            ->required(),

                        Forms\Components\Select::make('battle_style_preference')
                            ->label('Preferred Battle Style')
                            ->options([
                                'funny' => 'Funny Mode',
                                'hardcore' => 'Hardcore Mode'
                            ])
                            ->default('funny')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Character Attributes')
                    ->schema([
                        Forms\Components\KeyValue::make('character_attributes')
                            ->label('Character Attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Attribute Value')
                            ->reorderable()
                            ->addActionLabel('Add Attribute')
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn (TelegramUser $record) =>
                        $record->first_name . ($record->last_name ? ' ' . $record->last_name : '')
                    )
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->formatStateUsing(fn ($state) => $state ? '@' . $state : 'No username')
                    ->searchable(),

                Tables\Columns\TextColumn::make('charlie_points')
                    ->label('Charlie Points')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_wins')
                    ->label('Wins')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_losses')
                    ->label('Losses')
                    ->sortable(),

                Tables\Columns\TextColumn::make('win_rate')
                    ->label('Win Rate')
                    ->getStateUsing(function (TelegramUser $record) {
                        $total = $record->total_wins + $record->total_losses;
                        return $total > 0 ? round(($record->total_wins / $total) * 100, 1) . '%' : '0%';
                    })
                    ->color(fn ($state) => match (true) {
                        (float) str_replace('%', '', $state) >= 70 => 'success',
                        (float) str_replace('%', '', $state) >= 50 => 'warning',
                        default => 'danger'
                    }),

                Tables\Columns\BadgeColumn::make('skill_level')
                    ->colors([
                        'secondary' => 'beginner',
                        'primary' => 'intermediate',
                        'warning' => 'advanced',
                        'danger' => 'expert',
                    ]),

                Tables\Columns\IconColumn::make('has_wallet')
                    ->label('Wallet')
                    ->getStateUsing(fn (TelegramUser $record) => !is_null($record->wallet_address))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('skill_level')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                        'expert' => 'Expert'
                    ]),

                Tables\Filters\SelectFilter::make('battle_style_preference')
                    ->label('Battle Style')
                    ->options([
                        'funny' => 'Funny Mode',
                        'hardcore' => 'Hardcore Mode'
                    ]),

                Tables\Filters\Filter::make('has_wallet')
                    ->query(fn (Builder $query) => $query->whereNotNull('wallet_address'))
                    ->label('Has Wallet'),

                Tables\Filters\Filter::make('high_points')
                    ->query(fn (Builder $query) => $query->where('charlie_points', '>=', 10000))
                    ->label('High Rollers (10k+ CP)'),

                Tables\Filters\Filter::make('active_players')
                    ->query(fn (Builder $query) => $query->where(function ($q) {
                        $q->where('total_wins', '>', 0)->orWhere('total_losses', '>', 0);
                    }))
                    ->label('Active Players'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_points')
                    ->label('Add Points')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Charlie Points to Add')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (TelegramUser $record, array $data) {
                        $record->increment('charlie_points', $data['points']);
                        // Log the transaction
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramUsers::route('/'),
            'create' => Pages\CreateTelegramUser::route('/create'),
            'view' => Pages\ViewTelegramUser::route('/{record}'),
            'edit' => Pages\EditTelegramUser::route('/{record}/edit'),
        ];
    }
}
