<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('settings.edit', ['settings' => $request->user()->settings]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $data = $request->validated();
        $data['notify_low_liquidity_by_email'] = $request->boolean('notify_low_liquidity_by_email');

        $request->user()->settings->update($data);

        return redirect()->route('settings.edit')->with('status', 'Configuración actualizada correctamente.');
    }

    public function updateTheme(Request $request)
    {
        $data = $request->validate(['theme' => ['required', 'in:light,dark']]);

        $request->user()->settings->update($data);

        return response()->noContent();
    }
}
