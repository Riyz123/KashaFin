# KashaFin — Estado de requerimientos

Este archivo resume, en un solo lugar, **qué está hecho** y **qué se podría mejorar**. Es un
resumen de gestión — para el detalle técnico de cómo funciona cada cosa, ver `ARQUITECTURA.md`
(secciones 5-8 especialmente) y `GUIA_IAS_GRATUITAS.md` para la configuración de IA.

## 1. Resumen

- **43 de 64 RF implementados (~67%)**, de punta a punta (modelo + validación + UI + pruebas),
  más varias funcionalidades que se agregaron sobre la marcha porque se pidieron explícitamente
  y no estaban en la lista original de RF (facultades/decanos, alta de estudiantes por
  aprobación/CSV, function calling real de la IA, rotación multi-proveedor, etc.).
- **54 tests automatizados pasando** (`php artisan test`).
- Lo que falta son principalmente **funcionalidades completas fuera de alcance** (ver sección 3),
  no bugs conocidos — los bugs reportados durante el desarrollo ya se corrigieron (ver sección 4).

## 2. Requerimientos funcionales completados

| Código | Funcionalidad | RF cubiertos |
|---|---|---|
| F001 | Registro y autenticación | RF01, RF02, RF03, RF04 |
| F002 | Registro de ingresos | RF05, RF06, RF07, RF08, RF09 |
| F003 | Registro de gastos (+ filtros y orden extra) | RF10, RF11, RF12, RF13, RF14 |
| F004 | Proyección de liquidez | RF15, RF16, RF17, RF18 |
| F005 | Alertas de riesgo de iliquidez | RF19, RF20, RF21 |
| F006 | Metas de ahorro | RF22, RF23, RF24, RF45 |
| F007 | Reportes y visualización financiera (+ export PDF) | RF25, RF26, RF27 |
| F008 | Gestión de perfil de usuario | RF28, RF29, RF30 |
| F009 | Historial y búsqueda de movimientos | RF31, RF32, RF33, RF34 |
| F010 | Administración del sistema (+ ampliado, ver abajo) | RF35, RF36 |
| F013 | Presupuestos por categoría | RF46, RF47, RF48 |
| F016 | Configuración general y preferencias | RF56, RF57, RF58 |

### Funcionalidades agregadas que no estaban en la lista de RF original

Se construyeron porque el usuario las pidió explícitamente en el camino, no porque un RF las exigiera:

- **Multi-tenencia por facultad**: cada facultad tiene su propio decano, que solo ve/administra a
  sus propios estudiantes; el admin master ve y controla todo (incluidas las cuentas de decano).
- **Alta de estudiantes por aprobación o CSV masivo**, con correo real de contraseña temporal
  (Resend) y cambio de contraseña obligatorio en el primer login — reemplaza por completo la
  verificación de correo por clic que traía Laravel Breeze.
- **Alta manual de estudiantes desde el panel admin**, con reasignación de facultad.
- **Chatbot con rotación entre hasta 3 proveedores de IA gratuitos** (Groq / OpenRouter /
  Gemini), con reinicio automático de cuota diaria y *function calling* real: el modelo decide
  solo cuándo registrar un gasto/ingreso/presupuesto, en lugar de depender de frases exactas.
- **Prompt del asistente editable desde el panel**, con idioma configurable por estudiante
  (español / inglés / quechua) y dictado por voz con autoenvío tras silencio.
- **Activar/desactivar/eliminar cuentas de estudiante** desde el panel (con cierre de sesión
  inmediato si la cuenta se desactiva mientras el estudiante está conectado).

## 3. Fuera de alcance (no implementado, documentado explícitamente)

