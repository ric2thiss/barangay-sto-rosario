<?php

class QueryBuilder {
    protected $db;
    protected $table;
    protected $conditions = [];
    protected $params = [];
    protected $joins = [];
    protected $selects = ["*"];
    protected $orderBy = "";
    protected $limit = "";
    protected $offset = "";
    protected $groupBy = "";
    protected $having = "";
    protected static $fetchMode = PDO::FETCH_OBJ;


    public function __construct(PDO $dbconn) {
        $this->db = $dbconn;
    }

    public function table($table) {
        $this->table = $table;
        $this->resetQuery();
        return $this;
    }

    public function select(...$columns) {
        $this->selects = $columns;
        return $this;
    }

    public function distinct() {
        $this->selects[0] = "DISTINCT " . $this->selects[0];
        return $this;
    }

    // public function where($column, $value) {
    //     $this->conditions[] = ["AND", "$column = ?"];
    //     $this->params[] = $value;
    //     return $this;
    // }

    // public function orWhere($column, $value) {
    //     $this->conditions[] = ["OR", "$column = ?"];
    //     $this->params[] = $value;
    //     return $this;
    // }

    public function where($column, $value = null) {
        if (is_array($column)) {
            // Accept associative array
            foreach ($column as $col => $val) {
                $this->conditions[] = ["AND", "$col = ?"];
                $this->params[] = $val;
            }
        } else {
            // Backward compatible
            $this->conditions[] = ["AND", "$column = ?"];
            $this->params[] = $value;
        }
        return $this;
    }

