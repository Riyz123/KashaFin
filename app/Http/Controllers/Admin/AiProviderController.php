<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiSetting;
use Illuminate\Http\Request;

class AiProviderController extends Controller
{
    public function index()
    {
        $providers = AiProvider::query()->ordered()->get();
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
