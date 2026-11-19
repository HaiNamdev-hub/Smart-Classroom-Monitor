<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ClassroomRateLimitPolicyTest extends TestCase
{
    public function test_alert_and_exam_start_limits_are_account_scoped_for_students_sharing_nat(): void
    {
        $alertLimiter = RateLimiter::limiter('exam-alert');
        $sharedIpKeys = [];
        $studentKeys = [];

        for ($studentId = 1; $studentId <= 50; $studentId++) {
            $request = Request::create('/api/sinh-vien/bai-kiem-tra/1/canh-bao-camera', 'POST', [], [], [], [
                'REMOTE_ADDR' => '10.20.30.40',
            ]);
            $request->setUserResolver(fn () => new class($studentId) {
                public function __construct(public int $id) {}
                public function getAuthIdentifier(): int { return $this->id; }
            });

            [$ipLimit, $studentLimit] = $alertLimiter($request);
            $sharedIpKeys[] = $ipLimit->key;
            $studentKeys[] = $studentLimit->key;
            $this->assertSame(1200, $ipLimit->maxAttempts);
            $this->assertSame(60, $studentLimit->maxAttempts);
        }

        $this->assertCount(1, array_unique($sharedIpKeys));
        $this->assertCount(50, array_unique($studentKeys));

        $startLimiter = RateLimiter::limiter('exam-start');
        $request = Request::create('/api/sinh-vien/bai-kiem-tra/1/bat-dau', 'POST', [], [], [], [
            'REMOTE_ADDR' => '10.20.30.40',
        ]);
        $request->setUserResolver(fn () => new class {
            public function getAuthIdentifier(): int { return 1; }
        });
        [$startIpLimit, $startStudentLimit] = $startLimiter($request);
        $this->assertSame(120, $startIpLimit->maxAttempts);
        $this->assertSame(10, $startStudentLimit->maxAttempts);
    }
}
