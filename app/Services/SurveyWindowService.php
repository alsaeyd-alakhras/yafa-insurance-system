<?php

namespace App\Services;

use App\Models\Constant;
use Carbon\Carbon;

class SurveyWindowService
{
    private const START_KEY = 'survey_window_start';

    private const END_KEY = 'survey_window_end';

    public function isOpen(): bool
    {
        $start = $this->start();
        $end = $this->end();

        if (! $start || ! $end) {
            return false;
        }

        return now()->betweenIncluded($start, $end);
    }

    public function start(): ?Carbon
    {
        return $this->readDate(self::START_KEY);
    }

    public function end(): ?Carbon
    {
        return $this->readDate(self::END_KEY);
    }

    public function setWindow(string $start, string $end): void
    {
        Constant::updateOrCreate(['key' => self::START_KEY], ['value' => json_encode($start)]);
        Constant::updateOrCreate(['key' => self::END_KEY], ['value' => json_encode($end)]);
    }

    private function readDate(string $key): ?Carbon
    {
        $raw = Constant::where('key', $key)->value('value');

        if (! $raw) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);

        return $decoded ? Carbon::parse($decoded) : null;
    }
}
