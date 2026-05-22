<?php

namespace App\Filament\CompanyOwner\Resources\MessageResource\Pages;

use App\Filament\CompanyOwner\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On; // ضيف هاد السطر فوق

use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    // هاد السطر السحري بيخلي الصفحة تسمع كلمة "refreshTable" وتحدث نفسها
    #[On('refreshTable')]
    public function refresh(): void {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إرسال رسالة جديدة'),
        ];
    }

    public function getHeading(): string
    {
        return 'صندوق الرسائل';
    }





        // التبويبات

    public function getTabs(): array
{
    return [

    // تبويب "الكل" ما بنحط فيه "where" عشان يجيب كل شي يخص المستخدم
        'all' => \Filament\Resources\Components\Tab::make('كل الرسائل')
            ->icon('heroicon-m-envelope-open')
            ->modifyQueryUsing(fn ($query) => $query->where(function ($q) {
                $q->where('sender_id', auth()->id())
                  ->orWhere('receiver_id', auth()->id());
            })),



            
        'received' => Tab::make('الرسائل الواردة')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('receiver_id', auth()->id())),
            
        'sent' => Tab::make('الرسائل الصادرة')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('sender_id', auth()->id())),
    ];
}
}