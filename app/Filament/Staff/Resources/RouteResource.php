<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\RouteResource\Pages;
use App\Filament\Staff\Resources\RouteResource\RelationManagers;
use App\Models\Route;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static ?string $tenantOwnershipRelationshipName = 'company';


        protected static ?string $navigationIcon = 'heroicon-o-map'; // أيقونة دبوس الخريطة
        protected static ?string $navigationLabel = 'إضافة مسار';
        protected static ?string $pluralModelLabel = 'المسارات';
        protected static ?string $modelLabel = 'مسار';




    public static function form(Form $form): Form
    {
        return $form
            ->schema([


            // 2. تفاصيل المسار
            Forms\Components\Section::make('تفاصيل المسار الجغرافي')
                ->description('حدد المدن والعناوين بدقة')

                ->schema([


        // 1. حقل مخفي لـ company_id بياخذ شركة الموظف اللي مسجل دخول تلقائياً
        Forms\Components\Hidden::make('company_id')
            ->default(auth()->user()->company_id),


            // اختيار مدينة الانطلاق (الربط الجديد)
            Forms\Components\Select::make('departure_city_id')
                ->label('مدينة الانطلاق')
                ->options(\App\Models\City::where('is_active', true)->pluck('name', 'id')) // فقط المدن النشطة
                ->searchable()
                ->live()
                ->required()

                ->options(function (Forms\Get $get) {
                    $arrivalId = $get('arrival_city_id');
                    $query = \App\Models\City::where('is_active', true);
                    
                    if ($arrivalId) {
                        $query->where('id', '!=', $arrivalId); // إخفاء مدينة الوصول
                    }
                    
                    return $query->pluck('name', 'id');
                }),



            // اختيار مدينة الوصول (مع منع التكرار)
            Forms\Components\Select::make('arrival_city_id')

                ->label('مدينة الوصول')
                ->searchable()
                ->required()

                ->options(function (Forms\Get $get) {
                    $departureId = $get('departure_city_id');
                    $query = \App\Models\City::where('is_active', true);
                    
                    if ($departureId) {
                        $query->where('id', '!=', $departureId); // إخفاء مدينة الانطلاق
                    }
                    
                    return $query->pluck('name', 'id');
                }),


            Forms\Components\Textarea::make('departure_address')->label('عنوان الانطلاق (الكراج/المكتب)')->required(),

            Forms\Components\Textarea::make('arrival_address')->label('عنوان الوصول (الكراج/المكتب)')->required(),




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
                        ->defaultItems(0), // بيبدأ بـ 0 وأنت بتضيف يدوي
                ])



                ])->columns(2),



            Forms\Components\Section::make('البيانات الفنية والوقت')

                ->schema([

            Forms\Components\TextInput::make('distance')
                ->label('المسافة')
                ->integer() 
                ->suffix('كم')
                ->required(),


            Forms\Components\TextInput::make('estimated_time')
                ->label(' المدة الزمنية التقريبية للرحلة')
                ->required(),
                ])->columns(2),



            // 4. السعر الأساسي (متاح للأدمن لإضافته مبدئياً)
            Forms\Components\TextInput::make('base_price')
                ->label('السعر الأساسي')
                ->required()
                ->suffix('ل.س')
                ->helperText('سعر التذكرة على هذا المسار'),
       


            ]);
    }




    // المسارات تابعة لشركة وحدة فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id);
    }







    public static function table(Table $table): Table
    {
        return $table
            ->columns([


            Tables\Columns\TextColumn::make('company.name')
                ->label('اسم الشركة')
                ->searchable()
                ->sortable()
                // هاد السطر رح يظهر عدد المسارات تحت اسم الشركة بخط صغير وحلو
                ->description(fn (Route $record): string => "إجمالي مسارات هذه الشركة: " . $record->company->routes()->count())
                ->color('primary'),





            Tables\Columns\TextColumn::make('departureCity.name')->label('من مدينة')->searchable()
            ->color('info'), // لون أزرق

            Tables\Columns\TextColumn::make('departure_address')->label('عنوان الإنطلاق')->searchable()
            ->color('gray'), // لون رمادي

            



            Tables\Columns\TextColumn::make('arrivalCity.name')->label('إلى مدينة')->searchable()
            ->color('info'), // لون أزرق

            Tables\Columns\TextColumn::make('arrival_address')->label('عنوان الوصول')->searchable()
            ->color('gray'), // لون رمادي





            Tables\Columns\TextColumn::make('distance')
            ->formatStateUsing(fn ($state) => number_format($state))
            ->label('المسافة (كم)'),


            Tables\Columns\TextColumn::make('base_price')->label('السعر الأساسي')->money('SYP')
            ->formatStateUsing(fn ($state) => number_format($state)   . "  ل.س")
            ->color('success'), // لون اخضر


            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ إنشاء المسار')
                ->dateTime('Y-m-d H:i') // هون بحدد التنسيق 
                ->sortable() // مشان ترتب الشركات من الأحدث للأقدم
                ->color('success'), // لون اخضر


            ])



            ->filters([

            // فلترة حسب المدن لانو اني اساسا بالمسارات مابصير افلتر حسب المسار
            Tables\Filters\SelectFilter::make('departure_city_id')
                ->label('مدينة الانطلاق')
                ->relationship('departureCity', 'name')
                ->searchable()
                ->preload(),


            Tables\Filters\SelectFilter::make('arrival_city_id')
                ->label('مدينة الوصول')
                ->relationship('arrivalCity', 'name')
                ->searchable()
                ->preload(),



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
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }
}
