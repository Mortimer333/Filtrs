<?php
class Filtrs
{

  private string $filtr = '';
  private array $attrs  = [];

  function __construct(array $filtrs)
  {
    $this->attrs = $filtrs;
    $this->filtr = $this->create( $filtrs );
  }

  public function get()
  {
    return $this->filtr;
  }

  public function add( array $attrs )
  {
    $this->attrs = array_merge( $this->attrs, $attrs );
    $this->filtr = $this->create( $this->attrs );
  }

  private function create( array $filtrs )
  {
    $filtr_str = "";
    $end_filtrs = [];

    foreach ($filtrs as $column => $attr) {

      if ( !is_string( $column ) ) throw new Exception('Column must be a type of string');

      switch ( strtoupper( $column ) ) {
        case 'ORDER BY':
        case "GROUP BY":
        case "LIMIT":
        case "OFFSET":
          $end_filtrs[ strtoupper( $column ) ] = $attr;
          break;
        default:
          $filtr_str = $this->addToFiltr($filtr_str, $column, $attr);
      }

    }

    if ( isset($end_filtrs['GROUP BY']) ) $filtr_str = $this->AddGroup ($filtr_str, $end_filtrs['GROUP BY']);
    if ( isset($end_filtrs['ORDER BY']) ) $filtr_str = $this->AddOrder ($filtr_str, $end_filtrs['ORDER BY']);
    if ( isset($end_filtrs['LIMIT'   ]) ) $filtr_str = $this->AddLiOf  ($filtr_str, $end_filtrs['LIMIT'   ], "LIMIT" );
    if ( isset($end_filtrs['OFFSET'  ]) ) $filtr_str = $this->AddLiOf  ($filtr_str, $end_filtrs['OFFSET'  ], "OFFSET");

    return $filtr_str;
  }

  private function AddLiOf( string $filtr_str, int $value , string $type )
  {
    /*
      "limit" => int
    */
    $filtr_str .= " " . $type . " " . $value;
    return $filtr_str;
  }

  private function AddOrder(string $filtr_str, array $attr)
  {
    /*
      $attr = [
        "column" => "order",
      ]
    */
    $order_str = "";
    foreach ($attr as $column => $order) {
      if ( !is_string( $column ) ) throw new Exception("Column in order must be a type of string.");

      $order = strtoupper($order);
      if ( $order !== "" && $order !== "DESC" && $order !== "ASC" ) throw new Exception("Passed wrong orderd value.");

      if ($order_str === "") $order_str  = "ORDER BY " . $column . " " . $order;
      else                   $order_str .= ", " . $column . " " . $order;

    }

    return $filtr_str . " " . $order_str;
  }

  private function AddGroup(string $filtr_str, array $attr)
  {
    /*
      $attr = [
        "column",
      ]
    */

    $group_str = "";
    foreach ($attr as $column ) {

      if ( !is_string( $column ) ) throw new Exception("Column in group must be a type of string.");

      if ( $group_str === "" ) $group_str  = "GROUP BY " . $column;
      else                     $group_str .= ", " . $column;

    }

    return $filtr_str . " " . $group_str;
  }

  private function addToFiltr( string $filtr_str, string $column, array $attr )
  {
    /*
      $filtrs = [
        "column" => [
          "g_con" => "connector",
          "l_con" => "connector",
          "operator" => "value",
          "values" => [
            "value",
            "value",
            "value",
          ]
        ],
        "column" => [
          "g_con" => "connector",
          "l_con" => "connector",
          "operator" => "value",
          "values" => "value"
        ],
      ]

      all columns are joined by connector [g_con]
      all values in column are joined by connector [l_con]
    */

    $column = $this->StopEscSQL($column);

    if ( !isset    ($attr["g_con"]) ) throw new Exception('Column not possesing global connector.');
    if ( !is_string($attr["g_con"]) ) throw new Exception('Column global connector is of wrong type.');

    if ( isset($attr["l_con"]) && !is_string($attr["l_con"]) ) throw new Exception('Column local connector is of wrong type.');

    $attr["g_con"] = strtoupper($attr["g_con"]);
    if ( isset($attr["l_con"]) ) $attr["l_con"] = strtoupper($attr["l_con"]);
    $attr["operator"] = strtoupper($attr["operator"]);

    if (                          $attr["g_con"] != "AND" && $attr["g_con"] != "OR" ) throw new Exception('Column global connector is of wrong value.');
    if ( isset($attr["l_con"]) && $attr["l_con"] != "AND" && $attr["l_con"] != "OR" ) throw new Exception('Column local connector is of wrong value.');

    $posEqual = [">","<",">=","<=","=","!=","IS NOT","IS","LIKE"];

    if ( !isset( $attr["operator"] ) )                   throw new Exception('Column operator isn\'t set.');
    elseif ( !in_array( $attr["operator"], $posEqual ) ) throw new Exception('Column operator has wrong value.');

    if ( is_string( $attr["values"] ) || is_numeric( $attr["values"] ) || is_bool( $attr["values"] ) ) {

      if ( is_bool($attr["values"] ) ) {
        if ( $attr["values"] === false ) $attr["values"] = 0;
        else                             $attr["values"] = 1;
      }

      if ( $attr["operator"] === "LIKE" ) $attr["values"] = "%" . $attr["values"] . "%";

      if ( is_string( $attr["values"] ) ) {
        $attr["values"] = $this->StopEscSQL($attr["values"]);
        $attr["values"] = "'" . $attr["values"] . "'";
      } else
        $attr["values"] = $this->EscSQL($attr["values"]);

      if ( $filtr_str === "" ) $filtr_str  = "WHERE " . $column . " " . $attr["operator"] . " " . $attr["values"];
      else                     $filtr_str .= " " . $attr["g_con"] . " " . $column . " " . $attr["operator"] . " " . $attr["values"];

    } elseif (is_array($attr["values"])) {

      if ( !isset( $attr["l_con"] ) ) throw new Exception('Column local connector can\'t be unset when using array of values.');

      if ( $filtr_str === "" ) $filtr_str = "WHERE (";
      else                     $filtr_str .= " " . $attr["g_con"] . " (";

      foreach ($attr["values"] as $value) {

        if ($attr["operator"] === "LIKE") $value = "%" . $value . "%";

        if ( is_string( $value ) ) {
          $value = $this->StopEscSQL( $value );
          $value = "`" . $value . "`";
        }

        if ( substr( $filtr_str, -1 ) === "(" ) $filtr_str .= " " . $column . " " . $attr["operator"] . " " . $value;
        else                                    $filtr_str .= " " . $attr["l_con"] . " " . $column . " " . $attr["operator"] . " " . $value;

      }

      $filtr_str .= ")";
    } else
      throw new Exception('Column values are in wrong type.');
    return $filtr_str;
  }

  private function StopEscSQL($string)
  {
    $string = str_replace( "`", "'", $string );
    $string = str_replace(["\b","\n","\r","\t","\z"], "", $string);
    return trim($string);
  }

  private function EscSQL($string)
  {
    $string = mb_strtolower($string);
    $string = " " . $string;
    $string = str_replace(["\b","\n","\r","\t","\z", " or ", " and ", " where ", " select ", " delete ", " insert ", " from ", " desc ", " show "], "", $string);
    return trim($string);
  }

}
