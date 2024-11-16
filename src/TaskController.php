<?php

/*
 * The TaskController class handles task-related HTTP requests.
 * It decides what action to take based on the HTTP request method
 * and whether an 'id' is provided.
 * Associates tasks with a specific user based on their user ID.
 */
class TaskController {

    public function __construct(private TaskGateway $taskGateway, private int $user_id){}

    public function processRequest(string $request_method, ?string $id) : void {


        if ($id === null) {

       
            if ($request_method === 'GET') {

                echo json_encode($this->taskGateway->getAllTaskForUser($this->user_id));
      
            } elseif ($request_method === 'POST') {

                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data);

                if ( !empty($errors)) {

                    $this->respondUnprocessedEntity($errors);
                    return;

                }

                $id = $this->taskGateway->createTaskForUser($data, $this->user_id);

                $this->respondCreated($id);

            }else{
               $this->respondMethodNotAllowed('GET, POST');
            }

        } else {

            // store what is returned (array or false) in the $task variable
            $task = $this->taskGateway->getTaskByIdForUser($id, $this->user_id);

            if ($task === false) {

                $this->respondNotFound($id);
                return;

            }
         
            switch ($request_method) {

                case "GET":

                    echo json_encode($task);
                    break;

                case "PUT":
                    
                    $data = (array) json_decode(file_get_contents("php://input"), true);

                    $errors = $this->getValidationErrors($data, false);
    
                    if ( !empty($errors)) {
    
                        $this->respondUnprocessedEntity($errors);
                        return;
    
                    }

                    $rows = $this->taskGateway->updateTaskForUser($this->user_id, $id, $data);
                    echo json_encode(["message" => "Task Updated", "rows" => $rows]);
                    break;

                case "DELETE":

                    $rows = $this->taskGateway->deleteTaskForUser($this->user_id, $id);
                    echo json_encode(["message" => "Task Deleted", "rows" => $rows]);
                    break;

                default:
                    $this->respondMethodNotAllowed('GET, PUT, DELETE');
                    break;
            }
        }
    }

    private function respondMethodNotAllowed(string $allowed_methods) : void {

        http_response_code(405);
        header("Allow: $allowed_methods");

    }

    private function respondNotFound(string $id) : void {

        http_response_code(404);
        echo json_encode(["message" => "Task with ID $id not found for the specified user!"]);

    }

    private function respondCreated(string $id) : void {

        http_response_code(201);
        echo json_encode(["message" => "Task created.", "id" => $id]);

    }

    private function respondUnprocessedEntity(array $errors) : void {

        http_response_code(422);
        echo json_encode(["errors" => $errors]);

    }

    // The $is_new parameter defaults to true, differentiating validation rules for POST (new) and PATCH (update) requests
    public function getValidationErrors(array $data, bool $is_new = true): array {

        $errors = [];

        if ($is_new && empty($data['name'])) {

            $errors[] = 'name is required';
            
        }

        if (!empty($data['status'])) {

            if (filter_var($data['status'], FILTER_VALIDATE_INT) === false){

                $errors[] = 'status must be an integer';
            }
        }
        
        if (!empty($data['start_date'])) {
            $startDate = DateTime::createFromFormat('Y-m-d', $data['start_date']);
            if (!$startDate || $startDate->format('Y-m-d') !== $data['start_date']) {
                $errors[] = 'start_date must be a valid date in the format YYYY-MM-DD';
            }
        }
        
        if (!empty($data['end_priority'])) {
            $endPriority = DateTime::createFromFormat('Y-m-d', $data['end_priority']);
            if (!$endPriority || $endPriority->format('Y-m-d') !== $data['end_priority']) {
                $errors[] = 'end_priority must be a valid date in the format YYYY-MM-DD';
            }
        }
        

        return $errors;
    }
}

