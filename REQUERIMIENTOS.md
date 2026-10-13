# KashaFin — Estado de requerimientos

Este archivo resume, en un solo lugar, **qué está hecho** y **qué se podría mejorar**. Es un
resumen de gestión — para el detalle técnico de cómo funciona cada cosa, ver `ARQUITECTURA.md`
(secciones 5-8 especialmente) y `GUIA_IAS_GRATUITAS.md` para la configuración de IA.

## 1. Resumen

- **64 de 64 RF implementados (100%)**, de punta a punta (modelo + validación + UI + pruebas).
- **90 tests automatizados pasando** (`php artisan test`).
- Ya no quedan funcionalidades completas fuera de alcance (ver sección 3 de todas formas, como
  referencia histórica de cómo se priorizó el desarrollo).

## 2. Requerimientos funcionales completados

| Código | Funcionalidad | RF cubiertos |
|---|---|---|
| F001 | Registro y autenticación | RF01, RF02, RF03, RF04 |
| F002 | Registro de ingresos | RF05, RF06, RF07, RF08, RF09 |
| F003 | Registro de gastos (+ filtros y orden extra) | RF10, RF11, RF12, RF13, RF14 |
| F004 | Proyección de liquidez | RF15, RF16, RF17, RF18 |
| F005 | Alertas de riesgo de iliquidez | RF19, RF20, RF21 |
| F006 | Metas de ahorro | RF22, RF23, RF24, RF45 |
| F007 | Reportes y visualización financiera (+ export PDF, comparación de periodos) | RF25, RF26, RF27, RF44 |
| F008 | Gestión de perfil de usuario | RF28, RF29, RF30 |
| F009 | Historial y búsqueda de movimientos (+ exportación completa, vaciar historial) | RF31-RF34, RF41, RF42 |
| F010 | Administración del sistema (+ ampliado, ver abajo) | RF35, RF36, RF37 |
| F011 | Notificaciones y recordatorios | RF38, RF39, RF40, RF43 |
| F013 | Presupuestos por categoría | RF46, RF47, RF48 |
| F014 | Préstamos entre personas | RF49-RF52 |
| F015 | Gastos compartidos | RF53-RF55 |
| F016 | Configuración general y preferencias | RF56, RF57, RF58 |
| F017 | Ayuda y soporte (FAQ + incidencias) | RF59, RF60 |
| F018 | Onboarding guiado | RF61, RF62 |
| F019 | Accesibilidad dedicada | RF63, RF64 |

### Funcionalidades agregadas que no estaban en la lista de RF original

- **Multi-tenencia por facultad**: cada facultad tiene su propio decano, que solo ve/administra a
  sus propios estudiantes; el admin master ve y controla todo (incluidas las cuentas de decano).
- **Verificación de correo por código al autoregistrarse**: antes de que la solicitud llegue al
  decano, el estudiante confirma su correo con un código de 6 dígitos enviado por correo — la
  cuenta no se crea hasta que lo ingresa bien.
- **Alta de estudiantes por aprobación o CSV masivo**, con correo real de contraseña temporal
  (Resend o SMTP genérico, configurable por `.env`) y cambio de contraseña obligatorio en el
  primer login.
- **Alta manual de estudiantes desde el panel admin**, con reasignación de facultad.
- **Chatbot con rotación entre hasta 3 proveedores de IA gratuitos**, *function calling* real, y
  un flujo de **"hablar con un asesor"**: el estudiante pide ayuda humana desde el chat, describe
  su problema, y el decano/admin recibe una notificación inmediata en `/admin/soporte`.
- **El chat responde con datos reales a cualquier pregunta esporádica sobre sus finanzas** (no
  solo "¿puedo gastar en X?" — cualquier duda ad-hoc, en cualquier forma en que la haga), dando
  una recomendación clara (sí/no/con precaución) contrastada contra su saldo, proyección, umbral
  de liquidez y presupuestos reales, en vez de una respuesta genérica.
