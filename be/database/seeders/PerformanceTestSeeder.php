<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Disposable-only fixture seeder. Never use DatabaseSeeder for performance runs. */
class PerformanceTestSeeder extends Seeder
{
    private const STUDENT_COUNT = 100;
    private const TEST_PASSWORD = 'PerfOnly-2026-Disposable!';

    public function run(): void
    {
        $databasePath = $this->assertIsolatedDatabase();
        $fixturePath = dirname(base_path()) . DIRECTORY_SEPARATOR
            . 'performance-tests' . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'students.json';

        if (file_exists($fixturePath)) {
            throw new RuntimeException('Fixture file already exists; refusing to overwrite test credentials.');
        }

        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name <> 'migrations'");
        foreach ($tables as $tableInfo) {
            $table = (string) $tableInfo->name;
            if (DB::table($table)->exists()) {
                throw new RuntimeException("Table {$table} is not empty; refusing to seed over existing data.");
            }
        }

        $fixture = DB::transaction(function () use ($fixturePath, $databasePath): array {
            $now = Carbon::now('Asia/Ho_Chi_Minh');
            $stamp = $now->format('YmdHis');

            $studentRoleId = DB::table('vai_tro')->insertGetId([
                'ten_vai_tro' => 'sinh_vien',
                'mo_ta' => 'Performance test only',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $teacherRoleId = DB::table('vai_tro')->insertGetId([
                'ten_vai_tro' => 'giang_vien',
                'mo_ta' => 'Performance test only',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $teacherUserId = DB::table('nguoi_dung')->insertGetId([
                'ho_ten' => 'Performance Test Teacher',
                'email' => "teacher-{$stamp}@example.test",
                'password' => Hash::make(self::TEST_PASSWORD),
                'vai_tro_id' => $teacherRoleId,
                'trang_thai' => 'hoat_dong',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $facultyId = DB::table('khoa')->insertGetId([
                'ma_khoa' => "PERF-{$stamp}",
                'ten_khoa' => 'Performance Test Faculty',
                'mo_ta' => 'Synthetic data only',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $majorId = DB::table('nganh')->insertGetId([
                'khoa_id' => $facultyId,
                'ma_nganh' => "PERF-MAJOR-{$stamp}",
                'ten_nganh' => 'Performance Test Major',
                'mo_ta' => 'Synthetic data only',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $studentUserIds = [];
            $studentProfileIds = [];
            $users = [];
            for ($i = 1; $i <= self::STUDENT_COUNT; $i++) {
                $number = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
                $email = "perf-student-{$stamp}-{$number}@example.test";
                $userId = DB::table('nguoi_dung')->insertGetId([
                    'ho_ten' => "Synthetic Student {$number}",
                    'email' => $email,
                    'password' => Hash::make(self::TEST_PASSWORD),
                    'vai_tro_id' => $studentRoleId,
                    'trang_thai' => 'hoat_dong',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $profileId = DB::table('sinh_vien')->insertGetId([
                    'nguoi_dung_id' => $userId,
                    'nganh_id' => $majorId,
                    'ma_sinh_vien' => "PERF{$stamp}{$number}",
                    'nam_trung_tuyen' => (int) $now->format('Y'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $studentUserIds[] = $userId;
                $studentProfileIds[] = $profileId;
                $users[] = [
                    'email' => $email,
                    'password' => self::TEST_PASSWORD,
                ];
            }

            $classId = DB::table('lop_hoc')->insertGetId([
                'ma_lop' => "PERF-CLASS-{$stamp}",
                'ten_lop' => 'Performance Test Class',
                'giang_vien_id' => $teacherUserId,
                'mo_ta' => 'Synthetic data only',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($studentUserIds as $userId) {
                DB::table('thanh_vien_lop')->insert([
                    'lop_hoc_id' => $classId,
                    'sinh_vien_id' => $userId,
                    'trang_thai' => 'dang_hoc',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $start = $now->copy()->subMinutes(5);
            $end = $now->copy()->addHours(6);
            $assignmentId = DB::table('bai_tap')->insertGetId([
                'lop_hoc_id' => $classId,
                'nguoi_tao_id' => $teacherUserId,
                'tieu_de' => 'Disposable Performance Exam',
                'mo_ta' => 'Synthetic performance fixture; one question worth 10 points.',
                'thoi_gian_bat_dau' => $start,
                'thoi_gian_ket_thuc' => $end,
                'han_nop' => $end,
                'cho_phep_nop_tre' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $questionId = DB::table('cau_hoi')->insertGetId([
                'bai_tap_id' => $assignmentId,
                'noi_dung' => 'Synthetic performance test question',
                'loai_cau_hoi' => 'trac_nghiem',
                'diem' => 10,
                'thu_tu' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $answerIds = [];
            for ($i = 1; $i <= 4; $i++) {
                $answerIds[] = DB::table('dap_an')->insertGetId([
                    'cau_hoi_id' => $questionId,
                    'noi_dung' => "Synthetic option {$i}",
                    'la_dap_an_dung' => $i === 1,
                    'thu_tu' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($studentProfileIds as $profileId) {
                DB::table('bai_kiem_tra_luot_lam')->insert([
                    'bai_tap_id' => $assignmentId,
                    'sinh_vien_id' => $profileId,
                    'bat_dau_luc' => $start,
                    'created_at' => $start,
                    'updated_at' => $start,
                ]);
            }

            Schema::create('performance_test_metadata', function ($table): void {
                $table->string('dataset_key')->primary();
                $table->unsignedInteger('student_count');
                $table->timestamp('created_at');
            });
            DB::table('performance_test_metadata')->insert([
                'dataset_key' => 'SCM_PERF_TEST_DATASET_V1',
                'student_count' => self::STUDENT_COUNT,
                'created_at' => $now,
            ]);

            foreach ($users as &$user) {
                $user['baiTapId'] = $assignmentId;
                $user['answers'] = [(string) $questionId => (int) $answerIds[0]];
                $user['draftVariants'] = [
                    [(string) $questionId => (int) $answerIds[1]],
                    [(string) $questionId => (int) $answerIds[2]],
                ];
            }
            unset($user);

            return [
                'database' => $databasePath,
                'assignmentId' => $assignmentId,
                'studentCount' => self::STUDENT_COUNT,
                'users' => $users,
                'fixture' => $fixturePath,
            ];
        });

        if (!is_dir(dirname($fixturePath)) && !mkdir(dirname($fixturePath), 0700, true) && !is_dir(dirname($fixturePath))) {
            throw new RuntimeException('Could not create the ignored performance fixture directory.');
        }
        if (file_put_contents($fixturePath, json_encode($fixture['users'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
            throw new RuntimeException('Database was seeded, but fixture output failed. Preserve the disposable DB and inspect manually.');
        }
        @chmod($fixturePath, 0600);

        $this->command?->info('Created 100 synthetic students, one class, one 10-point exam, and 100 in-progress attempts.');
        $this->command?->info('Disposable database: ' . $fixture['database']);
        $this->command?->info('Private test credential fixture: ' . $fixture['fixture']);
        $this->command?->warn('The fixture contains test passwords. Do not commit or share it.');
    }

    private function assertIsolatedDatabase(): string
    {
        $expected = database_path('performance-test' . DIRECTORY_SEPARATOR . 'template.sqlite');
        $configured = (string) config('database.connections.sqlite.database');
        $normalize = static fn (string $path): string => strtolower(str_replace('\\', '/', realpath($path) ?: $path));

        if (app()->environment() !== 'performance'
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.url')
            || $normalize($configured) !== $normalize($expected)
            || !is_file($expected)) {
            throw new RuntimeException('Refusing to seed: expected APP_ENV=performance and the exact dedicated SQLite template path.');
        }

        $database = DB::connection()->getDatabaseName();
        if ($normalize($database) !== $normalize($expected)) {
            throw new RuntimeException('Refusing to seed: active PDO database path does not match the isolated template.');
        }

        return $expected;
    }
}
