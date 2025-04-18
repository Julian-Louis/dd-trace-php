#ifndef DD_INTEGRATIONS_INTEGRATIONS_H
#define DD_INTEGRATIONS_INTEGRATIONS_H
#include <php.h>
#include <stdbool.h>

#include "../ddtrace_string.h"

#define DD_TRACE_INTEGRATION_NOT_LOADED 0
#define DD_TRACE_INTEGRATION_LOADED 1
#define DD_TRACE_INTEGRATION_NOT_AVAILABLE 2

#define DDTRACE_LONGEST_INTEGRATION_NAME_LEN 16  // "symfonymessenger" FTW!

#define DD_INTEGRATIONS                                                                                                  \
    INTEGRATION(AMQP, "amqp")                                                                                            \
    INTEGRATION(CAKEPHP, "cakephp")                                                                                      \
    INTEGRATION(CODEIGNITER, "codeigniter")                                                                              \
    INTEGRATION(EXEC, "exec")                                                                                            \
    INTEGRATION(CURL, "curl")                                                                                            \
    INTEGRATION(DRUPAL, "drupal")                                                                                        \
    INTEGRATION(ELASTICSEARCH, "elasticsearch")                                                                          \
    INTEGRATION(ELOQUENT, "eloquent")                                                                                    \
    INTEGRATION_CUSTOM_ENABLED(FILESYSTEM, "filesystem", is_filesystem_enabled)                                          \
    INTEGRATION(FRANKENPHP, "frankenphp")                                                                                \
    INTEGRATION(GOOGLESPANNER, "googlespanner")                                                                          \
    INTEGRATION(GUZZLE, "guzzle")                                                                                        \
    INTEGRATION(KAFKA, "kafka")                                                                                          \
    INTEGRATION(LAMINAS, "laminas")                                                                                      \
    INTEGRATION(LARAVEL, "laravel")                                                                                      \
    INTEGRATION(LARAVELQUEUE, "laravelqueue")                                                                            \
    INTEGRATION(LOGS, "logs", "false", CALIASES("DD_LOGS_INJECTION"), .ini_change = ddtrace_alter_DD_TRACE_LOGS_ENABLED) \
    INTEGRATION(LUMEN, "lumen")                                                                                          \
    INTEGRATION(MAGENTO, "magento")                                                                                      \
    INTEGRATION(MEMCACHE, "memcache")                                                                                    \
    INTEGRATION(MEMCACHED, "memcached")                                                                                  \
    INTEGRATION(MONGO, "mongo")                                                                                          \
    INTEGRATION(MONGODB, "mongodb")                                                                                      \
    INTEGRATION(MYSQLI, "mysqli")                                                                                        \
    INTEGRATION(NETTE, "nette")                                                                                          \
    INTEGRATION(OPENAI, "openai")                                                                                        \
    INTEGRATION(PCNTL, "pcntl")                                                                                          \
    INTEGRATION(PDO, "pdo")                                                                                              \
    INTEGRATION(PHPREDIS, "phpredis")                                                                                    \
    INTEGRATION(PREDIS, "predis")                                                                                        \
    INTEGRATION(PSR18, "psr18")                                                                                          \
    INTEGRATION(RATCHET, "ratchet")                                                                                       \
    INTEGRATION(ROADRUNNER, "roadrunner")                                                                                \
    INTEGRATION(SQLSRV, "sqlsrv")                                                                                        \
    INTEGRATION(SLIM, "slim")                                                                                            \
    INTEGRATION(SWOOLE, "swoole")                                                                                        \
    INTEGRATION(SYMFONY, "symfony")                                                                                      \
    INTEGRATION(SYMFONYMESSENGER, "symfonymessenger")                                                                    \
    INTEGRATION(WEB, "web")                                                                                              \
    INTEGRATION(WORDPRESS, "wordpress")                                                                                  \
    INTEGRATION(YII, "yii")                                                                                              \
    INTEGRATION(ZENDFRAMEWORK, "zendframework")

#define INTEGRATION(id, ...) DDTRACE_INTEGRATION_##id,
#define INTEGRATION_CUSTOM_ENABLED(id, ...) INTEGRATION(id)
typedef enum { DD_INTEGRATIONS } ddtrace_integration_name;
#undef INTEGRATION

struct ddtrace_integration {
    ddtrace_integration_name name;
    char *name_ucase;
    char *name_lcase;
    size_t name_len;
    bool (*is_enabled)(void);
    bool (*is_analytics_enabled)(void);
    double (*get_sample_rate)(void);
    void *aux[10]; // Needs to be the max number of deferred loading hooks for any integration plus one NULL sentinel
};
typedef struct ddtrace_integration ddtrace_integration;

extern ddtrace_integration ddtrace_integrations[];
extern size_t ddtrace_integrations_len;

void ddtrace_integrations_minit(void);
void ddtrace_integrations_mshutdown(void);

ddtrace_integration *ddtrace_get_integration_from_string(ddtrace_string integration);

#endif  // DD_INTEGRATIONS_INTEGRATIONS_H
