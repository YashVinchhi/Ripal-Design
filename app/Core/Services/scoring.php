<?php
/**
 * Scoring service implementing Layers 0-11 for worker and vendor scoring.
 * Functions are kept procedural for compatibility with existing codebase.
 */

if (!defined('PROJECT_ROOT')) { define('PROJECT_ROOT', dirname(__DIR__, 3)); }

/** Normalize a 0-10 score to 0-1. */
function sc_normalize($score) {
    $s = floatval($score);
    if ($s <= 0) return 0.0;
    if ($s >= 10) return 1.0;
    return $s / 10.0;
}

/** Compute WPS (worker project score) for a metric event. Input metrics expected 0-10. */
function sc_compute_wps(array $e) {
    $weights = [
        'charges_efficiency' => 0.15,
        'work_quality' => 0.20,
        'experience' => 0.10,
        'speed_timing' => 0.10,
        'reliability' => 0.15,
        'rework_rate' => 0.10,
        'communication' => 0.05,
        'client_feedback' => 0.05,
        'flexibility' => 0.05,
        'safety' => 0.05,
    ];
    $sum = 0.0;
    foreach ($weights as $metric => $w) {
        $val = isset($e[$metric]) ? floatval($e[$metric]) : 0.0;
        $sum += sc_normalize($val) * $w;
    }
    return $sum; // already in 0-1 range
}

/** Compute VBS (vendor batch score) for a metric event. */
function sc_compute_vbs(array $e) {
    $weights = [
        'pricing' => 0.15,
        'product_quality' => 0.20,
        'consistency' => 0.10,
        'delivery_reliability' => 0.15,
        'stock_availability' => 0.10,
        'variety' => 0.05,
        'warranty_replacement' => 0.05,
        'communication' => 0.05,
        'credit_terms' => 0.10,
        'logistics' => 0.05,
    ];
    $sum = 0.0;
    foreach ($weights as $metric => $w) {
        $val = isset($e[$metric]) ? floatval($e[$metric]) : 0.0;
        $sum += sc_normalize($val) * $w;
    }
    return $sum;
}

/** Compute age in months between two timestamps. */
function sc_age_in_months($older_ts, $newer_ts = null) {
    $older = is_numeric($older_ts) ? (int)$older_ts : strtotime((string)$older_ts);
    $newer = $newer_ts === null ? time() : (is_numeric($newer_ts) ? (int)$newer_ts : strtotime((string)$newer_ts));
    if ($older <= 0) return 0.0;
    $seconds = max(0, $newer - $older);
    return $seconds / (30.4375 * 24 * 3600); // average month
}

/** Compute time-weighted score given normalized P_i values and timestamps. */
function sc_time_weighted_score(array $events, $lambda = 0.4) {
    $num = 0.0; $den = 0.0;
    foreach ($events as $ev) {
        $p = isset($ev['p']) ? floatval($ev['p']) : (isset($ev['normalized']) ? floatval($ev['normalized']) : 0.0);
        $ts = isset($ev['created_at']) ? strtotime((string)$ev['created_at']) : (isset($ev['ts']) ? (int)$ev['ts'] : time());
        $age = sc_age_in_months($ts);
        $w = exp(-$lambda * $age);
        $num += $p * $w;
        $den += $w;
    }
    if ($den <= 0) return 0.0;
    return $num / $den;
}

/** Compute quantile (linear interpolation) on sorted numeric array. */
function sc_quantile(array $sortedVals, $q) {
    $n = count($sortedVals);
    if ($n === 0) return 0.0;
    if ($q <= 0) return $sortedVals[0];
    if ($q >= 1) return $sortedVals[$n-1];
    $pos = ($n - 1) * $q;
    $lo = (int)floor($pos);
    $hi = (int)ceil($pos);
    if ($lo === $hi) return $sortedVals[$lo];
    $frac = $pos - $lo;
    return ($sortedVals[$lo] * (1 - $frac)) + ($sortedVals[$hi] * $frac);
}

/** Filter outliers using Tukey's IQR method. Returns array: [filteredEvents, outlierCount].
 * Events expected in form [['p'=>0.12,'created_at'=>'...'], ...]
 */
function sc_filter_outliers(array $events, $multiplier = 1.5) {
    $vals = [];
    foreach ($events as $ev) { $vals[] = isset($ev['p']) ? floatval($ev['p']) : 0.0; }
    $n = count($vals);
    if ($n < 3) return [$events, 0];
    sort($vals, SORT_NUMERIC);
    $q1 = sc_quantile($vals, 0.25);
    $q3 = sc_quantile($vals, 0.75);
    $iqr = $q3 - $q1;
    $lower = $q1 - ($multiplier * $iqr);
    $upper = $q3 + ($multiplier * $iqr);

    $filtered = [];
    $outliers = 0;
    foreach ($events as $ev) {
        $p = isset($ev['p']) ? floatval($ev['p']) : 0.0;
        if ($p < $lower || $p > $upper) {
            $outliers++;
            continue;
        }
        $filtered[] = $ev;
    }
    return [$filtered, $outliers];
}

/** Compute a similarity-weighted value for events relative to a project or category.
 * If no project/context provided, falls back to time-weighted average.
 * $context may be an array with keys like 'category_id' or 'project_id' and caller may
 * implement richer similarity logic; here we provide a simple category-match boost.
 */
