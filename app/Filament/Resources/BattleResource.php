<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BattleResource\Pages;
use App\Models\Battle;
use App\Models\TelegramUser;
use App\Models\Tournament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BattleResource extends Resource
{
    protected static ?string $model = Battle::class;
    protected static ?string $navigationIcon = 'heroicon-o-sword-clash';
    protected static ?string $navigationGroup = 'Game Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Battle Details')
                    ->schema([
                        Forms\Components\Select::make('player1_id')
                            ->label('Player 1')
                            ->options(TelegramUser::all()->pluck('first_name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('player2_id')
                            ->label('Player 2')
                            ->options(TelegramUser::all()->pluck('first_name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('winner_id')
                            ->label('Winner')
                            ->options(TelegramUser::all()->pluck('first_name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('battle_type')
                            ->options([
                                'pvp' => 'Player vs Player',
                                'pve' => 'Player vs Environment',
                                'tournament' => 'Tournament'
                            ])
                            ->required(),

                        Forms\Components\Select::make('battle_style')
                            ->options([
                                'funny' => 'Funny Mode',
                                'hardcore' => 'Hardcore Mode'
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('card_count')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(50),

                        Forms\Components\TextInput::make('total_pot')
                            ->numeric()
                            ->required()
                            ->label('Total Charlie Points'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled'
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('is_risk_mode')
                            ->label('Risk Mode (NFT Loss)'),

                        Forms\Components\Select::make('tournament_id')
                            ->label('Tournament')
                            ->options(Tournament::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('transaction_hash')
                            ->label('Blockchain Transaction Hash')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Round Results')
                    ->schema([
                        Forms\Components\KeyValue::make('round_results')
                            ->label('Round by Round Results')
                            ->nullable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('player1.first_name')
                    ->label('Player 1')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('player2.first_name')
                    ->label('Player 2')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Waiting...'),

                Tables\Columns\TextColumn::make('winner.first_name')
                    ->label('Winner')
                    ->searchable()
                    ->sortable()
                    ->placeholder('TBD'),

                Tables\Columns\BadgeColumn::make('battle_type')
                    ->colors([
                        'primary' => 'pvp',
                        'warning' => 'pve',
                        'success' => 'tournament',
                    ]),

                Tables\Columns\BadgeColumn::make('battle_style')
                    ->colors([
                        'primary' => 'funny',
                        'danger' => 'hardcore',
                    ]),

                Tables\Columns\TextColumn::make('card_count')
                    ->label('Cards')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_pot')
                    ->label('Total Pot')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' CP')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\IconColumn::make('is_risk_mode')
                    ->label('Risk Mode')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('battle_type')
                    ->options([
                        'pvp' => 'PvP',
                        'pve' => 'PvE',
                        'tournament' => 'Tournament'
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled'
                    ]),

                Tables\Filters\Filter::make('is_risk_mode')
                    ->query(fn (Builder $query) => $query->where('is_risk_mode', true))
                    ->label('Risk Mode Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBattles::route('/'),
            'create' => Pages\CreateBattle::route('/create'),
            'view' => Pages\ViewBattle::route('/{record}'),
            'edit' => Pages\EditBattle::route('/{record}/edit'),
        ];
    }
}
