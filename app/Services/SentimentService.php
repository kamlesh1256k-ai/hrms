<?php

namespace App\Services;

use App\Models\SurveyAnswer;
use App\Models\SurveySentimentAnalysis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hybrid sentiment analysis for free-text survey answers.
 *
 * Two engines:
 *   1) OpenAI (GPT) — used when OPENAI_API_KEY is set in .env. Returns
 *      structured JSON via the `response_format = json_object` mode.
 *   2) Keyword fallback — pure-PHP rule engine. Always works, ~70% accuracy
 *      on simple feedback. Used when no API key is set, or as a graceful
 *      degradation when the API call fails.
 *
 * Output schema (always normalized):
 *   sentiment:  positive | neutral | negative
 *   topic:      array<string>   from {salary, manager, workload, culture,
 *                                     growth, policy, benefits}
 *   emotion:    happy | frustrated | stressed | motivated | neutral
 *   risk_level: low | medium | high
 *   ai_summary: short one-line gist (≤200 chars)
 */
class SentimentService
{
    public const TOPICS = ['salary', 'manager', 'workload', 'culture', 'growth', 'policy', 'benefits'];

    /** When true, the high-level analyse() will use OpenAI if configured. */
    protected bool $useOpenAi;

    public function __construct()
    {
        $key = (string) config('services.openai.key', env('OPENAI_API_KEY', ''));
        $this->useOpenAi = $key !== '';
    }

    /**
     * Top-level entry point. Always returns a complete, normalized result —
     * never throws on bad text or API failures.
     */
    public function analyze(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return $this->emptyResult();
        }

        if ($this->useOpenAi) {
            try {
                $result = $this->analyzeWithOpenAi($text);
                if (is_array($result)) {
                    return $this->normalize($result, $text);
                }
            } catch (\Throwable $e) {
                Log::warning('Sentiment OpenAI failed, using keyword fallback', ['err' => $e->getMessage()]);
                // fall through to keyword
            }
        }

