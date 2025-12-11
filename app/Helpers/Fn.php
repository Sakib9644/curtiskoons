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
        $totalDelta = 0;

        // Store deltas for reporting
        $biomarkerDeltas = [];

        // Loop over biomarker records
        foreach ($biomarkers as $bio) {
            $key = $bio->name; // ✅ Fixed: use 'name' not 'key'

            // Skip if patient does not have value
            if (!isset($patientData[$key])) {
                continue;
            }

            $value = $patientData[$key];
            $delta = 0;

            // ------------------------------------------------------------
            // NUMERIC BIOMARKER
            // ------------------------------------------------------------
            if ($bio->is_numeric) { // ✅ Fixed: use is_numeric boolean

                foreach ($bio->ranges as $range) {
                    // ✅ Fixed: use range_start and range_end
                    if ($value >= $range->range_start && $value <= $range->range_end) {
                        $delta = $range->delta;
                        $blueAge += $delta;
                        break;
                    }
                }
            }

            // ------------------------------------------------------------
            // GENETIC BIOMARKER (non-numeric)
            // ------------------------------------------------------------
            else {
                // ✅ Fixed: use 'genotype' not 'variant'
                $variant = $bio->genetics->where('genotype', $value)->first();

                if ($variant) {
                    $delta = $variant->delta;
                    $blueAge += $delta;
                }
            }

            // Track delta for this biomarker
            $biomarkerDeltas[$key] = $delta;
            $totalDelta += $delta;
        }

        // ------------------------------------------------------------
        // CALCULATE OPTIMAL BLUE AGE
        // (What Blue Age would be if ALL biomarkers were optimal)
        // ------------------------------------------------------------

        $optimalDelta = 0;

        foreach ($biomarkers as $bio) {

            if ($bio->is_numeric) {
                // Find the most negative (best) delta
                $optimalDelta += $bio->ranges->min('delta');
            } else {
                // Find the most negative (best) delta for genetics
                $optimalDelta += $bio->genetics->min('delta');
            }
        }

        $optimalBlueAge = $chronologicalAge + $optimalDelta;

        return [
            'blue_age'            => round($blueAge, 1),
            'chronological_age'   => $chronologicalAge,
            'total_delta'         => round($totalDelta, 1),
            'optimal_blue_age'    => round($optimalBlueAge, 1),
            'years_from_optimal'  => round($blueAge - $optimalBlueAge, 1),
            'biomarker_deltas'    => $biomarkerDeltas,
            'last_updated'        => now()->format('F d, Y'),
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





