<?php
declare(strict_types=1);

namespace Database\Seeders;

use PDO;
use VMS\Core\Database;

/**
 * Class MaintenanceTypeSeeder
 *
 * Seeds baseline automotive maintenance types into the global catalog idempotently
 * using stable alphanumeric codes as the immutable business identity.
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
                'code' => 'oil-change',
                'name' => 'Oil & Filter Change',
                'slug' => 'oil-change',
                'description' => 'Engine oil replacement and new oil filter installation',
                'default_interval_days' => 180,
                'default_interval_distance_km' => 5000,
            ],
            [
                'code' => 'tire-rotation',
                'name' => 'Tire Rotation & Balance',
                'slug' => 'tire-rotation',
                'description' => 'Rotate and balance all four tires to ensure even tread wear',
                'default_interval_days' => 180,
                'default_interval_distance_km' => 10000,
            ],
            [
                'code' => 'brake-check',
                'name' => 'Brake Inspection & Service',
                'slug' => 'brake-check',
                'description' => 'Inspect brake pads, rotors, drums, and brake fluid lines',
                'default_interval_days' => 365,
                'default_interval_distance_km' => 20000,
            ],
            [
                'code' => 'battery-replacement',
                'name' => 'Battery Replacement & Test',
                'slug' => 'battery-replacement',
                'description' => 'Inspect battery terminals, charge voltage, and alternator output',
                'default_interval_days' => 730,
                'default_interval_distance_km' => 20000,
            ],
            [
                'code' => 'engine-service',
                'name' => 'Engine Service & Tune-up',
                'slug' => 'engine-service',
                'description' => 'Replace engine spark plugs, inspect filters, and diagnostic check',
                'default_interval_days' => 365,
                'default_interval_distance_km' => 15000,
            ],
            [
                'code' => 'transmission-service',
                'name' => 'Transmission Fluid Service',
                'slug' => 'transmission-service',
                'description' => 'Drain and refill transmission fluid, inspect gaskets and seals',
                'default_interval_days' => 1095,
                'default_interval_distance_km' => 60000,
            ],
            [
                'code' => 'vehicle-inspection',
                'name' => 'Comprehensive Vehicle Inspection',
                'slug' => 'vehicle-inspection',
                'description' => 'Multi-point safety inspection covering chassis, suspension, lights, and steering',
                'default_interval_days' => 365,
                'default_interval_distance_km' => 20000,
            ],
            [
                'code' => 'other',
                'name' => 'Other Maintenance',
                'slug' => 'other',
                'description' => 'General vehicle repair, custom servicing, or miscellaneous bodywork',
                'default_interval_days' => null,
                'default_interval_distance_km' => null,
            ],
        ];

        $stmt = $this->db->prepare("
            INSERT INTO maintenance_types (code, name, slug, description, default_interval_days, default_interval_distance_km, is_active)
            VALUES (:code, :name, :slug, :description, :days, :dist, 1)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                slug = VALUES(slug),
                description = VALUES(description),
                default_interval_days = VALUES(default_interval_days),
                default_interval_distance_km = VALUES(default_interval_distance_km),
                is_active = 1
        ");

        $seededCount = 0;
        foreach ($types as $item) {
            $stmt->execute([
                ':code'        => $item['code'],
                ':name'        => $item['name'],
                ':slug'        => $item['slug'],
                ':description' => $item['description'],
                ':days'        => $item['default_interval_days'],
                ':dist'        => $item['default_interval_distance_km'],
            ]);
            $seededCount++;
        }

        return $seededCount;
    }
}
