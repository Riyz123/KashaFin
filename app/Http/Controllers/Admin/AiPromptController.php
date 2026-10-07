<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use Illuminate\Http\Request;

class AiPromptController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'system_prompt' => ['nullable', 'string', 'max:4000'],
        ]);

        AiSetting::current()->update(['system_prompt' => $data['system_prompt'] ?? null]);

        return back()->with('status', 'Prompt del asistente actualizado correctamente.');
    }

    public function reset()
    {
        AiSetting::current()->update(['system_prompt' => null]);

        return back()->with('status', 'Prompt restaurado al valor por defecto.');
    }
}
