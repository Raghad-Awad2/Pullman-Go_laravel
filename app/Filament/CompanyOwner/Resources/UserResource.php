<?php

namespace App\Filament\CompanyOwner\Resources;

use App\Filament\CompanyOwner\Resources\UserResource\Pages;
use App\Filament\CompanyOwner\Resources\UserResource\RelationManagers;
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



        //  أيقونة في لوحة التحكم heroicon مكتبة
protected static ?string $navigationIcon = 'heroicon-o-identification';

    
    protected static ?string $navigationLabel = ' إدارة الموظفين';
    protected static ?string $pluralModelLabel = 'موظفين الشركة ';
    protected static ?string $modelLabel = 'موظف';






    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
        Forms\Components\Hidden::make('company_id')
            ->default(auth()->user()->company_id),




            Forms\Components\TextInput::make('name')->required()->label('الاسم'),

            Forms\Components\TextInput::make('email')->email()->required()->label('الايميل'),

            Forms\Components\TextInput::make('password')
                ->password()

                ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                ->label('كلمة المرور'),

                
            // حقل مخفي يضع ID الشركة الحالية تلقائياً
            Forms\Components\Hidden::make('company_id')
                ->default(auth()->user()->company_id),
            // حقل مخفي يضع الرتبة (موظف) تلقائياً
            Forms\Components\Hidden::make('role')
                ->default('staff'),
        

            ]);
    }
    


// مهمممممممممممم جداااا

// لضمان أن صاحب الشركة لا يرى الأدمن أو موظفي الشركات الأخرى
    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('company_id', auth()->user()->company_id) // جلب التابعين لنفس الشركة فقط
        ->where('role', 'staff'); // عرض الموظفين فقط
}








    public static function table(Table $table): Table
    {
        return $table
            ->columns([



            Tables\Columns\ImageColumn::make('avatar_url') 
                ->label('الصورة')
                ->circular()



// الدالة defaultImageUrl

                // العمود بيقرأ avatar_url بشكل أساسي.
                // الدالة بتعوض النقص بـ logo_url إذا كان الأول فاضي.

                //   اللي بيخلي الجدول يقرأ الدالة السحرية تبعت الموديل
                ->defaultImageUrl(fn ($record) => $record->getFilamentAvatarUrl()) 
                ->visibility(fn ($record) => true),





            Tables\Columns\TextColumn::make('name')
                ->label('اسم الموظف')
                ->searchable()
                ->icon('heroicon-m-user') // هاي الأيقونة الشخصية
                ->iconColor('primary')
                ->color('success'), // اللون الاخضر للموظف 


            Tables\Columns\TextColumn::make('email')
                ->label('البريد الإلكتروني')
                ->color('gray'), // لون رمادي


            Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإضافة')->dateTime('Y-m-d')
            ->color('success'),


            Tables\Columns\TextColumn::make('role')
                ->label('الرتبة')
                ->badge()
                ->color('warning') // اللون البرتقالي للموظف  
                



            ])
            
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
