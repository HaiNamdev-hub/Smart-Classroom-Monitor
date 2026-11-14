<?php

declare(strict_types=1);

if ($argc < 2 || $argc > 3) {
    fwrite(STDERR, "Usage: php verify-sqlite.php <database.sqlite> [expected-submissions]\n");
    exit(2);
}

$databasePath = realpath($argv[1]);
$expectedSubmissions = isset($argv[2]) ? filter_var($argv[2], FILTER_VALIDATE_INT) : 0;
$allowedRoot = realpath(__DIR__ . '/../be/database/performance-test');
if ($expectedSubmissions === false || $expectedSubmissions < 0 || $expectedSubmissions > 100
    || !$databasePath || !$allowedRoot || !str_starts_with(strtolower($databasePath), strtolower($allowedRoot . DIRECTORY_SEPARATOR))) {
    fwrite(STDERR, "Refusing to inspect a database outside the dedicated performance-test directory.\n");
    exit(3);
}

try {
    $pdo = new PDO('sqlite:' . $databasePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('PRAGMA query_only = ON');
    $marker = $pdo->query("SELECT dataset_key, student_count FROM performance_test_metadata")->fetch(PDO::FETCH_ASSOC);
    if (!$marker || $marker['dataset_key'] !== 'SCM_PERF_TEST_DATASET_V1' || (int) $marker['student_count'] !== 100) {
        throw new RuntimeException('Test-dataset marker missing or unexpected.');
    }

    $counts = [];
    foreach ([
        'users' => "SELECT count(*) FROM nguoi_dung WHERE email LIKE '%@example.test'",
        'student_profiles' => "SELECT count(*) FROM sinh_vien WHERE ma_sinh_vien LIKE 'PERF%'",
        'class_members' => "SELECT count(*) FROM thanh_vien_lop WHERE trang_thai = 'dang_hoc'",
        'classes' => 'SELECT count(*) FROM lop_hoc WHERE ma_lop LIKE \'PERF-CLASS-%\'',
        'exams' => "SELECT count(*) FROM bai_tap WHERE tieu_de = 'Disposable Performance Exam'",
        'questions' => "SELECT count(*) FROM cau_hoi WHERE loai_cau_hoi = 'trac_nghiem' AND diem = 10",
        'options' => "SELECT count(*) FROM dap_an WHERE noi_dung LIKE 'Synthetic option %'",
        'attempts' => 'SELECT count(*) FROM bai_kiem_tra_luot_lam',
        'submissions' => 'SELECT count(*) FROM bai_nop',
        'submitted_answers' => 'SELECT count(*) FROM cau_tra_loi',
        'full_scores' => 'SELECT count(*) FROM diem WHERE diem = 10',
    ] as $name => $sql) {
        $counts[$name] = (int) $pdo->query($sql)->fetchColumn();
    }

    $expected = [
        'users' => 101,
        'student_profiles' => 100,
        'class_members' => 100,
        'classes' => 1,
        'exams' => 1,
        'questions' => 1,
        'options' => 4,
        'attempts' => 100,
        'submissions' => $expectedSubmissions,
        'submitted_answers' => $expectedSubmissions,
        'full_scores' => $expectedSubmissions,
    ];
    foreach ($expected as $name => $count) {
        if ($counts[$name] !== $count) {
            throw new RuntimeException("Unexpected {$name} count: {$counts[$name]} (expected {$count}).");
        }
    }

    foreach ($counts as $name => $count) {
        fwrite(STDOUT, "{$name}={$count}\n");
    }
    fwrite(STDOUT, "ISOLATED_SQLITE_DATASET=PASS\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'ISOLATION_CHECK=FAIL: ' . $error->getMessage() . "\n");
    exit(4);
}
