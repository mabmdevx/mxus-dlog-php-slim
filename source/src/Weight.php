<?php

class Weight
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Find weight by UUID
     *
     * @param string $uuid
     * @return array|null
     */
    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM weights WHERE weight_uuid = ? AND is_deleted = 0',
            [$uuid]
        );
    }

    /**
     * Find weight by ID
     *
     * @param int $weightId
     * @return array|null
     */
    public function findById(int $weightId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM weights WHERE weight_id = ? AND is_deleted = 0',
            [$weightId]
        );
    }

    /**
     * Get all weights by data object UUID for a user by UUID
     *
     * @param string $dataObjectUuid
     * @param string $userUuid
     * @return array
     */
    public function getByDataObjectUuidAndUserUuid(string $dataObjectUuid, string $userUuid): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM weights WHERE weight_data_object_uuid = ? AND created_by = ? AND is_deleted = 0 ORDER BY weight_timestamp DESC',
            [$dataObjectUuid, $userUuid]
        );
    }

    /**
     * Get all weights by data object UUID for a user
     *
     * @param string $dataObjectUuid
     * @param string $userId
     * @return array
     */
    public function getByDataObjectUuid(string $dataObjectUuid, string $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM weights WHERE weight_data_object_uuid = ? AND created_by = ? AND is_deleted = 0 ORDER BY weight_timestamp DESC',
            [$dataObjectUuid, $userId]
        );
    }

    /**
     * Get all weights for a user by UUID
     *
     * @param string $userUuid
     * @return array
     */
    public function getByUserUuid(string $userUuid): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM weights WHERE created_by = ? AND is_deleted = 0 ORDER BY created_on DESC',
            [$userUuid]
        );
    }

    /**
     * Get all weights for a user
     *
     * @param string $userId
     * @return array
     */
    public function getByUser(string $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM weights WHERE created_by = ? AND is_deleted = 0 ORDER BY created_on DESC',
            [$userId]
        );
    }

    /**
     * Count weight records by data object UUID and user UUID
     *
     * @param string $dataObjectUuid
     * @param string $userUuid
     * @return int
     */
    public function countByDataObjectUuidAndUserUuid(string $dataObjectUuid, string $userUuid): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as count FROM weights WHERE weight_data_object_uuid = ? AND created_by = ? AND is_deleted = 0',
            [$dataObjectUuid, $userUuid]
        );
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Create new weight entry
     *
     * @param array $data Weight data
     * @return int Weight ID
     * @throws Exception
     */
    public function create(array $data): int
    {
        $uuid = $this->generateUuid();
        $now = date('Y-m-d H:i:s');
        $timestamp = $data['weight_timestamp'] ?? $now;

        $weightId = $this->db->insert(
            'INSERT INTO weights (weight_uuid, weight_val, weight_data_object_uuid, weight_timestamp, created_by, updated_by, created_on, is_deleted) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)',
            [$uuid, $data['weight_val'], $data['weight_data_object_uuid'], $timestamp, $data['created_by'], $data['created_by'], $now]
        );

        return $weightId;
    }

    /**
     * Update weight entry
     *
     * @param int $weightId
     * @param array $data
     * @return bool
     */
    public function update(int $weightId, array $data): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE weights SET weight_val = ?, weight_timestamp = ?, weight_data_object_uuid = ?, updated_by = ?, updated_on = ? WHERE weight_id = ?',
            [$data['weight_val'], $data['weight_timestamp'], $data['weight_data_object_uuid'], $data['updated_by'], $now, $weightId]
        );

        return true;
    }

    /**
     * Delete weight entry (soft delete)
     *
     * @param int $weightId
     * @return bool
     */
    public function delete(int $weightId): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE weights SET is_deleted = 1, updated_on = ? WHERE weight_id = ?',
            [$now, $weightId]
        );

        return true;
    }

    /**
     * Delete weight entry by UUID (soft delete)
     *
     * @param string $weightUuid
     * @return bool
     */
    public function deleteByUuid(string $weightUuid): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE weights SET is_deleted = 1, updated_on = ? WHERE weight_uuid = ?',
            [$now, $weightUuid]
        );

        return true;
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
