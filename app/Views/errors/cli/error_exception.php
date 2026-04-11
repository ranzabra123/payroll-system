<?php
/** @var string $type */
/** @var string|int $code */
/** @var string $message */
/** @var string $file */
/** @var int|string $line */
fwrite(STDERR, "ERROR: " . ($type ?? 'Exception') . " (" . (string) ($code ?? 500) . ")\n");
fwrite(STDERR, ($message ?? 'Unexpected error.') . "\n");
fwrite(STDERR, "In " . ($file ?? 'unknown') . ':' . (string) ($line ?? '?') . "\n");
