<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $data = json_encode([
            'title'     => '¡Nuevo! Chat de soporte en línea',
            'body'      => 'Ya puedes contactarnos directamente desde el sistema. Haz clic en el ícono de chat en la esquina inferior derecha para iniciar una conversación con nuestro equipo.',
            'icon'      => 'heroicon-o-chat-bubble-left-right',
            'iconColor' => 'success',
            'status'    => 'success',
            'actions'   => [],
            'duration'  => 'persistent',
        ]);

        $ahora   = now()->toDateTimeString();
        $records = $usuarios->map(fn ($user) => [
            'id'              => (string) Str::orderedUuid(),
            'type'            => \Filament\Notifications\Notification::class,
            'notifiable_type' => 'App\Models\User',
            'notifiable_id'   => $user->id,
            'data'            => $data,
            'read_at'         => null,
            'created_at'      => $ahora,
            'updated_at'      => $ahora,
        ])->values()->all();

        DB::table('notifications')->insert($records);

        $this->info("Notificación enviada a {$usuarios->count()} usuario(s).");
    }
}