| RF / Funcionalidad | Descripción |
|---|---|
| RF37 | Registro simple de incidencias reportadas por estudiantes |
| F011 (RF38-40) | Notificaciones y recordatorios más allá de la alerta de iliquidez: aviso de 3 días sin gastos, confirmación de ingreso esperado, toggles por tipo de notificación |
| RF41 | Exportación del historial completo de movimientos |
| RF42 | Eliminación de historial conservando la meta asociada |
| RF43 | Resumen semanal automático enviado por correo |
| RF44 | Comparación de gasto/ingreso contra el periodo anterior |
| F014 | Gestión de préstamos entre personas |
| F015 | Gastos compartidos entre varios usuarios |
| F017 | Ayuda y soporte (FAQ, reporte de incidencias) |
| F018 | Configuración inicial / onboarding guiado para un estudiante nuevo |
| F019 | Accesibilidad dedicada (más allá del HTML semántico y contraste ya presentes) |

## 4. Qué se puede mejorar

Cosas identificadas durante el desarrollo que funcionan, pero tienen una limitación conocida o
requieren una acción pendiente del usuario — en orden de impacto:

1. **Verificación de dominio en Resend (pendiente de confirmar)**: sin un dominio propio
   verificado en resend.com, el remitente de pruebas `onboarding@resend.dev` **no entrega** a
   correos `@upn.edu.pe` reales, solo al correo de la propia cuenta de Resend. Mientras no se
   verifique un dominio, los estudiantes no recibirán su contraseña temporal en producción.
2. **Rotar la API key de Resend expuesta en este chat**: se pegó una key real en texto plano
   durante la conversación — conviene revocarla en resend.com y generar una nueva, por higiene
   de seguridad, aunque nunca se haya commiteado al repo.
3. **Logo en PNG**: el componente ya soporta variante clara/oscura, pero los archivos actuales en
   `public/images/logo-dark.png` / `logo-light.png` son **placeholders** generados a partir del
   ícono anterior. Falta reemplazarlos por el arte final.
4. **Importación de estudiantes solo en CSV, no `.xls`/`.xlsx` directo**: se decidió no agregar
   `maatwebsite/excel` (tiene advisories de seguridad pendientes); el decano debe guardar su
   roster como CSV antes de subirlo. Si en algún momento se resuelve esa librería (o se evalúa
   una alternativa más acotada), se podría soportar Excel nativo.
5. **Voz requiere HTTPS en producción**: la Web Speech API del navegador no funciona en un
   origen sin HTTPS. Si el hosting final no tiene certificado, el dictado por voz quedará
   deshabilitado ahí aunque funcione en local.
6. **Correo enviado de forma síncrona (no en cola)**: los `Mailable` no usan `ShouldQueue` a
   propósito, porque InfinityFree no ofrece un proceso de worker para colas — esto es correcto
   para ese hosting, pero si el proyecto migra a un hosting con soporte de colas, convendría
   volver a activarlas para no bloquear la respuesta HTTP mientras se envía el correo.
7. **Fluidez conversacional del prompt no verificable por tests**: las instrucciones de tono
   (no repetir saludo, variar inicios de respuesta, etc.) dependen de que el modelo real las
   siga — ningún test automatizado con `Http::fake()` puede confirmar esto, solo probarlo a mano
   con un proveedor de IA activo.
8. **Dashboard admin es agregado, no por facultad**: las métricas de `/admin/dashboard` son
   globales (todas las facultades juntas); un decano no tiene su propio mini-dashboard de
   métricas de su facultad, solo la lista de estudiantes.
9. **Sin pruebas de integración real contra Resend/Groq/OpenRouter/Gemini**: todo lo relacionado
   a IA y correo está probado con `Http::fake()` / mailer `log`, nunca contra las APIs reales en
   el CI — correcto para no gastar cuota/costo en cada test, pero significa que un cambio de
   formato de respuesta de un proveedor externo no se detectaría hasta producción.
10. **RF37 y F011 (recordatorios)** son los más simples de agregar si se decide ampliar alcance
    más adelante — ya existe toda la infraestructura de alertas (`liquidity_alerts`) y de correo
    transaccional (Resend) que se podría reutilizar directamente.

## 5. Dónde mirar para más detalle

- `ARQUITECTURA.md` — arquitectura técnica completa, decisiones de diseño, rutas, paquetes.
- `GUIA_IAS_GRATUITAS.md` — cómo obtener y configurar cada proveedor de IA gratuito.
- `php artisan test` — suite de pruebas automatizadas (54 tests a la fecha de este documento).
