<?php
declare(strict_types=1);

namespace Tests\Unit;

use ReflectionClass;
use RuntimeException;
use Tests\TestCase;
use VMS\Security\OwnershipGuard;

/**
 * Class OwnershipGuardTest
 *
 * Verifies owner-scoped resource authorization guard logic and IDOR prevention.
 */
class OwnershipGuardTest extends TestCase
{
    public function testInvalidIdValuesReturnNullSafely(): void
    {
        $mockPdo = $this->createMockPdo();

        $this->assertNull(OwnershipGuard::getOwnedVehicle($mockPdo, 0, 1));
        $this->assertNull(OwnershipGuard::getOwnedVehicle($mockPdo, 1, -5));
        $this->assertNull(OwnershipGuard::getOwnedServiceRecord($mockPdo, -1, 1));
        $this->assertNull(OwnershipGuard::getUserNotification($mockPdo, 0, 0));
        $this->assertNull(OwnershipGuard::getOwnedMaintenanceSchedule($mockPdo, -2, 1));
    }

    public function testAssertVehicleOwnershipThrowsOnUnauthorized(): void
    {
        $mockPdo = $this->createMockPdo();
        $this->expectException(RuntimeException::class);

        // Vehicle ID 0 will return null, triggering RuntimeException
        OwnershipGuard::assertVehicleOwnership($mockPdo, 0, 1);
    }

    public function testAssertServiceRecordOwnershipThrowsOnUnauthorized(): void
    {
        $mockPdo = $this->createMockPdo();
        $this->expectException(RuntimeException::class);

        // Service Record ID 0 will return null, triggering RuntimeException
        OwnershipGuard::assertServiceRecordOwnership($mockPdo, 0, 1);
    }

    private function createMockPdo(): \PDO
    {
        $reflector = new ReflectionClass(\PDO::class);
        /** @var \PDO */
        return $reflector->newInstanceWithoutConstructor();
    }
}
