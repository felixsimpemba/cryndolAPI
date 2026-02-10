<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('business_id')
                    ->maxLength(36),
                Forms\Components\TextInput::make('fullName')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phoneNumber')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('otp_code')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('otp_expires_at'),
                Forms\Components\TextInput::make('role')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('is_super_user')
                    ->required(),
                Forms\Components\Toggle::make('acceptTerms')
                    ->required(),
                Forms\Components\TextInput::make('working_capital')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Toggle::make('email_notifications')
                    ->required(),
                Forms\Components\Toggle::make('payment_reminders')
                    ->required(),
                Forms\Components\Toggle::make('marketing_updates')
                    ->required(),
                Forms\Components\DateTimePicker::make('last_login'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fullName')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phoneNumber')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('otp_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('otp_expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\IconColumn::make('is_super_user')
                    ->boolean(),
                Tables\Columns\IconColumn::make('acceptTerms')
                    ->boolean(),
                Tables\Columns\TextColumn::make('working_capital')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_notifications')
                    ->boolean(),
                Tables\Columns\IconColumn::make('payment_reminders')
                    ->boolean(),
                Tables\Columns\IconColumn::make('marketing_updates')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_login')
                    ->dateTime()
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
