<?php
/** @var string $message */
fwrite(STDERR, "ERROR 404: Page not found\n");
fwrite(STDERR, ($message ?? 'The page you requested was not found.') . "\n");
