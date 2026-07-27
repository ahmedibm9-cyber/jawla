<?php
// Simplest possible test
echo "TEST 1: passthru php --version\n";
$rc = 0;
passthru('php --version 2>&1', $rc);
echo "\nExit: $rc\n\n";

echo "TEST 2: shell_exec php --version\n";
$out = shell_exec('php --version 2>&1');
echo "Output: " . ($out ?? 'NULL') . "\n\n";

echo "TEST 3: file_put_contents to C:\\projects\\jawla\n";
$rc = file_put_contents('C:\projects\jawla\test_write.log', "hello world\n");
echo "Wrote: $rc bytes\n";
if (file_exists('C:\projects\jawla\test_write.log')) {
    echo "File exists, content: " . file_get_contents('C:\projects\jawla\test_write.log');
} else {
    echo "File does not exist\n";
}
echo "\n";

echo "TEST 4: cwd test\n";
echo "getcwd(): " . getcwd() . "\n";
echo "chdir test...\n";
$chdir_ok = chdir('C:\projects\jawla');
echo "chdir: " . ($chdir_ok ? 'OK' : 'FAILED') . "\n";
echo "new cwd: " . getcwd() . "\n";
