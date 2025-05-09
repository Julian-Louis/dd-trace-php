--TEST--
Installing a live debugger span probe on a class
--SKIPIF--
<?php include __DIR__ . '/../includes/skipif_no_dev_env.inc'; ?>
<?php if (getenv('USE_ZEND_ALLOC') === '0' && !getenv("SKIP_ASAN")) die('skip timing sensitive test - valgrind is too slow'); ?>
--ENV--
DD_AGENT_HOST=request-replayer
DD_TRACE_AGENT_PORT=80
DD_TRACE_GENERATE_ROOT_SPAN=0
DD_DYNAMIC_INSTRUMENTATION_ENABLED=1
DD_REMOTE_CONFIG_POLL_INTERVAL_SECONDS=0.01
--INI--
datadog.trace.agent_test_session_token=live-debugger/span_probe_class
--FILE--
<?php

require __DIR__ . "/live_debugger.inc";

reset_request_replayer();

class Bar {
    static function foo() {
        $span = \DDTrace\active_span();
        return "{$span->name} {$span->resource}";
    }
}

function delayed() {
    class Delayed {
        static function foo() {
            $span = \DDTrace\active_span();
            return "{$span->name} {$span->resource}";
        }
    }
}

await_probe_installation(function() {
    build_span_probe(["where" => ["typeName" => "Bar", "methodName" => "foo"]]);
    build_span_probe(["where" => ["typeName" => "Delayed", "methodName" => "foo"]]);

    \DDTrace\start_span(); // submit span data
}, 2);

var_dump(Bar::foo());

delayed();
var_dump(Delayed::foo());

$dlr = new DebuggerLogReplayer;
$ordered = [];
$events = 0;
$time = time();
do {
    try {
        $log = $dlr->waitForDiagnosticsDataAndReplay();
        foreach (json_decode($log["files"]["event"]["contents"], true) as $payload) {
            $diagnostic = $payload["debugger"]["diagnostics"];
            $ordered[$diagnostic["probeId"]][$payload["timestamp"]][] = $diagnostic["status"];
            ++$events;
        }
    } catch (Exception $e) {
        // handle the timeout?
    }
} while ($events < 5 && $time > time() - 10);
ksort($ordered);
foreach ($ordered as &$value) {
    ksort($value);
    foreach ($value as &$v) {
        uasort($v, function($a, $b) { $map = ["RECEIVED" => 0, "INSTALLED" => 1, "EMITTING" => 2]; return $map[$a] - $map[$b]; });
    }
}
var_dump($log["uri"]);
var_dump($log["files"]["event"]["name"]);
foreach ($ordered as $id => $statuses) {
    print "$id: " . implode(", ", array_merge(...$statuses)) . "\n";
}

?>
--CLEAN--
<?php
require __DIR__ . "/live_debugger.inc";
reset_request_replayer();
?>
--EXPECTF--
string(23) "dd.dynamic.span Bar.foo"
string(27) "dd.dynamic.span Delayed.foo"
string(%d) "/debugger/v1/diagnostics?ddtags=debugger_version:%s,env:none,version:,runtime_id:%s,host_name:%s"
string(10) "event.json"
1: INSTALLED, EMITTING
2: RECEIVED, INSTALLED, EMITTING
