--TEST--
Check the library config files
--SKIPIF--
<?php
copy(__DIR__.'/local_config.yaml', '/tmp/test_c_local_config_overloads_ini.yaml');
?>
--INI--
datadog.service=service_from_ini
datadog.env=env_from_ini
datadog.dynamic_instrumentation.enabled=false
--ENV--
_DD_TEST_LIBRARY_CONFIG_FLEET_FILE=/foo
_DD_TEST_LIBRARY_CONFIG_LOCAL_FILE=/tmp/test_c_local_config_overloads_ini.yaml
--FILE--
<?php

function to_str($val) {
    return $val ? "true" : "false";
}

echo 'DD_SERVICE: '.dd_trace_env_config("DD_SERVICE")."\n";
echo 'DD_ENV: '.dd_trace_env_config("DD_ENV")."\n";

// System INI
echo 'DD_DYNAMIC_INSTRUMENTATION_ENABLED: '.to_str(dd_trace_env_config("DD_DYNAMIC_INSTRUMENTATION_ENABLED"))."\n";

dd_trace_internal_fn("finalize_telemetry");

?>
--EXPECT--
DD_SERVICE: service_from_local_config
DD_ENV: env_from_local_config
DD_DYNAMIC_INSTRUMENTATION_ENABLED: true
