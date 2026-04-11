<?php
/** @var string $message */
fwrite(STDERR, "ERROR: Application error\n");
fwrite(STDERR, ($message ?? 'An unexpected error occurred.') . "\n");
