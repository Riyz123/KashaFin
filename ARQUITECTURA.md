# KashaFin — Arquitectura del proyecto

> Aplicación web de finanzas personales para estudiantes universitarios. Construida sobre **Laravel 12**, con vistas **Blade** renderizadas en servidor, estilos con **Tailwind CSS** y una capa ligera de interactividad con **Alpine.js**.

## Índice

1. [Stack tecnológico](#1-stack-tecnológico)
2. [Arquitectura de la aplicación](#2-arquitectura-de-la-aplicación)
3. [Estructura de carpetas](#3-estructura-de-carpetas)
4. [Base de datos](#4-base-de-datos)
5. [Roles y panel de administración](#5-roles-y-panel-de-administración)
6. [Asistente de IA (chatbot)](#6-asistente-de-ia-chatbot)
7. [Requerimientos implementados](#7-requerimientos-implementados)
8. [Requerimientos fuera de alcance](#8-requerimientos-fuera-de-alcance)
9. [Paquetes instalados además del esqueleto base](#9-paquetes-instalados-además-del-esqueleto-base)
10. [Rutas principales](#10-rutas-principales)
11. [Cómo ejecutar el proyecto](#11-cómo-ejecutar-el-proyecto)
12. [Despliegue en hosting compartido](#12-despliegue-en-hosting-compartido)

---

## 1. Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12 (PHP 8.2), patrón MVC |
| Base de datos | SQLite (`database/database.sqlite`) |
| Autenticación | Laravel Breeze (stack Blade) |
| Vistas | Blade + componentes Blade reutilizables |
| Estilos | Tailwind CSS v3 (`tailwind.config.js`) + paleta de marca personalizada |
| Interactividad cliente | Alpine.js (drawer móvil, modales, toggles) — sin SPA ni framework JS pesado |
| Gráficos | Chart.js (proyección de liquidez, reportes) |
| Exportación PDF | barryvdh/laravel-dompdf |
| Exportación Excel/CSV | CSV nativo (`fputcsv`), sin dependencia externa |
| Build de assets | Vite |
| Envío de correo | Driver `log` (Mailables reales, sin SMTP configurado) |
| Colas | Driver `database` |

No se usa Livewire ni una API separada: toda la app es renderizada en servidor con formularios tradicionales y algo de `fetch()` puntual (categoría rápida, alerta de liquidez, tema).

## 2. Arquitectura de la aplicación

```
Request → Route (routes/web.php) → Middleware (auth) → Controller
        → Form Request (validación) → Modelo / Service → Vista Blade → Response
```

### Capas principales

- **Controladores** (`app/Http/Controllers/`): delgados, delegan la lógica de negocio a los *Services* y devuelven vistas o redirecciones.
- **Form Requests** (`app/Http/Requests/`): centralizan las reglas de validación de cada formulario (montos > 0, campos condicionales, etc.).
- **Modelos Eloquent** (`app/Models/`): representan las tablas, con relaciones, scopes y accessors (por ejemplo `Budget::percent_consumed`, `SavingsGoal::progress_percent`).
- **Services** (`app/Services/`): lógica de negocio que no pertenece a un modelo ni a un controlador:
  - `LiquidityProjectionService` — cálculo de saldo actual y proyección de liquidez a 7/15 días.
  - `RecurringIncomeService` — genera las ocurrencias de ingresos fijos vencidos.
  - `LiquidityAlertService` — decide si corresponde alertar (panel/correo) y aplica throttle de una vez al día.
  - `ReportService` — cálculos compartidos entre la vista de reportes, el PDF y el CSV.
- **Observers** (`app/Observers/UserObserver.php`): crea automáticamente la fila de `user_settings` cuando se registra un usuario nuevo.
- **View Composer** (`app/View/Composers/LayoutComposer.php`): inyecta la configuración del usuario (`$userSettings`) en el layout autenticado.
- **Mailables** (`app/Mail/LowLiquidityAlertMail.php`): correo de alerta de iliquidez.
- **Comandos de consola** (`app/Console/Commands/GenerateRecurringIncomes.php`): programado diariamente en `routes/console.php` para generar ingresos fijos vencidos.

### Layout y diseño

- `resources/views/layouts/app.blade.php`: shell autenticado con sidebar fija en escritorio y *drawer* deslizable en móvil (Alpine).
- `resources/views/layouts/guest.blade.php`: layout para login/registro con la identidad de marca.
- Componentes reutilizables en `resources/views/components/`: `sidebar`, `topbar`, `stat-card`, `progress-bar`, `alert-banner`, `currency`, `card`, `page-header`, `empty-state`, `icon`.
- Modo claro/oscuro controlado por clase (`darkMode: 'class'` en Tailwind), persistido en `user_settings.theme` y aplicado server-side (sin parpadeo).
- Paleta de marca definida en `tailwind.config.js`: `#14532D` (oscuro), `#22C55E` (principal), `#6EE7B7` (claro), `#F3FDF6` (fondo).

## 3. Estructura de carpetas

```
app/
├── Console/Commands/GenerateRecurringIncomes.php
├── Http/
│   ├── Controllers/          # Dashboard, Income, Expense, Category, Budget,
│   │                         # Projection, Goal, GoalContribution, Report,
│   │                         # History, Notification, Settings, Chat, Profile (Breeze)
│   │   └── Admin/            # DashboardController, UserController, CategoryController, AiProviderController
│   ├── Middleware/            # EnsureUserIsAdmin, EnsureAccountIsActive
│   └── Requests/             # Store/Update*Request por cada formulario
├── Mail/LowLiquidityAlertMail.php
├── Models/                   # User, UserSetting, Category, Income, Expense, Budget,
│                             # SavingsGoal, GoalContribution, LiquidityAlert,
│                             # AiProvider, AiUsageLog, ChatMessage, ChatState
├── Observers/UserObserver.php
├── Providers/AppServiceProvider.php
├── Services/
│   ├── LiquidityProjectionService.php, RecurringIncomeService.php,
│   │   LiquidityAlertService.php, ReportService.php
│   └── Ai/                    # ChatService, AiProviderRouter, AiReply, FuzzyMatch,
│       ├── Drivers/           #   OpenAiCompatibleDriver, GeminiDriver (+ AiDriverInterface)
│       ├── Exceptions/        #   QuotaExceededException
│       ├── ExpenseChatIntent.php, BudgetChatIntent.php, IncomeChatIntent.php
├── Support/WeekHelper.php
└── View/
    ├── Components/AppLayout.php, GuestLayout.php, AdminLayout.php
    └── Composers/LayoutComposer.php, ChatWidgetComposer.php

database/
├── migrations/                # 14 migraciones propias + las 3 de Laravel/Breeze
├── factories/                 # Category, Income, Expense, Budget, SavingsGoal, GoalContribution
└── seeders/                   # DatabaseSeeder, CategorySeeder

resources/
├── css/app.css                 # Tailwind + tokens de marca
├── js/
│   ├── app.js                  # bootstrap Alpine + montaje de gráficos + chat
│   ├── chat-voice.js            # Web Speech API (dictado continuo + lectura en voz alta)
│   └── charts/                 # liquidity-chart.js, report-charts.js
└── views/
    ├── layouts/, components/   # shell y piezas reutilizables (sidebar, admin-sidebar,
    │                           # layouts/admin.blade.php, chat-widget.blade.php)
    ├── dashboard/, incomes/, expenses/, budgets/, projections/,
    │   goals/, reports/, history/, notifications/, settings/, pdf/
    ├── admin/                  # dashboard, users/index, categories/index, ai/index
    ├── auth/, profile/         # generadas por Breeze
    └── emails/low-liquidity.blade.php

lang/es.json                    # traducciones de las cadenas en inglés de Breeze
routes/web.php, console.php

tests/Feature/                  # ExampleTest, Auth/*, ProfileTest, ChatToolCallingTest, ChatFuzzyIntentTest
```

## 4. Base de datos

Motor: **SQLite**, con `foreign_key_constraints = true` (borrado en cascada real). Además de las tablas por defecto de Laravel (`users`, `cache`, `jobs`), se agregaron **12 tablas nuevas**: las 8 del núcleo financiero descritas abajo, más `ai_providers`, `ai_usage_logs`, `chat_messages` y `chat_states` para el asistente de IA (ver [sección 6](#6-asistente-de-ia-chatbot)). La tabla `users` y la tabla `categories` recibieron además columnas extra para soportar roles y administración (ver [sección 5](#5-roles-y-panel-de-administración)):

- `users.role` — enum `estudiante` \| `admin`, por defecto `estudiante`.
- `users.is_active` — boolean, por defecto `true`; permite al administrador bloquear una cuenta.
- `categories.is_active` — boolean, por defecto `true`; permite al administrador desactivar una categoría global sin borrarla (evita romper gastos/presupuestos existentes que la referencian).

### `user_settings` (1:1 con `users`)
Preferencias y configuración financiera del estudiante.

| Columna | Tipo | Notas |
|---|---|---|
| `user_id` | FK única → `users` | cascade on delete |
| `currency` | string(3) | `PEN` \| `USD` |
| `theme` | string | `light` \| `dark` |
| `week_start_day` | tinyint | 0=domingo … 6=sábado |
| `liquidity_threshold` | decimal(10,2) | umbral mínimo configurable (RF19) |
| `notify_low_liquidity_by_email` | boolean | toggle de alerta por correo |
| `starting_balance` | decimal(10,2) | ancla para el saldo actual |

### `categories`
Predefinidas (compartidas) + personalizadas por usuario, en una sola tabla.

| Columna | Tipo | Notas |
|---|---|---|
| `user_id` | FK nullable → `users` | `NULL` = categoría predefinida (seed) |
| `name` | string | única por `(user_id, name)` |
| `icon` | string nullable | |
| `is_default` | boolean | |

### `incomes`
Ingresos fijos y variables. Los fijos usan patrón plantilla/ocurrencia.

| Columna | Tipo | Notas |
|---|---|---|
| `user_id` | FK → `users` | cascade on delete |
| `parent_income_id` | FK nullable → `incomes` (self) | liga la ocurrencia generada con su plantilla |
| `amount` | decimal(10,2) | validado > 0 |
| `date` | date | |
| `description` | string | |
| `type` | enum `fijo` \| `variable` | |
| `frequency` | enum `semanal`\|`quincenal`\|`mensual`, nullable | solo si `type = fijo` |
| `next_occurrence_date` | date nullable | solo en la plantilla |

### `expenses`

| Columna | Tipo | Notas |
|---|---|---|
| `user_id` | FK → `users` | cascade on delete |
| `category_id` | FK nullable → `categories` | null on delete |
| `amount` | decimal(10,2) | validado > 0 |
| `date` | date | |
| `description` | string nullable | |

### `budgets`
Presupuesto mensual por categoría.

| Columna | Tipo | Notas |
|---|---|---|
| `user_id` | FK → `users` | |
| `category_id` | FK → `categories` | |
| `period_month` | date | normalizado al día 1 del mes |
| `amount` | decimal(10,2) | |

Único por `(user_id, category_id, period_month)`.

### `savings_goals`

| Columna | Tipo | Notas |
|---|---|---|
| `user_id` | FK → `users` | |
| `name` | string | |
| `target_amount` | decimal(10,2) | |
| `target_date` | date nullable | opcional |
| `current_amount` | decimal(10,2) | acumulado de aportes |
| `status` | enum `active` \| `completed` | |
| `completed_at` | timestamp nullable | para el historial (RF45) |

### `goal_contributions`
Aportes individuales a una meta (para trazabilidad e historial).

| Columna | Tipo | Notas |
|---|---|---|
| `savings_goal_id` | FK → `savings_goals` | |
| `user_id` | FK → `users` | |
| `amount` | decimal(10,2) | |
| `date` | date | |
| `note` | string nullable | |

### `liquidity_alerts`
Registro de cada alerta emitida (para Notificaciones y throttle diario).

| Columna | Tipo | Notas |
|---|---|---|
| `user_id` | FK → `users` | |
| `projected_balance` | decimal(10,2) | |
| `threshold` | decimal(10,2) | |
| `channel` | enum `dashboard` \| `email` | |
| `sent_at` | timestamp | |

### Relaciones (resumen)

```
User 1─1 UserSetting
User 1─N Income, Expense, Category, Budget, SavingsGoal, LiquidityAlert
Income  N─1 Income (parent_income_id, autorreferencia)
Expense N─1 Category
Budget  N─1 Category
SavingsGoal 1─N GoalContribution
```

Todas las FK hacia `user_id` tienen `cascadeOnDelete()`, por lo que eliminar una cuenta (RF30) borra automáticamente todos sus datos.

## 5. Roles y panel de administración

KashaFin tiene **tres tipos de cuenta** sobre la misma tabla `users`, diferenciadas por la columna `role`:

- **`estudiante`** (por defecto): accede a toda la app descrita en las secciones anteriores — ingresos, gastos, presupuesto, proyecciones, metas, reportes, historial, configuración y perfil.
- **`decano`**: cuenta de staff acotada a **una sola facultad** (`users.faculty_id`). Ve y administra (activar/desactivar/eliminar) solo a los estudiantes de su propia facultad, más el panel de categorías globales compartido — nada de panel de IA, métricas globales ni gestión de facultades. Puede exportar un CSV de sus propios estudiantes (activos/inactivos) desde `/admin/usuarios/exportar`.
- **`admin`** (master): accede a un panel separado en `/admin/*`, con su propio layout, sidebar y navegación (`resources/views/layouts/admin.blade.php` + `<x-admin-sidebar>`), visualmente distinto (sidebar oscuro). Ve y controla todo: todos los estudiantes de todas las facultades, todas las cuentas de decano, proveedores de IA, y la creación de facultades/decanos.

### Facultades y decanos (`App\Models\Faculty`, `Admin\FacultyController`)

- Cada facultad (`facultades`) tiene como máximo un decano. El admin master las crea y les asigna un decano desde `/admin/decanos` (nombre, correo, contraseña — la cuenta queda creada ya verificada, no pasa por el flujo de autoregistro).
- `User::canManage(User $target)` centraliza la regla de autorización: un admin puede gestionar cualquier cuenta que no sea admin; un decano solo estudiantes de su propia `faculty_id`. La usan `AdminUserController::toggleActive/destroy`.
- Middleware `staff` (`EnsureUserIsStaff`, admin **o** decano) protege `/admin/usuarios` y `/admin/categorias`; middleware `admin` (solo master) protege `/admin/dashboard`, `/admin/ia` y `/admin/decanos`.
- Un decano que inicia sesión cae directo en `/admin/usuarios` (no tiene dashboard de estudiante); el middleware `student-app` (`EnsureUserIsStudentApp`) lo redirige ahí si intenta entrar a cualquier ruta de la app de estudiante. El admin master sigue pudiendo entrar a la app de estudiante a propósito, vía "Ver app de estudiante".

### Cómo se separan las dos áreas

- **Registro público** (`/register`): siempre crea una **solicitud pendiente** de estudiante (`role = estudiante`, `approved_at = NULL`, contraseña aleatoria que nadie conoce), exigiendo un correo `@upn.edu.pe` y una facultad. No hay forma de auto-registrarse como admin o decano, esas cuentas solo se crean desde el panel.
- **Aprobación por el decano** (reemplaza la verificación por correo de Breeze, que se quitó — ver más abajo): el decano de esa facultad (o el admin master) ve la solicitud en `/admin/usuarios` con badge "Pendiente" y la aprueba con un clic (`AdminUserController::approve`). Al aprobar, se genera una contraseña real al azar (`Str::password`), se marca `approved_at`/`is_active`/`must_change_password = true`, y se envía por correo (`App\Mail\TemporaryPasswordMail`, ver [sección 6.1](#61-correo-y-alta-de-estudiantes)). El estudiante entra con esa contraseña y el middleware `password-fresh` (`EnsurePasswordIsFresh`) lo manda directo a `/cambiar-contrasena` antes de dejarlo tocar cualquier otra cosa; al guardar su propia contraseña, el flag se limpia y ya es un estudiante normal.
- **Alta masiva por CSV** (`/admin/usuarios/importar`, mismo botón "Importar CSV" en la lista de usuarios): el decano (o el admin, eligiendo la facultad) sube un CSV con columnas `nombre,correo` — cada fila válida nace **ya aprobada** (sin esperar un segundo clic) con el mismo mecanismo de contraseña aleatoria + correo. Filas con correo que no termina en `@upn.edu.pe` o ya existente se omiten y se listan en el mensaje de resultado, nunca se descartan en silencio. No se soporta `.xls` directamente (ver nota sobre `maatwebsite/excel` en la [sección 9](#9-paquetes-instalados-además-del-esqueleto-base)) — hay que guardarlo como CSV primero.
- **Middleware `admin`** (`app/Http/Middleware/EnsureUserIsAdmin.php`, alias registrado en `bootstrap/app.php`): protege todas las rutas bajo `Route::prefix('admin')`; devuelve `403` si el usuario autenticado no es admin.
- **Redirección post-login**: `AuthenticatedSessionController` revisa `$user->isAdmin()` y manda al admin a `admin.dashboard` en vez de `dashboard`. La ruta raíz `/` hace lo mismo.
- **Acceso cruzado**: un admin puede entrar a la app de estudiante desde el enlace "Ver app de estudiante" en su sidebar (sin restricción, ya que no hay necesidad de bloquearlo); un estudiante que intente visitar `/admin/*` recibe `403`.

### Control de cuentas (lo pedido explícitamente)

- **`users.is_active`**: el administrador activa/desactiva cualquier cuenta de estudiante desde `/admin/usuarios` (`AdminUserController::toggleActive`). Las cuentas admin están protegidas: no se pueden desactivar ni eliminar entre sí.
- **Bloqueo en el login**: `LoginRequest::authenticate()` revisa `is_active` justo después de validar la contraseña; si la cuenta está desactivada, cierra la sesión y muestra "Tu cuenta ha sido desactivada. Contacta a un administrador."
- **Bloqueo en caliente**: el middleware `app/Http/Middleware/EnsureAccountIsActive.php` (alias `active`, aplicado a todos los grupos de rutas autenticadas) cierra la sesión inmediatamente si el admin desactiva a un estudiante que ya tenía una sesión abierta — no espera a que vuelva a iniciar sesión.
- **Eliminar cuenta**: `AdminUserController::destroy` borra al estudiante y, por los `cascadeOnDelete()` ya descritos, todos sus ingresos/gastos/metas/etc.

### Qué más gestiona el panel admin

- **Dashboard** (`/admin/dashboard`, RF36): métricas agregadas y anónimas — total de estudiantes, cuentas activas/desactivadas, cantidad de ingresos/gastos/presupuestos/metas/categorías registrados en todo el sistema. **No** expone montos ni movimientos de ningún estudiante en particular.
- **Categorías globales** (`/admin/categorias`, RF35): el admin crea, renombra y activa/desactiva las categorías de gasto compartidas (`categories.user_id = NULL`). Una categoría desactivada deja de aparecer en el selector de gastos de los estudiantes (`Category::scopeForUser` filtra por `is_active = true`), pero no se borra — así no rompe gastos/presupuestos existentes que ya la usan.

### Cuentas de prueba

El seeder crea 6 facultades (`FacultySeeder`) y **tres usuarios**: el admin master (`admin@kashafin.test`), un decano demo (`decano@kashafin.test`, asignado a la primera facultad) y el estudiante demo (`demo@kashafin.test`, misma facultad que el decano), los tres con contraseña `password`. Ver [sección 11](#11-cómo-ejecutar-el-proyecto).

## 6. Asistente de IA (chatbot)

Burbuja flotante visible en toda la app de estudiante (`resources/views/components/chat-widget.blade.php`, incluida solo en `layouts/app.blade.php`), que da recomendaciones financieras y permite registrar gastos por voz o texto.

### Rotación entre proveedores gratuitos

El admin agrega proveedores de IA desde `/admin/ia` (tabla `ai_providers`): nombre, tipo (`openai_compatible` — Groq, OpenRouter, etc. — o `gemini`, que tiene un formato de API distinto), URL base, modelo, API key (guardada con cast `encrypted`, nunca se muestra en texto plano) y prioridad. `App\Services\Ai\AiProviderRouter` toma el primero activo y disponible por prioridad; `App\Services\Ai\ChatService` lo intenta, y si tira una `QuotaExceededException` (HTTP 429 o mensaje de cuota agotada) lo marca `is_exhausted` y sigue con el siguiente — hasta 3 por mensaje para no colgar la respuesta. Un proveedor agotado se recupera solo cuando pasa su `period_reset_at` (configurable: cada cuántos días se reinicia), sin necesidad de un cron aparte. Si fallan todos (o no hay ninguno configurado), el chat responde con un resumen de los datos del estudiante calculado sin IA, en vez de un error.

El panel admin muestra el **% de uso** de cada proveedor (`requests_used` / `quota_limit`, con `<x-progress-bar>`) y el estado (activo/inactivo/agotado).

### Personalización ("aprender" de cada estudiante)

No es reentrenamiento del modelo — ninguna API gratuita de terceros lo expone. En cada mensaje, `ChatService` arma un resumen fresco con los datos reales del estudiante (saldo actual, ingresos/gastos del mes, presupuestos, metas activas, proyección de liquidez — reutilizando `LiquidityProjectionService` y `ReportService`) y se lo manda al modelo como contexto, junto con los últimos 10 mensajes de la conversación (`chat_messages`). Así las respuestas son personalizadas sin entrenar nada.

### Registrar gastos, ingresos o presupuestos por chat (nunca vía IA ciega)

Hay dos caminos para registrar un gasto, un ingreso o un presupuesto por chat, en este orden:

1. **Reconocimiento difuso, sin IA** (`App\Services\Ai\ExpenseChatIntent` / `BudgetChatIntent` / `IncomeChatIntent`, método `handle()`): detecta la intención de agregar un gasto/presupuesto/ingreso buscando una palabra de acción ("agrega", "registra", "anota", "pon"...) junto con el sustantivo correspondiente ("gasto"/"presupuesto"/"ingreso") en cualquier parte del mensaje — no una frase exacta. La comparación es **difusa** (`App\Services\Ai\FuzzyMatch`, por distancia de Levenshtein) para tolerar errores de tipeo ("agrga un gsto"), tildes/acentos omitidos, palabras repetidas, y pronombres pegados al verbo como en español ("Agrégame", "anótame", "regístrame" — no es un typo, es gramática normal, así que se les quita el sufijo antes de comparar). Deliberadamente **no** incluye verbos genéricos como "quiero" como gatillo — eso causaría falsos positivos en preguntas normales ("quiero saber cuánto gasto tengo"). Monto/categoría/tipo/frecuencia se extraen con la misma técnica difusa. Para ingresos pregunta además si es fijo o variable y, si es fijo, cada cuánto se repite (reutiliza `Income::nextOccurrenceAfter()` para calcular `next_occurrence_date`, igual que el formulario normal). Es gratis e instantáneo, y sigue funcionando aunque el estudiante no tenga ningún proveedor de IA configurado. El estado de la conversación en curso (`chat_states`, una fila por estudiante) guarda qué falta por preguntar.
2. **Function calling real con la IA** (si el mensaje no calzó con ningún gatillo exacto): `ChatService` le declara al modelo tres herramientas — `add_expense`, `add_budget` y `add_income` — con sus parámetros en formato JSON Schema (la categoría restringida a un `enum` con los nombres reales del estudiante, para que la coincidencia sea exacta, no difusa). El modelo decide solo cuándo invocar una, con qué datos, a partir de **cualquier** forma de pedirlo ("me gasté 20 en...", "ponme un tope de...", "me depositaron mi beca...") — ya no depende de que yo anticipe la frase exacta. Si falta un dato obligatorio, el modelo simplemente pregunta en texto normal (usa el historial de `chat_messages` para recordar la respuesta en el siguiente turno) en vez de inventarlo. Cuando sí invoca una herramienta, `ChatService` la ejecuta llamando a `*ChatIntent::createFromToolCall()` correspondiente — que **vuelven a validar** cada dato antes de guardar nada, nunca confían ciegamente en lo que extrajo el modelo — y arma la confirmación él mismo (nunca le pide al modelo que "redacte la confirmación": costaría una llamada extra y podría desviarse del monto real guardado).

Para todo lo que no sea una de esas dos acciones (saludos, preguntas, pedir un análisis o reporte), el modelo simplemente responde en texto libre, usando el mismo resumen de datos del estudiante como contexto (ahora incluye el desglose de gastos por categoría del mes). Sin ningún proveedor de IA activo, este camino libre cae al resumen de datos sin IA — eso es inevitable sin un modelo real detrás, no una limitación de la arquitectura.

Probado con `tests/Feature/ChatToolCallingTest.php` usando `Http::fake()` (sin depender de ninguna API key real): simula la respuesta de un proveedor con un `tool_call` de cada tipo, una respuesta de texto plano normal, y un caso donde el modelo manda un monto inválido (confirma que se rechaza en vez de guardarse).

### Idiomas y prompt editable

- **Español, inglés y quechua**: el system prompt instruye al modelo a entender los tres y responder en el idioma que el estudiante configuró en `/configuracion` (`user_settings.language`, `es`/`en`/`qu`) — no en el que detecte del último mensaje, para que la conversación no cambie de idioma sola a media conversación.
- **Prompt editable desde el panel** (`App\Models\AiSetting`, fila única; `/admin/ia`): el admin master edita el párrafo de personalidad/tono del asistente, con botón "Restaurar por defecto". El manejo de idiomas y las tres herramientas (`add_expense`/`add_budget`/`add_income`) quedan fuera de ese texto — son parte fija del código, así un prompt mal editado nunca puede romper la funcionalidad, solo el tono.

### Voz

Web Speech API del navegador (sin backend ni costo): `SpeechRecognition` dicta con `continuous: true` (para no cortar tras una sola palabra) y se autoenvía sola tras ~1.5s de silencio sin habla nueva, o de inmediato si el estudiante pulsa el botón del micrófono para pausar antes; `speechSynthesis` lee la respuesta en voz alta (toggle en el widget). **Requiere HTTPS en producción** — si el hosting no tiene HTTPS, el micrófono no funcionará ahí aunque sí en local.

### 6.1 Correo y alta de estudiantes

KashaFin envía correos reales (contraseñas temporales) vía **Resend** (`resend/resend-php`, mailer nativo de Laravel 12 — `MAIL_MAILER=resend`). No hay ninguna integración con Outlook/Microsoft Graph: se eligió Resend porque se configura con una sola API key, sin que el hosting necesite credenciales de tu cuenta de correo ni registrar una app en Azure AD.

**Para que funcione en producción**, el usuario del proyecto debe:
1. Crear una cuenta gratis en [resend.com](https://resend.com).
2. Verificar un dominio propio en Resend (agregar los registros DNS que te da) **o**, para probar rápido sin dominio propio, usar el remitente de pruebas `onboarding@resend.dev` — pero ese remitente de pruebas de Resend solo entrega al correo de la cuenta de Resend, no a correos `@upn.edu.pe` reales. Para que los estudiantes reciban su contraseña de verdad, hace falta verificar un dominio propio.
3. Copiar la API key generada a `RESEND_KEY` en `.env`, y poner `MAIL_MAILER=resend` (en local se deja en `log`: el correo se escribe en `storage/logs/laravel.log` en vez de enviarse, para no gastar cuota probando).

`App\Mail\TemporaryPasswordMail` es el único correo transaccional del sistema — lo dispara `AdminUserController::grantAccess()`, compartido por dos caminos:
- **Aprobación individual**: un estudiante se autoregistra (`/register`, solo correo `@upn.edu.pe` + facultad, sin contraseña — queda con `approved_at = NULL`), su decano lo ve como "Pendiente" en `/admin/usuarios` y lo aprueba con un clic.
- **Importación CSV** (`/admin/usuarios/importar`): el decano sube un roster con columnas `nombre,correo` y cada fila válida se da de alta ya aprobada, sin pasar por el paso de solicitud pendiente.

En ambos casos se genera una contraseña real al azar (nunca elegida por nadie), se envía por correo, y la cuenta queda con `must_change_password = true` — el middleware `password-fresh` obliga a cambiarla en el primer login (`/cambiar-contrasena`) antes de dejar pasar a cualquier otra ruta. Esto **reemplazó** la verificación de correo por clic de Breeze (que se quitó del proyecto: controladores, rutas y test de `EmailVerificationTest` eliminados) — recibir esa contraseña ya es la prueba de que el correo es real y de que el decano aprobó a esa persona, así que un segundo paso de verificación habría sido redundante.

## 7. Requerimientos implementados

Se implementaron **43 de los 64 RF (~67%)**, agrupados por funcionalidad completa de extremo a extremo (modelo + validación + UI), no como una cobertura superficial.

| Código funcionalidad | Funcionalidad | RF cubiertos |
|---|---|---|
| F001 | Registro y autenticación | RF01, RF02, RF03, RF04 |
| F002 | Registro de ingresos | RF05, RF06, RF07, RF08, RF09 |
| F003 | Registro de gastos | RF10, RF11, RF12, RF13, RF14 |
| F004 | Proyección de liquidez | RF15, RF16, RF17, RF18 |
| F005 | Alertas de riesgo de iliquidez | RF19, RF20, RF21 |
| F006 | Metas de ahorro | RF22, RF23, RF24, RF45 |
| F007 | Reportes y visualización financiera | RF25, RF26, RF27 |
| F008 | Gestión de perfil de usuario | RF28, RF29, RF30 |
| F009 | Historial y búsqueda de movimientos | RF31, RF32, RF33, RF34 |
| F010 | Administración del sistema | RF35, RF36 (ver [sección 5](#5-roles-y-panel-de-administración)) |
| F013 | Presupuestos por categoría | RF46, RF47, RF48 |
| F016 | Configuración general y preferencias | RF56, RF57, RF58 |

> F010 se amplió más allá de lo original: además de las métricas agregadas (RF36) y la gestión de categorías globales (RF35), el admin master puede **activar/desactivar y eliminar cuentas de estudiante**, y ahora existe un segundo nivel de administración — **decanos acotados por facultad** (ver [sección 5](#5-roles-y-panel-de-administración)) — ninguno de los dos estaba en la tabla de requerimientos original, se pidieron explícitamente.

> F003 también se amplió: además del CRUD de gastos (RF10-14), la lista en `/gastos` tiene filtros independientes por descripción, categoría y rango de fechas, más orden por monto (mayor a menor / menor a mayor) o por fecha — se combinan todos entre sí (AND) y se conservan al paginar (`ExpenseController::index`, con `withQueryString()`).

### Decisiones de diseño relevantes

- **Ingresos fijos**: la fila `fijo` actúa como plantilla con `next_occurrence_date`; un comando programado diario (`incomes:generate-recurring`) clona la ocurrencia real y avanza la plantilla (RF08).
- **Proyección (RF16)**: los ingresos fijos se proyectan por fecha exacta desde la plantilla; los ingresos variables y los gastos se suavizan como tasa diaria promedio de las últimas 4 semanas.
- **Alertas (RF19-21)**: umbral configurable por usuario, verificación automática al cargar el dashboard (con límite de una alerta por día) y botón manual "Revisar ahora" en Notificaciones que ignora ese límite.
- **Aportes a metas (RF24)**: si un aporte haría caer la proyección de liquidez por debajo del umbral, se muestra una advertencia en vez de bloquear la acción; el usuario puede confirmar igual.

## 8. Requerimientos fuera de alcance

No implementados en esta versión (documentado explícitamente, sin dejar código a medias):

- RF37 — Registro simple de incidencias reportadas por estudiantes (el resto de F010 sí está implementado, ver arriba)
- F011 — Notificaciones y recordatorios (más allá de la alerta de iliquidez): recordatorio de 3 días sin gastos (RF38), confirmación de ingreso esperado (RF39), toggles individuales por tipo (RF40)
- RF41-44 — Exportación de historial completo, eliminación de historial conservando meta, resumen semanal automático por correo, comparación contra periodo anterior
- F014 — Gestión de préstamos entre personas
- F015 — Gastos compartidos
- F017 — Ayuda y soporte (FAQ, reporte de incidencias)
- F018 — Configuración inicial / onboarding guiado
- F019 — Accesibilidad dedicada (más allá de HTML semántico y contraste razonable ya presentes)

## 9. Paquetes instalados además del esqueleto base

El proyecto partió de un Laravel 12 recién creado (solo `laravel/framework` y `laravel/tinker`, Tailwind v4 sin configurar). Se instaló:

### Composer

| Paquete | Versión | Uso |
|---|---|---|
| `laravel/breeze` (dev) | ^2.4 | Scaffolding de autenticación (stack Blade): login, registro, recuperación de contraseña, perfil (la verificación de email que trae por defecto se quitó, ver [sección 6.1](#61-correo-y-alta-de-estudiantes)) |
| `barryvdh/laravel-dompdf` | ^3.1 | Exportación de reportes a PDF (RF27) |
| `resend/resend-php` | ^1.16 | SDK que usa el mailer nativo `resend` de Laravel para enviar la contraseña temporal a estudiantes nuevos |

> Se probó `maatwebsite/excel`, pero solo instalaba una versión antigua (v1.1.5, con `phpoffice/phpexcel` **abandonado** y 20 advisories de seguridad) por requerir PHP ^8.3 la versión actual. Se descartó y se optó por **CSV nativo** (`fputcsv`/`fgetcsv`), que Excel abre y guarda sin problema — mismo enfoque para export (reportes, listados) e import (alta de estudiantes), sin dependencias inseguras.

### npm

| Paquete | Versión | Uso |
|---|---|---|
| `alpinejs` | ^3.4 | Interactividad cliente (drawer móvil, modales, toggle de tema) — instalado junto con Breeze |
| `chart.js` | ^4.5 | Gráfico de proyección de liquidez y gráficos de reportes |
| `tailwindcss` (downgrade a v3) | ^3.1 | Breeze instaló la v3 con `tailwind.config.js` clásico (el esqueleto traía v4 sin configurar) |
| `@tailwindcss/forms`, `autoprefixer`, `postcss` | — | Dependencias del stack Blade de Breeze |

### Otros cambios de configuración

- `APP_LOCALE=es` + `lang/es.json`: traduce al español las cadenas en inglés que trae Breeze por defecto (labels de formularios, mensajes de la vista de perfil, etc.)
- `APP_NAME=KashaFin`
- `tailwind.config.js`: `darkMode: 'class'` + paleta de marca (`brand.dark/DEFAULT/light/surface`)

## 10. Rutas principales

Todas bajo `middleware(['auth', 'active'])`, más las rutas de invitado que trae Breeze (`login`, `register`, `forgot-password`, etc.):

| Ruta | Descripción |
|---|---|
| `GET /dashboard` | Panel principal |
| `GET/POST /ingresos`, `/ingresos/{id}/edit`, etc. | CRUD de ingresos |
| `GET/POST /gastos`, `/gastos-categorias` | CRUD de gastos + categorías rápidas |
| `GET/POST /presupuesto` | CRUD de presupuestos |
| `GET /proyecciones` | Proyección de liquidez (7/15 días) |
| `GET/POST /metas`, `/metas/{id}/aportes`, `/metas-historial` | CRUD de metas + aportes + historial |
| `GET /reportes`, `/reportes/pdf`, `/reportes/csv` | Reportes + exportación |
| `GET /historial`, `POST /historial/{tipo}/{id}/duplicar` | Historial unificado |
| `GET/POST /notificaciones` | Alertas de liquidez |
| `GET/PATCH /configuracion` | Preferencias del usuario |
| `GET/PATCH/DELETE /profile` | Perfil (Breeze) |

Bajo `middleware(['auth', 'active', 'admin'])`, prefijo `/admin`:

| Ruta | Descripción |
|---|---|
| `GET /admin/dashboard` | Métricas agregadas del sistema |
| `GET /admin/usuarios` | Lista de estudiantes (buscar, filtrar por estado) |
| `PATCH /admin/usuarios/{id}/estado` | Activar/desactivar una cuenta |
| `DELETE /admin/usuarios/{id}` | Eliminar una cuenta y todos sus datos |
| `GET/POST /admin/categorias` | Crear categorías globales |
| `PATCH /admin/categorias/{id}` | Renombrar una categoría global |
| `PATCH /admin/categorias/{id}/estado` | Activar/desactivar una categoría global |
| `GET/POST /admin/ia`, `PUT /admin/ia/{id}`, `PATCH .../estado`, `DELETE /admin/ia/{id}` | Gestión de proveedores de IA |
| `PUT /admin/ia-prompt`, `DELETE /admin/ia-prompt` | Editar o restaurar el prompt del asistente (solo admin master) |
| `GET /admin/usuarios/exportar` | CSV de estudiantes (acotado a la facultad si es decano) |
| `GET/POST /admin/usuarios/importar` | Alta masiva de estudiantes por CSV (ya aprobados) |
| `PATCH /admin/usuarios/{id}/aprobar` | Aprobar una solicitud pendiente (genera y envía la contraseña) |
| `GET/PUT /cambiar-contrasena` | Forzar el cambio de contraseña temporal en el primer login |
| `GET/POST /admin/decanos`, `POST /admin/decanos/{faculty}/decano` | Crear facultades y asignarles un decano (solo admin master) |

Y en el grupo de estudiante: `POST /asistente/mensaje` (`chat.send`) — envía un mensaje al asistente (recomendaciones o "agrega un gasto") y devuelve la respuesta en JSON.

## 11. Cómo ejecutar el proyecto

```bash
composer install
npm install
cp .env.example .env   # si no existe
php artisan key:generate
php artisan migrate:fresh --seed
npm run build           # o `npm run dev` en desarrollo
php artisan serve
```

Cuentas de prueba ya cargadas por el seeder (las tres con contraseña `password`):

| Cuenta | Email | Rol |
|---|---|---|
| Administrador | `admin@kashafin.test` | `admin` — entra directo al panel `/admin`, ve y controla todo |
| Decano demo | `decano@kashafin.test` | `decano` — acotado a la primera facultad sembrada, entra directo a `/admin/usuarios` |
| Estudiante demo | `demo@kashafin.test` | `estudiante` (misma facultad que el decano demo) — incluye ingresos, gastos, presupuestos y metas de ejemplo para que el dashboard no se vea vacío |

Sin ningún proveedor de IA configurado, el asistente sigue funcionando: "agrega un gasto" funciona igual (es determinístico) y las preguntas libres devuelven el resumen de datos sin IA. Para que responda con un modelo real, entra como admin a `/admin/ia` y agrega al menos un proveedor con su API key.

## 12. Despliegue en hosting compartido

El proyecto está desplegado en un hosting compartido (InfinityFree) que no permite apuntar el dominio directo a `public/`. El layout en el hosting es distinto al de desarrollo local:

```
htdocs/                  ← raíz pública del hosting
├── index.php            ← copia modificada (ver abajo)
├── build/                ← contenido de public/build/ copiado aquí (CSS/JS compilado, gitignored)
└── kashafin/             ← el proyecto completo (app/, vendor/, bootstrap/, routes/, etc.)
```

- **`index.php`**: en el repo (`public/index.php`) se mantiene siempre como el Laravel estándar (`../vendor/autoload.php`, `../bootstrap/app.php`) para que el desarrollo local no se vea afectado. **Antes de subir al hosting**, esos `require` deben apuntar a `kashafin/vendor/autoload.php` y `kashafin/bootstrap/app.php` en vez de `../`, porque en el hosting este archivo vive un nivel arriba de esa carpeta, no dentro de `public/`. Avisar explícitamente cuándo corresponde hacer ese cambio antes de cada despliegue.
- **Assets compilados**: `public/build/` está en `.gitignore`, así que no viaja por git — hay que copiarlo a mano a `htdocs/build/` cada vez que cambie algo en `resources/js/` o `resources/css/` (correr `npm run build` localmente primero).
- **Base de datos**: el hosting usa MySQL (phpMyAdmin), no SQLite. El archivo `kashafin.sql` en la raíz del repo es un volcado (`mysqldump --add-drop-table`, sin sentencias `CREATE/DROP DATABASE` porque la cuenta del hosting no tiene permiso para crearlas) listo para importar por la pestaña **Importar** de phpMyAdmin — reemplaza todas las tablas y las recarga con los datos de ejemplo del seeder. Se regenera corriendo las migraciones contra un MySQL local (XAMPP) y volviendo a hacer el dump cada vez que cambia el esquema (columnas o tablas nuevas) — ya pasó dos veces en este proyecto (roles/admin, y luego el asistente de IA).
- **`.env` de producción**: vive solo en el hosting, nunca se sube por git (está en `.gitignore`). Apunta a la base MySQL real del hosting — nunca debe sobrescribirse con el `.env` local (que usa SQLite para desarrollo).
