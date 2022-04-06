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
        for($i=0; $i < count($data['id']); $i++) {
            $rule = [
                'project' => trim($data['project'][$i]),
                'department' => trim($data['department'][$i]),
                'domain' => trim($data['domain'][$i]),
                'rights' => trim($data['rights'][$i]),
            ];

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
        $table_name = plugin_table(self::$table_name);
        db_query("DELETE FROM " . $table_name . " WHERE id=" . db_param(), [$id]);
        return true;
    }

}