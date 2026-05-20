<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeepFaceVerificationService
{
    /**
     * Run DeepFace.verify via Python CLI script.
     *
     * @return array{match: bool, confidence: float, message: string, error: bool, provider_error?: bool, distance?: float, threshold?: float}
     */
    public function verify(string $imagePath1, string $imagePath2): array
    {
        if (! config('services.deepface.enabled')) {
            return [
                'match' => false,
                'confidence' => 0,
                'message' => 'DeepFace is disabled',
                'error' => true,
                'provider_error' => true,
            ];
        }

        $script = base_path(config('services.deepface.script', 'scripts/deepface_verify.py'));
        if (! is_readable($script)) {
            Log::error('DeepFace script missing or unreadable', ['path' => $script]);

            return [
                'match' => false,
                'confidence' => 0,
                'message' => 'DeepFace script not found',
                'error' => true,
                'provider_error' => true,
            ];
        }

        $python = (string) config('services.deepface.python', 'python');
        $timeout = (float) config('services.deepface.timeout', 120);
        $model = (string) config('services.deepface.model', 'Facenet512');
        $detector = (string) config('services.deepface.detector_backend', 'opencv');

        $cmd = array_filter([
            $python,
            $script,
            $imagePath1,
            $imagePath2,
            $model,
            $detector,
        ], static fn ($v) => $v !== '' && $v !== null);

        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->setWorkingDirectory(base_path());

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::warning('DeepFace process failed', ['exception' => $e->getMessage()]);

            return [
                'match' => false,
                'confidence' => 0,
                'message' => 'DeepFace process error: '.$e->getMessage(),
                'error' => true,
                'provider_error' => true,
            ];
        }

        $stdout = trim($process->getOutput());
        $stderr = trim($process->getErrorOutput());

        if ($stdout === '') {
            Log::warning('DeepFace empty stdout', ['stderr' => $stderr, 'exit' => $process->getExitCode()]);

            return [
                'match' => false,
                'confidence' => 0,
                'message' => $stderr !== '' ? $stderr : 'DeepFace returned no output',
                'error' => true,
                'provider_error' => true,
            ];
        }

        $decoded = json_decode($stdout, true);
        if (! is_array($decoded)) {
            Log::warning('DeepFace invalid JSON', ['stdout' => substr($stdout, 0, 500)]);

            return [
                'match' => false,
                'confidence' => 0,
                'message' => 'DeepFace invalid response',
                'error' => true,
                'provider_error' => true,
            ];
        }

        $isError = ! empty($decoded['error']);
        $match = (bool) ($decoded['match'] ?? false);
        $confidence = (float) ($decoded['confidence'] ?? 0);
        $message = (string) ($decoded['message'] ?? 'DeepFace verification');

        $out = [
            'match' => $match,
            'confidence' => $confidence,
            'message' => $message,
            'error' => $isError,
        ];

        if (isset($decoded['distance'])) {
            $out['distance'] = (float) $decoded['distance'];
        }
        if (isset($decoded['threshold'])) {
            $out['threshold'] = (float) $decoded['threshold'];
        }

        if ($isError) {
            $out['provider_error'] = true;
        }

        return $out;
    }
}
