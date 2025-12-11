<?php
use App\Models\CMS;
use App\Enums\PageEnum;
use App\Enums\SectionEnum;
use App\Models\Setting;
use App\Models\User;
use App\Models\Biomarker;

function getFileName($file): string
{
    return time().'_'.pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
}



if (!function_exists('calculateBlueAge')) {
    function calculateBlueAge(array $patientData): array
    {
        // Fetch all biomarkers with ranges + genetics
        $biomarkers = Biomarker::with(['ranges', 'genetics'])
            ->get();

        $chronologicalAge = $patientData['chronological_age'] ?? 0;
        $blueAge = $chronologicalAge;

        // Loop over biomarker records
        foreach ($biomarkers as $bio) {
            $key = $bio->key;

            // Skip if patient does not have value
            if (!isset($patientData[$key])) {
                continue;
            }

            $value = $patientData[$key];

            // ------------------------------------------------------------
            // NUMERIC BIOMARKER
            // ------------------------------------------------------------
            if ($bio->type === 'numeric') {

                foreach ($bio->ranges as $range) {
                    if ($value >= $range->min_value && $value <= $range->max_value) {
                        $blueAge += $range->delta;
                        break;
                    }
                }
            }

            // ------------------------------------------------------------
            // GENETIC BIOMARKER
            // ------------------------------------------------------------
            if ($bio->type === 'genetic') {

                $variant = $bio->genetics->where('variant', $value)->first();

                if ($variant) {
                    $blueAge += $variant->delta;
                }
            }
        }

        // ------------------------------------------------------------
        // CALCULATE MIN–MAX OPTIMAL RANGE
        // ------------------------------------------------------------

        $minDelta = 0;
        $maxDelta = 0;

        foreach ($biomarkers as $bio) {

            if ($bio->type === 'numeric') {
                $minDelta += $bio->ranges->min('delta');
                $maxDelta += $bio->ranges->max('delta');
            }

            if ($bio->type === 'genetic') {
                $minDelta += $bio->genetics->min('delta');
                $maxDelta += $bio->genetics->max('delta');
            }
        }

        $optimalRange =
            round($chronologicalAge + $minDelta, 1) .
            '–' .
            round($chronologicalAge + $maxDelta, 1) .
            ' years';

        return [
            'blue_age'            => round($blueAge, 1),
            'chronological_age'   => $chronologicalAge,
            'optimal_range'       => $optimalRange,
            'last_updated'        => date('F d, Y'),
        ];
    }
}


function getEmailName($email): string
{
    $parts = explode('@', $email);
    return $parts[0];
}
function getCommonData()
{
    $common = CMS::where('page', PageEnum::COMMON)->where('status', 'active');
    foreach (SectionEnum::getCommon() as $key => $section) {
        $cms[$key] = (clone $common)->where('section', $key)->latest()->take($section['item'])->{$section['type']}();
    }
    return $cms;
}

function formatNumber($number, $precision = 2): array
{
    if ($number >= 1000000000000000) {
        return [
            'number' => number_format($number / 1000000000000000, $precision),
            'format' => 'Q'
        ];
    } elseif ($number >= 1000000000000) {
        return [
            'number' => number_format($number / 1000000000000, $precision),
            'format' => 'T'
        ];
    } elseif ($number >= 1000000000) {
        return [
            'number' => number_format($number / 1000000000, $precision),
            'format' => 'B'
        ];
    } elseif ($number >= 1000000) {
        return [
            'number' => number_format($number / 1000000, $precision),
            'format' => 'M'
        ];
    } elseif ($number >= 1000) {
        return [
            'number' => number_format($number / 1000, $precision),
            'format' => 'K'
        ];
    }

    // For numbers less than 1K, no format suffix is needed
    return [
        'number' => number_format($number),
        'format' => ''
    ];
}

if (!function_exists('is_url')) {
    function is_url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('settings')) {
    function settings(?string $key = null)
    {
        $settings = Setting::first();
        if ($key) {
            return $settings->{$key} ?? null;
        }
        return $settings;
    }
}





