<?php
chdir('C:\projects\jawla');
// Try with --debug to see what phpstan is doing
$rc = 0;
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$process = proc_open('php -d memory_limit=4G vendor\bin\phpstan analyse app\Models\Company.php --debug --no-progress 2>&1', $descriptors, $pipes, 'C:\projects\jawla');
if (is_resource($process)) {
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $rc = proc_close($process);
    file_put_contents('C:\projects\jawla\phpstan_debug.log', "EXIT: $rc\n--- STDOUT ---\n$stdout\n--- STDERR ---\n$stderr\n");
    echo "Exit: $rc\n";
    echo "Stdout length: " . strlen($stdout) . "\n";
    echo "Stderr length: " . strlen($stderr) . "\n";
} else {
    echo "Failed to start process\n";
}
