<?php
namespace carry0987\Helper;

use carry0987\RememberMe\RememberMe;
use carry0987\RememberMe\Interfaces\TokenRepositoryInterface;
use carry0987\RememberMe\Interfaces\CookieHandlerInterface;
use PDO;

class RememberMeHelper extends Helper implements TokenRepositoryInterface, CookieHandlerInterface
{
    private static $path = '/';
    private $pdo;
    private RememberMe $rememberMe;

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }

        if (empty($this->rememberMe)) {
            $this->rememberMe = new RememberMe($this, $this);
        }
    }

    public static function clearAuthCookie(): bool
    {
        if (isset($_COOKIE['random_pw'])) {
            return self::setAuthCookie('random_pw', 'none', 0, self::$path);
        }

        return false;
    }

    public static function setAuthCookie(string $name, string $value, int $expire): bool
    {
        $domain = (string) null;

        return setcookie($name, $value, $expire, self::$path, $domain, true, true);
    }

    public function setPath(string $path)
    {
        self::$path = $path;

        return $this;
    }

    public function setConnection(PDO $connectDB)
    {
        $this->pdo = $connectDB;

        return $this;
    }

    public function getTokenByUserID(int $userID, string $selector)
    {
        $stmt = $this->pdo->prepare('SELECT pw_hash, expiry_date FROM remember_me WHERE user_id = :user_id AND selector_hash = :selector');
        $stmt->bindParam(':user_id', $userID, PDO::PARAM_INT);
        $stmt->bindParam(':selector', $selector, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function invalidateToken(string $selector): bool
    {
        $stmt = $this->pdo->prepare('UPDATE remember_me SET pw_hash = :pw_hash, expiry_date = 0 WHERE selector_hash = :selector');
        $stmt->bindValue(':pw_hash', '', PDO::PARAM_STR);
        $stmt->bindParam(':selector', $selector, PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function getUserInfo(int $userID)
    {
        $stmt = $this->pdo->prepare('SELECT username FROM user WHERE uid = :user_id');
        $stmt->bindParam(':user_id', $userID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByName(string $username)
    {
        $results = array();
        $query = $this->pdo->prepare('SELECT uid, password FROM user WHERE username = ?');
        try {
            $query->execute([$username]);
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            return (!empty($results)) ? $results : false;
        } catch (\PDOException $e) {
            self::throwDBError($e->getMessage(), $e->getCode());
        }
    }

    public function updateToken(int $userID, string $selector, string $tokenHash)
    {
        $query = $this->pdo->prepare('UPDATE remember_me SET pw_hash = ?, expiry_date = ? WHERE user_id = ? AND selector_hash = ?');
        $getTime = time() + (30 * 24 * 60 * 60);
        try {
            $query->execute([$tokenHash, $getTime, $userID, $selector]);
            return true;
        } catch (\PDOException $e) {
            self::throwDBError($e->getMessage(), $e->getCode());
        }
    }

    public function insertToken(int $userID, string $selector, string $tokenHash, int $expiryDate = 0)
    {
        $query = $this->pdo->prepare('INSERT INTO remember_me (user_id, selector_hash, pw_hash, expiry_date) VALUES (?, ?, ?, ?)');
        try {
            $query->execute([$userID, $selector, $tokenHash, $expiryDate]);
            return true;
        } catch (\PDOException $e) {
            self::throwDBError($e->getMessage(), $e->getCode());
        }
    }

    public function getToken(int $length): string
    {
        return $this->rememberMe->getToken($length);
    }

    public function verifyToken(int $userID, string $selector, string $randomPW): array
    {
        return $this->rememberMe->verifyToken($userID, $selector, $randomPW);
    }

    private static function throwDBError(string $message, int $code)
    {
        $error = '<h1>Service unavailable</h1>'."\n";
        $error .= '<h2>Error Info :'.$message.'</h2>'."\n";
        $error .= '<h3>Error Code :'.$code.'</h3>'."\n";

        throw new \PDOException($error);
    }
}
