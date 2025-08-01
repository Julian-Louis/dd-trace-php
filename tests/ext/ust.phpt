--TEST--
Foo
--ENV--
DD_SERVICE=version_test
DD_VERSION=5.2.0
DD_ENV=env_test
DD_TRACE_AUTO_FLUSH_ENABLED=0
DD_TRACE_GENERATE_ROOT_SPAN=0
DD_CODE_ORIGIN_FOR_SPANS_ENABLED=0
--FILE--
<?php

$s1 = \DDTrace\start_trace_span();
$s1->name = "s1";
\DDTrace\close_span();

$s2 = \DDTrace\start_trace_span();
$s2->name = "s2";
$s2->service = "no dd_service";
\DDTrace\close_span();

var_dump(dd_trace_serialize_closed_spans());

?>
--EXPECTF--
array(2) {
  [0]=>
  array(%d) {
    ["trace_id"]=>
    string(%d) "%s"
    ["span_id"]=>
    string(%d) "%s"
    ["start"]=>
    int(%d)
    ["duration"]=>
    int(%d)
    ["name"]=>
    string(2) "s2"
    ["resource"]=>
    string(2) "s2"
    ["service"]=>
    string(13) "no dd_service"
    ["type"]=>
    string(3) "cli"
    ["meta"]=>
    array(%d) {
      ["_dd.p.dm"]=>
      string(2) "-0"
      ["_dd.p.tid"]=>
      string(16) "%s"
      ["env"]=>
      string(8) "env_test"
      ["runtime-id"]=>
      string(36) "%s"
    }
    ["metrics"]=>
    array(%d) {
      ["_dd.agent_psr"]=>
      float(1)
      ["_sampling_priority_v1"]=>
      float(1)
      ["php.compilation.total_time_ms"]=>
      float(%f)
      ["php.memory.peak_real_usage_bytes"]=>
      float(%f)
      ["php.memory.peak_usage_bytes"]=>
      float(%f)
      ["process_id"]=>
      float(%f)
    }
  }
  [1]=>
  array(%d) {
    ["trace_id"]=>
    string(%d) "%s"
    ["span_id"]=>
    string(%d) "%s"
    ["start"]=>
    int(%d)
    ["duration"]=>
    int(%d)
    ["name"]=>
    string(2) "s1"
    ["resource"]=>
    string(2) "s1"
    ["service"]=>
    string(12) "version_test"
    ["type"]=>
    string(3) "cli"
    ["meta"]=>
    array(%d) {
      ["_dd.p.dm"]=>
      string(2) "-0"
      ["_dd.p.tid"]=>
      string(16) "%s"
      ["env"]=>
      string(8) "env_test"
      ["runtime-id"]=>
      string(36) "%s"
      ["version"]=>
      string(5) "5.2.0"
    }
    ["metrics"]=>
    array(%d) {
      ["_dd.agent_psr"]=>
      float(1)
      ["_sampling_priority_v1"]=>
      float(1)
      ["php.compilation.total_time_ms"]=>
      float(%f)
      ["php.memory.peak_real_usage_bytes"]=>
      float(%f)
      ["php.memory.peak_usage_bytes"]=>
      float(%f)
      ["process_id"]=>
      float(%f)
    }
  }
}
