<?php

namespace App\Console\Commands;

use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotificarChatbotCommand extends Command
{
    protected $signature   = 'notificar:chatbot';
    protected $description = 'Notifica a todos los usuarios sobre el nuevo chat de soporte';

    public function handle(): void
    {
        $usuarios = User::all();

        if ($usuarios->isEmpty()) {
            $this->warn('No se encontraron usuarios.');
            return;
        }

        Notification::make()
            ->title('¡Nuevo! Chat de soporte en línea')
            ->body('Ya puedes contactarnos directamente desde el sistema. Haz clic en el ícono de chat en la esquina inferior derecha para iniciar una conversación con nuestro equipo.')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('success')
            ->actions([
                Action::make('entendido')
                    ->label('Entendido')
                    ->close()
                    ->button(),
            ])
            ->sendToDatabase($usuarios);

        $this->info("Notificación enviada a {$usuarios->count()} usuario(s).");
    }
}
