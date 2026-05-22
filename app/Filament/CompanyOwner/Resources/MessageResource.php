<?php

namespace App\Filament\CompanyOwner\Resources;

use App\Filament\CompanyOwner\Resources\MessageResource\Pages;
use App\Filament\CompanyOwner\Resources\MessageResource\RelationManagers;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


use Illuminate\Support\Str;



class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    // الأيقونة: فقاعة دردشة 
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    // المسمى في القائمة الجانبية
    protected static ?string $navigationLabel = ' مراسلة';

    // المسمى عند عرض كل الرسائل
    protected static ?string $pluralModelLabel = ' مراسلة';

    // المسمى عند إنشاء رسالة واحدة
    protected static ?string $modelLabel = 'رسالة';

    // الترتيب (عشان تطلع تحت إدارة الموظفين مثلاً)
    protected static ?int $navigationSort = 5;



    
public static function form(Form $form): Form
{

    return $form

        ->schema([
            Forms\Components\Section::make('إنشاء رسالة')
                ->schema([

        Forms\Components\Hidden::make('company_id')
            ->default(auth()->user()->company_id),


                    // حطه هون في بداية الفورم عشان يظهر فوق
                    Forms\Components\Placeholder::make('reply_to')
                        ->label('رد على الرسالة:')
                        ->content(fn ($record) => $record?->parent?->content)
                        ->visible(fn ($record) => $record && $record->parent_id !== null)
                        ->columnSpanFull(),
                        

                    // اختيار الشخص المستلم
                Forms\Components\Section::make('تفاصيل الرسالة')
                ->schema([
                    // 1. حقل "مِن" (المرسل) - للعرض فقط دايماً
                    Forms\Components\Select::make('sender_id')
                        ->label('مِن')
                        ->relationship('sender', 'name')
                        ->disabled() // دايماً مقفل لأنه السيستم بيعرفه لحاله
                        ->placeholder('سيتم تحديده تلقائياً'),



                    // 2. حقل "إلى" (المستلم) - يتقفل بس إذا كانت الرسالة "واردة"
                    Forms\Components\Select::make('receiver_id')
                        ->label('إرسال إلى')
                        ->relationship('receiver', 'name')
                        ->options(function () {
                            return \App\Models\User::where('company_id', auth()->user()->company_id)
                                ->where('id', '!=', auth()->id())
                                ->pluck('name', 'id');
                        })

                        // السطر السحري: بيقفل الحقل إذا أنت "المستقبل" (يعني الرسالة واردة لك)
                        ->disabled(fn ($record) => $record && $record->receiver_id === auth()->id()) 
                        ->required()
                        ->searchable(),





                    // نص الرسالة
                    Forms\Components\Textarea::make('content')
                        ->label('محتوى الرسالة')
                        ->placeholder('اكتب رسالتك هنا...')
                        ->rows(5)

                        // نفس الشرط: إذا أنت المستقبل، ممنوع تعدل المحتوى
                        ->disabled(fn ($record) => $record && $record->receiver_id === auth()->id())
                        ->required(),



                    // حقول بتنحفظ أوتوماتيك خلف الكواليس
                    Forms\Components\Hidden::make('sender_id')

                    // المستخدم الحالي
                        ->default(auth()->id()),

                    Forms\Components\Hidden::make('company_id')
                        ->default(fn () => auth()->user()->company_id),




                    

                ])
 ])
        ]);


}



    // مشان بدل كلمة (إضافة) خليناها ارسال الان 

    public static function getModelLabel(): string
    {
        return 'رسالة';
    }



    // الرسائل تابعة لشركة وحدة فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id);
    }





    public static function table(Table $table): Table
    {
        return $table

        ->recordAction(function ($record) {
    // إذا أنا اللي بعت الرسالة، افتح تعديل. إذا استلمتها، افتح قراءة بس.
    return $record->sender_id === auth()->id() 
        ? Tables\Actions\EditAction::class 
        : Tables\Actions\ViewAction::class;
})


        
        ->columns([

            // مين اللي بعت الرسالة
            Tables\Columns\TextColumn::make('sender.name')
                ->label('من')
                ->searchable()
                ->color('success') // لون برتقالي
                ->sortable(),


            Tables\Columns\TextColumn::make('receiver.name')
                ->label('إلى ')
                ->searchable()
                ->color('warning') // لون برتقالي
                ->sortable(),


            // نص الرسالة
           
            Tables\Columns\TextColumn::make('content')
                ->label('الرسالة')
                ->limit(100) // بيعرض أول 100 حرف  

                //  السطر هو اللي بيظهر "رد على: ..." تحت نص الرسالة
                ->description(fn ($record) => $record->parent_id ? 'رد على: ' . Str::limit($record->parent?->content, 30) : null)
                ->searchable(),
                
                

            // الحالة: انقرأت ولا لسا
            Tables\Columns\IconColumn::make('is_read')
                ->label('تم الاطلاع')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle') // أيقونة الصح
                ->falseIcon('heroicon-o-clock')        // أيقونة الساعة
                ->color(fn (bool $state): string => $state ? 'success' : 'warning'), //success يعني أخضر


            // تاريخ الإرسال
            Tables\Columns\TextColumn::make('created_at')
                ->label('وقت الإرسال')
                ->dateTime('Y-m-d H:i')
                ->color('success') // لون اخضر
                ->sortable(),
        ])
        ->defaultSort('created_at', 'desc') // بيخلي أحدث الرسائل فوق دايماً



        

            ->filters([
                //
            ])


   

               ->actions([
            
            Tables\Actions\Action::make('reply')
                ->label('رد ')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('success')
                ->visible(fn ($record) => $record->receiver_id === auth()->id())
                ->form([
                    Forms\Components\Placeholder::make('parent_content')
                        ->label('الرسالة الأصلية')
                        ->content(fn ($record) => $record->content), 

                    Forms\Components\Textarea::make('content')
                        ->label('ردك هنا')
                        ->required()
                        ->rows(3),
                ])
                



                // هاد الاكشن تبع الرد
                ->action(function ($record, array $data)
            {
            // خزنا الرسالة بمتغير اسمهnewMessage 

                    $newMessage = \App\Models\Message::create([
                        'sender_id' => auth()->id(),
                        'receiver_id' => $record->sender_id,
                        'company_id' => $record->company_id,
                        'content' => $data['content'],
                        'parent_id' => $record->id,
                    ]);

                // إرسال الإشعار للمستلم (صاحب الرسالة الأصلية)
                    \Filament\Notifications\Notification::make()
                            ->title('رد جديد على رسالتك')
                            ->body("قام " . auth()->user()->name . " بالرد على رسالتك")
                            ->info()
                            ->sendToDatabase($record->sender);


                    \Filament\Notifications\Notification::make()
                        ->title('تم إرسال الرد بنجاح')
                        ->success()
                        ->send();
            }),


            

           
            // زر القراءة (العرض)
Tables\Actions\Action::make('read_message')
    ->label('قراءة')
    ->icon('heroicon-o-eye')
    ->color('info')
    ->modalHeading('تفاصيل المراسلة')
    ->modalSubmitActionLabel('إغلاق')
    ->modalCancelAction(false)

    ->fillForm(fn ($record) => [
        'sender_id' => $record->sender_id,
        'receiver_id' => $record->receiver_id,
        'content' => $record->content,
    ])

    ->form([
        // 1. قسم "الرسالة السابقة": بيظهر فقط إذا كان هذا السجل عبارة عن "رد" (له parent_id)
        Forms\Components\Section::make('سياق الرد')
            ->description('هذه الرسالة هي رد على ما يلي:')
            ->schema([
                Forms\Components\Placeholder::make('parent_content')
                    ->label('رسالتك الأصلية:')
                    ->content(fn ($record) => $record->parent?->content ?? 'لا يوجد سياق متاح'),
            ])
            // اذا في رسالة مردود عليها بتبين واذا مافي : يقوم هاد السطر بحذف المورد (المساحة تبعيت الرسالة)
            ->visible(fn ($record) => $record->parent_id !== null) // بيظهر فقط إذا فيه رسالة أصيلة
            ->collapsible() // خيار لتصغير القسم
            ->compact(),

        // 2. تفاصيل الرسالة الحالية
        Forms\Components\Section::make('الرسالة الحالية')
            ->schema([
                Forms\Components\Select::make('sender_id')
                    ->relationship('sender', 'name')
                    ->label('من')
                    ->disabled(),
                Forms\Components\Select::make('receiver_id')
                    ->relationship('receiver', 'name')
                    ->label('إرسال إلى')
                    ->disabled(),
                
                // تكبير حقل المحتوى ليعرض مساحة أكبر
                Forms\Components\Textarea::make('content')
                    ->label('محتوى الرسالة ')
                    ->rows(8) // خليناه كبير (حوالي 8 أسطر)
                    ->columnSpanFull()
                    ->disabled()
                    ->extraAttributes([
                        'style' => 'font-size: 1.1rem; line-height: 1.6;', // تحسين القراءة
                    ]),
            ])->columns(2),
    ])

    // 3. تحديث الحالة + ريفريش للصفحة عشان ينقص العداد
    ->action(function ($record, $livewire) {
        if (auth()->id() === $record->receiver_id && !$record->is_read) {
            $record->update(['is_read' => true]);
        }
        
        // جافاسكربت لتحديث الصفحة فوراً
        $livewire->js('window.location.reload()');
    }),

    
            // زر التعديل: يظهر فقط إذا كنت اني "المرسل"
            Tables\Actions\EditAction::make()
                ->label('تعديل')
                ->visible(fn ($record) => $record->sender_id === auth()->id()), // السطر السحري


            Tables\Actions\DeleteAction::make(),
            ])







            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }





public static function getNavigationBadge(): ?string
{
    $count = static::getModel()::where('receiver_id', auth()->id())
        ->where('is_read', false)
        ->count();

    return $count > 0 ? (string) $count : null;
}


// أضف هذه الدالة تحتها مباشرة (مهمة جداً للتحديث اللحظي)
public static function getNavigationBadgeTooltip(): ?string
{
    return 'الرسائل الجديدة';
}


public static function getNavigationBadgeColor(): ?string
{
    return 'danger';
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
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}
