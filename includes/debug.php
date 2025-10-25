<?php
// includes/debug.php - SQL Query Debugger

// Include the Database class
require_once __DIR__ . '/../config/database.php';

class QueryDebugger {
    private static $queries = [];
    private static $enabled = false;
    
    public static function enable() {
        self::$enabled = true;
    }
    
    public static function disable() {
        self::$enabled = false;
    }
    
    public static function addQuery($query, $params = [], $executionTime = 0) {
        if (self::$enabled) {
            self::$queries[] = [
                'query' => $query,
                'params' => $params,
                'execution_time' => $executionTime,
                'timestamp' => microtime(true)
            ];
        }
    }
    
    public static function getQueries() {
        return self::$queries;
    }
    
    public static function isEnabled() {
        return self::$enabled;
    }
    
    public static function clear() {
        self::$queries = [];
    }
    
    public static function renderDebugPanel() {
        if (!self::$enabled || empty(self::$queries)) {
            return '';
        }
        
        $html = '<div id="queryDebugger" class="query-debugger">';
        $html .= '<div class="debug-header">';
        $html .= '<h6><i class="fas fa-database me-2"></i>SQL Query Debugger</h6>';
        $html .= '<button type="button" class="btn-close" onclick="toggleDebugger()"></button>';
        $html .= '</div>';
        $html .= '<div class="debug-content">';
        
        foreach (self::$queries as $index => $queryData) {
            $html .= '<div class="query-item">';
            $html .= '<div class="query-header">';
            $html .= '<span class="query-number">Query #' . ($index + 1) . '</span>';
            $html .= '<span class="execution-time">' . number_format($queryData['execution_time'] * 1000, 2) . 'ms</span>';
            $html .= '</div>';
            $html .= '<div class="query-sql">';
            $html .= '<pre>' . htmlspecialchars($queryData['query']) . '</pre>';
            $html .= '</div>';
            
            if (!empty($queryData['params'])) {
                $html .= '<div class="query-params">';
                $html .= '<strong>Parameters:</strong><br>';
                foreach ($queryData['params'] as $param => $value) {
                    $html .= '<code>' . htmlspecialchars($param) . '</code> = <code>' . htmlspecialchars($value) . '</code><br>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

// Enhanced Database class with query logging
class DebugDatabase extends Database {
    public function prepare($query) {
        if (QueryDebugger::isEnabled()) {
            $startTime = microtime(true);
            $stmt = parent::prepare($query);
            $endTime = microtime(true);
            
            // Store the query for debugging
            QueryDebugger::addQuery($query, [], $endTime - $startTime);
            
            return new DebugStatement($stmt, $query);
        }
        return parent::prepare($query);
    }
}

class DebugStatement {
    private $stmt;
    private $query;
    
    public function __construct($stmt, $query) {
        $this->stmt = $stmt;
        $this->query = $query;
    }
    
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR) {
        return $this->stmt->bindParam($parameter, $variable, $data_type);
    }
    
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR) {
        return $this->stmt->bindValue($parameter, $value, $data_type);
    }
    
    public function execute($input_parameters = null) {
        $startTime = microtime(true);
        $result = $this->stmt->execute($input_parameters);
        $endTime = microtime(true);
        
        if (QueryDebugger::isEnabled()) {
            $params = [];
            if ($input_parameters) {
                $params = $input_parameters;
            }
            QueryDebugger::addQuery($this->query, $params, $endTime - $startTime);
        }
        
        return $result;
    }
    
    public function fetch($fetch_style = PDO::FETCH_ASSOC) {
        return $this->stmt->fetch($fetch_style);
    }
    
    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        return $this->stmt->fetchAll($fetch_style);
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function __call($method, $args) {
        return call_user_func_array([$this->stmt, $method], $args);
    }
}
?>