        return $this->normalize($this->analyzeWithKeywords($text), $text);
    }

    /**
     * Analyze and persist to survey_sentiment_analysis (or refresh existing row).
     * Returns the SurveySentimentAnalysis model.
     */
    public function analyzeAndStore(SurveyAnswer $answer): SurveySentimentAnalysis
    {
        $result = $this->analyze((string) $answer->text_value);

        $hrAlert = ($result['sentiment'] === 'negative' && $result['risk_level'] === 'high');

        return SurveySentimentAnalysis::updateOrCreate(
            ['answer_id' => $answer->id],
            [
                'sentiment'   => $result['sentiment'],
                'topic'       => $result['topic'],
                'emotion'     => $result['emotion'],
                'risk_level'  => $result['risk_level'],
                'hr_alert'    => $hrAlert,
                'ai_summary'  => $result['ai_summary'],
            ]
        );
    }

    /* ──────────────────────────────────────────────────────────────
     * Engine: OpenAI
     * ──────────────────────────────────────────────────────────── */

    protected function analyzeWithOpenAi(string $text): ?array
    {
        $key = (string) env('OPENAI_API_KEY', '');
        if ($key === '') return null;

        $system = "You analyze employee survey feedback. Return STRICT JSON with these keys: "
                . "sentiment (positive|neutral|negative), "
                . "topic (array of: salary, manager, workload, culture, growth, policy, benefits), "
                . "emotion (happy|frustrated|stressed|motivated|neutral), "
                . "risk_level (low|medium|high), "
                . "ai_summary (one short sentence, max 200 chars). "
                . "If feedback expresses serious distress, burnout, harassment, or intent to leave — set risk_level=high. "
                . "Be concise. Output JSON only.";

        $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ])
            ->timeout(15)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => "Feedback:\n\"\"\"\n{$text}\n\"\"\""],
                ],
            ]);

        if (!$resp->ok()) {
            Log::warning('OpenAI sentiment HTTP non-2xx', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }

        $body    = $resp->json();
        $content = $body['choices'][0]['message']['content'] ?? null;
        if (!$content) return null;

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) return null;
        return $decoded;
    }

    /* ──────────────────────────────────────────────────────────────
     * Engine: keyword-based fallback
     * ──────────────────────────────────────────────────────────── */

    protected function analyzeWithKeywords(string $text): array
    {
        $lower = mb_strtolower($text);

        // ── Sentiment scoring ───────────────────────────────
        $negWords = ['bad','poor','terrible','awful','hate','toxic','useless','unfair','broken','worst','disappointing','frustrated','angry','upset','stressed','overwhelmed','burned out','burnout','exhausted','mistreat','rude','ignore','ignored','unrespect','unprofessional','demotivat','no support','not happy','dissatisf','quit','resign','leave the company','leaving the company','no growth','underpaid','low pay','low salary','too much work','workload','overworked','micromanag','politics','bias','toxic','harassment','not listen','does not listen','don\'t listen','don\'t care','dont care','no value','undervalued','no recognition','overtime','not paid','salary issue','salary problem'];
        $posWords = ['great','good','awesome','amazing','love','loved','happy','satisfied','grateful','supportive','best','excellent','fair','transparent','flexible','growth','learning','recognized','recognition','appreciate','motivated','motivating','team is great','great team','fun','engaging','fulfilling','proud','rewarding','bonus','promotion','salary increase','helpful','responsive','listens','listening'];
        $neg = $this->countMatches($lower, $negWords);
        $pos = $this->countMatches($lower, $posWords);

        if ($neg === 0 && $pos === 0)        $sentiment = 'neutral';
        elseif ($pos > $neg + 1)             $sentiment = 'positive';
        elseif ($neg > $pos)                 $sentiment = 'negative';
        else                                  $sentiment = 'neutral';

        // ── Topic detection ─────────────────────────────────
        $topicMap = [
            'salary'   => ['salary','pay','compensation','wage','bonus','increment','underpaid','overpaid','raise','ctc','take-home','take home'],
            'manager'  => ['manager','supervisor','boss','lead','team lead','line manager','reporting'],
            'workload' => ['workload','overwork','too much work','overtime','hours','overworked','exhausted','burnout','burned out','pressure','deadline','deadlines'],
            'culture'  => ['culture','toxic','politics','team','environment','vibe','inclusion','diversity','respect','harassment','bias','transparen'],
            'growth'   => ['growth','career','promotion','learning','training','development','progress','opportunity','opportunities','stagnant'],
            'policy'   => ['policy','policies','rule','rules','process','procedure','hr policy','company policy','leave policy','wfh','work from home','attendance','dress code'],
            'benefits' => ['benefit','benefits','insurance','health','medical','pf','provident fund','esic','perks','reimburs','allowance','gratuity','bonus'],
        ];
        $topics = [];
        foreach ($topicMap as $topic => $keywords) {
            if ($this->countMatches($lower, $keywords) > 0) $topics[] = $topic;
        }

        // ── Emotion ────────────────────────────────────────
        $emotion = 'neutral';
        if ($this->countMatches($lower, ['happy','great','love','grateful','excited','enjoy','recogniz','appreciate']) > 0) {
            $emotion = 'happy';
        } elseif ($this->countMatches($lower, ['stress','overwhelm','exhaust','burnout','burned out','tired']) > 0) {
            $emotion = 'stressed';
        } elseif ($this->countMatches($lower, ['frustrat','angry','upset','annoy','irritat','fed up']) > 0) {
            $emotion = 'frustrated';
        } elseif ($this->countMatches($lower, ['motivat','inspire','energiz','engaged','driven']) > 0) {
            $emotion = 'motivated';
        }

        // ── Risk level ─────────────────────────────────────
        $highSignals = ['quit','resign','leave the company','leaving the company','attorney','lawyer','sue','harassment','discriminat','toxic','burnout','burned out','can\'t take','cant take','suicid','depress','breakdown','at my breaking point','hostile','retaliation'];
        $medSignals  = ['stress','overworked','frustrated','demotivat','disappointed','unfair','no support','ignored','overlooked','underpaid','low pay'];

        $risk = 'low';
        if ($this->countMatches($lower, $highSignals) > 0)            $risk = 'high';
        elseif ($sentiment === 'negative' && $this->countMatches($lower, $medSignals) >= 2) $risk = 'high';
        elseif ($sentiment === 'negative')                             $risk = 'medium';
        elseif ($this->countMatches($lower, $medSignals) > 0)          $risk = 'medium';

        // ── Summary ────────────────────────────────────────
        $summary = mb_substr(preg_replace('/\s+/', ' ', $text), 0, 180);
        if (mb_strlen($text) > 180) $summary .= '…';

        return [
            'sentiment'  => $sentiment,
            'topic'      => $topics,
            'emotion'    => $emotion,
            'risk_level' => $risk,
            'ai_summary' => $summary,
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */

    /**
     * Coerce any input shape into a normalized result. Defends against bad
     * AI output (missing keys, invalid enums, etc.) so downstream code
     * never gets a malformed row.
     */
    protected function normalize(array $r, string $originalText): array
    {
        $sentiments = ['positive', 'neutral', 'negative'];
        $emotions   = ['happy', 'frustrated', 'stressed', 'motivated', 'neutral'];
        $risks      = ['low', 'medium', 'high'];

        $sent = strtolower((string) ($r['sentiment'] ?? 'neutral'));
        if (!in_array($sent, $sentiments, true)) $sent = 'neutral';

        $emo = strtolower((string) ($r['emotion'] ?? 'neutral'));
        if (!in_array($emo, $emotions, true)) $emo = 'neutral';

        $risk = strtolower((string) ($r['risk_level'] ?? 'low'));
        if (!in_array($risk, $risks, true)) $risk = 'low';

        $topics = $r['topic'] ?? [];
        if (is_string($topics)) {
            $topics = array_map('trim', explode(',', $topics));
        }
        if (!is_array($topics)) $topics = [];
        $topics = array_values(array_unique(array_filter(array_map(function ($t) {
            $t = strtolower(trim((string) $t));
            return in_array($t, self::TOPICS, true) ? $t : null;
        }, $topics))));

        $summary = (string) ($r['ai_summary'] ?? '');
        if ($summary === '') {
            $summary = mb_substr(preg_replace('/\s+/', ' ', $originalText), 0, 180);
            if (mb_strlen($originalText) > 180) $summary .= '…';
        }
        if (mb_strlen($summary) > 200) $summary = mb_substr($summary, 0, 200);

        return [
            'sentiment'  => $sent,
            'topic'      => $topics,
            'emotion'    => $emo,
            'risk_level' => $risk,
            'ai_summary' => $summary,
        ];
    }

    protected function emptyResult(): array
    {
        return [
            'sentiment'  => 'neutral',
            'topic'      => [],
            'emotion'    => 'neutral',
            'risk_level' => 'low',
            'ai_summary' => '',
        ];
    }

    /** Count how many of the given needles appear in $haystack. */
    protected function countMatches(string $haystack, array $needles): int
    {
        $n = 0;
        foreach ($needles as $needle) {
            if ($needle === '') continue;
            if (mb_strpos($haystack, $needle) !== false) $n++;
        }
        return $n;
    }
}
