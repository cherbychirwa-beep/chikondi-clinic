<?php

namespace App\Services;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Singleton access point to the application's database connection.
 *
 * Registered with $this->app->singleton() in AppServiceProvider, so every consumer receives the
 * same instance. It deliberately wraps Laravel's own connection manager (PDO + prepared statements)
 * rather than opening a competing connection.
 */
class DatabaseConnection
{
    public function connection(): ConnectionInterface
    {
        return DB::connection();
    }

    public function pdo(): PDO
    {
        return DB::connection()->getPdo();
    }

    /** Run a closure inside a database transaction (rolled back automatically on exception). */
    public function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
