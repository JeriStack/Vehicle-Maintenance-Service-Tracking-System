<?php
// tests/Unit/WebSocketHelperTest.php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/websocket.php';
require_once __DIR__ . '/../../helpers/websocket_helper.php';

class WebSocketHelperTest extends TestCase {

    public function testNotifyUserPayloadStructure() {
        $reflection = new \ReflectionClass(\WebSocketHelper::class);
        $method = $reflection->getMethod('notifyUser');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());

        // Verify parameter names
        $params = array_map(function($p) { return $p->getName(); }, $method->getParameters());
        $this->assertEquals(['userId', 'title', 'message', 'type', 'extra'], $params);
    }

    public function testNotifyAdminSignature() {
        $reflection = new \ReflectionClass(\WebSocketHelper::class);
        $method = $reflection->getMethod('notifyAdmin');

        $this->assertTrue($method->isStatic());
        $params = array_map(function($p) { return $p->getName(); }, $method->getParameters());
        $this->assertEquals(['event', 'data'], $params);
    }

    public function testNotificationReadSignature() {
        $reflection = new \ReflectionClass(\WebSocketHelper::class);
        $method = $reflection->getMethod('notificationRead');

        $this->assertTrue($method->isStatic());
        $params = array_map(function($p) { return $p->getName(); }, $method->getParameters());
        $this->assertEquals(['userId', 'notificationId'], $params);
    }

    public function testServiceAndVehicleUpdatedSignatures() {
        $reflection = new \ReflectionClass(\WebSocketHelper::class);

        $serviceMethod = $reflection->getMethod('serviceUpdated');
        $this->assertTrue($serviceMethod->isStatic());
        $this->assertEquals(['userId', 'serviceData'], array_map(function($p) { return $p->getName(); }, $serviceMethod->getParameters()));

        $vehicleMethod = $reflection->getMethod('vehicleUpdated');
        $this->assertTrue($vehicleMethod->isStatic());
        $this->assertEquals(['userId', 'vehicleData'], array_map(function($p) { return $p->getName(); }, $vehicleMethod->getParameters()));
    }

    public function testWebSocketConstantsDefined() {
        $this->assertTrue(defined('WS_HOST'));
        $this->assertTrue(defined('WS_PORT'));
        $this->assertTrue(defined('WS_SECRET_KEY'));
        $this->assertEquals(8080, WS_PORT);
        $this->assertStringContainsString('vms_websocket_secure_token_', WS_SECRET_KEY);
    }
}

