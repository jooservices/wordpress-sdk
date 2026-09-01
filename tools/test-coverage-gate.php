<?php

declare(strict_types=1);

/**
 * Coverage gate for the Unit suite.
 *
 * Fails when:
 * 1. aggregate statement coverage is below MINIMUM_STATEMENT_COVERAGE, or
 * 2. any coverable file, class, or method in src/ sits at 0% coverage
 *    without a documented exclusion.
 */

const MINIMUM_STATEMENT_COVERAGE = 90.0;

const COVERAGE_FILE = __DIR__ . '/../coverage.xml';

/**
 * @var array<string, string> documented zero-coverage exclusions:
 *      'file' | 'class' | 'method' => reason
 */
const EXCLUSIONS = [];

$xml = simplexml_load_file(COVERAGE_FILE);
if ($xml === false) {
    fwrite(STDERR, 'Could not load coverage.xml. Run composer test:coverage first.' . PHP_EOL);
    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$pct = $statements === 0 ? 100.0 : ($covered / $statements) * 100.0;

echo sprintf(
    "Coverage: %.2f%% (%d/%d statements)%s",
    $pct,
    $covered,
    $statements,
    PHP_EOL,
);

if ($pct < MINIMUM_STATEMENT_COVERAGE) {
    fwrite(STDERR, sprintf(
        "Aggregate coverage %.2f%% is below the %.0f%% gate.%s",
        $pct,
        MINIMUM_STATEMENT_COVERAGE,
        PHP_EOL,
    ));
    exit(1);
}

$failures = [];

foreach ($xml->project->file as $file) {
    $name = (string) $file['name'];
    if (! str_contains($name, '/src/')) {
        continue;
    }

    $key = 'file|' . basename($name);

    if ((int) $file->metrics['statements'] > 0 && (int) $file->metrics['coveredstatements'] === 0 && ! isset(EXCLUSIONS[$key])) {
        $failures[] = "File with 0% coverage: {$name}";
    }

    foreach ($file->class as $class) {
        $classKey = 'class|' . (string) $class['name'];
        $classStatements = (int) $class->metrics['statements'];
        $classCovered = (int) $class->metrics['coveredstatements'];

        if ($classStatements > 0 && $classCovered === 0 && ! isset(EXCLUSIONS[$classKey])) {
            $failures[] = "Class with 0% coverage: {$class['name']}";
        }

        foreach ($class->method as $method) {
            $methodKey = 'method|' . (string) $class['name'] . '::' . (string) $method['name'];
            $methodStatements = (int) $method->metrics['statements'];

            if ($methodStatements > 0 && (int) $method->metrics['coveredstatements'] === 0 && ! isset(EXCLUSIONS[$methodKey])) {
                $failures[] = "Method with 0% coverage: {$class['name']}::{$method['name']}";
            }
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, PHP_EOL . implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Coverage gate passed." . PHP_EOL;
