<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'إضافة شركة';
    protected static ?string $pluralModelLabel = 'الشركات';
    protected static ?string $modelLabel = 'شركة';


    protected static ?string $navigationGroup = 'إدارة الشركات'; // بتفصل كلشي عنهن مشان الترتيب 


    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // القسم الأول: معلومات الشركة الأساسية
                Section::make('معلومات الشركة الأساسية')
                    ->schema([

                    Forms\Components\Placeholder::make('created_at')
                    ->label('تاريخ إنضمام الشركة')

                    // diffForHumans() بتخلي التاريخ يطلع بشكل حلو مثل "منذ ساعتين" أو "قبل 3 أيام".
                    ->content(fn ($record): string => $record?->created_at ? $record->created_at->diffForHumans() : '-'),

                    
                        Forms\Components\TextInput::make('location_url')
                            ->label('رابط الموقع الجغرافي (Google Maps)')
                            ->placeholder('أدخل رابط خرائط جوجل هنا...')
                            ->url() // هاد السطر مهم جداً عشان يتأكد إن المدخل "رابط" مو كلام عشوائي
                            ->suffixIcon('heroicon-m-map-pin') // لمسة جمالية (أيقونة لوكيشن)
                            ->helperText('انسخ الرابط من خرائط جوجل والصقه هنا ليظهر في التطبيق.'),



                        TextInput::make('name')
                            ->label('اسم الشركة')
                            ->required()
                            ->validationMessages(['required' => 'عذرا، هذا الحقل مطلوب!']),



                        TextInput::make('phone')
                            ->label('رقم الهاتف الأساسي')
                            ->tel() // هاد بيفتح كيبورد الأرقام بالموبايل
                            ->numeric() // يمنع ادخال الحروف والرموز
                            ->required()
                            ->validationMessages([
                                'numeric' => 'عذرا، يجب إدخال أرقام فقط في هذا الحقل!',
                                'required' => 'هذا الحقل مطلوب'
                            ]),



                        TextInput::make('address')
                            ->label('عنوان المكتب الرئيسي للشركة')
                            ->required()
                            ->validationMessages(['required' => 'عذرا، هذا الحقل مطلوب!']),




                        FileUpload::make('logo_url')
                            ->label('لوغو الشركة')
                            ->image()
                            ->directory('companies-logos')
                            ->required()
                            ->validationMessages(['required' => 'عذرا، هذا الحقل مطلوب!']),

                        Select::make('status')
                            ->label('حالة الشركة')
                            ->options([
                                'active' => 'نشط',
                                'inactive' => 'غير نشط',
                                'suspended' => 'محظور',
                            ])
                            ->default('active')
                            ->required(),

                        Hidden::make('admin_id')
                            ->default(auth()->id()),
                    ])->columns(2),

                    


        // القسم الثاني: بيانات صاحب الشركة (المدير)
        Section::make('بيانات صاحب الشركة ')
            ->description('هذا الحساب سيملك كامل الصلاحيات لإدارة شركته')
            ->schema([
                // 1. حقل الاختيار (المرسل)
                Select::make('owner_id')
                    ->label('اسم صاحب الشركة (المدير)')
                    ->options(\App\Models\User::where('role', 'owner')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live() // يراقب التغيير فوراً
                    ->afterStateUpdated(function ($state, callable $set) {
                        // جلب الإيميل ووضعه في حقل owner_email بمجرد الاختيار
                        $user = \App\Models\User::find($state);

                        // عبيلي الاليميل تلقائيا
                        $set('owner_email', $user?->email);
                        
                    })
                    ->required(),

        // 2. حقل الإيميل (المستقبل)
        TextInput::make('owner_email')
            ->label('الإيميل الشخصي')
            ->email()
            ->disabled()
            ->required()
            ->rules(['regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/'])
            ->validationMessages([
                'regex' => 'عذراً، يجب استخدام حساب Gmail فقط.',
            ])
            ->extraInputAttributes(['autocomplete' => 'off'])

            // لجلب الإيميل تلقائياً عند فتح صفحة التعديل
            ->afterStateHydrated(function ($component, $record) {
                if ($record) {
                    $user = \App\Models\User::where('company_id', $record->id)->where('role', 'owner')->first();
                    $component->state($user?->email);
                }
            })
            ->placeholder('سيتم جلب الإيميل تلقائياً عند اختيار المالك'),
    




                            


        // 2. حقل كلمة السر وقت التعديل (اختياري تماماً)
        TextInput::make('owner_password_edit') // غيرنا الاسم شوي عشان ما يتصادم
            ->label('تغيير كلمة المرور (اختياري)')
            ->password()
            ->dehydrated(fn ($state) => filled($state)) // لا تحفظه إذا فاضي
            ->visibleOn('edit') // بيظهر بس بصفحة "تعديل"
            ->helperText('اترك الحقل فارغاً إذا كنت لا تريد تغيير كلمة السر الحالية')
            ->extraInputAttributes(['autocomplete' => 'new-password'])




                           


                            // منع التعبئة التلقائية من المتصفح
            ->extraInputAttributes(['autocomplete' => 'off']),



        ])->columns(2),


        




Section::make('إضافة موظف (اختياري)')
    ->description('أضف موظف لهذه الشركة او اتركها للمدير')
    ->schema([
        Forms\Components\Grid::make(3) // هاد السحر اللي بخليهم بصف واحد
            ->schema([
                Select::make('staff_id')
                    ->label('اسم الموظف')
                    ->options(\App\Models\User::where('role', 'staff')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()

                    // بجيب اسم الموظف تلقائيا عند فتح صفحة تعديل الشركة

                    ->afterStateHydrated(function ($set, $record)
                    
                    {
                        if ($record) {
                            $staff = \App\Models\User::where('company_id', $record->id)->where('role', 'staff')->first();
                            $set('staff_id', $staff?->id);
                            $set('staff_email', $staff?->email);
                        }
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        $user = \App\Models\User::find($state);
                        $set('staff_email', $user?->email);
                    }),

                TextInput::make('staff_email')
                    ->label('إيميل الموظف')
                    ->placeholder('سيتم جلب الإيميل تلقائياً عند اختيار الموظف')
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('staff_password_edit') // سميته staff عشان ما يضرب مع باسورد صاحب الشركة
                    ->label('كلمة مرور الموظف')
                    ->password()
                    ->placeholder(' تغيير كلمة المرور (اختياري)')
                    ->dehydrated(fn ($state) => filled($state))
                    ->visibleOn('edit')
            ]),
    ])
    ->collapsible(),


                          
    ]);

}









    public static function table(Table $table): Table
    {
        return $table
            ->columns([

            Tables\Columns\ImageColumn::make('logo_url')->label('اللوغو')->circular(),


            Tables\Columns\TextColumn::make('name')->label('اسم الشركة')
                ->searchable()
                ->weight('bold') // خط عريض
                ->color('primary'),//لون ذهبي 
                


                // هاد العمود الجديد اللي رح يظهرلك اسم المالك
            Tables\Columns\TextColumn::make('owner_name')
                ->label('صاحب الشركة')
                
                ->icon('heroicon-m-user') // هاي الأيقونة الشخصية
                ->iconColor('success')
                ->getStateUsing(function ($record) {
                    // منروح منجيب أول مستخدم مربوط بهي الشركة ورتبته صاحب شركة
                    $owner = \App\Models\User::where('company_id', $record->id)
                            ->where('role', 'owner')
                            ->first();
                    return $owner ? $owner->name : 'غير معين';
                })
                ->color('white'),//لون رمادي




            Tables\Columns\TextColumn::make('phone')->label('الهاتف')
                ->color('success'),

            Tables\Columns\TextColumn::make('address')->label('عنوان المكتب الرئيسي ')
                ->color('gray'),


            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنضمام')
                ->dateTime('Y-m-d H:i') // هون بحدد التنسيق 
                ->sortable() // مشان ترتب الشركات من الأحدث للأقدم
                ->color('success'), // لون اخضر




            Tables\Columns\TextColumn::make('routes_count')
                ->label('عدد المسارات')
                ->counts('routes') // هاد السطر السحري بيجيب العدد تلقائياً من علاقة routes
                ->badge()          // بيطلع الرقم جوا دائرة ملونة حلوة
                ->color('success') //  لون الدائرة أخضر
                ->sortable(),      //   ترتب الشركات من الأكثر مسارات للأقل



            Tables\Columns\BadgeColumn::make('status')
                ->label('الحالة')
                ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                        'warning' => 'inactive',
                    ]),
            ])




            ->filters([])

            
            ->actions([Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}