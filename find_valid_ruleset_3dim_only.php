<?php
$currentMinimum = assumedMinimum + 1;
$emptyMatchesMatrix = array();
$resultValues = array();
initializeValuesOfLastDimensionOfArrayToZeroesAndGetAllResultValues(eligibilitiesMatrix);
$resultValues = array_unique($resultValues);


$validRuleset = array();
define('numOfRuleComponents', count(criteriaValues));
const notSpecifiedValue = null;

function initializeValuesOfLastDimensionOfArrayToZeroesAndGetAllResultValues($array, $indices = []) {
    $numOfElements = count($array);
    for ($i = 0; $i < $numOfElements; $i++) {
        if (is_array($array[$i])) {
            initializeValuesOfLastDimensionOfArrayToZeroesAndGetAllResultValues($array[$i], array_merge($indices, array($i)));
        }
        else {
            global $emptyMatchesMatrix;
            global $resultValues;
            eval('$emptyMatchesMatrix[' . implode('][', array_merge($indices, array($i))) . '] = 0;');
            $resultValues[] = $array[$i];
        }
    }
}

function incrementMatchMatrix(&$matrix, $possiblyUnstructuredRule) {
    $positionOfUndefinedState = array_search(notSpecifiedValue, $possiblyUnstructuredRule, true);
    if ($positionOfUndefinedState !== false) {
        foreach (criteriaValues[$positionOfUndefinedState] as $critVal) {
            $possiblyUnstructuredRule[$positionOfUndefinedState] = $critVal;
            incrementMatchMatrix($matrix, $possiblyUnstructuredRule);
        }
    }
    else {
        $i = array_search($possiblyUnstructuredRule[0], criteriaValues[0], true);
        $j = array_search($possiblyUnstructuredRule[1], criteriaValues[1], true);
        $k = array_search($possiblyUnstructuredRule[2], criteriaValues[2], true);
        $matrix[$i][$j][$k]++;
    }
}

function checkIfRulesMatch($crits1, $crits2, $crits3, $vals) {
    $numOfCriterias = count($crits1);
    for ($a = 0; $a < $numOfCriterias; $a++) {
        $i = $crits1[$a];
        $j = $crits2[$a];
        $k = $crits3[$a];
        $value = $vals[$a];
        if (checkIfRuleMatches(array($i, $j, $k), $value) === false) {
            return false;
        }
    }
    return true;
}

function checkIfRuleMatches($rule, $value) {
    $positionOfUndefinedState = array_search(notSpecifiedValue, $rule, true);
    if ($positionOfUndefinedState !== false) {
        foreach (criteriaValues[$positionOfUndefinedState] as $critVal) {            
            $rule[$positionOfUndefinedState] = $critVal;
            if (checkIfRuleMatches($rule, $value) === false) {
                return false;
            }
        }
    }
    else {
        $i = array_search($rule[0], criteriaValues[0], true);
        $j = array_search($rule[1], criteriaValues[1], true);
        $k = array_search($rule[2], criteriaValues[2], true);
        return eligibilitiesMatrix[$i][$j][$k] === $value;
    }
    return true;
}

function checkIfNoOverlapping($crits1, $crits2, $crits3) {
    global $emptyMatchesMatrix;
    $matches = $emptyMatchesMatrix;
    $numOfCriterias = count($crits1);
    for ($a = 0; $a < $numOfCriterias; $a++) {
        $i = $crits1[$a];
        $j = $crits2[$a];
        $k = $crits3[$a];
        incrementMatchMatrix($matches, array($i, $j, $k));
    }
    for ($i = 0; $i < count(criteriaValues[0]); $i++) {
        for ($j = 0; $j < count(criteriaValues[1]); $j++) {
            for ($k = 0; $k < count(criteriaValues[2]); $k++) {
                if ($matches[$i][$j][$k] !== 1) {
                    return false;
                }
            }
        }
    }
    return true;
}

function checkIfValueOfEachElementOfLastDimensionIsOne($array) {
    foreach ($array as $value) {
        if (is_array($value)) {
            if (checkIfValueOfEachElementOfLastDimensionIsOne($value) === false) {
                return false;
            }
        }
        else {
            if ($value !== 1) {
                return false;
            }
        }
    }
    return true;
}

function findShortestRuleset($iOld = [], $jOld = [], $kOld = [], $valueOld = []) {
    global $currentMinimum;
    foreach (array_merge(criteriaValues[0], array(notSpecifiedValue)) as $critValI) {
        foreach (array_merge(criteriaValues[1], array(notSpecifiedValue)) as $critValJ) {
            foreach (array_merge(criteriaValues[2], array(notSpecifiedValue)) as $critValK) {
                global $resultValues;
                foreach ($resultValues as $value) {
                    $iPos = array_keys($iOld, $critValI, true);
                    $jPos = array_keys($jOld, $critValJ, true);
                    $kPos = array_keys($kOld, $critValK, true);
                    if (empty(array_intersect($iPos, $jPos, $kPos))) {
                        $iNew = array_merge($iOld, array($critValI));
                        $jNew = array_merge($jOld, array($critValJ));
                        $kNew = array_merge($kOld, array($critValK));
                        $valueNew = array_merge($valueOld, array($value));
                        $rulesNum = count($iNew);
                        if (checkIfNoOverlapping($iNew, $jNew, $kNew) && checkIfRulesMatch($iNew, $jNew, $kNew, $valueNew)) {
                            if ($rulesNum < $currentMinimum) {
                                $currentMinimum = $rulesNum;
                                global $validRuleset;
                                $validRuleset = array();
                                for ($i = 0; $i < $rulesNum; $i++) {
                                    $ithComponentValues = array($iNew[$i], $jNew[$i], $kNew[$i]);
                                    $validRuleset[] = array($ithComponentValues, $valueNew[$i]);
                                }
                            }
                        }
                        else {
                            if (/*(empty($validRuleset) && $rulesNum+1 <= $currentMinimum) || */$rulesNum+1 < $currentMinimum) {
                                findShortestRuleset($iNew, $jNew, $kNew, $valueNew);
                            }
                        }
                    }
                }
            }
        }
    }
}

?>