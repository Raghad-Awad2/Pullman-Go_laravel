<?php

namespace App\Filament\CompanyOwner\Resources;

use App\Filament\CompanyOwner\Resources\CompanyAccountResource\Pages;
use App\Filament\CompanyOwner\Resources\CompanyAccountResource\RelationManagers;
use App\Models\CompanyAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyAccountResource extends Resource
{

// هاد موديل وهمي مو موجود عندي بال models CompanyAccount
    // protected static ?string $model = CompanyAccount::class;

    
    // هاد الموديل الي موجود عندي Company
    protected static ?string $model = \App\Models\Company::class;

    protected static bool $isScopedToTenant = false;



    public static function shouldRegisterNavigation(): bool
{
    // بتظهر فقط إذا كان المستخدم "صاحب شركة" (وليس أدمن)
    return auth()->user()->role === 'owner'; 
}




    protected static ?string $modelLabel = 'بيانات الشركة الرسمية'; // للمفرد
    protected static ?string $pluralModelLabel = 'شركات'; // للجمع
    protected static ?string $navigationLabel = 'إدارة حسابي'; // للاسم في القائمة الجانبية
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';



        public static function canCreate(): bool
        {
            return false; // إخفاء زر "إضافة شركة" نهائياً
        }

        public static function canDeleteAny(): bool
        {
            return false; // منع الحذف
        }



    public static function form(Form $form): Form
    {
        return $form

            ->schema([
                
        Forms\Components\Hidden::make('company_id')
            ->default(auth()->user()->company_id),



        Forms\Components\Section::make('المسارات المتاحة لشركتك')

            ->description('هذه المسارات محددة من قبل الموظف ولا يمكنك تعديلها من هنا.')
                ->schema([


        Forms\Components\Repeater::make('routes')
            ->relationship()
            ->label('المسارات المتاحة') // بدل كلمة Routes اللي فوق



        ->itemLabel(function (Forms\Components\Repeater $component, $state): \Illuminate\Support\HtmlString {

            // جلب كل المسارات الموجودة حالياً في الريبيتر
            $items = $component->getState();

            // البحث عن ترتيب المسار الحالي داخل المصفوفة
            $index = array_search(array_search($state, $items), array_keys($items)) + 1;

            $map = [
                1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 
                5 => 'الخامس', 6 => 'السادس', 7 => 'السابع', 8 => 'الثامن',
                9 => 'التاسع', 10 => 'العاشر' , 11 => 'الحادي عاشر'
            ];

            $label = 'المسار ' . ($map[$index] ?? $index);

            // هون السحر: بنرجع نص HTML ملون بالأخضر وبخط عريض
            return new \Illuminate\Support\HtmlString("<span style='color: #22c55e; font-weight: bold;'>{$label}</span>");
        })// بيعطيك عنوان لكل مسار



                        ->schema([
                            Forms\Components\Grid::make(2) // تقسيم الحقول لـ 3 أعمدة بكل سطر لترتيب أحلى
                        
                        
                        ->schema([

                            // 1. مدينة الانطلاق
                            Forms\Components\Select::make('departure_city_id') 
                                ->label('مدينة الانطلاق')
                                ->relationship('departureCity', 'name')
                                ->disabled(),
                            

                            // 2. عنوان الانطلاق (التفصيلي)
                            Forms\Components\TextInput::make('departure_address')
                                ->label('عنوان الانطلاق بالتفصيل')
                                ->disabled(),

 


                            // 3. مدينة الوصول
                            Forms\Components\Select::make('arrival_city_id')
                                ->label('مدينة الوصول')
                                ->relationship('arrivalCity', 'name')
                                ->disabled(),


                            // 4. عنوان الوصول (التفصيلي)
                            Forms\Components\TextInput::make('arrival_address')
                                ->label('عنوان الوصول بالتفصيل')
                                ->disabled(),



                            // نظام الاستراحات بتخزنو بمصفوفة
            Forms\Components\Section::make('استراحات المسار')
                ->description('أضف الاستراحات التي يمر بها الباص خلال هذا المسار بالترتيب')
                ->collapsible() // عشان اقدر اصغر القسم وتكبره
                ->schema([
                    Forms\Components\Repeater::make('rest_stops') // اسم الحقل في الداتا بيز


                        ->itemLabel(function (array $state, $uuid, $component): string {
                            $items = $component->getState();
                            $index = array_search($uuid, array_keys($items)) + 1;
                            
                            $ordinals = [
                                1 => 'الأولى', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة', 
                                5 => 'الخامسة', 6 => 'السادسة', 7 => 'السابعة', 8 => 'الثامنة'
                            ];

                            $label = $ordinals[$index] ?? $index; // إذا زاد عن 8 بيطلع رقم
                            $name = $state['stop_name'] ?? '';
                            $Location = $state['stop_location']?? '';
                            return "الاستراحة {$label}" . ($name ? " - {$name}" : "") . ($state['stop_location'] ? " في {$state['stop_location']}" : "");
                        })


                        ->collapsible()
                        ->label('قائمة الاستراحات')
                        ->schema([
                            Forms\Components\TextInput::make('stop_name')
                                ->label('اسم الاستراحة')
                                ->placeholder('مثلاً: استراحة العابدين')
                                ->live(onBlur: true) // عشان يغير العنوان فوراً بس تخلص كتابة
                                ->required(),
                            
                            Forms\Components\TextInput::make('stop_location')
                                ->label('الموقع / المنطقة (اختياري)')
                                ->live(onBlur: true) // عشان يغير الموقع فوراً بس اخلص كتابة
                                ->placeholder('مثلاً: ريف حمص'),
                        ])
                        ->columns(2) // عشان يطلع اسم الاستراحة وموقعها جنب بعض
                        ->createItemButtonLabel('إضافة استراحة جديدة')
                        ->reorderableWithButtons() // بيسمحلك ترتب الاستراحات (تطلع وحدة فوق وحدة)
                        ->addActionLabel('إضافة')
                        // للعرض فقط
                        ->disabled()
                        ->defaultItems(0), // بيبدأ بـ 0 وأنت بتضيف يدوي
                ]),



                            Forms\Components\TextInput::make('distance')
                                ->label('المسافة (كم)')
                                ->suffix('كم')
                                ->disabled(),

                                
                            Forms\Components\TextInput::make('estimated_time')
                                ->label(' المدة المتوقعة للرحلة  ')
                                ->disabled(),


                            Forms\Components\TextInput::make('base_price')
                                ->label('سعر التذكرة لهذا المسار')
                                ->suffix('ل.س')
                                ->afterStateHydrated(function (Forms\Components\TextInput $component, $state) {
                                        $component->state(number_format($state, 0, '.', ',')); // بيحول الـ 50000 لـ 50,000 عند العرض
                                    })
                                ->disabled(),
                    ])


 ])
                    ->addable(false) // يمنع إضافة مسار جديد
                    ->deletable(false) // يمنع حذف مسار
                    ->reorderable(false) // يمنع إعادة الترتيب
                    ->columns(1) // عشان يطلعوا بجنب بعض بشكل مرتب



            ]),





                Forms\Components\Section::make('معلومات الشركة الأساسية')
                ->description('يمكنك تعديل بيانات شركتك الرسمية من هنا')

                ->schema([
                    // 1. اسم الشركة
                    Forms\Components\TextInput::make('name')
                        ->label('اسم الشركة')
                        ->required(),



                    // 2. رقم الهاتف
                    Forms\Components\TextInput::make('phone')
                        ->label('رقم الهاتف للتواصل')
                        ->tel()
                        ->required(),



                    // 3. عنوان المكتب الرئيسي
                    Forms\Components\Textarea::make('address')
                        ->label('عنوان المكتب الرئيسي')
                        ->required(),

                    Forms\Components\TextInput::make('location_url')
                                ->label('رابط الموقع الجغرافي (Google Maps)')
                                ->placeholder('أدخل رابط خرائط جوجل هنا...')
                                ->url() // هاد السطر مهم جداً عشان يتأكد إن المدخل "رابط" مو كلام عشوائي
                                ->suffixIcon('heroicon-m-map-pin') // لمسة جمالية (أيقونة لوكيشن)
                                ->autocomplete('off') // هاد السطر بيمنع ظهور الايميل تلقائياً
                                ->columnSpanFull()
                                ->required()
                                ->helperText('انسخ الرابط من خرائط جوجل والصقه هنا ليظهر في التطبيق.'),



            // يا فيلامينت، اعملي حقل لرفع الملفات

                    // 4. اللوغو (أو صورة الملف الشخصي)
                    Forms\Components\FileUpload::make('logo_url')
                        ->label('لوغو الشركة')
                        ->image()

                    //خزن الصورة داخل الملف companies-logos الي موجود داخل مجلد Storage
                        ->directory('companies-logos')
                        ->required(),



            Forms\Components\TextInput::make('slogan')
                ->label('وصف قصير للشركة')
                ->required()
                ->placeholder('')
                ->maxLength(100), //  ما يطول بزيادة ويخرب شكل التطبيق
               



            Forms\Components\CheckboxList::make('features')
                ->label('خدمات ومميزات الشركة')

                ->options([

                    // الخدمات الأساسية
                    'wifi' => 'واي فاي مجاني',
                    'ac' => 'تكييف هواء',
                    'comfortable_seats' => 'مقاعد مريحة',
                    'usb' => 'شواحن USB',
                    'wc' => 'دورة مياه داخلية',
                    
                    // خدمات إضافية للفخامة
                    'screen' => 'شاشات عرض خلف المقاعد',
                    'water' => 'توزيع مياه ضيافة',
                    'snacks' => 'توزيع وجبات خفيفة',
                    'coffee' => 'ركن قهوة ومشروبات ساخنة',
                    
                    // خدمات تقنية وأمان
                    'gps' => 'تتبع مباشر للرحلة (GPS)',
                    'camera' => 'كاميرات مراقبة للأمان',
                    'insurance' => 'تأمين سفر شامل',
                    
                    // خدمات العفش
                    'luggage' => 'خدمة حمل الأمتعة',
                    'extra_bag' => 'وزن إضافي مسموح',
                ])
                ->columns(2) // عمودين
                ->gridDirection('row')
                ->bulkToggleable() // زر تحديد الكل
                ->searchable() //   البحث عشان لو القائمة طولت يلقى الخدمة بسرعة

                
                ]) 


            ]);


    }







    // مهمممممممممممم جداااا

    // لضمان صاحب الشركة مابقدر يعدل على رقم او لوغو او اسم شركة او عنوان اي شركة ثانية فقط الشركة تبعيتو
public static function getEloquentQuery(): Builder
{
    // هون بنجبر السيستم يجيب فقط الشركة اللي صاحبها هو المستخدم اللي داخل حالياً
    return parent::getEloquentQuery()->where('owner_id', auth()->id());
}











    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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

public static function getPages(): array
{
    return [
        'index' => Pages\EditCompanyAccount::route('/'), // جعل الصفحة الرئيسية هي التعديل
    ];
}


}
