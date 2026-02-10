<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanProductResource\Pages;
use App\Filament\Resources\LoanProductResource\RelationManagers;
use App\Models\LoanProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanProductResource extends Resource
{
    protected static ?string $model = LoanProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('interest_type')
                    ->required(),
                Forms\Components\TextInput::make('interest_rate')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('min_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('max_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('min_term')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('max_term')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('term_unit')
                    ->required(),
                Forms\Components\TextInput::make('repayment_frequency')
                    ->required(),
                Forms\Components\TextInput::make('grace_period')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('processing_fee_type')
                    ->required(),
                Forms\Components\TextInput::make('processing_fee_value')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('late_penalty_type')
                    ->required(),
                Forms\Components\TextInput::make('late_penalty_value')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\TextInput::make('user_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('interest_type'),
                Tables\Columns\TextColumn::make('interest_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_term')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_term')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('term_unit'),
                Tables\Columns\TextColumn::make('repayment_frequency'),
                Tables\Columns\TextColumn::make('grace_period')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processing_fee_type'),
                Tables\Columns\TextColumn::make('processing_fee_value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('late_penalty_type'),
                Tables\Columns\TextColumn::make('late_penalty_value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListLoanProducts::route('/'),
            'create' => Pages\CreateLoanProduct::route('/create'),
            'edit' => Pages\EditLoanProduct::route('/{record}/edit'),
        ];
    }
}
