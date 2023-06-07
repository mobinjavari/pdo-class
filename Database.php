<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

class Database
{
    /**
     * @var string
     */
    private string $Host = 'localhost';

    /**
     * @var string
     */
    private string $Username;

    /**
     * @var string
     */
    private string $Password;

    /**
     * @var string
     */
    private string $Name;

    /**
     * @var mixed
     */
    private array $Connection;

    /**
     * @param string $username
     * @param string $password
     * @param string $name
     */
    public function __construct(string $username, string $password, string $name)
    {
        $this->Username = $username;

        $this->Password = $password;

        $this->Name = $name;

        try {
            $dsn = "mysql:host=$this->Host;dbname=$this->Name;charset=utf8";
            $pdo = new PDO($dsn, $this->Username, $this->Password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->Connection = [
                'status' => true,
                'message' => null,
                'result' => $pdo
            ];
        } catch (PDOException $e) {
            $this->Connection = [
                'status' => true,
                'message' => "Database connection failed: {$e->getMessage()}",
                'result' => null
            ];
        }
    }

    /**
     * @param string $sql
     * @param array $data
     * @return array
     */
    private function SafeQuery(string $sql, array $data): array #|PDOStatement
    {
        if ($this->Connection['status']) {
            try {
                $statement = $this->Connection['result'];
                $statement = $statement->prepare($sql);
                $statement->execute($data);
                return [
                    'status' => true,
                    'message' => null,
                    'result' => $statement
                ];
            } catch (PDOException $e) {
                return [
                    'status' => false,
                    'message' => "Query execution failed: {$e->getMessage()}",
                    'result' => null
                ];
            }
        }

        return $this->Connection;
    }

    /**
     * @param string $sql
     * @param array $data
     * @return array
     * @info <<SELECT, INSERT, UPDATE, DELETE>>
     */
    public function Query(string $sql, array $data = []): array
    {
        $select = $this->SafeQuery($sql, $data);

        if ($select['status']) {
            try {
                $select = $select['result'];
                return [
                    'status' => true,
                    'message' => null,
                    'result' => [
                        'fetch' => $select->fetchAll(PDO::FETCH_ASSOC)
                    ]
                ];
            } catch (PDOException $e) {
                return [
                    'status' => false,
                    'message' => "Query execution failed: {$e->getMessage()}",
                    'result' => null
                ];
            }
        }

        return $select;
    }

    /**
     * @param string $sql
     * @param array $data
     * @return array
     * @info <<UPDATE, DELETE>>
     */
    public function Modify(string $sql, array $data = []): array
    {
        $update = $this->SafeQuery($sql, $data);

        if ($update['status']) {
            try {
                $update = $update['result'];
                return [
                    'status' => true,
                    'message' => null,
                    'result' => [
                        'count' => $update->rowCount()
                    ]
                ];
            } catch (PDOException $e) {
                return [
                    'status' => false,
                    'message' => "Query execution failed: {$e->getMessage()}",
                    'result' => null
                ];
            }
        }

        return $update;
    }

    /**
     * @param string $sql
     * @param array $data
     * @return array
     * @info <<INSERT>>
     */
    public function Insert(string $sql, array $data = []): array
    {
        $insert = $this->SafeQuery($sql, $data);

        if ($insert['status']) {
            try {
                return [
                    'status' => true,
                    'message' => null,
                    'result' => $this->Connection['result']->lastInsertId()
                ];
            } catch (PDOException $e) {
                return [
                    'status' => false,
                    'message' => "Query execution failed: {$e->getMessage()}",
                    'result' => null
                ];
            }
        }

        return $insert;
    }

    /**
     * @return void
     */
    public function Close(): void
    {
        $this->Connection = [];
    }
}