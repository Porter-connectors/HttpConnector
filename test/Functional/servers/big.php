<?php declare(strict_types=1);

for ($i = 0; $i < 100000; ++$i) {
    foreach (getallheaders() as $name => $value) {
        echo "$name: $value\n";
    }
}
