--TEST--
IPv6 address in referrer header
--ENV--
DD_TRACE_GENERATE_ROOT_SPAN=0
DD_CODE_ORIGIN_FOR_SPANS_ENABLED=0
SERVER_NAME=localhost:8888
SCRIPT_NAME=/foo.php
REQUEST_URI=/foo
METHOD=GET
HTTP_REFERER=https://[2001:db8::1]:8080/path?query=123#fragment
--GET--
foo=bar
--FILE--
<?php
DDTrace\start_span();
DDTrace\close_span();
$spans = dd_trace_serialize_closed_spans();
var_dump($spans[0]['meta']);
?>
--EXPECTF--
array(7) {
  ["_dd.p.dm"]=>
  string(2) "-0"
  ["_dd.p.tid"]=>
  string(16) "%s"
  ["http.method"]=>
  string(3) "GET"
  ["http.referrer_hostname"]=>
  string(13) "[2001:db8::1]"
  ["http.status_code"]=>
  string(3) "200"
  ["http.url"]=>
  string(25) "http://localhost:8888/foo"
  ["runtime-id"]=>
  string(36) "%s"
}