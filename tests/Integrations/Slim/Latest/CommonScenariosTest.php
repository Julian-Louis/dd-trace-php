<?php

namespace DDTrace\Tests\Integrations\Slim\Latest;

use DDTrace\Tag;
use DDTrace\Tests\Common\SpanAssertion;
use DDTrace\Tests\Common\WebFrameworkTestCase;
use DDTrace\Tests\Frameworks\Util\Request\RequestSpec;

class CommonScenariosTest extends WebFrameworkTestCase
{
    public static function getAppIndexScript()
    {
        return __DIR__ . '/../../../Frameworks/Slim/Latest/public/index.php';
    }

    public static function getTestedLibrary()
    {
        return 'slim/slim';
    }

    protected static function getEnvs()
    {
        return array_merge(parent::getEnvs(), [
            'DD_SERVICE' => 'slim_test_app',
        ]);
    }

    /**
     * @dataProvider provideSpecs
     * @param RequestSpec $spec
     * @param array $spanExpectations
     * @throws \Exception
     */
    public function testScenario(RequestSpec $spec, array $spanExpectations)
    {
        $traces = $this->tracesFromWebRequest(function () use ($spec) {
            $this->call($spec);
        });

        $this->assertFlameGraph($traces, $spanExpectations);
    }

    private function wrapMiddleware(array $children, array $setError = []): SpanAssertion
    {
        if (!empty($setError)) {
            return SpanAssertion::build(
                'slim.middleware',
                'slim_test_app',
                'web',
                'Slim\\Middleware\\ErrorMiddleware'
            )->withExactTags([
                Tag::COMPONENT => 'slim'
            ])->withChildren([
                SpanAssertion::build(
                    'slim.middleware',
                    'slim_test_app',
                    'web',
                    'Slim\Middleware\RoutingMiddleware'
                )->withExactTags([
                    Tag::COMPONENT => 'slim'
                ])->withChildren([
                    SpanAssertion::build(
                        'slim.middleware',
                        'slim_test_app',
                        'web',
                        'Slim\\Views\\TwigMiddleware'
                    )->withExactTags([
                        Tag::COMPONENT => 'slim'
                    ])
                    ->withChildren($children)
                    ->withExistingTagsNames(['error.stack'])
                    ->setError(...$setError)
                ])->withExistingTagsNames(['error.stack'])->setError(...$setError),
            ])/* ->setError(...$setError) ; no error on ErrorMiddleware*/;
        } else {
            return SpanAssertion::build(
                'slim.middleware',
                'slim_test_app',
                'web',
                'Slim\\Middleware\\ErrorMiddleware'
            )->withExactTags([
                Tag::COMPONENT => 'slim'
            ])->withChildren([
                SpanAssertion::build(
                    'slim.middleware',
                    'slim_test_app',
                    'web',
                    'Slim\Middleware\RoutingMiddleware'
                )->withExactTags([
                    Tag::COMPONENT => 'slim'
                ])->withChildren([
                    SpanAssertion::build(
                        'slim.middleware',
                        'slim_test_app',
                        'web',
                        'Slim\\Views\\TwigMiddleware'
                    )->withExactTags([
                        Tag::COMPONENT => 'slim'
                    ])->withChildren($children)
                ]),
            ]);
        }
    }

    public function provideSpecs()
    {
        return $this->buildDataProvider(
            [
                'A simple GET request returning a string' => [
                    SpanAssertion::build(
                        'slim.request',
                        'slim_test_app',
                        'web',
                        'GET /simple'
                    )->withExactTags([
                        'slim.route.name' => 'simple-route',
                        'slim.route.handler' => 'Closure::__invoke',
                        'http.method' => 'GET',
                        'http.url' => 'http://localhost/simple?key=value&<redacted>',
                        'http.status_code' => '200',
                        Tag::SPAN_KIND => 'server',
                        Tag::COMPONENT => 'slim',
                        Tag::HTTP_ROUTE => '/simple',
                    ])->withChildren([
                        $this->wrapMiddleware([
                            SpanAssertion::build(
                                'slim.route',
                                'slim_test_app',
                                'web',
                                'Closure::__invoke'
                            )->withExactTags([
                                Tag::COMPONENT => 'slim',
                                'slim.route.name' => 'simple-route',
                            ])
                        ]),
                    ]),
                ],
                'A simple GET request with a view' => [
                    SpanAssertion::build(
                        'slim.request',
                        'slim_test_app',
                        'web',
                        'GET /simple_view'
                    )->withExactTags([
                        'slim.route.handler' => 'Closure::__invoke',
                        'http.method' => 'GET',
                        'http.url' => 'http://localhost/simple_view?key=value&<redacted>',
                        'http.status_code' => '200',
                        Tag::SPAN_KIND => 'server',
                        Tag::COMPONENT => 'slim',
                        Tag::HTTP_ROUTE => '/simple_view',
                    ])->withChildren([
                        $this->wrapMiddleware([
                            SpanAssertion::build(
                                'slim.route',
                                'slim_test_app',
                                'web',
                                'Closure::__invoke'
                            )->withExactTags([
                                Tag::COMPONENT => 'slim'
                            ])->withChildren([
                                SpanAssertion::build(
                                    'slim.view',
                                    'slim_test_app',
                                    'web',
                                    'simple_view.phtml'
                                )->withExactTags([
                                    Tag::COMPONENT => 'slim',
                                    'slim.view' => 'simple_view.phtml',
                                ]),
                            ]),
                        ]),
                    ]),
                ],
                'A GET request with an exception' => [
                    SpanAssertion::build(
                        'slim.request',
                        'slim_test_app',
                        'web',
                        'GET /error'
                    )->withExactTags([
                        'slim.route.handler' => 'Closure::__invoke',
                        'http.method' => 'GET',
                        'http.url' => 'http://localhost/error?key=value&<redacted>',
                        'http.status_code' => '500',
                        Tag::SPAN_KIND => 'server',
                        Tag::COMPONENT => 'slim',
                        Tag::HTTP_ROUTE => '/error',
                    ])
                    ->setError(null, null)
                    ->withChildren([
                        $this->wrapMiddleware(
                            [
                                SpanAssertion::build(
                                    'slim.route',
                                    'slim_test_app',
                                    'web',
                                    'Closure::__invoke'
                                )->withExactTags([
                                    Tag::COMPONENT => 'slim',
                                ])->withExistingTagsNames([
                                    'error.stack',
                                ])->setError(null, 'Foo error')
                            ],
                            [null, 'Foo error']
                        )
                    ]),
                ],
                'A GET request to a route with a parameter' => [
                    SpanAssertion::build(
                        'slim.request',
                        'slim_test_app',
                        'web',
                        'GET /parameterized/paramValue'
                    )->withExactTags([
                        'slim.route.handler' => 'Closure::__invoke',
                        'http.method' => 'GET',
                        'http.url' => 'http://localhost/parameterized/paramValue',
                        'http.status_code' => '200',
                        Tag::SPAN_KIND => 'server',
                        Tag::COMPONENT => 'slim',
                        Tag::HTTP_ROUTE => '/parameterized/{value}',
                    ])->withChildren([
                        $this->wrapMiddleware([
                            SpanAssertion::build(
                                'slim.route',
                                'slim_test_app',
                                'web',
                                'Closure::__invoke'
                            )->withExactTags([
                                Tag::COMPONENT => 'slim',
                            ])
                        ]),
                    ]),
                ],
            ]
        );
    }
}