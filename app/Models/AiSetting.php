<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (id 1) holding the admin-editable part of the chatbot's
 * system prompt. Null/blank means "use the default" — the tool-calling and
 * language instructions in ChatService are never overridable from here,
 * only the persona/tone paragraph is.
 */
class AiSetting extends Model
{
    protected $fillable = ['system_prompt'];

    public const DEFAULT_PROMPT = "Eres el asistente financiero de KashaFin, una app para estudiantes universitarios. ".
        "En tono cercano y natural: si te saludan, saluda de vuelta y pregunta en qué puedes ayudar; si te piden ".
        "un reporte, análisis o recomendación, usa los datos reales para dar una respuesta concreta (no hace ".
        "falta que sea breve si piden detalle); para preguntas simples, responde corto.";

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    public function promptOrDefault(): string
    {
        return filled($this->system_prompt) ? $this->system_prompt : self::DEFAULT_PROMPT;
    }
}
