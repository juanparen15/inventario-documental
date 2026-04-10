<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento — Inventario Documental</title>
    <link rel="stylesheet" href="/vendor/whatsapp-widget/assets/app-CgZ3I7dV.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #334155;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 48px 40px;
            max-width: 480px;
            width: 90%;
            text-align: center;
        }
        .icon { font-size: 48px; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 12px; color: #1e293b; }
        p  { font-size: 15px; line-height: 1.6; color: #64748b; margin-bottom: 8px; }
        .badge {
            display: inline-block;
            margin-top: 24px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 999px;
            padding: 6px 18px;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔧</div>
        <h1>Sistema en mantenimiento</h1>
        <p>Estamos realizando mejoras para brindarle un mejor servicio.</p>
        <p>Estaremos de vuelta en aproximadamente <strong>1 hora</strong>.</p>
        <span class="badge">Vuelva pronto</span>
    </div>

    @php
        use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;
        use Illuminate\Support\Facades\URL;
        try {
            $agents = WhatsappAgent::where('active', true)->get();
        } catch (\Exception $e) {
            $agents = collect();
        }
    @endphp

    @if($agents->isNotEmpty())
        <div class="ww-container ww-floating bottom-right">
            <span id="contact-trigger" class="ww-whatsapp-icon-only">
                <img class="icon" alt="WhatsApp"
                     src="/vendor/whatsapp-widget/assets/whatsapp-icon-a-1IqH2j5z.svg">
            </span>
            <div id="notification-badge">{{ $agents->count() }}</div>
            <ul class="ww-whatsapp-content">
                <li class="ww-content-header">
                    <a class="close-chat" title="Cerrar">Cerrar</a>
                    <img class="icon" alt="WhatsApp"
                         src="/vendor/whatsapp-widget/assets/whatsapp-icon-a-1IqH2j5z.svg">
                    <h5>{{ config('app.name') }} <span>Estamos disponibles</span></h5>
                </li>
                @foreach($agents as $agent)
                    <li class="available">
                        <a class="ww-whatsapp-button" target="_blank"
                           href="https://wa.me/{{ preg_replace('/\D/', '', $agent->phone) }}?text={{ urlencode('Hola, el sistema está en mantenimiento. Necesito soporte.') }}"
                           rel="nofollow">
                            <img width="60" height="60" class="ww-whatsapp-avatar ww-image"
                                 alt="{{ $agent->name }}"
                                 src="{{ $agent->image ? asset('storage/' . $agent->image) : '/vendor/whatsapp-widget/assets/whatsapp-icon-logo-s-EjW9Ft.svg' }}"/>
                            <span class="ww-whatsapp-text">
                                <span class="ww-whatsapp-label">
                                    <span class="status">En línea</span>
                                </span>
                                {{ $agent->name }}
                            </span>
                        </a>
                    </li>
                @endforeach
                <li class="ww-content-footer"><p></p></li>
            </ul>
        </div>
        <script src="/vendor/whatsapp-widget/assets/app-C5jCOlfi.js"></script>
    @endif
</body>
</html>
