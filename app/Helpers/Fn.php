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
        $biomarkers = Biomarker::with(['ranges', 'genetics'])->get();

        $chronAge = $patientData['chronological_age'] ?? 0;
        $blueAge = $chronAge;
        $totalDelta = 0;
        $biomarkerDeltas = [];

        foreach ($biomarkers as $bio) {
            $key = $bio->name;
            if (!isset($patientData[$key])) continue;

            $value = $patientData[$key];
            $delta = 0;

            if ($bio->is_numeric) {
                foreach ($bio->ranges as $range) {
                    if ($value >= $range->range_start && $value <= $range->range_end) {
                        $delta = $range->delta;
                        $blueAge += $delta;
                        break;
                    }
                }
            } else {
                $variant = $bio->genetics->where('genotype', $value)->first();
                if ($variant) {
                    $delta = $variant->delta;
                    $blueAge += $delta;
                }
            }

            $biomarkerDeltas[$key] = $delta;
            $totalDelta += $delta;
        }

        // ----------------------
        // CALCULATE REALISTIC OPTIMAL RANGE
        // ----------------------
        $optimalLower = 0; // sum of best possible deltas
        $optimalUpper = 0; // sum of healthy/normal deltas

        foreach ($biomarkers as $bio) {
            if ($bio->is_numeric) {
                // Lower bound: most negative delta (optimal)
                $minDelta = $bio->ranges->where('delta', '<=', 0)->min('delta') ?? 0;
                $optimalLower += $minDelta;

                // Upper bound: largest delta within normal/healthy range (<=0 or small positive)
                $normalDelta = $bio->ranges->where('delta', '<=', 0)->max('delta') ?? 0;
                $optimalUpper += $normalDelta;
            } else {
                // Genetic marker: healthiest variant = min delta
                $optimalLower += $bio->genetics->min('delta') ?? 0;
                $optimalUpper += $bio->genetics->max('delta') ?? 0;
            }
        }

        $optimalRangeLower = round($chronAge + $optimalLower, 1);
        $optimalRangeUpper = round($chronAge + $optimalUpper, 1);

        return [
            'blue_age' => round($blueAge, 1),
            'chronological_age' => $chronAge,
            'total_delta' => round($totalDelta, 1),
            'optimal_blue_age' => round($chronAge + $optimalLower, 1),
            'optimal_range' => $optimalRangeLower . ' - ' . $optimalRangeUpper . ' years (optimal)',
            'years_from_optimal' => round($blueAge - ($chronAge + $optimalLower), 1),
            'biomarker_deltas' => $biomarkerDeltas,
            'last_updated' => now()->format('F d, Y'),
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





