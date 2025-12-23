<?php

class Database
{
    private $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new \PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get PDO instance
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return statement
     *
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    public function query(string $query, array $params = [])
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch one row
     *
     * @param string $query
     * @param array $params
     * @return array|null
     */
    public function fetchOne(string $query, array $params = []): ?array
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetch() ?: null;
    }

    /**
     * Fetch all rows
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert and return last insert ID
     *
     * @param string $query
     * @param array $params
     * @return int
     */
    public function insert(string $query, array $params = []): int
    {
        $this->query($query, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update query
     *
     * @param string $query
     * @param array $params
     * @return int
     */
    public function update(string $query, array $params = []): int
    {
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete query
     *
     * @param string $query
     * @param array $params
     * @return int
     */
    public function delete(string $query, array $params = []): int
    {
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }
}
