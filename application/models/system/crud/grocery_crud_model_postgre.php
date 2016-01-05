<?php

class grocery_crud_model_Postgre extends grocery_CRUD_Generic_Model {

    public $ESCAPE_CHAR = '"';
    public $CAPABLE_CONCAT = TRUE;

    public function __construct(){
        parent::__construct();

        $test = $this->protect_identifiers('t');
        $first_char = substr($test,0,1);
        if($first_char !== 't'){
            $this->ESCAPE_CHAR = $first_char;
        }
    }

    public function protect_identifiers($value)  {
        return $this->db->protect_identifiers($value);
    }

    public function build_concat_from_template($template, $prefix_replacement='', $suffix_replacement='', $as=NULL){
        if($this->CAPABLE_CONCAT){
            $strings = array();
            $arrayString = explode('}', $template);
            $cleanString = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(array('{', '&nbsp;'), '', $arrayString));
            foreach ($cleanString as $string){
                if(!empty($string)){
                    $strings[] = "coalesce({$string})";
                }
            }
            $concat_str = implode(', ', $strings);
        } else {
            $concat_str = "('" . str_replace(array("{", "}"), array("' || COALESCE(".$replacement, ", '') || '"), str_replace("'","\\'",$template)) . "')";
        }

        if(isset($as)) $concat_str .= " as " . $as;

        return $concat_str;
    }

    function get_list() {
    	if($this->table_name === null) return false;

        $select = $this->protect_identifiers("{$this->table_name}") . ".*";

        $additional_fields = array();
    	if(!empty($this->relation)) {
    		foreach($this->relation as $relation) {
    			list($field_name, $related_table, $related_field_title) = $relation;
    			$unique_join_name = $this->_unique_join_name($field_name);
    			$unique_field_name = $this->_unique_field_name($field_name);

                if(strstr($related_field_title,'{')) {
                    $related_field_title = str_replace(" ", "&nbsp;", $related_field_title);
                    $select .= ", ".$this->build_concat_from_template(
                        $related_field_title,
                        $this->protect_identifiers($unique_join_name).".".$this->ESCAPE_CHAR,
                        $this->ESCAPE_CHAR,
                        $this->protect_identifiers($unique_field_name)
                    );
    			} else {
    				$select .= ', ' . $this->protect_identifiers($unique_join_name. '.'. $related_field_title).' AS '. $this->protect_identifiers($unique_field_name);
    			}

    			if($this->field_exists($related_field_title)){
    			    $additional_fields[$this->table_name. '.'. $related_field_title] = $related_field_title;
    			}
    		}
    	}

    	if(!empty($this->relation_n_n)) {
			$select = $this->relation_n_n_queries($select);
    	}

    	$this->db->select($select, false);
    	$results = $this->db->get($this->table_name)->result();

        for($i=0; $i<count($results); $i++){
            foreach($additional_fields as $alias=>$real_field){
                $results[$i]->{$alias} = $results[$i]->{$real_field};
            }
        }

    	return $results;
    }

    /**
     * (non-PHPdoc)
     * @see Grocery_crud_model::like()
     *
     * Function for single searching by method
     */
    public function like($field, $match = '', $side = 'both') {
        // checking data type from table
        $check = $this->db->select("pg_typeof({$field}) as field_type from {$this->table_name}")->get();
        foreach($check->result() as $types){
            $type = strtolower($types->field_type);
        }

        $matchChar = preg_match('/^character/', $type, $matches, PREG_OFFSET_CAPTURE);
        if($matchChar == true) {
            // if data type was character
            $this->db->like($field, $match, $side);
        }else{
            // if data type wasn't character
            $this->db->like("cast({$field} as text)", $match, $side);
        }
    }

    /**
     *
     * @param unknown $table
     * @param string $field
     * @param string $result
     * @param string $getObject
     */
    protected function getTableColumns($table, $field = false, $result = false, $getObject = false){

        /* Get Table Column(s)
         * ================================================
         * ini belum kepake si, buat apaan... Tadinya mau
         * buat crud model di fungsi or_like-nya
         * buat ngambil column type dari field tabelnya :D
         * ================================================
         */

        $where = false;
        if($field != false){
            $where = "  and column_name = '{$field}' ";
        }
        $sql = "column_name, data_type from information_schema.columns where table_name = '{$table}' {$where}";
        $query = $this->db->select($sql)->get();
        $data = $query->result();

        if($data){
            if($result == 'row'){
                if($getObject == 'name'){
                    return $data[0]->column_name;
                }elseif($getObject == 'type'){
                    return $data[0]->data_type;
                }else{
                    return $data[0];
                }
            }else{
                return $data;
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see Grocery_crud_model::or_like()
     *
     * Function for multiple or all searching by method
     */
    public function or_like($field, $match = '', $side = 'both', $type = array()) {
        $getType = $this->get_field_types($this->table_name);
        foreach ($getType as $data){
            if($data->type){
                $type[$data->name] = $data->type;
            }
        }
        if(isset($type[$field])){
            if($type[$field] == 'varchar')
                $this->db->or_like($field, $match, $side, false);
            else
                $this->db->or_like("cast({$field} as text)", $match, $side, false);
        }
    }

    function get_field_types_basic_table() {
    	$db_field_types = array();
        foreach($this->get_field_types($this->table_name) as $db_field_type) {
    	    $db_type = $db_field_type->type;
            $length = $db_field_type->max_length;
            $db_field_types[$db_field_type->name]['db_max_length'] = $length;
            $db_field_types[$db_field_type->name]['db_type'] = $db_type;
            $db_field_types[$db_field_type->name]['db_null'] = true;
            $db_field_types[$db_field_type->name]['db_extra'] = '';
    	}

    	$results = $this->get_field_types($this->table_name);
    	foreach($results as $num => $row) {
    		$row = (array)$row;
    		$results[$num] = (object)( array_merge($row, $db_field_types[$row['name']])  );
    	}
    	return $results;
    }

    function build_db_join_relation($related_table, $unique_name, $related_primary_key, $field_name) {
        $onString1 = str_replace('"', ' ', $this->protect_identifiers($unique_name.'.'.$related_primary_key));
        $onString2 = str_replace('"', ' ', $this->protect_identifiers($this->table_name.'.'.$field_name));//var_dump($onString2);exit;
        $this->db->join("{$this->protect_identifiers($related_table)} as {$this->protect_identifiers($unique_name)}", "{$onString1} = {$onString2}",'left');
    }

    function build_relation_n_n_subquery($field, $selection_table, $relation_table, $primary_key_alias_to_selection_table, $primary_key_selection_table, $primary_key_alias_to_this_table, $field_name){
        return "(
            SELECT array_agg(".$this->protect_identifiers($field).")
                FROM ".$this->protect_identifiers($selection_table)."
            LEFT JOIN ".$this->protect_identifiers($relation_table)."
                ON ".$this->protect_identifiers($relation_table.".".$primary_key_alias_to_selection_table)." = ".$this->protect_identifiers($selection_table.".".$primary_key_selection_table) ."
            WHERE ".$this->protect_identifiers($relation_table.".".$primary_key_alias_to_this_table)." = ".$this->protect_identifiers($this->table_name.".".$this->get_primary_key($this->table_name))."
            GROUP BY ".$this->protect_identifiers($relation_table.".".$primary_key_alias_to_this_table)."
        ) AS ".$this->protect_identifiers($field_name);
    }
}