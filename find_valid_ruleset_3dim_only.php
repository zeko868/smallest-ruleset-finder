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

define('numCritValI', count(criteriaValues[0]));
define('numCritValJ', count(criteriaValues[1]));
define('numCritValK', count(criteriaValues[2]));

function findShortestRuleset($iOld = [], $jOld = [], $kOld = [], $valueOld = [], $iStart = 0, $jStart = 0, $kStart = 0) {
    global $currentMinimum;
    global $valuesNumPerCriterias;
    for ($i = $iStart; $i <= numCritValI; $i++) {
        if ($i === numCritValI) {
            $critValI = notSpecifiedValue;
        }
        else {
            $critValI = criteriaValues[0][$i];
        }
        for ($j = $jStart; $j <= numCritValJ; $j++) {
            if ($j === numCritValJ) {
                $critValJ = notSpecifiedValue;
            }
            else {
                $critValJ = criteriaValues[1][$j];
            }
            for ($k = $kStart; $k <= numCritValK; $k++) {
                if ($k === numCritValK) {
                    $critValK = notSpecifiedValue;
                }
                else {
                    $critValK = criteriaValues[2][$k];
                }
                global $resultValues;
                foreach ($resultValues as $value) {
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
                            for ($a = 0; $a < $rulesNum; $a++) {
                                $athComponentValues = array($iNew[$a], $jNew[$a], $kNew[$a]);
                                $validRuleset[] = array($athComponentValues, $valueNew[$a]);
                            }
                        }
                    }
                    else {
                        if (/*(empty($validRuleset) && $rulesNum+1 <= $currentMinimum) || */$rulesNum+1 < $currentMinimum) {
                            if ($k === numCritValK) {
                                $newK = 0;
                                if ($j === numCritValJ) {
                                    $newJ = 0;
                                    if ($i === numCritValI) {
                                        return;
                                    }
                                    else {
                                        $newI = $i + 1;
                                    }
                                }
                                else {
                                    $newJ = $j + 1;
                                    $newI = $i;
                                }
                            }
                            else {
                                $newK = $k + 1;
                                $newJ = $j;
                                $newI = $i;
                            }
                            findShortestRuleset($iNew, $jNew, $kNew, $valueNew, $newI, $newJ, $newK);
                        }
                    }
                }
            }
            $kStart = 0;
        }
        $jStart = 0;
    }
}

?>