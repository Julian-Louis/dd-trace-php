--TEST--
Latest sdk values take priority
--INI--
extension=ddtrace.so
--ENV--
DD_APPSEC_ENABLED=1
--FILE--
<?php
use function datadog\appsec\testing\root_span_get_meta;
use function datadog\appsec\track_user_signup_event;
include __DIR__ . '/inc/ddtrace_version.php';

ddtrace_version_at_least('0.79.0');

track_user_signup_event("1234", ["value" => "something-from-automated"], true); //Automated
track_user_signup_event("Admin", ["value" => "something-from-sdk"]); //Sdk
track_user_signup_event("OtherUser", ["value" => "something-from-sdk-2"]); //Sdk
track_user_signup_event("456", ["value" => "something-from-automated-2"], true); //Automated

echo "root_span_get_meta():\n";
print_r(root_span_get_meta());
?>
--EXPECTF--
root_span_get_meta():
Array
(
    [runtime-id] => %s
    [usr.id] => OtherUser
    [_dd.appsec.events.users.signup.auto.mode] => identification
    [appsec.events.users.signup.track] => true
    [_dd.appsec.events.users.signup.sdk] => true
    [appsec.events.users.signup.value] => something-from-sdk-2
)
