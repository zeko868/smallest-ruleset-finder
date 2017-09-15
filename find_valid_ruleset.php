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
        $rule = array_column($criterias, $i);
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
        $rule = array_column($criterias, $i);
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

function getCoordinatesOfNextNode($nodeCoords) {
    $coordsOfNextNode = array();
    $incrementCoordinate = true;
    for ($i = count($nodeCoords) - 1; $i >= 0; $i--) {
        if ($incrementCoordinate) {
            if ($nodeCoords[$i] === notSpecifiedValue) {
                if ($i === 0) {
                    return null;                    
                }
                else {
                    array_unshift($coordsOfNextNode, 0);
                }
            }
            else {
                $positionOfCurrentCoordinate = array_search($nodeCoords[$i], criteriaValues[$i], true);
                array_unshift($coordsOfNextNode, $positionOfCurrentCoordinate + 1);
                $incrementCoordinate = false;
            }
        }
        else {
            $positionOfCurrentCoordinate = array_search($nodeCoords[$i], criteriaValues[$i], true);
            array_unshift($coordsOfNextNode, $positionOfCurrentCoordinate);
        }
    }
    return $coordsOfNextNode;
}

function findShortestRuleset($oldComponentValues = [], $oldValues = [], $componentValues = [], $startingPoint = []) {
    $currentCriteriaIndex = count($componentValues);
    if ($startingPoint === null) {
        return;
    }
    else if (empty($startingPoint)) {
        $pruning = false;
        $startingCoord = 0;
    }
    else {
        $pruning = true;
        $startingCoord = $startingPoint[$currentCriteriaIndex];
    }
    foreach (array_slice(array_merge(criteriaValues[$currentCriteriaIndex], array(notSpecifiedValue)), $startingCoord) as $critVal) {
        if ($currentCriteriaIndex+1 < numOfRuleComponents) {
            if ($pruning) {
                findShortestRuleset($oldComponentValues, $oldValues, array_merge($componentValues, array($critVal)), $startingPoint);
                $pruning = false;
            }
            else {
                findShortestRuleset($oldComponentValues, $oldValues, array_merge($componentValues, array($critVal)));
            }
        }
        else {
            global $resultValues;
            global $currentMinimum;
            foreach ($resultValues as $value) {
                $numOfCriterias = count($oldValues);
                $newComponentValues = $oldComponentValues;
                for ($i = 0; $i < numOfRuleComponents-1; $i++) {
                    $newComponentValues[$i][] = $componentValues[$i];
                }
                $newComponentValues[$i][] = $critVal;
                $newValues = array_merge($oldValues, array($value));
                $rulesNum = count($newValues);
                if (checkIfNoOverlapping($newComponentValues) && checkIfRulesMatch($newComponentValues, $newValues)) {
                    $currentMinimum = $rulesNum;
                    global $validRuleset;
                    $validRuleset = array();
                    for ($i = 0; $i < $rulesNum; $i++) {
                        $ithComponentValues = array_column($newComponentValues, $i);
                        $validRuleset[] = array($ithComponentValues, $newValues[$i]);
                    }
                }
                else {
                    if (/*(empty($validRuleset) && $rulesNum+1 <= $currentMinimum) || */$rulesNum+1 < $currentMinimum) {
                        $continuingNode = getCoordinatesOfNextNode(array_column($newComponentValues, count($newValues)-1));
                        findShortestRuleset($newComponentValues, $newValues, [], $continuingNode);
                    }
                }
            }
        }
    }
}

?>