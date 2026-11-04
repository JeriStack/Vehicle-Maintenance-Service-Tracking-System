<?php
declare(strict_types=1);

namespace Database\Seeders;

use PDO;
use VMS\Core\Database;

/**
 * Class MaintenanceTypeSeeder
 *
 * Seeds baseline automotive maintenance types into the global catalog idempotently.
 */
class MaintenanceTypeSeeder
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function run(): int
    {
        $types = [
            [
                'name' => 'Oil & Filter Change',
                'description' => 'Engine oil replacement and new oil filter installation',
                'default_interval_days' => 180,
                'default_interval_distance_km' => 5000,
            ],
            [
                'name' => 'Tire Rotation & Balance',
                'description' => 'Rotate and balance all four tires to ensure even tread wear',
                'default_interval_days' => 180,
                'default_interval_distance_km' => 10000,
            ],
            [
                'name' => 'Brake Inspection & Service',
                'description' => 'Inspect brake pads, rotors, drums, and brake fluid lines',
                'default_interval_days' => 365,
                'default_interval_distance_km' => 20000,
            ],
            [
                'name' => 'Air & Cabin Filter Replacement',
                'description' => 'Replace engine air filter and cabin air ventilation filter',
                'default_interval_days' => 365,
                'default_interval_distance_km' => 15000,
            ],
            [
                'name' => 'Battery & Charging Test',
                'description' => 'Inspect battery terminals, charge voltage, and alternator output',
                'default_interval_days' => 180,
                'default_interval_distance_km' => 10000,
            ],
            [
                'name' => 'Coolant / Radiator Flush',
                'description' => 'Drain, flush, and refill engine coolant and check radiator hoses',
                'default_interval_days' => 730,
                'default_interval_distance_km' => 40000,
            ],
            [
                'name' => 'Spark Plug Replacement',
                'description' => 'Replace spark plugs and inspect ignition coils/cables',
                'default_interval_days' => 730,
                'default_interval_distance_km' => 40000,
            ],
            [
                'name' => 'Transmission Fluid Service',
                'description' => 'Drain and refill transmission fluid, inspect gaskets and seals',
                'default_interval_days' => 1095,
                'default_interval_distance_km' => 60000,
            ],
        ];

        $stmt = $this->db->prepare("
            INSERT INTO maintenance_types (name, description, default_interval_days, default_interval_distance_km, is_active)
            VALUES (:name, :description, :days, :dist, 1)
            ON DUPLICATE KEY UPDATE 
                description = VALUES(description),
                default_interval_days = VALUES(default_interval_days),
                default_interval_distance_km = VALUES(default_interval_distance_km)
        ");

        $seededCount = 0;
        foreach ($types as $item) {
            $stmt->execute([
                ':name'        => $item['name'],
                ':description' => $item['description'],
                ':days'        => $item['default_interval_days'],
                ':dist'        => $item['default_interval_distance_km'],
            ]);
            $seededCount++;
        }

        return $seededCount;
    }
}
