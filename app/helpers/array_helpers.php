<?php

/**
 * Generates all possible non-empty subsets of an array of IDs.
 * Used for calculating statistics based on groups of beneficiaries.
 *
 * @param array $elements The input array of IDs.
 * @return array An associative array where keys are sorted, comma-separated signatures of the subsets,
 *               and values are the arrays of IDs in each subset.
 */
function findSubsetsOfIds(array $elements): array
{
    $subsets = [];
    $n = count($elements);

    // Iterate from 1 to 2^n - 1 (to get all non-empty subsets)
    for ($i = 1; $i < (1 << $n); $i++) {
        $subset = [];
        for ($j = 0; $j < $n; $j++) {
            // Check if j-th bit is set in i
            if (($i >> $j) & 1) {
                $subset[] = $elements[$j];
            }
        }
        sort($subset); // Ensure consistent order for signature
        $signature = implode(',', $subset);
        $subsets[$signature] = $subset;
    }

    // Sort subsets by signature for consistent output (optional, but good for debugging/consistency)
    ksort($subsets);

    return $subsets;
}
