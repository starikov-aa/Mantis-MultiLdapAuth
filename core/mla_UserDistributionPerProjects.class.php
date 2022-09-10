<?php

class mla_UserDistributionPerProjects
{
    static $table_name = 'udpp_rules';

    const RULES_FIELDS_LIST = [
        'id',
        'project',
        'department',
        'domain',
        'rights'
    ];

    function __construct(){

    }

    public function processing($data): bool
    {
        $data = self::validate_rules_data($data);

        for($i=0; $i < count($data['id']); $i++) {
            $rule = [
                'project' => trim($data['project'][$i]),
                'department' => trim($data['department'][$i]),
                'domain' => trim($data['domain'][$i]),
                'rights' => trim($data['rights'][$i]),
            ];

            if (is_null($data['project'][$i]) || is_null($data['department'][$i])
                || is_null($data['domain'][$i]) || is_null($data['rights'][$i])) {
                continue;
            }

            if (!empty($data['id'][$i]) && $data['id'][$i] > 0) {
                $this->update_rule($data['id'][$i], $rule);
            } else {
                $this->create_rule($rule);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    static function get_rules(): array
    {
        $result = [];
        $query = "SELECT * FROM " . plugin_table(self::$table_name);
        $query_select= db_query($query);
        while ($row = db_fetch_array($query_select)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @param $data
     */
    private function create_rule($data)
    {
        $table_name = plugin_table(self::$table_name);
        db_param_push();
        $query = "INSERT INTO " . $table_name . " (`project_id`, `department`, `domain`, `rights`) VALUES (
        '" . $data['project'] . "',
        '" . $data['department'] . "',
        '" . $data['domain'] . "',
        '" . $data['rights'] . "'
        );";
        db_query($query);
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection
     */
    private function update_rule($id, $data): bool
    {
        $table_name = plugin_table(self::$table_name);
        $query = "UPDATE " . $table_name . " SET 
        project_id='". $data['project'] ."',  
        department='". $data['department'] ."', 
        domain='". $data['domain'] ."',  
        rights='". $data['rights'] ."' 
        WHERE id=" . $id;

        db_query($query);

        return true;
    }

    /**
     * @param $id
     * @return bool
     */
    static function delete_rule($id): bool
    {
        if (!is_int($id))
            return false;

        $table_name = plugin_table(self::$table_name);
        db_query("DELETE FROM " . $table_name . " WHERE id=" . db_param(), [$id]);
        return true;
    }

    static function validate_rules_data($data)
    {
        $base_regexp = [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => "#^[a-z0-9\.,-_&/\*]+$#i"],
            'flags'  => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE
        ];

        $int_filter = [
            'filter' => FILTER_VALIDATE_INT,
            'flags'  => FILTER_REQUIRE_ARRAY
        ];

        $args = [
            'id' => $int_filter,
            'project' => $int_filter,
            'department' => $base_regexp,
            'domain' => $base_regexp,
            'rights' => $int_filter,
        ];

        return filter_input_array(INPUT_POST, $args );
    }

}