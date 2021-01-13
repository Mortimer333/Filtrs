# Filtrs

Create SQL `where` queries programmatically.

# Example

 ```php
 $filters = [
    "id" => [
      "g_con" => "and",
      "l_con" => "or",
      "operator" => "=",
      "values" => [
        1,2,3,4
      ]
    ],
    "login" => [
      "g_con" => "and",
      "operator" => "like",
      "values" => "lo"
    ],
    "ORDER BY" => [
      "id" => "asc"
    ],
    "group by" => [
      "login"
    ],
    "limit" => 50,
    "offset" => 10
  ];

  $filtr = new Filtrs($filters);
  $filtr->add([
    "login" => [
      "g_con" => "and",
      "operator" => "like",
      "values" => "u"
    ]
  ]);
  echo $filtr->get();
  
  // output WHERE ( id = 1 OR id = 2 OR id = 3 OR id = 4) AND login LIKE '%u%' GROUP BY login ORDER BY id ASC LIMIT 50 OFFSET 10 
  ```
  
# How to use
 
If you need to create very simple filters from user input (in string format) this library is what you need. It's in its 0.0.1 version so very VERY simple queries. 

## Columns

To filter by columns just pass column name as key and assign :
  - g_con - global connector
  - l_con - not required if you are using only one value, local connector
  - operator - available: >, <, >=, <=, =, !=, IS NOT, IS, LIKE
  - values -  it can be array of values or single variable
 
 ## PREDEFINED
 
 Predefined keys are closing ones like GROUP BY or ORDER BY. List (not case sensitive) :
  - GROUP BY - assoc array of columns and directions (key is column, value is direction (ASC, DESC))
  - ORDER BY - array of column names
  - LIMIT    - intiger
  - OFFSET   - intiger
  
# FUNCTIONS

## GET

Get returns query (string)

## ADD

Adds new colums/predefined key to fitrl (if you are add the same column it will get replaced)
