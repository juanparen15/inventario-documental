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
            'format'    => 'filament',
            'title'     => 'Nuevo: Asistente con Inteligencia Artificial',
            'body'      => 'Ahora puedes consultar cualquier duda sobre la información almacenada en el sistema a través de nuestro chatbot con IA. Encuéntralo en la esquina inferior izquierda.',
            'icon'      => 'heroicon-o-cpu-chip',
            'iconColor' => 'primary',
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
