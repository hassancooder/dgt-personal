<?php
// ======== ENV Loader ========
function loadEnv(string $path): bool
{
    if (!file_exists($path)) return false;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, "\"' ");
        } else {
            $name = trim($line);
            $value = '';
        }

        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
    return true;
}
// ======== Database Class ========
class DB
{
    private static ?DB $instance = null;
    private static string $status = 'disconnected';
    public static PDO $pdo;

    private static $requests = []; // ✨ cache array

    // New mysqli static connection instance
    public static ?mysqli $conn = null;

    private function __construct()
    {
        $host    = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname  = $_ENV['DB_NAME'] ?? '';
        $user    = $_ENV['DB_USER'] ?? '';
        $pass    = $_ENV['DB_PASS'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            self::$status = 'connected';

            // Initialize mysqli connection as well
            self::$conn = new mysqli($host, $user, $pass, $dbname);
            if (self::$conn->connect_error) {
                throw new Exception("MySQLi Connection failed: " . self::$conn->connect_error);
            }
            self::$conn->set_charset($charset);
        } catch (PDOException $e) {
            self::$status = 'error';
            logError("DB ERROR: " . $e->getMessage(), 'db');
            showErrorPage(500, "Database Error", $e->getMessage());
        }
    }

    public static function con(): DB
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public static function status(): string
    {
        return self::$status;
    }

    public static function setupCheck(): void
    {
        // Ensure connection is established
        self::con();

        try {
            $requiredTables = [
                'users' => "SELECT * FROM users WHERE role = 'superadmin' LIMIT 1",
                'navbar' => "SELECT COUNT(*) FROM navbar",
                'static_types' => "SELECT COUNT(*) FROM static_types"
            ];

            foreach ($requiredTables as $table => $checkQuery) {
                // Step 1: Check table existence
                $sql = "
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = :dbname 
                AND table_name = :tablename
            ";
                $stmt = self::$pdo->prepare($sql);
                $stmt->execute([
                    'dbname' => $_ENV['DB_NAME'],
                    'tablename' => $table
                ]);
                $exists = $stmt->fetchColumn();

                if (!$exists) {
                    showSetupPage("Required table '{$table}' not found. Please run the setup.");
                }

                // Step 2: Check if data exists
                $stmt2 = self::$pdo->query($checkQuery);
                $result = $stmt2->fetchColumn();

                if (!$result) {
                    showSetupPage("Table '{$table}' is empty or missing required data.");
                }
            }
        } catch (Exception $e) {
            logError("SETUP CHECK ERROR: " . $e->getMessage(), 'db');
            showErrorPage(500, 'Setup Check Error', $e->getMessage());
        }
    }


    public static function insert(string $table, array $data): bool
    {
        // Ensure connection is established
        self::con();

        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public static function update(string $table, array $data, array $where): bool
    {
        // Ensure connection is established
        self::con();

        $setClause = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($k) => "$k = :w_$k", array_keys($where)));

        $params = array_merge(
            $data,
            array_combine(array_map(fn($k) => "w_$k", array_keys($where)), array_values($where))
        );

        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(string $table, array $where): bool
    {
        // Ensure connection is established
        self::con();

        $whereClause = implode(' AND ', array_map(fn($k) => "$k = :$k", array_keys($where)));
        $sql = "DELETE FROM $table WHERE $whereClause";
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute($where);
    }

    public static function fetch(string $table, array $where = [], string $fetchType = 'all', string $customSql = '')
    {
        self::con();

        $whereClause = '';
        $params = [];

        if ($where) {
            $whereClause = 'WHERE ' . implode(' AND ', array_map(fn($k) => "$k = :$k", array_keys($where)));
            $params = $where;
        }

        $sql = "SELECT * FROM $table $whereClause $customSql";
        $hash = self::generateQueryHash($sql, $params);

        // 🔁 Cache check
        if (isset(self::$requests[$hash])) {
            $result = self::$requests[$hash];

            if ($fetchType === 'one') {
                if (is_array($result)) $result['_source'] = 'cache';
            } else {
                foreach ($result as &$row) {
                    $row['_source'] = 'cache';
                }
            }

            return $result;
        }

        // 🧠 DB query
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);

        $result = self::fetchResult($stmt, $fetchType === 'one' ? 'one' : 'all');