function sc_similarity_weighted(array $events, $context = null, $lambda = 0.4) {
    if (empty($events)) return 0.0;
    // If context has category_id and events include project_category, give boost to matches
    $hasContext = is_array($context) && isset($context['category_id']);
    if ($hasContext) {
        $matched = [];
        $others = [];
        foreach ($events as $ev) {
            $p = isset($ev['p']) ? floatval($ev['p']) : 0.0;
            $projCat = isset($ev['project_category']) ? intval($ev['project_category']) : null;
            if ($projCat !== null && $projCat === intval($context['category_id'])) $matched[] = $ev;
            else $others[] = $ev;
        }
        // If there are matched events, weight them higher
        if (!empty($matched)) {
            $wMatched = sc_time_weighted_score($matched, $lambda);
            $wOthers = empty($others) ? 0.0 : sc_time_weighted_score($others, $lambda);
            // matched contributes 0.75, others 0.25
            return (0.75 * $wMatched) + (0.25 * $wOthers);
        }
    }
    // Fallback to time-weighted score
    return sc_time_weighted_score($events, $lambda);
}

/** Compute coefficient of variation based Consistency bounded [0,1]. */
function sc_consistency(array $events) {
    $vals = [];
    foreach ($events as $ev) {
        $v = isset($ev['p']) ? floatval($ev['p']) : (isset($ev['normalized']) ? floatval($ev['normalized']) : 0.0);
        $vals[] = $v;
    }
    $n = count($vals);
    if ($n === 0) return 0.0;
    $mean = array_sum($vals) / $n;
    if ($mean == 0) return 0.0;
    $sumSq = 0.0;
    foreach ($vals as $v) { $sumSq += ($v - $mean) * ($v - $mean); }
    $variance = $sumSq / $n;
    $std = sqrt($variance);
    $cv = $std / $mean;
    $consistency = max(0.0, 1.0 - $cv);
    if ($consistency < 0) $consistency = 0.0;
    if ($consistency > 1) $consistency = 1.0;
    return $consistency;
}

/** Compute availability factor: ratio value between 0-1. Expects numerator and denominator. */
function sc_availability_factor($confirmed, $total) {
    $c = floatval($confirmed); $t = floatval($total);
    if ($t <= 0) return 0.0;
    $r = $c / $t;
    if ($r < 0) $r = 0; if ($r > 1) $r = 1;
    return $r;
}

/** Compute risk for worker using proxies when explicit failure counts are not available.
 * Accepts an array with optional delay_rate, rework_rate, no_show_rate (0-1). */
function sc_compute_risk_worker(array $rates) {
    $delay = isset($rates['delay_rate']) ? floatval($rates['delay_rate']) : (isset($rates['speed_timing']) ? max(0.0, 1.0 - sc_normalize($rates['speed_timing'])) : 0.0);
    $rework = isset($rates['rework_rate']) ? floatval($rates['rework_rate']) : (isset($rates['rework_rate_metric']) ? sc_normalize($rates['rework_rate_metric']) : 0.0);
    $noshow = isset($rates['no_show_rate']) ? floatval($rates['no_show_rate']) : 0.0;
    $delay = min(1.0, max(0.0, $delay));
    $rework = min(1.0, max(0.0, $rework));
    $noshow = min(1.0, max(0.0, $noshow));
    return ($delay * 0.4) + ($rework * 0.4) + ($noshow * 0.2);
}

/** Compute risk for vendor using proxies. */
function sc_compute_risk_vendor(array $rates) {
    $late = isset($rates['late_delivery_rate']) ? floatval($rates['late_delivery_rate']) : (isset($rates['delivery_reliability']) ? max(0.0, 1.0 - sc_normalize($rates['delivery_reliability'])) : 0.0);
    $defect = isset($rates['defect_rate']) ? floatval($rates['defect_rate']) : (isset($rates['product_quality']) ? max(0.0, 1.0 - sc_normalize($rates['product_quality'])) : 0.0);
    $stock = isset($rates['stockout_rate']) ? floatval($rates['stockout_rate']) : (isset($rates['stock_availability']) ? max(0.0, 1.0 - sc_normalize($rates['stock_availability'])) : 0.0);
    $late = min(1.0, max(0.0, $late));
    $defect = min(1.0, max(0.0, $defect));
    $stock = min(1.0, max(0.0, $stock));
    return ($late * 0.4) + ($defect * 0.4) + ($stock * 0.2);
}

/** Confidence score per Layer 9. */
function sc_confidence($consistency, $N, $k = 5) {
    $cons = floatval($consistency);
    $n = max(0, intval($N));
    if ($n <= 0) return 0.0;
    $comp = ($n / ($n + $k));
    $c = $cons * $comp;
    if ($c < 0) $c = 0.0; if ($c > 1) $c = 1.0;
    return $c;
}

/** Final aggregation for worker/vendor (weights identical per spec). */
function sc_final_score($timeWeighted, $consistency, $similarityWeighted, $availability) {
    return (0.45 * $timeWeighted) + (0.20 * $consistency) + (0.20 * $similarityWeighted) + (0.15 * $availability);
}

/** Decision score calculation */
function sc_decision_score($finalScore, $risk, $confidence) {
    $riskAdj = $finalScore * (1.0 - $risk);
    return $riskAdj * $confidence;
}

?>
