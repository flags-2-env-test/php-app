<?php
/**
 * PHP consumer of oresoftware/flags-2-env.
 *
 * Asserts the contract in EXPECTED.md. Exits non-zero on the first
 * disagreement, which is what makes `docker run` the whole test.
 */

$repo = dirname(__DIR__);
$vendor = $repo . '/.vendor/.zed/oresoftware/flags-2-env';

require $vendor . '/clients/php/lib.php';

$config = $repo . '/.cli-flags.toml';

// The PHP client takes the library path as a constructor argument rather than
// reading the environment, so the variable the Dockerfile exports is resolved
// here explicitly.
$library = getenv('FLAGS2ENV_NATIVE_LIB') ?: $vendor . '/build/libflags2env.so';
$sdk = new Flags2Env($library);

$defaults = ['PORT' => '3000', 'DEBUG' => 'false', 'APP_ENV' => 'development', 'COLOR' => 'true'];
$overridden = ['PORT' => '8181', 'DEBUG' => 'true', 'APP_ENV' => 'production', 'COLOR' => 'true'];

$cases = [
    ['defaults',     [],                                                                   $defaults],
    ['long flags',   ['--port', '8181', '--debug=t', '--mode', 'production'],               $overridden],
    ['short flags',  ['-p', '8181', '-d', '1', '--env', 'production'],                      $overridden],
    ['long aliases', ['--listen-port', '8181', '--debug', '1', '--mode', 'production'],     $overridden],
    ['joined by =',  ['--port=8181', '--debug=yes', '--mode=production'],                   $overridden],
    ['negation',     ['--no-color'],                                                        ['COLOR' => 'false'] + $defaults],
];

$failures = 0;

foreach ($cases as [$label, $flags, $expected]) {
    $got = $sdk->parse(array_merge(['demo'], $flags), $config);

    ksort($expected);
    $normalized = $got;
    ksort($normalized);
    $ok = $normalized === $expected;
    if (!$ok) {
        $failures++;
    }

    printf("%-4s %-13s demo %s\n", $ok ? 'ok' : 'FAIL', $label, implode(' ', $flags));
    foreach (array_keys($expected) as $key) {
        printf("       %s=%s\n", $key, $got[$key] ?? '<missing>');
    }
    if (!$ok) {
        fwrite(STDERR, '       expected ' . json_encode($expected) . PHP_EOL);
        fwrite(STDERR, '       got      ' . json_encode($normalized) . PHP_EOL);
    }
}

if ($failures > 0) {
    fwrite(STDERR, sprintf("\nphp-app: %d of %d cases disagree with the contract\n", $failures, count($cases)));
    exit(1);
}

printf("\nphp-app OK: %d cases, via FFI into oresoftware/flags-2-env\n", count($cases));
