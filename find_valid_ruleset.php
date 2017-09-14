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
        $command = '$matrix';
        for ($i = 0; $i < numOfRuleComponents; $i++) {
            $componentIndex = array_search($possiblyUnstructuredRule[$i], criteriaValues[$i], true);
            $command .= "[$componentIndex]";
        }
        $command .= '++;';
        eval($command);
    }
}

function checkIfRulesMatch($criterias, $vals) {
    $numOfCriterias = count($criterias[0]);
    for ($i = 0; $i < $numOfCriterias; $i++) {
        $rule = [];
        foreach ($criterias as $criteriaComponent) {
            $rule[] = $criteriaComponent[$i];
        }
        $value = $vals[$i];
        if (checkIfRuleMatches($rule, $value) === false) {
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
        $command = '$match = eligibilitiesMatrix';
        for ($i = 0; $i < numOfRuleComponents; $i++) {
            $componentIndex = array_search($rule[$i], criteriaValues[$i], true);
            $command .= "[$componentIndex]";
        }
        $command .= ' === $value;';
        eval($command);
        return $match;
    }
    return true;
}

function checkIfNoOverlapping($criterias) {
    global $emptyMatchesMatrix;
    $matches = $emptyMatchesMatrix;
    $numOfCriterias = count($criterias[0]);
    for ($i = 0; $i < $numOfCriterias; $i++) {
        $rule = [];
        foreach ($criterias as $criteriaComponent) {
            $rule[] = $criteriaComponent[$i];
        }
        incrementMatchMatrix($matches, $rule);
    }
    return checkIfValueOfEachElementOfLastDimensionIsOne($matches);
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

function findShortestRuleset($oldComponentValues = [], $oldValues = [], $componentValues = []) {
    $currentCriteriaIndex = count($componentValues);
    foreach (array_merge(criteriaValues[$currentCriteriaIndex], array(notSpecifiedValue)) as $critVal) {
        if ($currentCriteriaIndex+1 < numOfRuleComponents) {
            findShortestRuleset($oldComponentValues, $oldValues, array_merge($componentValues, array($critVal)));
        }
        else {
            global $resultValues;
            foreach ($resultValues as $value) {
                $numOfCriterias = count($oldValues);
                $foundIndices = array();
                for ($i = 0; $i < numOfRuleComponents - 1; $i++) {
                    if (!empty($oldComponentValues[$i])) {
                        $foundIndices[] = array_keys($oldComponentValues[$i], $componentValues[$i], true);
                    }
                }
                if (!empty($oldComponentValues[$i])) {
                    $foundIndices[] = array_keys($oldComponentValues[$i], $critVal, true);
                }
                if (empty($foundIndices)) {
                    $intersect = array();
                }
                else {
                    $intersect = call_user_func_array('array_intersect', $foundIndices);
                }
                if (empty($intersect)) {
                    $newComponentValues = $oldComponentValues;
                    for ($i = 0; $i < numOfRuleComponents-1; $i++) {
                        $newComponentValues[$i][] = $componentValues[$i];
                    }
                    $newComponentValues[$i][] = $critVal;
                    $newValues = array_merge($oldValues, array($value));
                    $rulesNum = count($newValues);
                    global $currentMinimum;
                    if (checkIfNoOverlapping($newComponentValues) && checkIfRulesMatch($newComponentValues, $newValues)) {
                        $currentMinimum = $rulesNum;
                        global $validRuleset;
                        $validRuleset = array();
                        for ($i = 0; $i < $rulesNum; $i++) {
                            $ithComponentValues = array();
                            foreach ($newComponentValues as $componentValue) {
                                $ithComponentValues[] = $componentValue[$i];
                            }
                            $validRuleset[] = array($ithComponentValues, $newValues[$i]);
                        }
                    }
                    else {
                        if (/*(empty($validRuleset) && $rulesNum+1 <= $currentMinimum) || */$rulesNum+1 < $currentMinimum) {
                            findShortestRuleset($newComponentValues, $newValues);
                        }
                    }
                }
            }
        }
    }
}

?>