<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Abstract base model for all database models.
 *
 * Provides common CRUD operations using $wpdb with prepared statements.
 * All child models must implement table_name() to return their table suffix.
 *
 * Usage:
 *   class Package extends BaseModel {
 *       protected function table_name(): string { return 'packages'; }
 *   }
 *   $model = new Package();
 *   $model->find(1);
 */
abstract class BaseModel
{
    protected \wpdb $db;
    protected string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'whoiscrm_' . $this->table_name();
    }

    /**
     * Return the table name suffix (without prefix and 'whoiscrm_').
     * Example: 'packages', 'subscriptions', 'payments'
     */
    abstract protected function table_name(): string;

    /**
     * Get the full table name.
     */
    public function get_table(): string
    {
        return $this->table;
    }

    // ─── Read Operations ──────────────────────────────────────────────────

    /**
     * Find a single record by ID.
     */
    public function find(int $id): ?object
    {
        $result = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id)
        );

        return $result ?: null;
    }

    /**
     * Find a single record by a specific column value.
     *
     * @param string $column Column name (must be a valid column — no user input).
     * @param mixed  $value  Value to search for.
     */
    public function find_by(string $column, mixed $value): ?object
    {
        $column = sanitize_key($column);
        $format = is_int($value) ? '%d' : '%s';

        $result = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE {$column} = {$format}",
                $value
            )
        );

        return $result ?: null;
    }

    /**
     * Get all records matching a set of conditions.
     *
     * @param array  $where   Associative array of column => value conditions (AND).
     * @param string $orderby Column to order by.
     * @param string $order   ASC or DESC.
     * @param int    $limit   Max records (0 = no limit).
     * @param int    $offset  Offset for pagination.
     * @return array<object>
     */
    public function get_where(
        array $where = [],
        string $orderby = 'id',
        string $order = 'DESC',
        int $limit = 0,
        int $offset = 0
    ): array {
        $orderby = sanitize_key($orderby);
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$this->table}";
        $values = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $col => $val) {
                $col = sanitize_key($col);
                $format = is_int($val) ? '%d' : '%s';
                $clauses[] = "{$col} = {$format}";
                $values[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= " ORDER BY {$orderby} {$order}";

        if ($limit > 0) {
            $sql .= ' LIMIT %d OFFSET %d';
            $values[] = $limit;
            $values[] = $offset;
        }

        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built with sanitized keys and prepared values.
            $sql = $this->db->prepare($sql, ...$values);
        }

        return $this->db->get_results($sql) ?: [];
    }

    /**
     * Get all records (with optional ordering and limit).
     *
     * @return array<object>
     */
    public function get_all(string $orderby = 'id', string $order = 'DESC', int $limit = 0): array
    {
        return $this->get_where([], $orderby, $order, $limit);
    }

    /**
     * Count records matching conditions.
     */
    public function count(array $where = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $values = [];

        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $col => $val) {
                $col = sanitize_key($col);
                $format = is_int($val) ? '%d' : '%s';
                $clauses[] = "{$col} = {$format}";
                $values[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $this->db->prepare($sql, ...$values);
        }

        return (int) $this->db->get_var($sql);
    }

    // ─── Write Operations ─────────────────────────────────────────────────

    /**
     * Insert a new record.
     *
     * Automatically sets created_at and updated_at if not provided.
     *
     * @param array $data Column => value pairs.
     * @return int|false The insert ID on success, false on failure.
     */
    public function insert(array $data): int|false
    {
        $now = current_time('mysql', true);

        if (!isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (!isset($data['updated_at']) && $this->has_column('updated_at')) {
            $data['updated_at'] = $now;
        }

        $formats = $this->get_formats($data);

        $result = $this->db->insert($this->table, $data, $formats);

        if ($result === false) {
            return false;
        }

        return (int) $this->db->insert_id;
    }

    /**
     * Update a record by ID.
     *
     * Automatically updates the updated_at timestamp.
     *
     * @param int   $id   Record ID.
     * @param array $data Column => value pairs to update.
     */
    public function update(int $id, array $data): bool
    {
        if (!isset($data['updated_at']) && $this->has_column('updated_at')) {
            $data['updated_at'] = current_time('mysql', true);
        }

        $formats = $this->get_formats($data);

        $result = $this->db->update($this->table, $data, ['id' => $id], $formats, ['%d']);

        return $result !== false;
    }

    /**
     * Delete a record by ID.
     */
    public function delete(int $id): bool
    {
        $result = $this->db->delete($this->table, ['id' => $id], ['%d']);

        return $result !== false;
    }

    /**
     * Delete records matching conditions.
     *
     * @param array $where Column => value conditions.
     */
    public function delete_where(array $where): bool
    {
        $formats = $this->get_formats($where);

        $result = $this->db->delete($this->table, $where, $formats);

        return $result !== false;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Determine wpdb format strings for an array of values.
     *
     * @param array $data Column => value pairs.
     * @return array Format strings (%s, %d, %f).
     */
    protected function get_formats(array $data): array
    {
        $formats = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }
        return $formats;
    }

    /**
     * Check if a column name exists in this model's schema.
     *
     * Uses a static cache to avoid repeated DESCRIBE queries.
     */
    protected function has_column(string $column): bool
    {
        static $cache = [];

        $key = $this->table;

        if (!isset($cache[$key])) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $columns = $this->db->get_col("DESCRIBE {$this->table}", 0);
            $cache[$key] = array_map('strtolower', $columns ?: []);
        }

        return in_array(strtolower($column), $cache[$key], true);
    }

    /**
     * Get the last database error.
     */
    public function last_error(): string
    {
        return $this->db->last_error;
    }

    /**
     * Execute a raw prepared query and return results.
     *
     * @param string $sql    SQL with placeholders.
     * @param mixed  ...$args Values for placeholders.
     * @return array<object>
     */
    protected function query(string $sql, mixed ...$args): array
    {
        if (!empty($args)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $this->db->prepare($sql, ...$args);
        }

        return $this->db->get_results($sql) ?: [];
    }

    /**
     * Execute a raw prepared query and return a single value.
     */
    protected function query_var(string $sql, mixed ...$args): mixed
    {
        if (!empty($args)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $this->db->prepare($sql, ...$args);
        }

        return $this->db->get_var($sql);
    }
}
