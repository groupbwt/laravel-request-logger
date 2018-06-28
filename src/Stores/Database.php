<?php
declare(strict_types=1);

namespace BwtTeam\LaravelRequestLogger\Stores;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class Database implements StoreInterface
{
    use Concerns\Caster;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The request logger database table.
     *
     * @var string
     */
    protected $table;

    /**
     * Database constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param string $table
     */
    public function __construct(ConnectionInterface $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Get the connection.
     *
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Begin a new database query against the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query(): Builder
    {
        return $this->connection->table($this->table);
    }

    /**
     * @inheritdoc
     */
    public function save(array $data): bool
    {
        return $this->query()->insert(array_merge($this->format($data), [
            'created_at' => Carbon::now(),
        ]));
    }

    /**
     * Formats a log record.
     *
     * @param array $data
     *
     * @return array
     */
    protected function format(array $data): array
    {
        return array_map(function ($field) {
            if (is_array($field)) {
                return json_encode($this->castField($field));
            }

            return $this->castField($field);
        }, $data);
    }
}