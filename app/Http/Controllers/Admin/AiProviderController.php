<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiSetting;
use App\Services\Ai\AiProviderRouter;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiCompatibleDriver;
use Illuminate\Http\Request;

class AiProviderController extends Controller
{
    public function index()
    {
        // isAvailable() lazily recovers an exhausted provider once its
        // quota period has elapsed — calling it here (not just when a chat
        // message happens to hit it) keeps what the admin sees in sync with
        // reality, instead of showing a stale "se reinicia" date forever if
        // nobody has sent a message since it actually became eligible.
        $providers = AiProvider::query()->ordered()->with('latestUsageLog')->get()->each(fn (AiProvider $provider) => $provider->isAvailable());
        $aiSetting = AiSetting::current();

        return view('admin.ai.index', ['providers' => $providers, 'aiSetting' => $aiSetting]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, requireKey: true);

        AiProvider::create($data);

        return back()->with('status', 'Proveedor de IA agregado correctamente.');
    }

    public function update(Request $request, AiProvider $aiProvider)
    {
        $data = $this->validateData($request, requireKey: false);

        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        $aiProvider->update($data);

        return back()->with('status', 'Proveedor de IA actualizado correctamente.');
    }

    public function toggleActive(AiProvider $aiProvider)
    {
        $aiProvider->is_active = ! $aiProvider->is_active;
        $aiProvider->save();

        return back()->with('status', $aiProvider->is_active ? 'Proveedor activado.' : 'Proveedor desactivado.');
    }

    public function destroy(AiProvider $aiProvider)
    {
        $aiProvider->delete();

        return back()->with('status', 'Proveedor eliminado correctamente.');
    }

    public function reactivate(AiProvider $aiProvider)
    {
        $aiProvider->reactivate();

        return back()->with('status', 'Proveedor reactivado: se quitó el estado "agotado" y se reinició su contador de uso.');
    }

    /**
     * Polled every ~10s from the Asistente IA page so "tráfico en tiempo
     * real" updates on its own, the same pattern as presence-poll.js. This
     * never calls out to an external provider — it only re-reads state
     * already in our own database (usage logs + quota counters), so
     * polling it costs nothing against anyone's free-tier quota. The one
     * provider actually reachable by a real chat message right now (first
     * active + available, in priority order) is flagged `is_preferred` —
     * that's the direct answer to "a qué IA está disponible el uso".
     */
    public function status(AiProviderRouter $router)
    {
        $preferredId = $router->candidates(1)->first()?->id;

        $providers = AiProvider::query()->ordered()->get()->map(function (AiProvider $provider) use ($preferredId) {
            $available = $provider->isAvailable();

            $recentLogs = $provider->usageLogs()
                ->latest()
                ->take(5)
                ->get(['status', 'error_message', 'created_at'])
                ->map(fn ($log) => [
                    'status' => $log->status,
                    'error_message' => $log->error_message,
                    'at' => $log->created_at->toIso8601String(),
                ]);

            return [
                'id' => $provider->id,
                'available' => $available,
                'is_preferred' => $available && $provider->id === $preferredId,
                'is_exhausted' => $provider->is_exhausted,
                'is_active' => $provider->is_active,
                'requests_used' => $provider->requests_used,
                'quota_limit' => $provider->quota_limit,
                'usage_percent' => $provider->usage_percent,
                'period_reset_at' => $provider->period_reset_at?->toIso8601String(),
                'recent_logs' => $recentLogs,
            ];
        });

        return response()->json($providers->keyBy('id'));
    }

    /**
     * Sends one real, minimal request straight through the driver —
     * bypassing AiProviderRouter entirely, so this never touches rotation
     * state (is_exhausted/requests_used) and works even for a provider the
     * router wouldn't currently pick. Lets the admin see the exact error
     * (key inválida, URL mal escrita, modelo inexistente, etc.) instead of
     * only the generic "no hay ninguna IA disponible" the chat shows.
     *
     * It does log the outcome (success or error) so "Último uso" in the
     * panel reflects this test — otherwise a provider the router rarely
     * picks (e.g. a deprioritized backup model) would keep showing a stale
     * old chat failure forever, even after the admin confirms it works.
     */
    public function testConnection(AiProvider $aiProvider)
    {
        $driver = $aiProvider->driver === 'gemini' ? new GeminiDriver : new OpenAiCompatibleDriver;

        try {
            $reply = $driver->send($aiProvider, [
                ['role' => 'user', 'content' => 'Responde solo con la palabra: ok'],
            ]);

            $aiProvider->logTestResult(success: true);

            return back()->with('status', 'Conexión exitosa. Respuesta del proveedor: "'.$reply->content.'"');
        } catch (\Throwable $e) {
            $aiProvider->logTestResult(success: false, errorMessage: $e->getMessage());

            return back()->with('status', 'Falló la prueba de conexión: '.$e->getMessage());
        }
    }

    private function validateData(Request $request, bool $requireKey): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'in:openai_compatible,gemini'],
            'base_url' => ['required', 'url', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'api_key' => [$requireKey ? 'required' : 'nullable', 'string'],
            'priority' => ['required', 'integer', 'min:0'],
            'quota_limit' => ['nullable', 'integer', 'min:1'],
            'quota_period_days' => ['required', 'integer', 'min:1'],
        ]);
    }
}
