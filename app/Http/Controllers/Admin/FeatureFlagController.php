<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    public function index()
    {
        $flags = FeatureFlag::query()->orderBy('label')->get();

        return view('admin.feature-flags.index', ['flags' => $flags]);
    }

    /**
     * Only flips access — never touches a single row of the data that
     * already lives in that section (see FeatureFlag's migration docblock).
     */
    public function toggle(Request $request, FeatureFlag $featureFlag)
    {
        $enabling = ! $featureFlag->is_enabled;

        $featureFlag->update([
            'is_enabled' => $enabling,
            'disabled_at' => $enabling ? null : now(),
            'disabled_by' => $enabling ? null : $request->user()->id,
        ]);

        $status = $enabling
            ? "Se activó \"{$featureFlag->label}\". Los estudiantes ya pueden usarla de nuevo."
            : "Se desactivó \"{$featureFlag->label}\". Nadie podrá usarla hasta que la reactives; lo que ya tenían registrado no se toca.";

        return back()->with('status', $status);
    }
}
