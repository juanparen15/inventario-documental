---
title: Usuario
description: Guía para el rol usuario (user) en el Sistema de Inventario Documental.
---

## Perfil del Usuario Estándar

El rol `usuario` (también llamado `user`) es el rol base del sistema. Corresponde a los funcionarios de las dependencias o secretarías que crean y consultan documentos de su propia unidad organizacional.

---

## Lo Que Puede Hacer el Usuario

| Función | Acceso |
|---------|--------|
| Ver actos de **su** unidad | ✓ |
| Crear actos administrativos | ✓ |
| Editar sus propios actos | ✓ |
| Adjuntar PDFs a sus actos | ✓ |
| Ver sección confidencial **de sus actos** | ✓ |
| Ver inventario de **su** unidad | ✓ |
| Crear registros de inventario FUID | ✓ |
| Ver actos de otras unidades | ✗ |
| Acceder a reportes mensuales | ✗ |
| Gestionar usuarios o configuración | ✗ |

:::note
El usuario solo puede ver y trabajar con los documentos de la unidad organizacional asignada en su perfil.
:::

---

## Flujo de Trabajo Típico

### Registrar un Acto Administrativo

```
1. Ir a "Actos Administrativos"
2. Clic en "Nuevo Acto"
3. Seleccionar Serie y Subserie del acto
4. Ingresar el asunto/objeto
5. Guardar → El radicado se genera automáticamente
6. Adjuntar el PDF oficial (dentro de los 30 días)
```

### Adjuntar el PDF de un Acto

```
1. Buscar el acto en la tabla
2. Clic en el ícono de editar (lápiz)
3. En la sección "Adjuntos", subir el PDF
4. Si han pasado más de 30 días: registrar la razón de tardanza
5. Guardar
```

### Registrar Inventario Documental

```
1. Ir a "Inventario Documental"
2. Clic en "Nuevo Registro"
3. Seleccionar la Serie y Subserie FUID
4. Completar título, fechas extremas y ubicación física
5. Guardar → El código de referencia se genera automáticamente
```

---

## Restricciones Importantes

### Solo Mi Unidad

El usuario solo ve los documentos de su unidad organizacional. Si necesitas acceder a documentos de otra dependencia, el administrador debe:
- Reasignar tu unidad en tu perfil de usuario, o
- Solicitar que el supervisor o super_admin genere el informe

### Adjuntos Confidenciales

Solo el creador de un acto puede ver su sección confidencial. Si creaste el acto, la sección confidencial aparece en el formulario de edición y en el modal de adjuntos.

Si otro usuario creó el acto, la sección confidencial no es visible.

### No Puedes Cambiar el Radicado

El número de radicado se asigna automáticamente al crear el acto y **no puede modificarse**. Si hay un error en el radicado, contacta al super_admin.

---

## Notificaciones

El sistema envía notificaciones en el panel cuando:

- Se realizan acciones relevantes sobre tus documentos
- Hay alertas del sistema configuradas por el administrador

Las notificaciones aparecen en el ícono de campana 🔔 en el encabezado y se actualizan cada 60 segundos.

---

## Cambiar tu Contraseña

1. Clic en tu nombre/avatar (esquina superior derecha)
2. Seleccionar **"Cambiar Contraseña"**
3. Ingresar la contraseña actual y la nueva
4. Guardar

---

## Preguntas Frecuentes

**¿Por qué no veo el número de radicado antes de guardar?**
La vista previa del radicado aparece al seleccionar la Unidad y la Serie. Si no aparece, verifica que ambos campos estén seleccionados.

**¿Qué hago si olvidé adjuntar el PDF antes de los 30 días?**
Edita el acto y adjunta el PDF. El sistema te pedirá registrar la **razón de la subida tardía**. Completa el campo con la justificación correspondiente.

**¿Puedo ver quién más modificó mi acto?**
El campo "Modificado por" en el formulario de edición muestra el último usuario que editó el acto. Para el historial completo, el super_admin puede consultar el log de auditoría.

**¿Cómo sé si mi unidad puede importar desde Excel?**
Si ves la opción "Importar" en el menú lateral, tu unidad tiene habilitada la importación. Si no aparece, contacta al administrador.
