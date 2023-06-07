#!/usr/bin/php
<?php
while ($f = fgets(STDIN)) {
    file_put_contents("/tmp/testing.log", $f, FILE_APPEND);
}