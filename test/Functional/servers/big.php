<?php
for ($i = 0; $i < 100000; ++$i) {
    foreach (getallheaders() as $name => $value) {
        echo "$name: $value\n";
    }
}
