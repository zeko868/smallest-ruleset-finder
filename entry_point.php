<?php
const eligibilitiesMatrix =
[
    [
        [
            'ELIGIBLE', 'ELIGIBLE'
        ],
        [
            'ELIGIBLE', 'INELIGIBLE'
        ]
    ],
    [
        [
            'ELIGIBLE', 'INELIGIBLE'
        ],
        [
            'ELIGIBLE', 'INELIGIBLE'
        ]
    ]
];

const criteriaValues =
[
    [
        'ACCEPT', 'DECLINE'
    ],
    [
        true, false
    ],
    [
        'ACCEPT', 'DECLINE'
    ]
];

const criteriaNames = ['category', 'affordability', 'expert review', 'eligibility'];

$assumedMinimum = 4;

//require 'find_valid_ruleset_3dim_only.php';   // takes approximately 40 seconds on my local machine for current specified data
require 'find_valid_ruleset.php';   // takes approximately 75 seconds on my local machine for current specified data


findShortestRuleset();

if (empty($validRuleset)) {
    echo "Looks like that there are no $assumedMinimum or less rules that could satisfy specified eligibility matrix without rule-overlapping";
}
else {
    $widthsPerColumns = array();
    for ($i = 0; $i < numOfRuleComponents; $i++) {
        $minimumRequiredWidth = 0;
        foreach (array_merge(criteriaValues[$i], array(criteriaNames[$i])) as $cellData) {
            if (is_bool($cellData)) {
                $cellData = $cellData ? 'true' : 'false';
            }
            if ($minimumRequiredWidth < strlen($cellData)) {
                $minimumRequiredWidth = strlen($cellData);
            }
        }
        $widthsPerColumns[] = $minimumRequiredWidth + 1;
    }
    echo "Minimal number of rules required to satisfy specified eligibility matrix without rule-overlapping: $currentMinimum\n";
    echo "Example of valid ruleset:\n";
    echo 'Rule# ';
    for ($i = 0; $i < numOfRuleComponents; $i++) {
        echo '|' . criteriaNames[$i] . str_repeat(' ', $widthsPerColumns[$i] - strlen(criteriaNames[$i]));
    }
    echo '||' . criteriaNames[$i] . "\n";
    $rowWidth = strlen('Rule# |') + array_sum($widthsPerColumns) + count($widthsPerColumns) + 1 + strlen(criteriaNames[$i]);
    echo str_repeat("-", $rowWidth) . "\n";
    $rulesNum = count($validRuleset);
    for ($i = 0; $i < $rulesNum; $i++) {
        $rule = $validRuleset[$i];
        $criteriaValues = $rule[0];
        $resultValue = $rule[1];
        echo $i+1 . str_repeat(' ', strlen('Rule# ') - strlen($i));
        for ($j = 0; $j < numOfRuleComponents; $j++) {
            if ($criteriaValues[$j] === notSpecifiedValue) {
                $criteriaValues[$j] = '*';
            }
            else if (is_bool($criteriaValues[$j])) {
                $criteriaValues[$j] = $criteriaValues[$j] ? 'true' : 'false';
            }
            echo '|' . $criteriaValues[$j] . str_repeat(' ', $widthsPerColumns[$j] - strlen($criteriaValues[$j]));
        }
        if (is_bool($resultValue)) {
            echo '||' . ($resultValue ? 'true' : 'false') . "\n";
        }
        else {
            echo "||$resultValue\n";
        }
    }
}

?>