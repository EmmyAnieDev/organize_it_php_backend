<?php

class RefreshTokenGateway {

    private PDO $conn;

    public function __construct(Database $database, private string $secret_key) {

        $this->conn = $database->getConnection();

    }

    public function addRefreshTokenToDatabase(string $token, int $expiry) {

        $hash = hash_hmac("sha256", $token, $this->secret_key);

        $sql = "INSERT INTO refresh_token (token_hash, expires_at) VALUES (:token_hash, :expires_at)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":token_hash", $hash, PDO::PARAM_STR);
        $stmt->bindValue(":expires_at", $expiry, PDO::PARAM_STR);

        return $stmt->execute();

    }

    public function deleteRefreshTokenFromDatabase(string $token) : int {

        $hash = hash_hmac("sha256", $token, $this->secret_key);
    
        $sql = "DELETE FROM refresh_token WHERE token_hash = :token_hash";
    
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue("token_hash", $hash, PDO::PARAM_STR);
    
        $stmt->execute();
    
        return $stmt->rowCount();
    }

    public function getByToken(string $token) : array | false {

        $hash = hash_hmac("sha256", $token, $this->secret_key);

        $sql =  "SELECT * FROM refresh_token WHERE token_hash= :token_hash";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":token_hash", $hash, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteExpiredTokenFromDatabase() : int {

        $sql = "DELETE FROM refresh_token WHERE expires_at < UNIX_TIMESTAMP()";

        $stmt = $this->conn->query($sql);

        return $stmt->rowCount();

    }
    
}