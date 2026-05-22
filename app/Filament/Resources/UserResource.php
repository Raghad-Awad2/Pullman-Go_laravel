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

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Filament\Tables\Columns\TextColumn;


use Filament\Forms\Get;

class UserResource extends Resource
{
    protected static ?int $navigationSort = 1; // الرقم 1 بيخليه أول خيار بالقائمة


    protected static ?string $model = User::class;


    //  أيقونة في لوحة التحكم heroicon مكتبة
protected static ?string $navigationIcon = 'heroicon-o-identification';

    
    protected static ?string $navigationLabel = ' إدارة المستخدمين';
    protected static ?string $pluralModelLabel = 'مستخدمين الشركات';
    protected static ?string $modelLabel = 'مستخدم';



    public static function form(Form $form): Form
    {
return $form
        ->schema([
            TextInput::make('name')
                ->required()
                ->label('الاسم'),

            TextInput::make('email')
                ->email()
                ->required()
                ->label('البريد الإلكتروني')

                ->extraInputAttributes([
                    'autocomplete' => 'no-fill-thanks', 
                    'readonly' => true, // خدعة: خليه للقراءة فقط
                    'onfocus' => "this.removeAttribute('readonly');", // وشيلها بس يضغط عليه
                ]),



            TextInput::make('password')
                ->password()
                ->label('كلمة المرور')
                // التشفير التلقائي للباسورد عند الحفظ
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->required(fn (string $context): bool => $context === 'create')
                
                ->extraInputAttributes([
                    'autocomplete' => 'new-password',
                    'readonly' => true,
                    'onfocus' => "this.removeAttribute('readonly');",
                ]),


                
            // الحقل السحري 1: الرتبة
            Select::make('role')
                ->label('الرتبة')
                ->options([
                    'admin' => 'أدمن النظام',
                    'owner' => 'صاحب شركة',
                    'staff' => 'موظف',

                    'passenger' => 'مسافر',
                ])
                ->required(),

                

                // 2. حقل رقم الهاتف (يظهر فقط للمسافر)
            TextInput::make('phone')
                    ->label('رقم هاتف المسافر')
                    ->tel()
                    ->placeholder('')
                    // يظهر فقط إذا كان الـ role هو passenger
                    ->visible(fn (Get $get): bool => $get('role') === 'passenger')
                    // بصير إجباري فقط إذا كان ظاهر
                    ->required(fn (Get $get): bool => $get('role') === 'passenger')
                    ->maxLength(10),
                
        ]);
           
    }






    

    public static function table(Table $table): Table
    {
return $table
        ->columns([
            TextColumn::make('name')->label('اسم المستخدم')->searchable()
            ->icon('heroicon-m-user') // هاي الأيقونة الشخصية
            ->iconColor('primary')
            ->color('success'), // لون اخضر



            TextColumn::make('email')->label('البريد الإلكتروني')
            ->color('gray'), // لون رمادي


            TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                     ->color('success') // لون اخضر

                   
                    // وبشتغل فوراً بدون ريفريش لأنه بفحص البيانات مش الرابط
                    ->visible(function ($livewire) {
                        
                        // إذا كنا بتبويب "طاقم العمل" أخفيه تماماً
                        if ($livewire->activeTab === 'staff') {
                            return false;
                        }
                        return true;

                        }),

                   



            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنضمام')
                ->dateTime('Y-m-d H:i') // هون بحدد التنسيق 
                ->sortable() // مشان ترتب الشركات من الأحدث للأقدم
                ->color('success'), // لون اخضر






            Tables\Columns\TextColumn::make('role')
                ->label('الرتبة')
                ->badge() // السطر هذا بحول النص لـ Badge
                ->color(fn (string $state): string => match ($state) {
                    'owner' => 'info', // اللون الازرق لصاحب الشركة
                    'staff' => 'warning',    // اللون البرتقالي  للموظف
                    'admin' => 'success', // اللون الاخضر للأدمن حسب طلبك

                    'passenger'=>'danger', //اللون الابيض للمسافرين
                    default => 'gray',    // لون رمادي لأي رتبة تانية
                })
                ->searchable(),





                
Tables\Columns\TextColumn::make('virtual_status') // هاد اسم من كيسي، مو موجود بالداتا بيز
    ->label('الحالة العامة') // هاد اللي رح يظهر للأدمن
->getStateUsing(function ($record) {
    if (!$record->is_active) return 'غير مفعل';

    // بنجيب آخر توكن تم استخدامه
    $lastToken = $record->tokens()->latest('last_used_at')->first();

    // إذا التوكن تم استخدامه خلال آخر 5 دقائق، بنعتبره "نشط الآن"
    if ($lastToken && $lastToken->last_used_at && $lastToken->last_used_at->diffInMinutes(now()) < 5) {
        return 'نشط الآن';
    }

    return $record->tokens()->exists() ? 'متصل سابقاً' : 'غير متصل';
})
                    ->visible(function ($livewire) {
                        
                        // إذا كنا بتبويب " مستخدمين التطبيق" أخفيه تماماً
                        if ($livewire->activeTab === 'staff') {
                            return false;
                        }
                        return true;

                        })
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'نشط الآن' => 'success',   // أخضر
        'متصل سابقاً' => 'gray',    // رمادي
        'غير مفعل' => 'danger',   // أحمر
    })
    ->icon(fn (string $state): string => match ($state) {
        'نشط الآن' => 'heroicon-m-bolt',
        'متصل سابقاً' => 'heroicon-m-moon',
        'غير مفعل' => 'heroicon-m-x-circle',
    }),









            TextColumn::make('company.name')
            ->label('الشركة التي ينتمي إليها')// بيظهر اسم الشركة التابع لها

            ->color('primary')//لون ذهبي 
            ->visible(function ($livewire) {
                        
                        // إذا كنا بتبويب " مستخدمين التطبيق" أخفيه تماماً
                        if ($livewire->activeTab === 'passengers') {
                            return false;
                        }
                        return true;

                        }),


        ])
        ->filters([ /* ... */ ])
        ->actions([
            Tables\Actions\EditAction::make(),
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
