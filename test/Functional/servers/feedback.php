<?php
echo "$_SERVER[REQUEST_METHOD] $_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI] $_SERVER[SERVER_PROTOCOL]\n";

foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}

foreach ($_COOKIE as $name => $value) {
    echo "Cookie: $name=$value\n";
}

echo "\n", file_get_contents('php://input');
