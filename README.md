# Smallest ruleset finder for specified decision table<br/>
Purpose of this application is to minimize number of rules without changing former logic while achieving that rules do not overlap.
Let the following image represent initial decision table.<br/>
![Initial decision table](/images/decision_table.png?raw=true "Initial decision table which may not be optimized (number of rules can be minimized or rule-overlapping can be avoided)")
<br/>
<br/>
Each decision table is possible to convert to its belonging decision tree. Next figure shows decision tree based on previously-provided decision table.<br/>
![Derived decision tree](/images/decision_tree.png?raw=true "Decision tree derived from previous decision table.")
<br/>
Each intersection-node forks to multiple other nodes and that's the case because decisions (or criteria checks) are linearly-independent and each can occur, no matter which have occurred before. Nodes that are "rounded" with green colour characterize in this scenario that examinee would be eligible if it satisfies all criterias from its left side, and those with red colour mean that in that case it would be ineligible.<br/>
After we create visual representation of decision table, it is easy to adapt main script (entry_point.php) with data specific to our problem.
In constant ```eligibilitiesMatrix``` should be set result values for each leaf of derived decision tree. Number of dimensions in matrix is equal to depth of decision tree (number of nodes between root and leaf, including them).
Constant ```criteriaValues``` should contain two dimensional array, i.e. it should contain value names (alternative names) for each criteria (decision).
Last array is ```criteriaNames``` with only has one dimension which elements represent criteria names. Its purpose is only aesthetic - to add column names on generated result table.
```php
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
```
Last constant that has to be set is ```assumedMinimum``` which represent size of ruleset we are excepting or we are sure that exists for given decision table. Purpose of this constant is shortening execution time, i.e. avoiding deep recursions if it is known that answer contains of only few rules. At example, for given problem has been initially set 4 rules and the question is it possible to remain same decision tree with lower amount of rules. In that case we can set this constant to 3 - if given problem cannot be represented with 3 rules, then the program will return that there is no ruleset of size 3 that can represent given decision tree. Otherwise would be printed found ruleset that satisfy all given criterias.
<br/>
<br/>
To start script it is required to call script with PHP from the command line.
```shell
php -f entry_point.php
```
According to the complexity of given problem, shell will remain in busy state certain amount of time.
![Output after execution of script](/images/output_no_overlapping_ruleset.png?raw=true "Output after successful execution of script")
<br/>
<br/>
If specified decision table consists of exactly 3 criterias, in that case can be used ```find_valid_ruleset_3dim_only.php``` script. That script is not based on recursion as much as its counterpart is. Initial decition table is solved in 40 seconds on my local machine when that specific script is used and it takes about 75 seconds when its generic variant (```find_valid_ruleset.php```) is used.
