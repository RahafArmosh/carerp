<?php

$routeFiles = array_merge(
    glob(__DIR__ . '/routes/*.php') ?: [],
    glob(__DIR__ . '/Modules/*/Routes/*.php') ?: []
);

$names = [];
$uris = [];

foreach ($routeFiles as $file) {
    $relative = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
    $fileLines = explode("\n", file_get_contents($file));

    foreach ($fileLines as $num => $line) {
        if (!preg_match('/^\s*\/\//', $line) && preg_match('/\/\/.*Route::/', $line)) {
            continue;
        }
        if (preg_match('/^\s*\/\//', trim($line)) || preg_match('/^\s*\/\*|\*\//', trim($line))) {
            continue;
        }
        if (!preg_match('/Route::(get|post|put|patch|delete|any|match|resource)\s*\(/i', $line)) {
            continue;
        }

        $lineNo = $num + 1;
        $block = $line;
        $j = $num;
        while ($j + 1 < count($fileLines) && !preg_match('/;\s*$/', $block)) {
            $j++;
            $block .= "\n" . $fileLines[$j];
        }

        if (preg_match('/^\s*\/\//m', $block)) {
            continue;
        }

        $method = strtolower(preg_match('/Route::(\w+)/i', $line, $m) ? $m[1] : 'unknown');
        $uri = null;
        if (preg_match("/Route::\w+\s*\(\s*['\"]([^'\"]+)['\"]/", $block, $um)) {
            $uri = $um[1];
        } elseif (preg_match('/Route::resource\s*\(\s*[\'"]([^\'"]+)[\'"]/', $block, $um)) {
            $uri = $um[1];
            $method = 'resource';
        }

        $name = null;
        if (preg_match("/->name\s*\(\s*['\"]([^'\"]+)['\"]/", $block, $nm)) {
            $name = $nm[1];
        }

        $entry = [
            'file' => $relative,
            'line' => $lineNo,
            'method' => $method,
            'uri' => $uri ?? '(unknown)',
            'name' => $name,
        ];

        if ($name !== null) {
            $names[$name][] = $entry;
        }
        if ($uri !== null) {
            $uris[$method . ' ' . $uri][] = $entry;
        }
    }
}

echo "DUPLICATE ROUTE NAMES (same name, 2+ registrations)\n";
echo str_repeat('=', 60) . "\n\n";
foreach ($names as $name => $entries) {
    if (count($entries) < 2) {
        continue;
    }
    $methods = array_unique(array_column($entries, 'method'));
    $urisList = array_unique(array_column($entries, 'uri'));
    $sameUri = count($urisList) === 1 && count($methods) === 1;
    $flag = $sameUri ? ' [SAME URI+METHOD]' : (count($methods) > 1 && count($urisList) === 1 ? ' [GET+POST OK?]' : ' [DIFF URI]');
    echo "{$name}{$flag}\n";
    foreach ($entries as $e) {
        echo "  {$e['file']}:{$e['line']}  {$e['method']} {$e['uri']}\n";
    }
    echo "\n";
}

echo "\nDUPLICATE URI + METHOD (last registered wins)\n";
echo str_repeat('=', 60) . "\n\n";
foreach ($uris as $key => $entries) {
    if (count($entries) < 2) {
        continue;
    }
    echo "{$key}\n";
    foreach ($entries as $e) {
        $n = $e['name'] ? " ({$e['name']})" : '';
        echo "  {$e['file']}:{$e['line']}{$n}\n";
    }
    echo "\n";
}