        // Add source markers
        if ($fetchType === 'one') {
            if (is_array($result)) $result['_source'] = 'db';
        } else {
            foreach ($result as &$row) {
                $row['_source'] = 'db';
            }
        }

        self::$requests[$hash] = $result;
        return $result;
    }

    public static function query($sql, $params = [], $fetch = 'all')
    {
        $hash = self::generateQueryHash($sql, $params);

        if (isset(self::$requests[$hash])) {
            $result = self::$requests[$hash];

            if ($fetch === 'one') {
                if (is_array($result)) $result['_source'] = 'cache';
            } else {
                foreach ($result as &$row) {
                    $row['_source'] = 'cache';
                }
            }

            return $result;
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);

        $result = self::fetchResult($stmt, $fetch === 'one' ? 'one' : 'all');

        if ($fetch === 'one') {
            if (is_array($result)) $result['_source'] = 'db';
        } else {
            foreach ($result as &$row) {
                $row['_source'] = 'db';
            }
        }

        self::$requests[$hash] = $result;

        return $result;
    }


    public static function generateQueryHash($sql, $params)
    {
        return md5($sql . serialize($params));
    }
    public static function fetchResult(PDOStatement $stmt, string $type)
    {
        // No need for self::con() here, as this method is called after query/fetch
        return match ($type) {
            'one'    => $stmt->fetch(),
            'obj'    => $stmt->fetch(PDO::FETCH_OBJ),
            'column' => $stmt->fetchColumn(),
            default  => $stmt->fetchAll(),
        };
    }

    public static function mysqliLastInsertId(): int
    {
        // Ensure connection is established
        self::con();

        if (self::$conn) {
            return self::$conn->insert_id;
        }
        return 0;
    }

    // You can keep existing PDO lastInsertId method if needed
    public static function pdoLastInsertId(): string
    {
        // Ensure connection is established
        self::con();

        return self::$pdo->lastInsertId();
    }

    public static function raw(): PDO
    {
        // Ensure connection is established
        self::con();

        return self::$pdo;
    }
    public static function loadEntries($sql, $order_by, $filters = [])
    {
        $conn = DB::$conn;
        $EPP = $_SESSION['user']['preferences']['entries_per_page'] ?? 10;
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $offset = ($page - 1) * $EPP;
        $showAll = isset($filters['show_all']) && $filters['show_all'] === '1';
        $export = isset($filters['action']) && $filters['action'] === 'export';
        $limit = !$showAll || !$export ? "LIMIT $offset, " . ($EPP + 1) : '';
        $totalEntries = mysqli_fetch_assoc($conn->query("SELECT COUNT(*) as total FROM ($sql) AS subquery"))['total'];
        $entries = mysqli_fetch_all($conn->query("$sql $order_by $limit"), MYSQLI_ASSOC);
        $hasNext = false;
        if (!$showAll && count($entries) > $EPP) {
            $hasNext = true;
            array_pop($entries);
        }
        $from = $showAll ? 1 : ($totalEntries > 0 ? $offset + 1 : 0);
        $to   = $showAll ? $totalEntries : min($offset + count($entries), $totalEntries);
        $paginationLinks = [];
        if (!$showAll && $totalEntries > 0) {
            $startPage = max(1, $page - 2);
            $endPage   = min($page + 2, ceil($totalEntries / $EPP));
            for ($i = $startPage; $i <= $endPage; $i++) {
                $queryParams = $filters;
                $queryParams['page'] = $i;
                if ($showAll) {
                    $queryParams['show_all'] = '1';
                }
                $paginationLinks[] = [
                    'page'   => $i,
                    'url'    => App::currentPath() . '?' . http_build_query($queryParams),
                    'active' => ($i == $page)
                ];
            }
        }
        $prevURL = null;
        $nextURL = null;
        if (!$showAll) {
            if ($page > 1) {
                $qp = $filters;
                $qp['page'] = $page - 1;
                $prevURL = App::currentPath() . '?' . http_build_query($qp);
            }
            if ($hasNext) {
                $qp = $filters;
                $qp['page'] = $page + 1;
                $nextURL = App::currentPath() . '?' . http_build_query($qp);
            }
        }
        $info = [
            'entries'       => $entries,
            'entries_count' => count($entries),
            'pagination'    => [
                'current_page' => $page,
                'per_page'     => $EPP,
                'prev'         => $prevURL,
                'next'         => $nextURL,
                'links'        => $paginationLinks
            ],
            'page'    => [
                'from'  => $from,
                'to'    => $to,
                'total' => $totalEntries
            ],
            'show_all' => $showAll,
            'filters'  => $filters,
            'toggle_url' => App::currentPath() . '?' . http_build_query($filters)
        ];
        if (!$export) {
            return $info;
        } else {
            $info['sql'] = $sql;
            return App::setupPrintSystem($info);
        }
    }
}

