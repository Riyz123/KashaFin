<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

/**
 * Lightweight typo-tolerant matching for the deterministic chat intents.
 * Real spelling mistakes ("alimentaccion", "trasporte", "agrga un gasto")
 * and repeated words shouldn't break recognition — this compares by word,
 * with a small Levenshtein tolerance, instead of requiring an exact
 * substring match.
 */
class FuzzyMatch
{
    // Spanish attaches object pronouns directly to imperative verbs
    // ("agrégame", "anótame", "regístrame", "ponme") — that's not a typo,
    // it changes the word length by 2-3 chars, so plain edit-distance alone
    // would miss it. Checked longest-first so "los"/"las"/"les"/"nos" aren't
    // mistaken for the shorter "lo"/"la"/"le" + a stray letter.
    private const PRONOUN_SUFFIXES = ['nos', 'les', 'las', 'los', 'me', 'te', 'lo', 'la', 'le'];

    /**
     * Whether any word in $message is close enough to one of $words.
     */
    public static function hasWord(string $message, array $words, int $maxDistance = 1): bool
    {
        $normalizedWords = array_map(fn ($word) => Str::lower(Str::ascii($word)), $words);

        foreach (self::tokenize($message) as $token) {
            $candidates = array_filter([$token, self::stripPronounSuffix($token)]);

            foreach ($candidates as $candidateToken) {
                foreach ($normalizedWords as $word) {
                    if (self::closeEnough($candidateToken, $word, $maxDistance)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private static function stripPronounSuffix(string $token): ?string
    {
        foreach (self::PRONOUN_SUFFIXES as $suffix) {
            if (str_ends_with($token, $suffix) && mb_strlen($token) - mb_strlen($suffix) >= 3) {
                return substr($token, 0, -mb_strlen($suffix));
            }
        }

        return null;
    }

    /**
     * The candidate (from $candidates, e.g. real category names) whose
     * significant words best match words found anywhere in $message, or
     * null if nothing clears a reasonable similarity threshold.
     */
    public static function bestMatch(string $message, array $candidates): ?string
    {
        $tokens = self::tokenize($message);
        $best = null;
        $bestScore = 0.0;

        foreach ($candidates as $candidate) {
            $candidateWords = array_values(array_filter(
                self::tokenize($candidate),
                fn ($word) => mb_strlen($word) > 2
            ));

            if (empty($candidateWords)) {
                continue;
            }

            $matched = 0;

            foreach ($candidateWords as $word) {
                $distance = mb_strlen($word) > 5 ? 2 : 1;

                foreach ($tokens as $token) {
                    if (self::closeEnough($token, $word, $distance)) {
                        $matched++;
                        break;
                    }
                }
            }

            $score = $matched / count($candidateWords);

            if ($score > $bestScore && $score >= 0.6) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $best;
    }

    private static function closeEnough(string $a, string $b, int $maxDistance): bool
    {
        if ($a === $b) {
            return true;
        }

        if (abs(mb_strlen($a) - mb_strlen($b)) > $maxDistance) {
            return false;
        }

        return levenshtein($a, $b) <= $maxDistance;
    }

    /**
     * @return array<int, string>
     */
    private static function tokenize(string $text): array
    {
        $normalized = Str::lower(Str::ascii($text));
        preg_match_all('/[a-z]+/', $normalized, $matches);

        return $matches[0];
    }
}
