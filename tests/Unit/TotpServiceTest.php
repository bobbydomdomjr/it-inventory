<?php

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    public function test_rfc_6238_sha1_six_digit_test_vector(): void
    {
        $service = new TotpService();
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $service->codeAt($secret, 1));
    }

    public function test_totp_verification_accepts_the_current_time_window(): void
    {
        $service = new TotpService();
        $secret = $service->generateSecret();
        $timestamp = 1_700_000_000;
        $code = $service->codeAt($secret, intdiv($timestamp, 30));

        $this->assertTrue($service->verify($secret, $code, $timestamp));
        $this->assertFalse($service->verify($secret, 'abcdef', $timestamp));
    }
}
