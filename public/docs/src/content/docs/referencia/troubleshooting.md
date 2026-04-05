---
title: Solución de Problemas
description: Problemas comunes y sus soluciones en el Sistema de Inventario Documental.
---

## Problemas de Acceso

### No puedo iniciar sesión

**Síntomas:** El sistema muestra "Las credenciales no coinciden con nuestros registros."

**Causas y soluciones:**

1. **Contraseña incorrecta:** Verificar que no haya bloqueado mayúsculas (Caps Lock).
2. **Correo incorrecto:** Confirmar con el administrador el correo registrado en el sistema.
3. **Usuario inactivo:** El administrador puede reactivar el usuario desde el panel.

```bash
# El admin puede restablecer la contraseña vía Tinker
php artisan tinker
>>> $user = App\Models\User::where('email', 'correo@ejemplo.com')->first();
>>> $user->update(['password' => bcrypt('nueva_contraseña')]);
```

### No veo los módulos que debería ver

**Causa:** El rol asignado no tiene los permisos correctos.

**Solución (super_admin):**
1. Panel → Shield → Roles
2. Seleccionar el rol del usuario
3. Verificar y activar los permisos correspondientes

O regenerar todos los permisos:
```bash
php artisan shield:generate --all
```

---

## Problemas con Radicados

### El número de radicado no se genera

**Síntomas:** El campo de radicado queda vacío después de guardar.

**Causas:**
- La Unidad Organizacional no tiene una Entidad asignada
- La Serie Documental no tiene código configurado

**Solución:**
1. Verificar que la Unidad Organizacional tiene una Entidad asignada
2. Verificar que la Entidad tiene código (o nombre para derivar las siglas)
3. Verificar que la Serie Documental seleccionada tiene código

### Se generó un radicado duplicado

**Causa:** Condición de carrera en entornos de alta concurrencia.

**Solución (Tinker):**
```bash
php artisan tinker
>>> App\Models\AdministrativeAct::where('filing_number', '2026.AMB.01.001.SUR')->get()
```

Si hay duplicados, el administrador debe corregir manualmente el radicado de uno de los registros:
```bash
>>> $act = App\Models\AdministrativeAct::find(ID);
>>> $act->update(['filing_number' => '2026.AMB.01.002.SUR']);
```

---

## Problemas con Adjuntos PDF

### El PDF no se puede adjuntar (error de tamaño)

**Causa:** El PDF supera los **200 KB** permitidos para actos administrativos.

**Solución:** Comprimir el PDF antes de subirlo:
- Herramienta web: [ilovepdf.com](https://www.ilovepdf.com/)
- Herramienta de escritorio: Adobe Acrobat (Reducir tamaño de archivo)
- Linux: `gs -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -sOutputFile=compressed.pdf input.pdf`

:::note
Para registros de **Inventario FUID**, el límite es de 20 MB por archivo.
:::

### El conteo de folios es incorrecto

**Causa:** El sistema usa tres métodos para contar folios: `pdfinfo`, `pdfparser` y regex. Si el PDF está dañado o cifrado, el conteo puede fallar.

**Solución:** Editar el acto manualmente e ingresar el número de folios correcto en el campo correspondiente.

### No puedo ver los adjuntos confidenciales

**Causa:** Solo el creador del acto y el `super_admin` pueden ver los adjuntos confidenciales.

**Solución:** Si eres el creador y no ves la sección, verifica que estás viendo el acto en modo edición (no solo vista). Si no eres el creador, solicita al `super_admin` que consulte el archivo.

---

## Problemas con Reportes

### El reporte mensual no genera el Excel

**Síntomas:** El botón de exportar no descarga nada o da error.

**Causas y soluciones:**

1. **Error de permisos:** Verificar que el directorio `storage/` es escribible:
   ```bash
   chmod -R 775 storage
   chown -R www-data:www-data storage
   ```

2. **Falta la extensión PHP `zip`:**
   ```bash
   sudo apt install php8.2-zip
   sudo systemctl restart php8.2-fpm
   ```

3. **Error de memoria:** Aumentar `memory_limit` en `php.ini`:
   ```ini
   memory_limit = 256M
   ```

### Los correos del reporte no llegan

**Síntomas:** El botón "Enviar Reporte" no genera errores pero los correos no llegan.

**Diagnóstico:**
```bash
# Ver cola de trabajos fallidos
php artisan queue:failed

# Ver logs de correo
tail -f storage/logs/laravel.log | grep mail
```

**Soluciones:**

1. **Queue worker no está corriendo:**
   ```bash
   php artisan queue:work
   ```

2. **Configuración SMTP incorrecta:** Verificar variables en `.env`:
   ```env
   MAIL_HOST=smtp.ejemplo.com
   MAIL_PORT=587
   MAIL_USERNAME=tu@correo.com
   MAIL_PASSWORD=tu_contraseña
   MAIL_ENCRYPTION=tls
   ```

3. **Para pruebas, usar log:**
   ```env
   MAIL_MAILER=log
   ```
   Luego revisar `storage/logs/laravel.log` para ver el correo.

---

## Problemas de Importación

### La importación desde Excel falla

**Síntomas:** Aparecen errores en "Errores de Importación".

**Pasos de diagnóstico:**
1. Panel → **Errores de Importación** para ver el detalle de cada error
2. Verificar que el Excel sigue el formato de la plantilla exactamente
3. Revisar que los valores de listas (serie, unidad, etc.) existen en el sistema

**Causas frecuentes:**
- Nombre de serie documental no coincide exactamente
- Unidad organizacional no existe o está inactiva
- Fechas en formato incorrecto (usar `DD/MM/YYYY` o `YYYY-MM-DD`)
- Celdas con espacios al inicio/final

### La opción de importar no aparece en el menú

**Causa:** La unidad organizacional no tiene habilitado `can_import`.

**Solución (super_admin):**
1. Panel → **Unidades Organizacionales** → Editar
2. Activar **"Puede importar"**
3. Guardar

---

## Problemas de Rendimiento

### El panel carga muy lento

**Soluciones:**

1. **Activar caches en producción:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Verificar índices de base de datos:**
   ```sql
   EXPLAIN SELECT * FROM administrative_acts WHERE filing_number LIKE '2026%';
   ```

3. **Aumentar memoria PHP:**
   ```ini
   memory_limit = 512M
   max_execution_time = 120
   ```

---

## Errores Comunes de Laravel

| Error | Causa | Solución |
|-------|-------|---------|
| `SQLSTATE[HY000]: No such file or directory` | MySQL no está corriendo | Iniciar MySQL / Laragon |
| `500 Server Error` | Error de PHP o configuración | Revisar `storage/logs/laravel.log` |
| `419 CSRF Token Mismatch` | Sesión expirada | Recargar la página |
| `Class not found` | Falta ejecutar `composer install` | `composer install` |
| `Permission denied` en `storage/` | Permisos incorrectos | `chmod -R 775 storage` |
| `Please generate an application key` | Falta APP_KEY | `php artisan key:generate` |
