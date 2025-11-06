<?php
require_once dirname(__DIR__) . '/database/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = new DBFunctions();

class DBFunctions
{
    private $conn;
    private $sms_api_token = '870|h05YLghELQ8xSwBYKosPFx3w6svYs4EckHpQvsf9 ';
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }


    public function insert($table, $data)
    {
        try {
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
    
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
    
            foreach ($data as $key => &$val) {
                $stmt->bindParam(':' . $key, $val);
            }
    
            $stmt->execute();
    
            return ['status' => 'success', 'message' => 'Inserted successfully'];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function update($table, $data, $conditions = [])
    {
        try {
            $set_clause = implode(", ", array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($data)));

            $condition_clause = implode(" AND ", array_map(function ($key, $value) {
                if (is_string($value) && strpos($value, 'RAW:') === 0) {
                    return substr($value, 4);
                } elseif (is_array($value) && count($value) === 2) {
                    return "$key {$value[0]} :$key";
                }
                return "$key = :$key";
            }, array_keys($conditions), $conditions));

            $sql = "UPDATE $table SET $set_clause WHERE $condition_clause";
            $stmt = $this->conn->prepare($sql);

            foreach ($data as $key => &$val) {
                $stmt->bindParam(':' . $key, $val);
            }

            foreach ($conditions as $key => &$val) {
                if (is_array($val)) {
                    $stmt->bindParam(':' . $key, $val[1]);
                } elseif (strpos($val, 'RAW:') === false) {
                    $stmt->bindParam(':' . $key, $val);
                }
            }

            $stmt->execute();
            return ['status' => 'success', 'message' => 'Updated successfully'];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function delete($table, $conditions)
    {
        try {
            $condition_clause = implode(" AND ", array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($conditions)));

            $sql = "DELETE FROM $table WHERE $condition_clause";
            $stmt = $this->conn->prepare($sql);

            foreach ($conditions as $key => &$val) {
                $stmt->bindParam(':' . $key, $val);
            }

            $stmt->execute();
            return ['status' => 'success', 'message' => 'Deleted successfully'];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

 
    public function select($table, $columns = "*", $conditions = [], $options = "")
{
    try {
        $sql = "SELECT $columns FROM $table";

        if (!empty($conditions)) {
            $condition_clause = implode(" AND ", array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
            $sql .= " WHERE $condition_clause";
        }

        if (!empty($options)) {
            $sql .= " $options";
        }

        $stmt = $this->conn->prepare($sql);

        if (!empty($conditions)) {
            foreach ($conditions as $key => &$val) {
                $stmt->bindParam(':' . $key, $val);
            }
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If no data, still extract column names
        if (empty($rows)) {
            $columnNames = [];
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                if ($meta && isset($meta['name'])) {
                    $columnNames[] = $meta['name'];
                }
            }
            return [
                'status' => 'success',
                'data' => [],
                'columns' => $columnNames
            ];
        }

        return ['status' => 'success', 'data' => $rows];
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}


    public function custom($table, $columns = "*", $conditions = [], $customCondition = "")
    {
        try {
            $sql = "SELECT $columns FROM $table";

            if (!empty($conditions)) {
                $condition_clause = implode(" AND ", array_map(function ($key) {
                    return "$key = :$key";
                }, array_keys($conditions)));

                $sql .= " WHERE $condition_clause";
            }

            if ($customCondition) {
                $sql .= empty($conditions) ? " WHERE $customCondition" : " AND $customCondition";
            }

            $stmt = $this->conn->prepare($sql);

            if (!empty($conditions)) {
                foreach ($conditions as $key => &$val) {
                    $stmt->bindParam(':' . $key, $val);
                }
            }

            $stmt->execute();
            return ['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function count($table_name, $condition = null)
    {
        try {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
                throw new InvalidArgumentException('Invalid table name');
            }

            $sql = "SELECT COUNT(*) AS count FROM `$table_name`";

            if ($condition) {
                $sql .= " WHERE $condition";
            }

            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                throw new RuntimeException('Failed to prepare statement: ' . implode(' ', $this->conn->errorInfo()));
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new RuntimeException('Failed to fetch result: ' . implode(' ', $stmt->errorInfo()));
            }

            return ['status' => 'success', 'data' => $result['count'] ?? 0];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log('Error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function sum($table_name, $column_name, $condition = null)
    {
        try {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name) || !preg_match('/^[a-zA-Z0-9_]+$/', $column_name)) {
                throw new InvalidArgumentException('Invalid table name or column name');
            }

            $sql = "SELECT SUM($column_name) AS total FROM `$table_name`";

            if ($condition) {
                $sql .= " WHERE $condition";
            }

            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                throw new RuntimeException('Failed to prepare statement: ' . implode(' ', $this->conn->errorInfo()));
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return ['status' => 'success', 'data' => $result['total'] ?? 0];
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log('Error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getSMSBalance()
    {
        $url = 'https://app.philsms.com/api/v3/balance';
        $headers = [
            "Authorization: Bearer {$this->sms_api_token}",
            "Content-Type: application/json",
            "Accept: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            return 'Error decoding JSON: ' . json_last_error_msg();
        }

        if (isset($result['status']) && $result['status'] === 'success') {
            $balance = $result['data']['remaining_balance'] ?? 'N/A';
            $expiration = $result['data']['expired_on'] ?? 'N/A';

            return "{$balance}";
        } else {
            return 'Error retrieving balance: ' . ($result['message'] ?? 'Unknown error');
        }
    }

    public function sendemail($to, $message, $subject)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sharmaineeunice07@gmail.com';
            $mail->Password = 'fzyo optp wtjg yryl';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom('pnpsectortacloban@gmail.com', 'Shoppeep');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            return ['status' => 'success', 'message' => 'Email sent successfully.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Mail Error: ' . $e->getMessage()];
        }
    }

    public function sendSMS($mobileNumber, $message)
    {
        $url = 'https://app.philsms.com/api/v3/sms/send';

        $data = [
            'recipient' => $mobileNumber,
            'sender_id' => 'PhilSMS',
            'type' => 'plain',
            'message' => $message
        ];

        $headers = [
            "Authorization: Bearer {$this->sms_api_token}",
            "Content-Type: application/json",
            "Accept: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => 'error', 'message' => $error];
        }

        return json_decode($response, true);
    }

}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api'])) {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit;
    }

    $action = $input['action'];
    $params = $input['params'] ?? [];

    $allowed_methods = [
        'insert', 'update', 'delete', 'select', 'custom', 'count', 'sum',
        'getSMSBalance', 'sendemail', 'sendSMS'
    ];

    if (!in_array($action, $allowed_methods)) {
        echo json_encode(['status' => 'error', 'message' => 'Action not allowed']);
        exit;
    }

    $obj = new DBFunctions();

    try {
        $result = call_user_func_array([$obj, $action], $params);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
