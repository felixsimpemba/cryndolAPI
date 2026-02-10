<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BorrowerResource\Pages;
use App\Filament\Resources\BorrowerResource\RelationManagers;
use App\Models\Borrower;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BorrowerResource extends Resource
{
    protected static ?string $model = Borrower::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('fullName')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phoneNumber')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nrc_number')
                    ->maxLength(50),
                Forms\Components\TextInput::make('tpin_number')
                    ->maxLength(50),
                Forms\Components\TextInput::make('passport_number')
                    ->maxLength(50),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('date_of_birth'),
                Forms\Components\TextInput::make('gender'),
                Forms\Components\TextInput::make('marital_status'),
                Forms\Components\TextInput::make('employment_status'),
                Forms\Components\TextInput::make('employer_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('monthly_income')
                    ->numeric(),
                Forms\Components\TextInput::make('risk_segment'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fullName')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phoneNumber')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nrc_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tpin_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('passport_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('marital_status'),
                Tables\Columns\TextColumn::make('employment_status'),
                Tables\Columns\TextColumn::make('employer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('monthly_income')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('risk_segment'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListBorrowers::route('/'),
            'create' => Pages\CreateBorrower::route('/create'),
            'edit' => Pages\EditBorrower::route('/{record}/edit'),
        ];
    }
}
