<?php

namespace App\Filament\Resources\AdministrativeActResource\Pages;

use App\Filament\Resources\AdministrativeActResource;
use App\Models\AdministrativeAct;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewAdministrativeAct extends ViewRecord
{
    protected static string $resource = AdministrativeActResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        activity('inventory')
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->event('viewed')
            ->withProperties(['ip' => request()->ip()])
            ->log('Registro consultado');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() =>
                    auth()->user()?->hasRole('super_admin') || auth()->id() == $this->record->created_by
                ),

            Actions\Action::make('anular')
                ->label('Anular')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn() =>
                    ! $this->record->trashed() &&
                    (auth()->user()?->hasRole('super_admin') || auth()->id() == $this->record->created_by)
                )
                ->modalHeading('Anular registro')
                ->modalDescription('El registro se ocultará del listado y quedará excluido de los informes vigentes. Podrás consultarlo con el filtro «Anulados» y restaurarlo después. Esta acción queda en el registro de actividad.')
                ->modalIcon('heroicon-o-no-symbol')
                ->modalSubmitActionLabel('Anular registro')
                ->form([
                    Forms\Components\Textarea::make('annulment_reason')
                        ->label('Motivo de anulación')
                        ->placeholder('Explique por qué se anula este registro.')
                        ->required()
                        ->maxLength(1000)
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;
                    $record->disableLogging();
                    $record->annulment_reason = $data['annulment_reason'];
                    $record->annulled_by      = auth()->id();
                    $record->save();
                    $record->enableLogging();

                    $record->delete();

                    \Filament\Notifications\Notification::make()
                        ->title('Registro anulado')
                        ->success()
                        ->send();

                    $this->redirect(AdministrativeActResource::getUrl('index'));
                }),

            Actions\Action::make('restaurar')
                ->label('Restaurar')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restaurar registro')
                ->modalDescription('El registro volverá a estar vigente y se quitará la anulación.')
                ->modalSubmitActionLabel('Restaurar')
                ->visible(fn() =>
                    $this->record->trashed() &&
                    (auth()->user()?->hasRole('super_admin') || auth()->id() == $this->record->created_by)
                )
                ->action(function (): void {
                    $this->record->restore();

                    \Filament\Notifications\Notification::make()
                        ->title('Anulación revertida')
                        ->success()
                        ->send();

                    $this->redirect(AdministrativeActResource::getUrl('index'));
                }),
        ];
    }
}
