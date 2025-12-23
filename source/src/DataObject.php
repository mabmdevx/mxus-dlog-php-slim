<?php

class DataObject
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Find data object by UUID
     *
     * @param string $uuid
     * @return array|null
     */
    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM data_objects WHERE data_object_uuid = ? AND is_deleted = 0',
            [$uuid]
        );
    }

    /**
     * Find data object by ID
     *
     * @param int $dataObjectId
     * @return array|null
     */
    public function findById(int $dataObjectId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM data_objects WHERE data_object_id = ? AND is_deleted = 0',
            [$dataObjectId]
        );
    }

    /**
     * Get all data objects for a user by UUID
     *
     * @param string $userUuid
     * @return array
     */
    public function getByUserUuid(string $userUuid): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM data_objects WHERE created_by = ? AND is_deleted = 0 ORDER BY data_object_id ASC',
            [$userUuid]
        );
    }

    /**
     * Get all data objects for a user
     *
     * @param string $userId
     * @return array
     */
    public function getByUser(string $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM data_objects WHERE created_by = ? AND is_deleted = 0 ORDER BY data_object_name ASC',
            [$userId]
        );
    }

    /**
     * Create new data object
     *
     * @param array $data Data object data
     * @return int Data Object ID
     * @throws Exception
     */
    public function create(array $data): int
    {
        $uuid = $this->generateUuid();
        $now = date('Y-m-d H:i:s');

        $dataObjectId = $this->db->insert(
            'INSERT INTO data_objects (data_object_uuid, data_object_name, data_object_desc, data_object_unit, data_object_module_uuid, created_by, updated_by, created_on, is_deleted) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)',
            [$uuid, $data['data_object_name'], $data['data_object_desc'] ?? '', $data['data_object_unit'] ?? '', $data['data_object_module_uuid'] ?? '', $data['created_by'], $data['created_by'], $now]
        );

        return $dataObjectId;
    }

    /**
     * Update data object
     *
     * @param int $dataObjectId
     * @param array $data
     * @return bool
     */
    public function update(int $dataObjectId, array $data): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE data_objects SET data_object_name = ?, data_object_desc = ?, data_object_unit = ?, data_object_module_uuid = ?, updated_by = ?, updated_on = ? WHERE data_object_id = ?',
            [$data['data_object_name'], $data['data_object_desc'] ?? '', $data['data_object_unit'] ?? '', $data['data_object_module_uuid'] ?? '', $data['updated_by'], $now, $dataObjectId]
        );

        return true;
    }

    /**
     * Delete data object (soft delete)
     *
     * @param int $dataObjectId
     * @return bool
     */
    public function delete(int $dataObjectId): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE data_objects SET is_deleted = 1, updated_on = ? WHERE data_object_id = ?',
            [$now, $dataObjectId]
        );

        return true;
    }

    /**
     * Count data objects by module UUID
     *
     * @param string $moduleUuid
     * @param string $userUuid
     * @return int
     */
    public function countByModuleUuidAndUserUuid(string $moduleUuid, string $userUuid): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as count FROM data_objects WHERE data_object_module_uuid = ? AND created_by = ? AND is_deleted = 0',
            [$moduleUuid, $userUuid]
        );
        return $result['count'] ?? 0;
    }

    /**
     * Generate UUID v4
     *
     * @return string
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
