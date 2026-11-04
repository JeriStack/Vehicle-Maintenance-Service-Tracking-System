<?php
// tests/Unit/WebSocketFrameTest.php
namespace Tests\Unit;

use Tests\TestCase;

class WebSocketFrameTest extends TestCase {

    /**
     * Helper to encode RFC 6455 frames
     */
    private function encodeFrame($payload, $type = 'text') {
        $frameHead = [];
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                $frameHead[0] = 129; // 0x81 (FIN + opcode 1)
                break;
            case 'close':
                $frameHead[0] = 136; // 0x88 (FIN + opcode 8)
                break;
            case 'ping':
                $frameHead[0] = 137; // 0x89 (FIN + opcode 9)
                break;
            case 'pong':
                $frameHead[0] = 138; // 0x8A (FIN + opcode 10)
                break;
            default:
                $frameHead[0] = 129;
        }

        if ($payloadLength <= 125) {
            $frameHead[1] = $payloadLength;
            $header = pack('CC', $frameHead[0], $frameHead[1]);
        } elseif ($payloadLength <= 65535) {
            $frameHead[1] = 126;
            $header = pack('CCn', $frameHead[0], $frameHead[1], $payloadLength);
        } else {
            $frameHead[1] = 127;
            $header = pack('CCNN', $frameHead[0], $frameHead[1], 0, $payloadLength);
        }

        return $header . $payload;
    }

    /**
     * Helper to decode RFC 6455 frames
     */
    private function decodeFrame($data) {
        if (strlen($data) < 2) return null;

        $unmasked = ord($data[1]) & 128;
        $length = ord($data[1]) & 127;
        $offset = 2;

        if ($length === 126) {
            if (strlen($data) < 4) return null;
            $length = unpack('n', substr($data, 2, 2))[1];
            $offset = 4;
        } elseif ($length === 127) {
            if (strlen($data) < 10) return null;
            $length = unpack('J', substr($data, 2, 8))[1];
            $offset = 10;
        }

        $masks = '';
        if ($unmasked) {
            if (strlen($data) < $offset + 4) return null;
            $masks = substr($data, $offset, 4);
            $offset += 4;
        }

        if (strlen($data) < $offset + $length) return null;

        $payload = substr($data, $offset, $length);
        $text = '';
        if ($unmasked) {
            for ($i = 0; $i < $length; ++$i) {
                $text .= $payload[$i] ^ $masks[$i % 4];
            }
        } else {
            $text = $payload;
        }

        return [
            'opcode'  => ord($data[0]) & 15,
            'payload' => $text,
            'length'  => $offset + $length
        ];
    }

    public function testRfc6455HandshakeVector() {
        // Standard RFC 6455 Section 1.3 test vector
        $clientKey = 'dGhlIHNhbXBsZSBub25jZQ==';
        $guid = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $accept = base64_encode(sha1($clientKey . $guid, true));

        $this->assertEquals('s3pPLMBiTxaQ9kYGzzhZRbK+xOo=', $accept);
    }

    public function testEncodeAndDecodeShortTextFrame() {
        $text = 'Vehicle maintenance alert: Oil change needed!';
        $encoded = $this->encodeFrame($text, 'text');
        
        // Byte 0 should be 0x81 (129)
        $this->assertEquals(129, ord($encoded[0]));
        // Byte 1 should be the exact payload length
        $this->assertEquals(strlen($text), ord($encoded[1]));

        $decoded = $this->decodeFrame($encoded);
        $this->assertEquals(1, $decoded['opcode']);
        $this->assertEquals($text, $decoded['payload']);
    }

    public function testEncodeAndDecodeMediumFrame() {
        // Payload between 126 and 65535 bytes
        $mediumText = str_repeat('ServiceRecordLog1234567890;', 20); // 540 bytes
        $this->assertTrue(strlen($mediumText) > 125);

        $encoded = $this->encodeFrame($mediumText, 'text');
        // Byte 1 should indicate 126 length extension
        $this->assertEquals(126, ord($encoded[1]));

        $decoded = $this->decodeFrame($encoded);
        $this->assertEquals($mediumText, $decoded['payload']);
    }

    public function testClientMaskedFrameDecoding() {
        $sample = 'Client real-time ping message';
        $mask = 'WXYZ';
        $maskedPayload = '';
        for ($i = 0; $i < strlen($sample); $i++) {
            $maskedPayload .= $sample[$i] ^ $mask[$i % 4];
        }

        // 129 = FIN (0x80) + Text opcode (0x01)
        // 128 | strlen = Mask bit enabled + length
        $clientFrame = pack('CC', 129, 128 | strlen($sample)) . $mask . $maskedPayload;

        $decoded = $this->decodeFrame($clientFrame);
        $this->assertEquals(1, $decoded['opcode']);
        $this->assertEquals($sample, $decoded['payload']);
    }

    public function testPingPongOpcodes() {
        $ping = $this->encodeFrame('heartbeat', 'ping');
        $this->assertEquals(137, ord($ping[0])); // 0x89
        $decodedPing = $this->decodeFrame($ping);
        $this->assertEquals(9, $decodedPing['opcode']);

        $pong = $this->encodeFrame('heartbeat', 'pong');
        $this->assertEquals(138, ord($pong[0])); // 0x8A
        $decodedPong = $this->decodeFrame($pong);
        $this->assertEquals(10, $decodedPong['opcode']);
    }
}

