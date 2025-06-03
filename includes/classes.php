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

        $whereParts = [];
        $params = [];

        foreach ($where as $column => $value) {
            if (is_array($value)) {
                // build an IN clause for multiple values
                $placeholders = [];
                foreach ($value as $i => $v) {
                    $paramKey = "{$column}_{$i}";
                    $placeholders[] = ":$paramKey";
                    $params[$paramKey] = $v;
                }
                $inList = implode(', ', $placeholders);
                $whereParts[] = "$column IN ($inList)";
            } else {
                // single-value equality
                $whereParts[] = "$column = :$column";
                $params[$column] = $value;
            }
        }

        $whereClause = '';
        if (!empty($whereParts)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }

        $sql = "SELECT * FROM $table $whereClause $customSql";
        $hash = self::generateQueryHash($sql, $params);

        // Check cache
        if (isset(self::$requests[$hash])) {
            $result = self::$requests[$hash];

            if ($fetchType === 'one') {
                if (is_array($result)) {
                    $result['_source'] = 'cache';
                }
            } else {
                foreach ($result as &$row) {
                    $row['_source'] = 'cache';
                }
            }

            return $result;
        }

        // Execute query
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);

        $result = self::fetchResult($stmt, $fetchType === 'one' ? 'one' : 'all');

        // Mark source as 'db'
        if ($fetchType === 'one') {
            if (is_array($result)) {
                $result['_source'] = 'db';
            }
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
        $export  = isset($filters['action'])   && $filters['action'] === 'export';

        // Apply LIMIT only when NOT show_all AND NOT export
        $limit = (!$showAll && !$export)
            ? "LIMIT $offset, " . ($EPP + 1)  // +1 to detect if there's a next page
            : '';

        // Total count (independent of LIMIT)
        $totalEntries = mysqli_fetch_assoc(
            $conn->query("SELECT COUNT(*) as total FROM ($sql) AS subquery")
        )['total'];

        // Fetch rows
        $ymsql = "$sql $order_by $limit";
        $entries = mysqli_fetch_all($conn->query($ymsql), MYSQLI_ASSOC);

        // Pagination logic
        $hasNext = false;
        if (!$showAll && !$export && count($entries) > $EPP) {
            $hasNext = true;
            // Remove the extra one we fetched
            $entries = array_slice($entries, 0, $EPP);
        }

        // "From" and "To" entry numbers
        $from = $showAll
            ? 1
            : ($totalEntries > 0 ? $offset + 1 : 0);
        $to = $showAll
            ? $totalEntries
            : min($offset + count($entries), $totalEntries);

        // Pagination links (only if not showAll)
        $paginationLinks = [];
        if (!$showAll && $totalEntries > 0) {
            $startPage = max(1, $page - 2);
            $endPage   = min($page + 2, (int)ceil($totalEntries / $EPP));

            for ($i = $startPage; $i <= $endPage; $i++) {
                $queryParams = $filters;
                $queryParams['page'] = $i;

                if ($showAll) {
                    $queryParams['show_all'] = '1';
                }

                $paginationLinks[] = [
                    'page'   => $i,
                    'url'    => App::currentPath() . '?' . http_build_query($queryParams),
                    'active' => ($i === $page),
                ];
            }
        }

        // Previous & Next URLs
        $prevURL = null;
        $nextURL = null;
        if (!$showAll) {
            if ($page > 1) {
                $qp = $filters;
                $qp['page'] = $page - 1;
                if ($showAll) $qp['show_all'] = '1';
                $prevURL = App::currentPath() . '?' . http_build_query(array_filter($qp));
            }

            if ($hasNext) {
                $qp = $filters;
                $qp['page'] = $page + 1;
                if ($showAll) $qp['show_all'] = '1';
                $nextURL = App::currentPath() . '?' . http_build_query(array_filter($qp));
            }
        }

        // Return
        $info = [
            'entries'        => $entries,
            'entries_count'  => count($entries),
            'pagination'     => [
                'current_page' => $page,
                'per_page'     => $EPP,
                'prev'         => $prevURL,
                'next'         => $nextURL,
                'links'        => $paginationLinks,
            ],
            'page' => [
                'from'  => $from,
                'to'    => $to,
                'total' => $totalEntries,
            ],
            'show_all'    => $showAll,
            'filters'     => $filters,
            'toggle_url'  => App::currentPath() . '?' . http_build_query($filters),
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
    /**
     * Upload multiple files to a specific directory
     *
     * @param string $folder Relative folder path (e.g., 'uploads/files')
     * @param array $files Multiple files array like $_FILES['files']
     * @return array List of uploaded file names (with extension)
     */
    public static function uploadFiles(string $folder, array $files): array
    {
        $uploadedFiles = [];
        self::make($folder);

        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']]
            ];
        }

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if (!is_uploaded_file($files['tmp_name'][$i])) continue;
            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $random = uniqid('file_', true) . '.' . $ext;
            $dest = self::toAbsolute($folder . '/' . $random);
            if (file_exists($dest)) unlink($dest);
            if (move_uploaded_file($files['tmp_name'][$i], $dest)) $uploadedFiles[] = $random;
        }

        return $uploadedFiles;
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
        define('PAGE_TITLES', $labels);
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
    private static string $secretKey;
    public static function crypt_init(): void
    {
        if (!isset(self::$secretKey) || self::$secretKey === '') {
            self::$secretKey = $_ENV['CRYPT_KEY'] ?? '';
        }
    }
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
    public static function parentPath($isRelative = false): string
    {
        $segments = SEGMENTS;
        array_pop($segments);
        $path = $isRelative ? implode('/', $segments) : getRoot() . implode('/', $segments);
        return $path;
    }
    public static function rootPath($isRelative = false)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
            ? "https://"
            : "http://";

        $host = $_SERVER['HTTP_HOST'];
        $root = $_ENV['ROOT'] ?? '/';
        return $isRelative ? rtrim($root, '/') : rtrim($protocol . $host . $root, '/');
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
            case 'lock':
                $icon = '<a href="' . $url . '" class="text-lg text-textPrimary"><i class="ri-lock-2-line"></i></a>';
                break;
            case 'unlock':
                $icon = '<a href="' . $url . '" class="text-lg text-textPrimary"><i class="ri-lock-unlock-line"></i></a>';
                break;
            default:
                $icon = '<a href="' . $url . '" class="text-lg text-red-600">Icon Not Defined!</a>';
                break;
        }
        return $icon;
    }

    public static function encrypt(string $name): string
    {
        self::crypt_init();
        $iv = random_bytes(12);
        $ciphertext = openssl_encrypt(
            $name,
            'aes-256-gcm',
            self::$secretKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            "",
            16
        );
        $payload = $iv . $tag . $ciphertext;
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    public static function decrypt(string $encrypted): string
    {
        self::crypt_init();
        $base64 = strtr($encrypted, '-_', '+/');
        $pad = 4 - (strlen($base64) % 4);
        if ($pad < 4) $base64 .= str_repeat('=', $pad);
        $payload = base64_decode($base64, true);
        if ($payload === false) {
            throw new RuntimeException("Invalid base64 input");
        }
        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $ciphertext = substr($payload, 28);
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            self::$secretKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($plaintext === false) {
            throw new RuntimeException("Decryption failed or data tampered");
        }
        return $plaintext;
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
            $user['allowed_routes'] = [...$user['allowed_routes'], '', 'settings', 'action'];
            $user['preferences'] = jd($user['preferences'] ?? '[]');
            if (!isset($user['preferences']['color_theme'])) {
                $user['preferences']['color_theme'] = self::$defaultTheme;
            }
            return $user;
        } else {
            return null;
        }
    }
    public static function sortByColumn(array $entries, string $keyColumn = 'id', ?string $valueColumn = null): array
    {
        $result = [];
        foreach ($entries as $entry) {
            if (!isset($entry[$keyColumn])) {
                continue;
            }
            $key = $entry[$keyColumn];
            if ($valueColumn !== null && isset($entry[$valueColumn])) {
                $result[$key] = $entry[$valueColumn];
            } else {
                $result[$key] = $entry;
            }
        }
        return $result;
    }

    public static function setupPrintSystem($data) {}
    public static function pageTop(array $entries, $customContent = null, array $options = [])
    {
        $options['add_button_roles'] = empty($options['add_button_roles']) ? ['admin', 'superadmin'] : $options['add_button_roles'];
        $title =  PAGE_TITLES[SEGMENTS[count(SEGMENTS) - 1]];
        $EPP = DB::fetch('static_types', ['type_for' => 'entries_per_page'], 'all');
        $user_p = App::currentUser()['preferences'];
        $user_epp = !empty($user_p['entries_per_page']) ? $user_p['entries_per_page'] : '10';
        $newShowAll = $entries['show_all'] ? null : '1';
        if ($newShowAll === null) {
            unset($entries['filters']['show_all']);
        } else {
            $entries['filters']['show_all'] = '1';
        }
        $toggleUrl = App::currentPath() . (count($entries['filters']) ? ('?' . http_build_query($entries['filters'])) : '');
?>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
            <h1 class="text-3xl font-bold text-textPrimary"><?= $title; ?></h1>
            <div class="flex items-center gap-2 mt-3 md:mt-0">
                <!-- Search Form -->
                <form method="GET" action="<?= App::currentPath() ?>" class="relative">
                    <?php if ($entries['show_all']): ?>
                        <input type="hidden" name="show_all" value="1">
                    <?php endif; ?>
                    <input
                        type="text"
                        name="<?= $options['searchForm']['name'] ?? 'name' ?>"
                        id="<?= $options['searchForm']['id'] ?? 'name' ?>"
                        value="<?= htmlspecialchars($entries['filters'][$options['searchForm']['name'] ?? 'name'] ?? '') ?>"
                        placeholder="<?= $options['searchForm']['placeholder'] ?? 'Search Name...' ?>"
                        class="pl-10 pr-2 py-2 border border-textSecondary/30 rounded-lg text-sm focus:outline-none focus:ring-1 focus:!ring-accent transition w-52">
                    <i class="ri-filter-line text-xl cursor-pointer text-textSecondary/80 absolute left-3 top-1.5" @click="openFilters = true"></i>
                </form>

                <!-- Reset Filters -->
                <?php if (!empty($_GET) && !in_array($_GET[0] ?? '', ['page', 'export'])): ?>
                    <a href="<?= App::currentPath() ?>" class="text-textSecondary hover:text-red-600 transition transform hover:rotate-90">
                        <i class="ri-loop-left-line text-red-600 text-2xl"></i>
                    </a>
                <?php endif; ?>

                <!-- Add Button -->
                <?php if (in_array($_SESSION['user']['role'], $options['add_button_roles'])) { ?>
                    <a href="<?= App::currentPath() . '/manage?action=add-new' ?>" class="!bg-accent !text-bg rounded-lg hover:bg-accent/90 transition text-sm px-3 py-1 font-medium">
                        <i class="ri-add-line text-lg !text-bg mr-1"></i> Add
                    </a>
                <?php } ?>
                <!-- Export Button -->
                <a href="<?= $entries['toggle_url'] . '&action=export'; ?>" class="!bg-textPrimary !text-bg rounded-lg hover:!bg-textPrimary/90 transition text-sm px-3 py-1 font-medium">
                    <i class="ri-upload-2-line text-xl !text-bg"></i>
                </a>
            </div>
        </div>

        <!-- Info & Pagination -->
        <div class="flex flex-col-reverse gap-2 md:flex-row justify-between items-start md:items-center mb-4 space-y-2 md:space-y-0">
            <div class="text-sm text-textPrimary">
                Showing <?= $entries['page']['from'] ?> to <?= $entries['page']['to'] ?> of <?= $entries['page']['total'] ?> entries
            </div>

            <div class="flex flex-col-reverse md:flex-row gap-2">
                <!-- Show All Toggle -->
                <div class="flex items-center space-x-1">
                    <input
                        type="checkbox"
                        id="showAllToggle"
                        class="form-checkbox h-5 w-5 text-accent transition duration-150"
                        <?= $entries['show_all'] ? 'checked' : '' ?>
                        onclick="window.location.href='<?= $toggleUrl ?>'">
                    <label for="showAllToggle" class="text-textPrimary text-sm font-medium">Show All</label>
                </div>

                <?php if (!$entries['show_all']): ?>
                    <!-- Entries per page -->
                    <form action="<?= App::rootPath(true) . '/action'; ?>" method="POST" class="flex items-center space-x-0">
                        <input type="hidden" name="return_url" value="<?= App::currentPath(true); ?>" />
                        <select
                            name="entries_per_page"
                            id="entries_per_page"
                            class="px-3 py-2 border border-textSecondary/30 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                            onchange="this.form.submit()">
                            <?php foreach ($EPP as $s_epp): ?>
                                <option value="<?= $s_epp['value1'] ?>" <?= $user_epp === $s_epp['value1'] ? 'selected' : '' ?>>
                                    <?= $s_epp['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <!-- Pagination -->
                    <div class="flex items-center space-x-1">
                        <?php if ($entries['pagination']['prev']): ?>
                            <a href="<?= $entries['pagination']['prev'] ?>" class="px-1 py-2 bg-bg border border-textSecondary/30 rounded hover:bg-textSecondary/10 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-textSecondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </a>
                        <?php endif; ?>

                        <?php foreach ($entries['pagination']['links'] as $link): ?>
                            <?php if ($link['active']): ?>
                                <span class="px-3 py-2 bg-accent text-bg rounded border"><?= $link['page'] ?></span>
                            <?php else: ?>
                                <a href="<?= $link['url'] ?>" class="px-2.5 py-2 bg-bg border border-textSecondary/30 rounded hover:bg-textSecondary/10 transition">
                                    <?= $link['page'] ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if ($entries['pagination']['next']): ?>
                            <a href="<?= $entries['pagination']['next'] ?>" class="px-1 py-2 bg-bg border border-textSecondary/30 rounded hover:bg-textSecondary/10 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-textSecondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span class="text-textSecondary italic">All entries shown</span>
                <?php endif; ?>
            </div>
        </div>
        <div
            x-show="openFilters"
            x-cloak
            @keydown.escape.window="openFilters = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-textPrimary/70 bg-opacity-50">
            <div class="bg-bg w-full max-w-md rounded-lg shadow-lg overflow-y-auto">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-2 border border-textPrimary text-textPrimary">
                    <h3 class="text-xl font-semibold text-textPrimary">Apply Filters</h3>
                    <button @click="openFilters = false" class="text-textSecondary hover:text-textSecondary transition focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form method="GET" class="px-3 py-3 space-y-2">
                    <?php if ($entries['show_all']): ?>
                        <input type="hidden" name="show_all" value="1">
                    <?php endif; ?>
                    <?php
                    if (is_callable($customContent)) {
                        call_user_func($customContent, $entries);
                    }
                    ?>
                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 pt-4 border-t border-textSecondary/20">
                        <button
                            type="button"
                            @click="openFilters = false"
                            class="px-4 py-2 text-sm font-medium !text-textPrimary !bg-textSecondary/10 rounded-lg hover:!bg-textSecondary/20 transition focus:outline-none">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium !text-bg !bg-accent rounded-lg hover:!bg-accent/90 transition focus:outline-none">
                            Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

<?php
    }
    public static function buildTree(array $elements, int $parentId = 0): array
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) { //line no 999
                $children = self::buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                } else {
                    $element['children'] = [];
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }
}
