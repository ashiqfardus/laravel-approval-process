<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers\Api;

use AshiqFardus\ApprovalProcess\Models\ApprovableEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EntityController extends Controller
{
    // Tables that are always excluded from discovery
    private const SKIP_TABLES = [
        'migrations', 'failed_jobs', 'password_resets', 'password_reset_tokens',
        'personal_access_tokens', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'telescope_entries', 'telescope_entries_tags',
        'telescope_monitoring', 'pulse_aggregates', 'pulse_entries', 'pulse_values',
    ];

    /**
     * Get all approvable entities (active + inactive).
     */
    public function index(): JsonResponse
    {
        $entities = ApprovableEntity::orderBy('label')
            ->get()
            ->map(fn($e) => $this->formatEntity($e));

        return response()->json($entities);
    }

    /**
     * Return all configured Laravel DB connections with metadata.
     *
     * This powers the Connection dropdown in the Add Entity modal.
     * Each entry includes whether the connection is reachable.
     */
    public function connections(): JsonResponse
    {
        $configured = config('database.connections', []);
        $default    = config('database.default', 'mysql');

        $connections = [];
        foreach ($configured as $name => $config) {
            // Skip non-relational drivers that can't be queried for tables
            if (in_array($config['driver'] ?? '', ['redis', 'mongodb'])) {
                continue;
            }

            $connections[] = [
                'name'     => $name,
                'driver'   => $config['driver'] ?? 'unknown',
                'database' => $config['database'] ?? null,
                'host'     => $config['host'] ?? null,
                'is_default' => $name === $default,
            ];
        }

        return response()->json($connections);
    }

    /**
     * Discover available models and tables.
     *
     * Query params:
     *   ?connection=mysql          – scan a specific Laravel connection
     *   ?connection=default        – scan the default connection
     *
     * The response includes:
     *   - models: Eloquent model classes found in app/Models
     *   - tables: tables on the requested connection
     *   - cross_db_hint: true when the connection is MySQL/MariaDB
     *     (signals the UI to show the "db.table" prefix tip)
     */
    public function discover(Request $request): JsonResponse
    {
        $connectionName = $request->query('connection', config('database.default'));

        $discovered = [
            'models'        => $this->discoverModels(),
            'tables'        => $this->discoverTables($connectionName),
            'connection'    => $connectionName,
            'cross_db_hint' => $this->supportsCrossDbPrefix($connectionName),
        ];

        return response()->json($discovered);
    }

    /**
     * Store a new entity.
     *
     * For cross-DB prefix tables (e.g. core.menus on the same MySQL server),
     * store the full prefixed name in `name` and the Laravel connection in
     * `connection`. The package will use DB::connection($connection)->table($name).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'        => 'required|in:model,table',
            'name'        => 'required|string|max:255',
            'label'       => 'required|string|max:255',
            'connection'  => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'metadata'    => 'nullable|array',
        ]);

        $entity = ApprovableEntity::create($validated);

        return response()->json($this->formatEntity($entity), 201);
    }

    /**
     * Update an entity.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $entity = ApprovableEntity::findOrFail($id);

        $validated = $request->validate([
            'label'       => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'sometimes|boolean',
            'metadata'    => 'nullable|array',
        ]);

        $entity->update($validated);

        return response()->json($this->formatEntity($entity->fresh()));
    }

    /**
     * Delete an entity.
     */
    public function destroy(int $id): JsonResponse
    {
        $entity = ApprovableEntity::findOrFail($id);
        $entity->delete();

        return response()->json(['message' => 'Entity deleted successfully']);
    }

    /**
     * Search users for approver selection.
     *
     * Uses the configured user model (config approval-process.models.user)
     * so it works with any auth table — not just the default `users` table.
     *
     * Query params:
     *   ?search=john   – filter by name or email (case-insensitive)
     *   ?limit=50      – max results (default 50, max 200)
     */
    public function users(Request $request): JsonResponse
    {
        $userModelClass = config('approval-process.models.user', \Illuminate\Foundation\Auth\User::class);
        $search  = $request->query('search', '');
        $limit   = min((int) $request->query('limit', 50), 200);

        try {
            /** @var \Illuminate\Database\Eloquent\Model $instance */
            $instance  = new $userModelClass;
            $table     = $instance->getTable();
            $connection = $instance->getConnectionName();

            $schema = DB::connection($connection)->getSchemaBuilder();

            // Detect available columns — different apps use different column names
            $hasName      = $schema->hasColumn($table, 'name');
            $hasFirstName = $schema->hasColumn($table, 'first_name');
            $hasLastName  = $schema->hasColumn($table, 'last_name');
            $hasEmail     = $schema->hasColumn($table, 'email');
            $hasPk        = $schema->hasColumn($table, 'id');

            $query = DB::connection($connection)->table($table);

            // Build search filter
            if ($search !== '') {
                $query->where(function ($q) use ($search, $hasName, $hasFirstName, $hasLastName, $hasEmail) {
                    if ($hasName)      $q->orWhere('name', 'like', "%{$search}%");
                    if ($hasFirstName) $q->orWhere('first_name', 'like', "%{$search}%");
                    if ($hasLastName)  $q->orWhere('last_name', 'like', "%{$search}%");
                    if ($hasEmail)     $q->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Select only needed columns
            $select = ['id'];
            if ($hasName)      $select[] = 'name';
            if ($hasFirstName) $select[] = 'first_name';
            if ($hasLastName)  $select[] = 'last_name';
            if ($hasEmail)     $select[] = 'email';

            $rows = $query->select($select)->limit($limit)->get();

            $users = $rows->map(function ($row) use ($hasName, $hasFirstName, $hasLastName, $hasEmail) {
                // Build display name from available columns
                if ($hasName && !empty($row->name)) {
                    $displayName = $row->name;
                } elseif ($hasFirstName || $hasLastName) {
                    $displayName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                } else {
                    $displayName = $hasEmail ? ($row->email ?? "User #{$row->id}") : "User #{$row->id}";
                }

                return [
                    'id'    => $row->id,
                    'name'  => $displayName,
                    'email' => $hasEmail ? ($row->email ?? null) : null,
                    'label' => $displayName . ($hasEmail && !empty($row->email) ? " ({$row->email})" : ''),
                ];
            });

            return response()->json($users);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Could not load users: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available roles for approvers.
     */
    public function roles(): JsonResponse
    {
        $roles = [];

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $roles = \Spatie\Permission\Models\Role::pluck('name', 'name')->toArray();
        } else {
            $userModel = config('approval-process.models.user', \Illuminate\Foundation\Auth\User::class);
            if (method_exists($userModel, 'getTable')) {
                $table = (new $userModel)->getTable();
                if (DB::getSchemaBuilder()->hasColumn($table, 'role')) {
                    $roles = DB::table($table)
                        ->whereNotNull('role')
                        ->distinct()
                        ->pluck('role', 'role')
                        ->toArray();
                }
            }
        }

        $formatted = [];
        foreach ($roles as $key => $value) {
            $formatted[] = [
                'value' => $key,
                'label' => ucwords(str_replace('_', ' ', $value)),
            ];
        }

        return response()->json($formatted);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function formatEntity(ApprovableEntity $entity): array
    {
        return [
            'id'             => $entity->id,
            'type'           => $entity->type,
            'name'           => $entity->name,
            'label'          => $entity->label,
            'connection'     => $entity->connection,
            'description'    => $entity->description,
            'is_active'      => $entity->is_active,
            'full_identifier'=> $entity->full_identifier,
            'metadata'       => $entity->metadata,
        ];
    }

    /**
     * Discover Eloquent models from app/Models (and subdirectories).
     */
    private function discoverModels(): array
    {
        $models     = [];
        $modelsPath = app_path('Models');

        if (!is_dir($modelsPath)) {
            return $models;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modelsPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Build class name from relative path
            $relative  = str_replace($modelsPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relative  = str_replace(['/', '\\', DIRECTORY_SEPARATOR], '\\', $relative);
            $className = 'App\\Models\\' . str_replace('.php', '', $relative);

            if (class_exists($className)) {
                $shortName = basename($file->getPathname(), '.php');
                $models[]  = [
                    'class' => $className,
                    'name'  => $shortName,
                    'label' => $this->classToLabel($shortName),
                ];
            }
        }

        usort($models, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $models;
    }

    /**
     * Discover tables on a given Laravel connection.
     *
     * Handles MySQL/MariaDB, PostgreSQL, SQLite, and SQL Server.
     * Returns each table with a cross_db flag if it contains a dot (db.table).
     */
    private function discoverTables(string $connectionName): array
    {
        $tables = [];

        try {
            $connection = DB::connection($connectionName);
            $driver     = $connection->getDriverName();

            $rawTables = match ($driver) {
                'mysql', 'mariadb' => $this->mysqlTables($connection),
                'pgsql'            => $this->pgsqlTables($connection),
                'sqlite'           => $this->sqliteTables($connection),
                'sqlsrv'           => $this->sqlsrvTables($connection),
                default            => [],
            };

            foreach ($rawTables as $tableName) {
                if (in_array($tableName, self::SKIP_TABLES)) {
                    continue;
                }
                $tables[] = [
                    'name'     => $tableName,
                    'label'    => $this->tableToLabel($tableName),
                    'cross_db' => str_contains($tableName, '.'),
                ];
            }
        } catch (Throwable $e) {
            // Connection failed — return empty with error flag
            return [['error' => 'Could not connect: ' . $e->getMessage()]];
        }

        return $tables;
    }

    private function mysqlTables($connection): array
    {
        $primaryDb = $connection->getDatabaseName();

        // ── Primary DB tables ──────────────────────────────────────────────
        $rows   = $connection->select('SHOW TABLES');
        $key    = "Tables_in_{$primaryDb}";
        $tables = array_map(fn($r) => $r->$key, $rows);

        // ── Cross-DB tables from sibling databases ─────────────────────────
        // Query INFORMATION_SCHEMA to find all databases the MySQL user can see.
        // We exclude system databases and the primary DB itself.
        $systemDbs    = ['information_schema', 'mysql', 'performance_schema', 'sys'];
        $placeholders = implode(',', array_fill(0, count($systemDbs), '?'));

        try {
            $siblingDbs = $connection->select(
                "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA
                  WHERE SCHEMA_NAME NOT IN ({$placeholders})
                    AND SCHEMA_NAME != ?
                  ORDER BY SCHEMA_NAME",
                [...$systemDbs, $primaryDb]
            );

            foreach ($siblingDbs as $db) {
                $dbName    = $db->SCHEMA_NAME;
                $crossRows = $connection->select(
                    "SELECT TABLE_NAME FROM information_schema.TABLES
                      WHERE TABLE_SCHEMA = ?
                        AND TABLE_TYPE = 'BASE TABLE'
                      ORDER BY TABLE_NAME",
                    [$dbName]
                );
                foreach ($crossRows as $row) {
                    $tables[] = "{$dbName}.{$row->TABLE_NAME}";
                }
            }
        } catch (Throwable) {
            // INFORMATION_SCHEMA not accessible — skip cross-db discovery
        }

        return $tables;
    }

    private function pgsqlTables($connection): array
    {
        $rows = $connection->select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
        );
        return array_map(fn($r) => $r->tablename, $rows);
    }

    private function sqliteTables($connection): array
    {
        $rows = $connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );
        return array_map(fn($r) => $r->name, $rows);
    }

    private function sqlsrvTables($connection): array
    {
        $rows = $connection->select(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME"
        );
        return array_map(fn($r) => $r->TABLE_NAME, $rows);
    }

    /**
     * MySQL/MariaDB supports cross-DB queries via the `db.table` prefix syntax.
     */
    private function supportsCrossDbPrefix(string $connectionName): bool
    {
        try {
            $driver = DB::connection($connectionName)->getDriverName();
            return in_array($driver, ['mysql', 'mariadb']);
        } catch (Throwable) {
            return false;
        }
    }

    private function classToLabel(string $className): string
    {
        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $className));
    }

    private function tableToLabel(string $tableName): string
    {
        // For cross-db tables like "core.menus", use "Core: Menus"
        if (str_contains($tableName, '.')) {
            [$db, $table] = explode('.', $tableName, 2);
            return ucfirst($db) . ': ' . ucwords(str_replace('_', ' ', $table));
        }
        return ucwords(str_replace('_', ' ', $tableName));
    }
}