// Directory Class
class Dir
{
    private static ?string $assetBase = null;

    private static function ensureInit(): void
    {
        if (self::$assetBase === null) {
            self::$assetBase = $_ENV['ROOT'] . 'assets';
        }
    }


    /**
     * Get full path to an asset
     */
    public static function getAsset(string $path, bool $checkExists = false): ?string
    {
        self::ensureInit();
        $cleanPath = ltrim($path, '/');
        $fullPath = self::$assetBase . '/' . $cleanPath;

        if ($checkExists) {
            $serverPath = $_SERVER['DOCUMENT_ROOT'] . $fullPath;
            if (!file_exists($serverPath)) {
                return null;
            }
        }

        return $fullPath;
    }

    /**
     * Check if a path exists (file or folder)
     */
    public static function exists(string $relativePath): bool
    {
        $serverPath = self::toAbsolute($relativePath);
        return file_exists($serverPath);
    }

    /**
     * List files in a folder (non-recursive)
     */
    public static function list(string $dir): array
    {
        $path = self::toAbsolute($dir);
        if (!is_dir($path)) return [];

        return array_values(array_diff(scandir($path), ['.', '..']));
    }

    /**
     * Create directory if not exists
     */
    public static function make(string $dirPath, int $permission = 0755): bool
    {
        $path = self::toAbsolute($dirPath);
        if (!is_dir($path)) {
            return mkdir($path, $permission, true);
        }
        return true;
    }

    /**
     * Remove file or folder (recursively if folder)
     */
    public static function remove(string $path): bool
    {
        $fullPath = self::toAbsolute($path);

        if (!file_exists($fullPath)) return false;

        if (is_file($fullPath)) {
            return unlink($fullPath);
        }

        if (is_dir($fullPath)) {
            return self::deleteFolderRecursive($fullPath);
        }

        return false;
    }

    // Convert relative path to absolute server path
    private static function toAbsolute(string $relativePath): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($relativePath, '/');
    }

    // Helper: Recursively delete folder
    private static function deleteFolderRecursive(string $dir): bool
    {
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::deleteFolderRecursive($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }
}

class Doc
{
    private static array $headLinks = [];
    private static array $footerScripts = [];
    private static ?string $title = null; // New: Store page title
    private static ?string $breadCrumbs = null; // New: Store page title

    // Set Page Title
    public static function setTitle(string $pageTitle): void
    {
        self::$title = $pageTitle . ' - ' . $_ENV['APP_DISPLAY_NAME'];
    }
    public static function getBreadCrumb(): string
    {
        return self::$breadCrumbs;
    }
    public static function setTANDBC(array $segments, array $labels): void
    {
        $autoTitle = [];
        $autoBC = ['<a href="' . $_ENV['ROOT'] . '"><i class="ri-home-5-line text-xl"></i></a>'];
        $pathAccumulator = '';
        foreach (array_reverse($segments) as $seg) {
            $seg = ucwords(str_replace('-', ' ', $seg));
            $autoTitle[] = ucfirst($labels[$seg] ?? $seg);
        }
        foreach ($segments as $seg) {
            if (empty($seg)) continue;
            $segT = ucwords(str_replace('-', ' ', $seg));
            $pathAccumulator .= '/' . $seg;
            $label = ucfirst($labels[$seg] ?? $segT);
            $url = $pathAccumulator;
            $autoBC[] = '<a href="' . htmlspecialchars($url) . '" class="hover:text-blue-500">' . htmlspecialchars($label) . '</a>';
        }
        $finalBC = implode(' <i class="ri-arrow-right-s-line mx-1"></i> ', $autoBC);
        $finalTitle = implode(' < ', $autoTitle);
        self::setTitle($finalTitle);
        self::$breadCrumbs = '<div class="p-1 font-medium text-md m-1">' . $finalBC . '</div>';
    }
    // Render Page Title in <head>
    public static function renderTitle(): string
    {
        return htmlspecialchars(self::$title ?? 'Untitled Page');
    }

