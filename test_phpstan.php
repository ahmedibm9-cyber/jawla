<?php
chdir('C:\projects\jawla');
// Try clearing cache and running fresh
passthru('php -d memory_limit=4G vendor\bin\phpstan clear-result-cache 2>&1', $rc1);
echo "clear-result-cache exit: $rc1\n\n";

passthru('php -d memory_limit=4G vendor\bin\phpstan analyse --no-progress --error-format=table 2>&1', $rc2);
echo "analyse exit: $rc2\n";
