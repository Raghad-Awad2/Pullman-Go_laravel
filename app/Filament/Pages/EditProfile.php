<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                
                $this->getPasswordConfirmationFormComponent(),
                FileUpload::make('avatar_url')
                    ->label('صورتك الشخصية')
                    ->image()
                    ->avatar()
                    ->directory('avatars'),
            ]);
    }
}