    public function orWhere($column, $value = null) {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->conditions[] = ["OR", "$col = ?"];
                $this->params[] = $val;
            }
        } else {
            $this->conditions[] = ["OR", "$column = ?"];
            $this->params[] = $value;
        }
        return $this;
    }


    public function whereIn($column, array $values) {
        $placeholders = implode(", ", array_fill(0, count($values), "?"));
        $this->conditions[] = ["AND", "$column IN ($placeholders)"];
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereNotIn($column, array $values) {
        $placeholders = implode(", ", array_fill(0, count($values), "?"));
        $this->conditions[] = ["AND", "$column NOT IN ($placeholders)"];
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereNull($column) {
        $this->conditions[] = ["AND", "$column IS NULL"];
        return $this;
    }

    public function whereNotNull($column) {
        $this->conditions[] = ["AND", "$column IS NOT NULL"];
        return $this;
    }

    public function whereBetween($column, array $values) {
        $this->conditions[] = ["AND", "$column BETWEEN ? AND ?"];
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereNotBetween($column, array $values) {
        $this->conditions[] = ["AND", "$column NOT BETWEEN ? AND ?"];
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereRaw($sql, array $params = []) {
        $this->conditions[] = ["AND", "($sql)"];
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function orWhereRaw($sql, array $params = []) {
        $this->conditions[] = ["OR", "($sql)"];
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function join($table, $first, $operator, $second, $type = "INNER") {
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    public function leftJoin($table, $first, $operator, $second) {
        return $this->join($table, $first, $operator, $second, "LEFT");
    }

    public function orderBy($column, $direction = "ASC") {
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }

    /**
     * Full ORDER BY clause without additional keywords (e.g. "a.created_at DESC, a.id DESC").
     */
    public function orderByRaw(string $sql) {
        $this->orderBy = "ORDER BY " . $sql;
        return $this;
    }

    public function groupBy(...$columns) {
        $this->groupBy = "GROUP BY " . implode(", ", $columns);
        return $this;
    }

    public function having($column, $operator, $value) {
        $this->having = "HAVING $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function limit($limit) {
        $this->limit = "LIMIT $limit";
        return $this;
    }

    public function offset($offset) {
        $this->offset = "OFFSET $offset";
        return $this;
    }

    public function get() {
        $sql = "SELECT " . implode(", ", $this->selects) . " FROM {$this->table}";
        if ($this->joins) {
            $sql .= " " . implode(" ", $this->joins);
        }
        if ($this->conditions) {
            $sql .= " WHERE " . $this->compileConditions();
        }
        if ($this->groupBy) $sql .= " " . $this->groupBy;
        if ($this->having) $sql .= " " . $this->having;
        if ($this->orderBy) $sql .= " " . $this->orderBy;
        if ($this->limit) $sql .= " " . $this->limit;
        if ($this->offset) $sql .= " " . $this->offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params);
        // $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        $this->resetQuery();
        return $result;
    }

    public function first() {
        $this->limit(1);
        $rows = $this->get();
        return $rows ? $rows[0] : null;
    }

    public function insert(array $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute(array_values($data));

        // Return true on success (or lastInsertId if available)
        if ($success) {
            $id = $this->db->lastInsertId();
            return $id ? $id : true; // true if no auto-increment ID
        }

        return false;
    }

    // public function update(array $data) {
    //     if (empty($this->conditions)) {
    //         throw new Exception("Update requires a WHERE condition.");
    //     }

    //     $set = [];
    //     foreach ($data as $col => $val) {
    //         $set[] = "$col = ?";
    //         $this->params[] = $val;
    //     }

    //     $sql = "UPDATE {$this->table} SET " . implode(", ", $set);
    //     if ($this->conditions) {
    //         $sql .= " WHERE " . $this->compileConditions();
    //     }

    //     $stmt = $this->db->prepare($sql);
    //     $success = $stmt->execute($this->params);

    //     $this->resetQuery();
    //     return $success;
    // }

    public function update(array $data)
    {
        if (empty($this->conditions)) {
            throw new Exception("Update requires a WHERE condition.");
        }

        // Separate set parameters
        $set = [];
        $setParams = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = ?";
            $setParams[] = $val;
        }

        // Build SQL
        $sql = "UPDATE {$this->table} SET " . implode(", ", $set);
        if ($this->conditions) {
            $sql .= " WHERE " . $this->compileConditions();
        }

        $stmt = $this->db->prepare($sql);

        // Merge SET values first, then WHERE values
        $allParams = array_merge($setParams, $this->params);
        $success = $stmt->execute($allParams);

        $this->resetQuery();

        // Return number of affected rows instead of just true
        return $stmt->rowCount();
    }




    public function delete() {
        if (empty($this->conditions)) {
            throw new Exception("Delete requires a WHERE condition.");
        }

        $sql = "DELETE FROM {$this->table} WHERE " . $this->compileConditions();

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($this->params);

        $this->resetQuery();
        return $success;
    }

    public function count() {
        return $this->aggregate("COUNT", "*");
    }

    public function sum($column) {
        return $this->aggregate("SUM", $column);
    }

    public function avg($column) {
        return $this->aggregate("AVG", $column);
    }

    public function min($column) {
        return $this->aggregate("MIN", $column);
    }

    public function max($column) {
        return $this->aggregate("MAX", $column);
    }

    public function exists() {
        $this->selects = ["1"];
        $this->limit(1);
        $rows = $this->get();
        return !empty($rows);
    }

    public function pluck($column) {
        $this->selects = [$column];
        $rows = $this->get();
        return array_column($rows, $column);
    }

    protected function aggregate($function, $column) {
        $sql = "SELECT $function($column) as aggregate FROM {$this->table}";
        if ($this->conditions) {
            $sql .= " WHERE " . $this->compileConditions();
        }
        if ($this->groupBy) $sql .= " " . $this->groupBy;
        if ($this->having) $sql .= " " . $this->having;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->resetQuery();
        return $result['aggregate'] ?? null;
    }

    protected function compileConditions() {
        $sql = "";
        foreach ($this->conditions as $index => [$type, $clause]) {
            $sql .= ($index === 0 ? $clause : " $type $clause");
        }
        return $sql;
    }
    
    protected function resetQuery() {
        $this->conditions = [];
        $this->params = [];
        $this->joins = [];
        $this->selects = ["*"];
        $this->orderBy = "";
        $this->limit = "";
        $this->offset = "";
        $this->groupBy = "";
        $this->having = "";
    }
}
