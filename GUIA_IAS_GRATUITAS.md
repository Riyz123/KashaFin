# Guía: de dónde sacar las IAs gratuitas para KashaFin

Este archivo es solo una guía práctica de "dónde conseguir la API key y qué poner en cada campo
del formulario de `/admin/ia`". Para cómo funciona la rotación entre proveedores, el reinicio
de cuota, etc., ver `ARQUITECTURA.md` sección 6.

Puedes agregar los tres (recomendado, para que si uno se agota siga con el siguiente) o solo
los que quieras. El orden de "Prioridad" decide cuál se intenta primero — **ponle un número
distinto a cada uno** (0, 1, 2) — si los dejas todos iguales, el orden entre ellos no queda
garantizado (ver el bug que corregimos).

---

## 1. Groq (recomendado para empezar — el más rápido y generoso en el tier gratis)

1. Entra a **https://console.groq.com** y crea una cuenta (con Google o correo).
2. En el menú lateral, ve a **API Keys** → **Create API Key**. Cópiala — solo se muestra una vez.
3. En `/admin/ia`, completa el formulario así:

| Campo | Valor |
|---|---|
| Nombre | Groq (o el que quieras, es solo una etiqueta) |
| Tipo | Compatible con OpenAI |
| URL base | `https://api.groq.com/openai/v1` |
| Modelo | `llama-3.1-8b-instant` (rápido) o `llama-3.3-70b-versatile` (más capaz, más lento) |
| API key | la que copiaste |
| Prioridad | `0` (si quieres que sea el primero en intentarse) |
| Límite de solicitudes | revisa tus límites actuales en console.groq.com → **Settings → Limits** (cambian de vez en cuando; a la fecha de escribir esto, los modelos Llama gratuitos rondan las 14,400 solicitudes/día) |
| Días por periodo | `1` (se reinicia diario) |

## 2. OpenRouter (buen respaldo — reúne modelos de varios proveedores)

1. Entra a **https://openrouter.ai** y crea una cuenta.
2. Ve a **https://openrouter.ai/settings/keys** → **Create Key**. Cópiala.
3. Revisa **https://openrouter.ai/models?max_price=0** para ver qué modelos están marcados como
   gratis **ahora mismo** (la lista cambia — los que terminan en `:free` no cuestan nada, pero
   OpenRouter va rotando cuáles ofrece sin costo).
4. En `/admin/ia`:

| Campo | Valor |
|---|---|
| Nombre | OpenRouter |
| Tipo | Compatible con OpenAI |
| URL base | `https://openrouter.ai/api/v1` |
| Modelo | ejemplo: `meta-llama/llama-3.1-8b-instruct:free` (copia el id exacto del modelo que elegiste en el paso 3 — siempre termina en `:free`) |
| API key | la que copiaste |
| Prioridad | `1` |
| Límite de solicitudes | el tier gratis ronda 20 solicitudes/minuto y ~50-1000/día según cuánto hayas usado la cuenta — revisa tu dashboard de OpenRouter para el número exacto actual |
| Días por periodo | `1` |

## 3. Google Gemini (buen respaldo adicional, formato de API distinto — por eso KashaFin tiene un driver aparte para este)

1. Entra a **https://aistudio.google.com/apikey** (inicia sesión con una cuenta de Google).
2. **Create API key** → elige o crea un proyecto de Google Cloud (es automático, no necesitas
   configurar nada de facturación para el tier gratis).
3. Copia la key.
4. En `/admin/ia`:

| Campo | Valor |
|---|---|
| Nombre | Gemini |
| Tipo | Google Gemini |
| URL base | `https://generativelanguage.googleapis.com/v1beta` |
| Modelo | `gemini-2.0-flash` (rápido y con buen límite gratis) o `gemini-1.5-flash` |
| API key | la que copiaste |
| Prioridad | `2` |
| Límite de solicitudes | revisa **https://ai.google.dev/gemini-api/docs/rate-limits** para el límite diario/por-minuto vigente del modelo que elegiste |
| Días por periodo | `1` |

---

## Notas importantes

- **Los límites gratuitos cambian con el tiempo** — los números de esta guía son una referencia
  al momento de escribirla, no una promesa. Siempre confirma el límite actual en el dashboard de
  cada proveedor antes de poner el número en "Límite de solicitudes".
- Si dejas "Límite de solicitudes" vacío, KashaFin nunca lo marca como agotado por sí solo —
  solo rota al siguiente proveedor cuando el proveedor mismo responde con un error de cuota
  (HTTP 429 o un mensaje de "rate limit"/"quota"). Ponerle el número real es mejor porque evita
  intentos fallidos innecesarios cerca del límite.
- La API key se guarda cifrada en la base de datos (nunca en texto plano, ni se vuelve a mostrar
  completa en el panel) — es seguro pegarla ahí.
- Prueba cada proveedor apenas lo agregues: manda un mensaje al asistente desde la app y
  confirma que responde. Si falla, revisa el error en `/admin/ia` (cada proveedor muestra su
  estado) antes de asumir que la key está mal.