- **Prompt del asistente editable**, idioma configurable (español/inglés/quechua), dictado por voz.
- **Activar/desactivar/eliminar cuentas de estudiante**, con cierre de sesión inmediato al
  desactivar.

## 3. Historial de priorización (referencia)

Para gestionar el alcance, el desarrollo se hizo en dos etapas:

1. **Núcleo de finanzas personales** (F001-F010, F013, F016) — construido primero porque es lo
   que un estudiante usa a diario.
2. **Funcionalidades complementarias** (F011, F014-F019) — construidas después: recordatorios,
   préstamos entre personas, gastos compartidos, ayuda/soporte, onboarding y accesibilidad.

Ambas etapas están completas; no queda ningún RF pendiente.

## 4. Qué se puede mejorar

Cosas que funcionan pero tienen una limitación conocida, o requieren una acción pendiente del
usuario — en orden de impacto:

1. **Reconstruir los assets del frontend antes de desplegar**: cada vez que se agregan o cambian
   clases de Tailwind (como en esta sesión), hace falta correr `npm run build` y volver a subir
   `public/build/` a `htdocs/build/` en el hosting — si no, los estilos nuevos no se aplican
   aunque el PHP esté actualizado (bug real encontrado y corregido durante esta sesión: el tour
   de bienvenida se veía roto hasta reconstruir el CSS).
2. **Envío de correo**: InfinityFree no da control de DNS sobre sus subdominios gratis
   (`*.infinityfree.me`, confirmado probándolo), así que no se pudo verificar un dominio en
   Resend — por ahora se usa **SMTP con Gmail** (`.env` ya configurado, ver `ARQUITECTURA.md`
   sección 6.1) como alternativa sin costo. Si más adelante se consigue un dominio propio con DNS
   controlable (ej. gratis en eu.org + Cloudflare), conviene volver a Resend, que es más robusto
   para volumen que una cuenta de Gmail personal.
3. **Rotar la API key de Resend expuesta en el chat** (ya no se usa con SMTP, pero sigue siendo
   buena práctica invalidarla en resend.com).
4. **Logo en PNG**: el componente soporta variante clara/oscura; confirmar que los archivos en
   `public/images/logo-dark.png`/`logo-light.png` sean el arte final y no el placeholder.
5. **Importación de estudiantes solo en CSV, no `.xls`/`.xlsx` directo** (decisión de seguridad,
   ver sección 9 de `ARQUITECTURA.md`).
6. **Voz requiere HTTPS en producción** (limitación del navegador, no de la app).
7. **Correo síncrono, no en cola**: correcto para InfinityFree (sin worker de colas); si el
   proyecto migra de hosting, conviene reactivar colas para no bloquear la respuesta HTTP.
8. **Fluidez conversacional, recomendaciones de gasto y "hablar con un asesor" dependen del
   modelo real**: las instrucciones del prompt (incluida la de responder preguntas esporádicas
   con datos reales) están probadas en cuanto a que llegan correctamente al modelo, pero qué tan
   bien las sigue un proveedor de IA concreto solo se verifica probándolo a mano, no con tests
   automatizados.
9. **Préstamos y gastos compartidos no entran en la proyección de liquidez**: es una decisión de
   diseño deliberada (son deudas entre personas, no ingreso/gasto propio), documentada en
   `ARQUITECTURA.md` sección 7.
10. **Sin pruebas de integración real contra Resend/Groq/OpenRouter/Gemini**: todo se prueba con
    `Http::fake()` / mailer `log`, nunca contra las APIs reales en el CI.

## 5. Dónde mirar para más detalle

- `ARQUITECTURA.md` — arquitectura técnica completa, decisiones de diseño, rutas, paquetes.
- `GUIA_IAS_GRATUITAS.md` — cómo obtener y configurar cada proveedor de IA gratuito.
- `actualizacion_incremental.sql` / `actualizacion_incremental_2.sql` — scripts para actualizar
  una base de datos de producción ya poblada, sin perder datos.
- `php artisan test` — suite de pruebas automatizadas (90 tests a la fecha de este documento).
