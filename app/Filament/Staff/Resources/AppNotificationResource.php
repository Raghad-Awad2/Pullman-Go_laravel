<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\AppNotificationResource\Pages;
use App\Filament\Staff\Resources\AppNotificationResource\RelationManagers;
use App\Models\AppNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppNotificationResource extends Resource
{
    protected static ?string $model = AppNotification::class;



    protected static ?string $navigationIcon = 'heroicon-o-bell-alert'; // أيقونة الإشعارات
    protected static ?string $navigationLabel = 'إرسال إشعارات';
    protected static ?string $modelLabel = 'إشعار';
    protected static ?string $pluralModelLabel = 'الإشعارات';




    public static function form(Form $form): Form
    {
        return $form
            ->schema([

        Forms\Components\Hidden::make('company_id')
            ->default(auth()->user()->company_id),



            Forms\Components\Section::make('تفاصيل الإشعار المرسل للتطبيق')
                    ->description('سيتم إرسال هذا الإشعار لجميع المسافرين، وسيظهر مع لوغو واسم شركتك تلقائياً.')
                    ->schema([

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الرسالة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: عرض خاص، أو تنبيه هام..'),
                        

                        Forms\Components\Textarea::make('content')
                            ->label('محتوى الرسالة')
                            ->required()
                            ->rows(5)
                            ->placeholder('اكتب تفاصيل الرسالة هنا...'),


                        // حقل مخفي نثبت فيه إنو الإشعار رايح للمسافرين
                        Forms\Components\Hidden::make('target_role')
                            ->default('passenger'),
                    ]),
            ]);

    }




    // مشان الموظف يقدر يشوف فقط الاشعارات تبعيت شركتو فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id);
    }








    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // عرض لوغو الشركة بالجدول للتأكيد
                Tables\Columns\ImageColumn::make('company.logo_url')
                    ->label('لوغو الشركة')
                    ->circular(),


                Tables\Columns\TextColumn::make('sender.name')
                    ->label('المرسل (الموظف)'),



                    
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الرسالة')
                    ->searchable()
                    ->color('gray')
                    ->weight('bold'),


                    

                Tables\Columns\TextColumn::make('target_role')
                    ->label('إرسال إلى')
                    ->formatStateUsing(fn (string $state): string => $state === 'passenger' ? 'كافة المسافرين' : $state)
                    ->badge() // بخليها تظهر جوا كبسولة أو Badge
                    ->color('info') // لون أزرق 
                    ->icon('heroicon-m-users'),


                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime('Y-m-d H:i a')
                    ->color('success')
                    ->sortable(),


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
            'index' => Pages\ListAppNotifications::route('/'),
            'create' => Pages\CreateAppNotification::route('/create'),
            'edit' => Pages\EditAppNotification::route('/{record}/edit'),
        ];
    }
}
