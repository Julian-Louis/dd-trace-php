--TEST--
Push address gets blocked even when within a hook
--INI--
extension=ddtrace.so
datadog.appsec.enabled=1
--FILE--
<?php
use function datadog\appsec\testing\{rinit,rshutdown};
use function datadog\appsec\push_addresses;

include __DIR__ . '/inc/mock_helper.php';

$helper = Helper::createInitedRun([
    response_list(response_request_init([[['ok', []]]])),
    response_list(response_request_exec([[['block', ['status_code' => '404', 'type' => 'html']]], ['{"found":"attack"}','{"another":"attack"}']])),
]);
rinit();

class SomeIntegration {
    public function init()
    {
        DDTrace\install_hook("ltrim", self::hooked_function(), null);
    }

    private static function hooked_function()
    {
        return static function (DDTrace\HookData $hook) {
              push_addresses(["server.request.path_params", ["some" => "params", "more" => "parameters"]]);
              var_dump("This should be executed");
        };
    }
}

$integration = new SomeIntegration();
$integration->init();
echo "Something here to force partially booking";
var_dump(ltrim("     Calling wrapped function"));
var_dump("THIS SHOULD NOT GET IN THE OUTPUT");
?>
--EXPECTHEADERS--
Content-type: text/html; charset=UTF-8
--EXPECTF--
Something here to force partially booking