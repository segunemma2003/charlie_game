<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PnftCardResource\Pages;
use App\Models\PnftCard;
use App\Models\TelegramUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PnftCardResource extends Resource
{
    protected static ?string $model = PnftCard::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Game Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Card Information')
                    ->schema([
                        Forms\Components\Select::make('telegram_user_id')
                            ->label('Owner')
                            ->options(TelegramUser::all()->pluck('first_name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('Card Image')
                            ->image()
                            ->directory('cards')
                            ->required(),

                        Forms\Components\TextInput::make('token_id')
                            ->label('Blockchain Token ID')
                            ->nullable(),

                        Forms\Components\TextInput::make('contract_address')
                            ->label('Smart Contract Address')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Game Stats')
                    ->schema([
                        Forms\Components\TextInput::make('charlie_points')
                            ->label('Charlie Points Value')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\Select::make('rarity')
                            ->options([
                                'common' => 'Common',
                                'uncommon' => 'Uncommon',
                                'rare' => 'Rare',
                                'epic' => 'Epic',
                                'legendary' => 'Legendary'
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('power_level')
                            ->label('Power Level')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1)
                            ->maxValue(1000),

                        Forms\Components\Toggle::make('is_locked')
                            ->label('Locked in Battle')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Card Attributes')
                    ->schema([
                        Forms\Components\KeyValue::make('attributes')
                            ->label('Special Attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Attribute Value')
                            ->reorderable()
                            ->addActionLabel('Add Attribute'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image')
                    ->size(60)
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('telegramUser.first_name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('rarity')
                    ->colors([
                        'secondary' => 'common',
                        'primary' => 'uncommon',
                        'warning' => 'rare',
                        'danger' => 'epic',
                        'success' => 'legendary',
                    ]),

                Tables\Columns\TextColumn::make('charlie_points')
                    ->label('Charlie Points')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' CP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('power_level')
                    ->label('Power')
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 200 => 'danger',
                        $state >= 150 => 'warning',
                        $state >= 100 => 'success',
                        default => 'gray'
                    }),

                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean(),

                Tables\Columns\TextColumn::make('token_id')
                    ->label('Token ID')
                    ->placeholder('Not minted')
                    ->limit(10),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rarity')
                    ->options([
                        'common' => 'Common',
                        'uncommon' => 'Uncommon',
                        'rare' => 'Rare',
                        'epic' => 'Epic',
                        'legendary' => 'Legendary'
                    ]),

                Tables\Filters\SelectFilter::make('telegram_user_id')
                    ->label('Owner')
                    ->options(TelegramUser::all()->pluck('first_name', 'id'))
                    ->searchable(),

                Tables\Filters\Filter::make('is_locked')
                    ->query(fn (Builder $query) => $query->where('is_locked', true))
                    ->label('Locked Cards Only'),

                Tables\Filters\Filter::make('high_value')
                    ->query(fn (Builder $query) => $query->where('charlie_points', '>=', 1000))
                    ->label('High Value Cards (1000+ CP)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mint_nft')
                    ->label('Mint NFT')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->visible(fn (PnftCard $record) => is_null($record->token_id))
                    ->action(function (PnftCard $record) {
                        // Add NFT minting logic here
                        $record->update(['token_id' => 'pending_mint_' . time()]);
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
            'index' => Pages\ListPnftCards::route('/'),
            'create' => Pages\CreatePnftCard::route('/create'),
            'view' => Pages\ViewPnftCard::route('/{record}'),
            'edit' => Pages\EditPnftCard::route('/{record}/edit'),
        ];
    }
}
