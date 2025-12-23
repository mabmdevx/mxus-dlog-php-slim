<?php

class Module
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Find module by UUID
     *
     * @param string $uuid
     * @return array|null
     */
    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM modules WHERE module_uuid = ? AND is_deleted = 0',
            [$uuid]
        );
    }

    /**
     * Find module by ID
     *
     * @param int $moduleId
     * @return array|null
     */
    public function findById(int $moduleId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM modules WHERE module_id = ? AND is_deleted = 0',
            [$moduleId]
        );
    }

    /**
     * Get all modules
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM modules WHERE is_deleted = 0 ORDER BY module_id ASC',
            []
        );
    }
}
