<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('borrower_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('principal')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('interestRate')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('termMonths')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('term_unit')
                    ->required(),
                Forms\Components\DatePicker::make('startDate')
                    ->required(),
                Forms\Components\TextInput::make('status'),
                Forms\Components\TextInput::make('totalPaid')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('loan_product_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('borrower_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('principal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('interestRate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('termMonths')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('term_unit'),
                Tables\Columns\TextColumn::make('startDate')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('totalPaid')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('loan_product_id')
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
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
