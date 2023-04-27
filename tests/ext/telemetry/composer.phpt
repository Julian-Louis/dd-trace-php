--TEST--
Read telemetry via composer
--SKIPIF--
<?php
if (getenv('PHP_PEAR_RUNTESTS') === '1') die("skip: pecl run-tests does not support {PWD}");
?>
--ENV--
DD_TRACE_GENERATE_ROOT_SPAN=0
--INI--
datadog.trace.agent_url=file://{PWD}/composer-telemetry.out
--FILE--
<?php

DDTrace\start_span();

include __DIR__ . '/vendor/autoload.php';

DDTrace\close_span();

dd_trace_internal_fn("finalize_telemetry");

usleep(100000);
foreach (file(__DIR__ . '/composer-telemetry.out') as $l) {
    if ($l) {
        $json = json_decode($l, true);
        $batch = $json["request_type"] == "message-batch" ? $json["payload"] : [$json];
        foreach ($batch as $json) {
            if ($json["request_type"] == "app-dependencies-loaded") {
                print_r($json["payload"]);
            }
        }
    }
}

?>
--EXPECT--
Included
Array
(
    [dependencies] => Array
        (
            [0] => Array
                (
                    [name] => datadog/dd-trace
                    [version] => dev-master
                )

        )

)
--CLEAN--
<?php

@unlink(__DIR__ . '/composer-telemetry.out');