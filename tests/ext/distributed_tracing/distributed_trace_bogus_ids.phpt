--TEST--
Transmit distributed header information to spans
--ENV--
DD_TRACE_AUTO_FLUSH_ENABLED=0
HTTP_X_DATADOG_TRACE_ID=foo
HTTP_X_DATADOG_PARENT_ID=bar
HTTP_X_DATADOG_ORIGIN=datadog
DD_TRACE_GENERATE_ROOT_SPAN=0
DD_CODE_ORIGIN_FOR_SPANS_ENABLED=0
--FILE--
<?php

$span = DDTrace\start_span();
$span->name = 'span';
DDTrace\close_span();

var_dump(dd_trace_serialize_closed_spans());

?>
--EXPECTF--
array(1) {
  [0]=>
  array(10) {
    ["trace_id"]=>
    string(%d) "%d"
    ["span_id"]=>
    string(%d) "%d"
    ["start"]=>
    int(%d)
    ["duration"]=>
    int(%d)
    ["name"]=>
    string(4) "span"
    ["resource"]=>
    string(4) "span"
    ["service"]=>
    string(31) "distributed_trace_bogus_ids.php"
    ["type"]=>
    string(3) "cli"
    ["meta"]=>
    array(4) {
      ["_dd.origin"]=>
      string(7) "datadog"
      ["_dd.p.dm"]=>
      string(2) "-0"
      ["_dd.p.tid"]=>
      string(16) "%s"
      ["runtime-id"]=>
      string(36) "%s"
    }
    ["metrics"]=>
    array(6) {
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