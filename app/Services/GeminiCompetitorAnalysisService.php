<?php

namespace App\Services;

use App\Support\SafeJsonParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class GeminiCompetitorAnalysisService
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(array $input): array
    {
        $apiKey = (string) config('services.gemini.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY belum dikonfigurasi di file .env.');
        }

        $model = (string) config('services.gemini.model', 'gemini-3.5-flash');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
        $timeout = (int) config('services.gemini.timeout', 60);
        $endpoint = "{$baseUrl}/v1beta/models/{$model}:generateContent";

        $response = $this->sendGenerateContentRequest($endpoint, $apiKey, $timeout, $this->payloadWithSchema($input));

        if (! $response->successful() && $this->shouldRetryWithoutSchema($response->json('error.message'))) {
            $response = $this->sendGenerateContentRequest($endpoint, $apiKey, $timeout, $this->payloadWithJsonMimeOnly($input));
        }

        if (! $response->successful() && $this->shouldRetryWithoutJsonMime($response->json('error.message'))) {
            $response = $this->sendGenerateContentRequest($endpoint, $apiKey, $timeout, $this->payloadWithoutStructuredOutput($input));
        }

        if (! $response->successful()) {
            $message = $response->json('error.message') ?: Str::limit($response->body(), 280);

            throw new RuntimeException("Gagal menghubungi Gemini: {$message}");
        }

        $raw = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($raw) || blank($raw)) {
            $finishReason = data_get($response->json(), 'candidates.0.finishReason');
            $suffix = $finishReason ? " Finish reason: {$finishReason}." : '';

            throw new RuntimeException("Gemini tidak mengembalikan teks analisis.{$suffix}");
        }

        try {
            $analysis = SafeJsonParser::decodeObject($raw);
        } catch (JsonException $exception) {
            throw new RuntimeException('Gemini mengembalikan JSON yang tidak valid. Coba ulangi analisis atau periksa input kompetitor.', previous: $exception);
        }

        return $this->normalizeAnalysis($analysis, $input);
    }

    public function buildPrompt(array $input): string
    {
        $inputJson = json_encode(
            $this->sanitizeInput($input),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return <<<PROMPT
Kamu adalah konsultan strategi bisnis laundry untuk aplikasi POS toko laundry.

Gunakan HANYA data yang dimasukkan user di bawah ini. Jangan memakai asumsi lokasi, harga pasar, reputasi, tren, atau data kompetitor lain di luar input user.
Jika sebuah field kosong, null, atau bernilai "tidak_diketahui", nyatakan bahwa data tersebut belum tersedia dan jangan membuat fakta pengganti.
Analisis untuk "usaha laundry saya" harus berupa rekomendasi strategi berbasis input kompetitor, bukan klaim tentang kondisi usaha saya karena data internal usaha saya tidak diberikan.

INPUT USER DALAM JSON:
{$inputJson}

TUGAS:
1. Buat analisis kompetitor laundry berbasis input user.
2. Executive summary harus berisi 3 sampai 5 poin singkat.
3. Strengths, weaknesses, opportunities, dan threats harus berupa daftar poin.
4. chart_data harus berisi angka 0 sampai 100 untuk visual dashboard:
   - labels: faktor yang dinilai dari input user.
   - competitor_scores: skor sinyal kekuatan kompetitor berdasarkan input user saja.
   - opportunity_scores: skor peluang untuk usaha laundry saya berdasarkan celah dari input user saja.
   - notes: catatan singkat cara membaca skor.
5. confidence_note wajib menyebutkan bahwa analisis ini hanya berbasis input yang dimasukkan user.

Kembalikan HANYA JSON valid sesuai struktur yang diminta. Jangan gunakan markdown, code fence, atau teks di luar JSON.
PROMPT;
    }

    private function systemInstruction(): string
    {
        return 'Selalu jawab dalam Bahasa Indonesia. Kembalikan hanya JSON valid. Jangan mengarang fakta di luar input user.';
    }

    /**
     * @return \Illuminate\Http\Client\Response
     */
    private function sendGenerateContentRequest(string $endpoint, string $apiKey, int $timeout, array $payload)
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])
            ->timeout($timeout)
            ->post($endpoint, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadWithSchema(array $input): array
    {
        return [
            ...$this->basePayload($input),
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->responseSchema(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadWithJsonMimeOnly(array $input): array
    {
        return [
            ...$this->basePayload($input),
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadWithoutStructuredOutput(array $input): array
    {
        return [
            ...$this->basePayload($input),
            'generationConfig' => [
                'maxOutputTokens' => 4096,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function basePayload(array $input): array
    {
        return [
            'system_instruction' => [
                'parts' => [
                    ['text' => $this->systemInstruction()],
                ],
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->buildPrompt($input)],
                    ],
                ],
            ],
        ];
    }

    private function shouldRetryWithoutSchema(?string $message): bool
    {
        if (! $message) {
            return false;
        }

        return Str::contains($message, [
            'response_schema',
            'responseSchema',
            'generation_config.response_schema',
            'Invalid JSON payload',
        ]);
    }

    private function shouldRetryWithoutJsonMime(?string $message): bool
    {
        if (! $message) {
            return false;
        }

        return Str::contains($message, [
            'response_mime_type',
            'responseMimeType',
            'generation_config.response_mime_type',
            'mime_type',
            'mimeType',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        $stringList = [
            'type' => 'ARRAY',
            'items' => ['type' => 'STRING'],
        ];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'executive_summary' => [
                    ...$stringList,
                    'minItems' => 3,
                    'maxItems' => 5,
                ],
                'competitor_position' => ['type' => 'STRING'],
                'strengths' => $stringList,
                'weaknesses' => $stringList,
                'opportunities' => $stringList,
                'threats' => $stringList,
                'pricing_insight' => ['type' => 'STRING'],
                'service_gap' => ['type' => 'STRING'],
                'marketing_recommendation' => ['type' => 'STRING'],
                'differentiation_strategy' => ['type' => 'STRING'],
                'chart_data' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'labels' => $stringList,
                        'competitor_scores' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'NUMBER'],
                        ],
                        'opportunity_scores' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'NUMBER'],
                        ],
                        'notes' => $stringList,
                    ],
                    'required' => ['labels', 'competitor_scores', 'opportunity_scores', 'notes'],
                ],
                'final_recommendation' => ['type' => 'STRING'],
                'confidence_note' => ['type' => 'STRING'],
            ],
            'required' => [
                'executive_summary',
                'competitor_position',
                'strengths',
                'weaknesses',
                'opportunities',
                'threats',
                'pricing_insight',
                'service_gap',
                'marketing_recommendation',
                'differentiation_strategy',
                'chart_data',
                'final_recommendation',
                'confidence_note',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizeInput(array $input): array
    {
        return [
            'nama_kompetitor' => $this->cleanString($input['nama_kompetitor'] ?? null),
            'alamat_lokasi' => $this->cleanString($input['alamat_lokasi'] ?? null),
            'harga_cuci' => $this->cleanNumber($input['harga_cuci'] ?? null),
            'harga_setrika' => $this->cleanNumber($input['harga_setrika'] ?? null),
            'harga_express' => $this->cleanNumber($input['harga_express'] ?? null),
            'jam_operasional' => $this->cleanString($input['jam_operasional'] ?? null),
            'promo' => $this->cleanString($input['promo'] ?? null),
            'rating_ulasan' => $this->cleanString($input['rating_ulasan'] ?? null),
            'layanan_antar_jemput' => $this->cleanString($input['layanan_antar_jemput'] ?? null),
            'catatan_tambahan' => $this->cleanString($input['catatan_tambahan'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $analysis
     *
     * @return array<string, mixed>
     */
    private function normalizeAnalysis(array $analysis, array $input): array
    {
        $required = [
            'executive_summary',
            'competitor_position',
            'strengths',
            'weaknesses',
            'opportunities',
            'threats',
            'pricing_insight',
            'service_gap',
            'marketing_recommendation',
            'differentiation_strategy',
            'chart_data',
            'final_recommendation',
            'confidence_note',
        ];

        $missing = array_values(array_filter(
            $required,
            fn (string $key): bool => ! array_key_exists($key, $analysis)
        ));

        if ($missing) {
            throw new RuntimeException('Gemini mengembalikan JSON tanpa field wajib: ' . implode(', ', $missing) . '.');
        }

        return [
            'input' => $this->sanitizeInput($input),
            'generated_at' => now()->toDateTimeString(),
            'executive_summary' => array_slice($this->normalizeStringList($analysis['executive_summary']), 0, 5),
            'competitor_position' => $this->stringValue($analysis['competitor_position']),
            'strengths' => $this->normalizeStringList($analysis['strengths']),
            'weaknesses' => $this->normalizeStringList($analysis['weaknesses']),
            'opportunities' => $this->normalizeStringList($analysis['opportunities']),
            'threats' => $this->normalizeStringList($analysis['threats']),
            'pricing_insight' => $this->stringValue($analysis['pricing_insight']),
            'service_gap' => $this->stringValue($analysis['service_gap']),
            'marketing_recommendation' => $this->stringValue($analysis['marketing_recommendation']),
            'differentiation_strategy' => $this->stringValue($analysis['differentiation_strategy']),
            'chart_data' => $this->normalizeChartData(Arr::wrap($analysis['chart_data'])),
            'final_recommendation' => $this->stringValue($analysis['final_recommendation']),
            'confidence_note' => $this->stringValue($analysis['confidence_note']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeChartData(array $chartData): array
    {
        $labels = $this->normalizeStringList($chartData['labels'] ?? []);
        $competitorScores = $this->normalizeScores($chartData['competitor_scores'] ?? []);
        $opportunityScores = $this->normalizeScores($chartData['opportunity_scores'] ?? []);
        $notes = $this->normalizeStringList($chartData['notes'] ?? []);

        $count = min(count($labels), count($competitorScores), count($opportunityScores));

        if ($count === 0) {
            throw new RuntimeException('Gemini mengembalikan chart_data yang belum bisa digambar.');
        }

        return [
            'labels' => array_slice($labels, 0, $count),
            'competitor_scores' => array_slice($competitorScores, 0, $count),
            'opportunity_scores' => array_slice($opportunityScores, 0, $count),
            'notes' => $notes,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item): string => $this->stringValue($item),
            $value
        )));
    }

    /**
     * @return array<int, float>
     */
    private function normalizeScores(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $score): float => max(0, min(100, (float) $score)),
            array_filter($value, fn (mixed $score): bool => is_numeric($score))
        ));
    }

    private function stringValue(mixed $value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanNumber(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return $value + 0;
    }
}