    // Add CSS link to head (absolute or relative)
    public static function setLink(string $href, bool $isAbsolute = false): void
    {
        if (!$isAbsolute) {
            $href = self::getBasePath() . '/' . ltrim($href, '/');
        }
        self::$headLinks[$href] = '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
    }

    // Add JS script to footer (absolute or relative)
    public static function setScript(string $src, bool $isAbsolute = false): void
    {
        if (!$isAbsolute) {
            $src = self::getBasePath() . '/' . ltrim($src, '/');
        }
        self::$footerScripts[$src] = '<script src="' . htmlspecialchars($src) . '"></script>';
    }

    // Render all CSS links in head
    public static function renderHeadLinks(): string
    {
        return implode("\n", self::$headLinks);
    }

    // Render all JS scripts in footer
    public static function renderFooterScripts(): string
    {
        return implode("\n", self::$footerScripts);
    }

    // Helper function to get base path (like /myproject or /)
    private static function getBasePath(): string
    {
        $relativePath = ltrim(str_replace('\\', '/', dirname(__DIR__)), '/');
        $basePath = str_replace(rtrim($_SERVER['DOCUMENT_ROOT'], '/'), '', $relativePath);
        return '/' . ltrim($basePath, '/'); // ensure it starts with slash
    }
}

class App
{
    public static $defaultTheme = [
        'bgColor' => '#ffffff',
        'accent' => '#2563eb',
        'textPrimary' => '#0f172a',
        'textSecondary' => '#64748b'
    ];
    public static function checkLogin(): void
    {
        if (!isset($_SESSION)) session_start();
        $segment0 = implode('/', SEGMENTS);
        $skipPaths = ["auth/login", "auth/login/action", "auth/forget-password"];
        if (!in_array($segment0, $skipPaths)) {
            if (!isset($_SESSION['user']['id'])) {
                if (!isset($_SESSION['return_url'])) {
                    $_SESSION['return_url'] = $segment0;
                }
                header("Location: " . getRoot() . "auth/login");
                exit;
            }
        }
    }

    public static function currentPath($isRelative = false, $hasAction = false): string
    {
        $segments = SEGMENTS;
        if ($hasAction) {
            array_pop($segments);
        }
        $path = $isRelative ? implode('/', $segments) : getRoot() . implode('/', $segments);
        return $path;
    }
    public static function icon($type, $url): string
    {
        $icon = '';
        switch ($type) {
            case 'delete':
                $icon = '<a href="' . $url . '" onclick="return confirm(\'Are you sure?\')" class="text-red-600 text-lg"><i class="ri-delete-bin-7-line"></i></a>';
                break;
            case 'edit':
                $icon = '<a href="' . $url . '" class="text-lg text-blue-600"><i class="ri-edit-line"></i></a>';
                break;
            default:
                $icon = '<a href="' . $url . '" class="text-lg text-red-600">Icon Not Defined!</a>';
                break;
        }
        return $icon;
    }
    public static function currentUser(): ?array
    {
        if (!isset($_SESSION)) session_start();

        if (!isset($_SESSION['user']['id'])) {
            return null;
        }

        // Optional: Fetch user details from database
        $user = DB::fetch('users', ['id' => $_SESSION['user']['id']], 'one');
        if ($user) {
            // if ($user['role'] === 'superadmin') {
                $user['allowed_routes'] = array_column(DB::con()->fetch('navbar', [], 'all'), 'slug');
            // } else {
            //     $user['allowed_routes'] = jd($user['allowed_routes']);
            // }
            $user['allowed_routes'] = [...$user['allowed_routes'], '', 'settings'];
            $user['preferences'] = jd($user['preferences'] ?? '[]');
            if (!isset($user['preferences']['color_theme'])) {
                $user['preferences']['color_theme'] = self::$defaultTheme;
            }
            return $user;
        } else {
            return null;
        }
    }
    public static function setupPrintSystem($data) {}
}
