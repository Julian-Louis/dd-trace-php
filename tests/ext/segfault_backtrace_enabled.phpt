--TEST--
Dump backtrace when segmentation fault signal is raised and config enables it
--SKIPIF--
<?php
if (!extension_loaded('posix')) die('skip: posix extension required');
if (getenv('SKIP_ASAN') || getenv('USE_ZEND_ALLOC') === '0') die("skip: intentionally causes segfaults");
if (getenv('PHP_PEAR_RUNTESTS') === '1') die("skip: pecl run-tests does not support XFAIL");
if (file_exists("/etc/os-release") && preg_match("/alpine/i", file_get_contents("/etc/os-release"))) die("skip Unsupported LIBC");
?>
--XFAIL--
Code called in the segv handler is not signal safe, this will sometimes result in a segfault.
--FILE--
<?php

posix_setrlimit(POSIX_RLIMIT_CORE, 0, 0);

posix_kill(posix_getpid(), 11); // boom

?>
--EXPECTREGEX--
\[ddtrace] \[error] Segmentation fault
\[ddtrace] \[error] Datadog PHP Trace extension \(DEBUG MODE\)
\[ddtrace] \[error] Received Signal 11
\[ddtrace] \[error] Note: Backtrace below might be incomplete and have wrong entries due to optimized runtime
\[ddtrace] \[error] Backtrace:
.*ddtrace\.so.*
.*
