<?php

class  TaskGateway {

    private PDO $conn;

    public function __construct(Database $database){

        $this->conn = $database->getConnection();

    }

    public function getAllTaskForUser(int $user_id) : array {

        $sql = 'SELECT t.*, c.name AS category_name FROM task t
                LEFT JOIN task_category tc ON t.id = tc.task_id
                LEFT JOIN category c ON tc.category_id = c.id
                WHERE t.user_id = :user_id 
                ORDER BY t.end_date ASC, t.start_date ASC, t.name ASC';
    
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function getTaskByIdForUser(string $id, int $user_id) : array | false {
        
        $sql = 'SELECT t.*, c.name AS category_name FROM task t
                LEFT JOIN task_category tc ON t.id = tc.task_id
                LEFT JOIN category c ON tc.category_id = c.id
                WHERE t.id = :id AND t.user_id = :user_id';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convert 'is_completed' to boolean if data is retrieved.
        if ($data !== false) {
            $data['is_completed'] = (bool) $data['is_completed'];
        }

        return $data;
    }


    public function createTaskForUser(array $data, int $user_id): string {

        $categoryId = isset($data['category']) ? $this->getCategoryIdByName($data['category']) : null;

        $sql = "INSERT INTO task (name, is_completed, start_date, end_date, created_at, user_id) 
                VALUES (:name, :is_completed, :start_date, :end_date, :created_at, :user_id)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":name", $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':is_completed', $data['is_completed'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(":start_date", $data['start_date'], PDO::PARAM_STR);
        $stmt->bindValue(":end_date", $data['end_date'], PDO::PARAM_STR);
        $stmt->bindValue(":created_at", date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();

        $taskId = $this->conn->lastInsertId();

        if ($categoryId !== null) {
            $this->linkTaskToCategory($taskId, $categoryId);
        }

        return $taskId;
    }


    private function getCategoryIdByName(string $categoryName): int {

        $sql = "SELECT id FROM category WHERE name = :name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":name", $categoryName, PDO::PARAM_STR);
        $stmt->execute();

        $categoryId = $stmt->fetch(PDO::FETCH_COLUMN);

        if ($categoryId === false) {

            $sql = "INSERT INTO category (name) VALUES (:name)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":name", $categoryName, PDO::PARAM_STR);
            $stmt->execute();

            $categoryId = $this->conn->lastInsertId();
        }

        return (int) $categoryId;
    }


    private function linkTaskToCategory(int $taskId, int $categoryId) {

        $sql = "INSERT INTO task_category (task_id, category_id) VALUES (:task_id, :category_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":task_id", $taskId, PDO::PARAM_INT);
        $stmt->bindValue(":category_id", $categoryId, PDO::PARAM_INT);
        $stmt->execute();

    }


    public function updateTaskForUser(int $user_id, int $id, array $data): int {

        $fields = [];
    
        if (!empty($data['name'])) {
            $fields['name'] = [$data['name'], PDO::PARAM_STR];
        }
    
        if (array_key_exists('start_date', $data)) {
            $fields['start_date'] = [$data['start_date'], $data['start_date'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR];
        }
    
        if (array_key_exists('end_date', $data)) {
            $fields['end_date'] = [$data['end_date'], $data['end_date'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR];
        }
    
        if (array_key_exists('is_completed', $data)) {
            $fields['is_completed'] = [$data['is_completed'], PDO::PARAM_BOOL];
        }
    
        // If no fields are provided, return 0 (nothing to update)
        if (empty($fields)) {
            return 0;
        }
    
        // Map fields to SQL placeholders (e.g., "name = :name")
        $sets = array_map(function ($field) {
            return "$field = :$field";
        }, array_keys($fields));
    
        // Construct the SQL query
        $sql = "UPDATE task SET " . implode(", ", $sets) . " WHERE id = :id AND user_id = :user_id";
    
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    
        // Bind each field to its value and type
        foreach ($fields as $key => $values) {
            $stmt->bindValue(":$key", $values[0], $values[1]);
        }
    
        $stmt->execute();
    
        return $stmt->rowCount();
    }
    

    public function deleteTaskForUser(int $user_id, string $id): int {

        $sql = "DELETE FROM task WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }
    
}

?>
