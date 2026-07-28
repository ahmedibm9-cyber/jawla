<?php

// Capture phpstan output via a separate process
$cmd = 'C:\\projects\\jawla\\vendor\\bin\\phpstan analyse --no-progress --error-format=raw 2>&1';
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$env = ['PATH' => getenv('PATH'), 'HOME' => getenv('HOME')];
$proc = proc_open(['php', '-d', 'memory_limit=4G', 'vendor/bin/phpstan', 'analyse', '--no-progress', '--error-format=raw'], $descriptors, $pipes, 'C:\\projects\\jawla', $env);
if (is_resource($proc)) {
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($proc);
    echo "Exit: $exit\n";
    echo 'STDOUT length: '.strlen($stdout)."\n";
    echo 'STDERR length: '.strlen($stderr)."\n";
    if (strlen($stdout) > 0) {
        echo "STDOUT:\n$stdout\n";
    }
    if (strlen($stderr) > 0) {
        echo "STDERR:\n$stderr\n";
    }
}
