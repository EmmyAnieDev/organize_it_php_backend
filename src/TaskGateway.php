<?php

class  TaskGateway {

    private PDO $conn;

    public function __construct(Database $database){

        $this->conn = $database->getConnection();

    }

    public function getAllTaskForUser(int $user_id) : array {

        $sql = 'SELECT * FROM task WHERE user_id = :user_id ORDER BY name' ;

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getTaskByIdForUser(string $id, int $user_id) : array | false {

        $sql = 'SELECT * FROM task WHERE id = :id AND user_id = :user_id' ;

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

        $sql = "INSERT INTO task (name, is_completed, start_date, end_date, created_at, user_id) 
                VALUES (:name, :is_completed, :start_date, :end_date, :created_at, :user_id)";
    
        $stmt = $this->conn->prepare($sql);
    

        $stmt->bindValue(":name", $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':is_completed', $data['is_completed'] ?? false, PDO::PARAM_BOOL);
    

        if (empty($data['start_date'])) {
            $stmt->bindValue(":start_date", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":start_date", $data['start_date'], PDO::PARAM_STR);
        }
    
        if (empty($data['end_date'])) {
            $stmt->bindValue(":end_date", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":end_date", $data['end_date'], PDO::PARAM_STR);
        }
    
        $stmt->bindValue(":created_at", date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    
        $stmt->execute();
    
        return $this->conn->lastInsertId();
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
