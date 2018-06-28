<?php
declare(strict_types=1);

use BwtTeam\LaravelRequestLogger\Providers\RequestLoggerServiceProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Foundation\Auth\User as Authenticatable;
use BwtTeam\LaravelRequestLogger\RepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;

class RequestLoggerTest extends \Orchestra\Testbench\TestCase
{
    /** @var Illuminate\Http\Request */
    protected $request;
    /** @var Symfony\Component\HttpFoundation\Response */
    protected $response;
    /** @var BwtTeam\LaravelRequestLogger\RepositoryInterface */
    protected $requestLogger;

    /**
     * @inheritdoc
     */
    protected function getPackageProviders($app): array
    {
        return [RequestLoggerServiceProvider::class];
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('request-logger.except', []);
        config()->set('request-logger.casts.compress_html', false);

        $this->request = Request::create('/');
        $this->response = Response::create('hello world', 200, []);
        $this->requestLogger = $this->app->make(RepositoryInterface::class);
    }

    /**
     * @return void
     */
    public function testUnauthorizedUser(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('user_id', $data);

        $this->requestLogger->process(
            $this->request,
            $this->response
        );

        $data = $this->requestLogger->all();

        $this->assertArrayHasKey('user_id', $data);
        $this->assertNull($data['user_id']);
    }

    /**
     * @return void
     */
    public function testAuthorizedUser(): void
    {
        $this->be($user = new class(['id' => 1, 'name' => 'John', 'email' => 'example@mail.com']) extends Authenticatable
        {
            protected $fillable = [
                'id', 'name', 'email'
            ];
        });

        $this->requestLogger->process(
            $this->request->createFrom($this->request)->setUserResolver(function ($guard = null) {
                return call_user_func($this->app['auth']->userResolver(), $guard);
            }),
            $this->response
        );

        $data = $this->requestLogger->all();

        $this->assertArrayHasKey('user_id', $data);
        $this->assertSame($user->getKey(), $data['user_id']);
    }

    /**
     * @return void
     */
    public function testIp(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('ip', $data);

        $this->requestLogger->process(
            $request = $this->request->createFrom($this->request),
            $this->response
        );

        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('ip', $data);
        $this->assertSame($request->server->get('REMOTE_ADDR'), $data['ip']);

        $request->server->set('REMOTE_ADDR', '192.168.10.1');
        $this->requestLogger->process($request, $this->response);
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('ip', $data);
        $this->assertSame('192.168.10.1', $data['ip']);

        $request->server->set('REMOTE_ADDR', '8.8.8.8');
        $this->requestLogger->process($request, $this->response);
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('ip', $data);
        $this->assertSame('8.8.8.8', $data['ip']);
    }

    /**
     * @return void
     */
    public function testUrl(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('url', $data);

        $this->requestLogger->process($this->request, $this->response);
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('url', $data);
        $this->assertSame('http://localhost', $data['url']);

        $this->requestLogger->process(
            Request::create('http://example.com/'),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('url', $data);
        $this->assertSame('http://example.com', $data['url']);

        $this->requestLogger->process(
            Request::create('http://example.com/some_page'),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('url', $data);
        $this->assertSame('http://example.com/some_page', $data['url']);

        $this->requestLogger->process(
            Request::create('http://example.com/some_page.php'),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('url', $data);
        $this->assertSame('http://example.com/some_page.php', $data['url']);

        $this->requestLogger->process(
            Request::create('http://example.com?test=1234'),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('url', $data);
        $this->assertSame('http://example.com/?test=1234', $data['url']);

        $this->requestLogger->process(
            Request::create('http://example.com/?test=1234'),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('url', $data);
        $this->assertSame('http://example.com/?test=1234', $data['url']);
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('type', $data);

        $this->requestLogger->process(
            $this->request,
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('GET', $data['type']);

        $this->requestLogger->process(
            $request = tap($this->request->createFrom($this->request), function (Request $request) {
                $request->setMethod('POST');
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('POST', $data['type']);

        $this->requestLogger->process(
            $request = tap($this->request->createFrom($this->request), function (Request $request) {
                $request->setMethod('PUT');
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('PUT', $data['type']);

        $this->requestLogger->process(
            $request = tap($this->request->createFrom($this->request), function (Request $request) {
                $request->setMethod('PATCH');
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('PATCH', $data['type']);

        $this->requestLogger->process(
            $request = tap($this->request->createFrom($this->request), function (Request $request) {
                $request->setMethod('DELETE');
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('DELETE', $data['type']);

        $this->requestLogger->process(
            $request = tap($this->request->createFrom($this->request), function (Request $request) {
                $request->setMethod('OPTIONS');
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('OPTIONS', $data['type']);

        $this->requestLogger->process(
            $request = tap($this->request->createFrom($this->request), function (Request $request) {
                $request->setMethod('test');
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('TEST', $data['type']);
    }

    /**
     * @return void
     */
    public function testRequest(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('request', $data);

        $this->requestLogger->process($this->request, $this->response);
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);

        $this->assertArrayHasKey('user_agent', $data['request']);
        $this->assertInternalType('string', $data['request']['user_agent']);

        $this->assertArrayHasKey('headers', $data['request']);
        $this->assertInternalType('array', $data['request']['headers']);

        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);

        $this->assertArrayHasKey('get', $data['request']['params']);
        $this->assertArrayHasKey('post', $data['request']['params']);
        $this->assertArrayHasKey('files', $data['request']['params']);
        $this->assertArrayHasKey('raw', $data['request']['params']);

        $this->assertArrayHasKey('cookies', $data['request']);
        $this->assertInternalType('array', $data['request']['cookies']);

        $this->requestLogger->process(
            tap(Request::create('http://example.com/?test=1234'), function (Request $request) {
                $request->overrideGlobals();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);
        $this->assertArrayHasKey('get', $data['request']['params']);
        $this->assertSame(['test' => '1234'], $data['request']['params']['get']);

        $post = ['zxc' => 'www', 'password' => '123456'];
        $this->requestLogger->process(
            tap(Request::create('http://example.com/?test=qwerty', 'POST', $post), function (Request $request) {
                $request->overrideGlobals();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);
        $this->assertArrayHasKey('get', $data['request']['params']);
        $this->assertSame(['test' => 'qwerty'], $data['request']['params']['get']);
        $this->assertArrayHasKey('post', $data['request']['params']);
        $this->assertSame($post, $data['request']['params']['post']);

        $raw = ['some' => 'var', 'password' => 'password'];
        $this->requestLogger->process(
            tap(Request::create('http://example.com/?test=qwerty', 'POST', ['zdr' => '123'], [], [], [], http_build_query($raw)), function (Request $request) {
                $request->overrideGlobals();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);
        $this->assertArrayHasKey('raw', $data['request']['params']);
        $this->assertSame(http_build_query($raw), $data['request']['params']['raw']);

        $this->requestLogger->process(
            tap(Request::create('http://example.com/?qwerty=test', 'PUT', ['some' => 'var']), function (Request $request) {
                $request->overrideGlobals();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);
        $this->assertArrayHasKey('get', $data['request']['params']);
        $this->assertSame(['qwerty' => 'test'], $data['request']['params']['get']);
        $this->assertArrayHasKey('post', $data['request']['params']);
        $this->assertSame(['some' => 'var'], $data['request']['params']['post']);

        $this->requestLogger->process(
            tap(Request::create('http://example.com/?qwerty=test', 'GET', [], ['test' => 'qwerty']), function (Request $request) {
                $request->overrideGlobals();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('cookies', $data['request']);
        $this->assertSame(['test' => 'qwerty'], $data['request']['cookies']);

        $file = UploadedFile::fake()->image('img');
        $this->requestLogger->process(
            tap(Request::create('http://example.com/?qwerty=test', 'GET', [], [], [$file]), function (Request $request) {
                $request->overrideGlobals();
                $_FILES = $request->allFiles();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);
        $this->assertArrayHasKey('files', $data['request']['params']);
        $this->assertSame([$file], $data['request']['params']['files']);
    }

    /**
     * @return void
     */
    public function testExceptFilter(): void
    {
        $raw = ['some' => 'var', 'password' => 'qwerty'];
        $this->requestLogger->process(
            tap(Request::create('http://example.com/?test=qwerty', 'POST', ['zdr' => '123'], [], [], [], http_build_query($raw)), function (Request $request) {
                $request->overrideGlobals();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);
        $this->assertArrayHasKey('raw', $data['request']['params']);
        $this->assertSame(http_build_query($raw), $data['request']['params']['raw']);

        config()->set('request-logger.except', ['password']);
        $raw = ['some' => 'var', 'password' => 'qwerty'];
        $this->requestLogger->process(
            tap(Request::create('http://example.com/?test=qwerty', 'POST', $raw, [], [], [], http_build_query($raw)), function (Request $request) {
                $request->overrideGlobals();
            }),
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);
        $this->assertArrayHasKey('params', $data['request']);
        $this->assertInternalType('array', $data['request']['params']);
        $this->assertArrayHasKey('post', $data['request']['params']);
        $this->assertSame(array_except($raw, ['password']), $data['request']['params']['post']);
        $this->assertArrayHasKey('raw', $data['request']['params']);
        $this->assertSame(http_build_query(array_except($raw, ['password'])), $data['request']['params']['raw']);
    }

    /**
     * @return void
     */
    public function testCode(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('code', $data);

        $this->requestLogger->process(
            $this->request,
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('code', $data);
        $this->assertSame(200, $data['code']);

        $this->requestLogger->process(
            $this->request,
            (clone $this->response)->setStatusCode(301)
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('code', $data);
        $this->assertSame(301, $data['code']);

        $this->requestLogger->process(
            $this->request,
            (clone $this->response)->setStatusCode(404)
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('code', $data);
        $this->assertSame(404, $data['code']);

        $this->requestLogger->process(
            $this->request,
            (clone $this->response)->setStatusCode(500)
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('code', $data);
        $this->assertSame(500, $data['code']);
    }

    /**
     * @return void
     */
    public function testResponse(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('response', $data);

        $this->requestLogger->process(
            $this->request,
            Response::create('hello world', 200, [])
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('response', $data);
        $this->assertInternalType('array', $data['response']);
        $this->assertArrayHasKey('data', $data['response']);
        $this->assertSame('hello world', $data['response']['data']);
        $this->assertArrayHasKey('headers', $data['response']);
        $this->assertInternalType('array', $data['response']['headers']);


        $this->requestLogger->process(
            $this->request,
            JsonResponse::create(['test' => 'json'], 200, [])
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('response', $data);
        $this->assertInternalType('array', $data['response']);
        $this->assertArrayHasKey('data', $data['response']);
        $this->assertInternalType('object', $data['response']['data']);
        $this->assertSame(['test' => 'json'], (array)$data['response']['data']);

        $this->requestLogger->process(
            $this->request,
            BinaryFileResponse::create(__FILE__, 200, [])
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('response', $data);
        $this->assertInternalType('array', $data['response']);
        $this->assertArrayHasKey('data', $data['response']);
        $this->assertInstanceOf(\SplFileInfo::class, $data['response']['data']);


        $this->requestLogger->process(
            $this->request,
            Response::create($view = new class() implements Renderable
            {
                public function render()
                {
                    return "<div>test view content</div>";
                }

                public function __toString()
                {
                    return $this->render();
                }
            })
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('response', $data);
        $this->assertInternalType('array', $data['response']);
        $this->assertArrayHasKey('data', $data['response']);
        $this->assertSame("<div>test view content</div>", $data['response']['data']);
    }

    /**
     * @return void
     */
    public function testDuration(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayNotHasKey('duration', $data);

        $this->requestLogger->process(
            $this->request,
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('duration', $data);
        $this->assertInternalType('float', $data['duration']);
        $this->assertGreaterThan(0, $data['duration']);
    }

    /**
     * @return void
     */
    public function testId(): void
    {
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('id', $data);
        $id = $data['id'];

        $this->requestLogger->process(
            $this->request,
            $this->response
        );
        $data = $this->requestLogger->all();
        $this->assertArrayHasKey('id', $data);
        $this->assertSame($id, $data['id']);
    }

    /**
     * @return void
     */
    public function testExtra(): void
    {
        $this->requestLogger->process(
            $this->request,
            $this->response
        );

        $this->assertArrayHasKey('extra', $this->requestLogger->all());
        $this->assertInternalType('array', $this->requestLogger->all()['extra']);
        $this->assertSame($this->requestLogger->all()['extra'], $this->requestLogger->extra());

        $this->assertSame($this->requestLogger->extra(), []);
        $this->assertSame($this->requestLogger->get('test'), null);

        $this->requestLogger->put('test', 123);
        $this->assertInternalType('integer', $this->requestLogger->get('test'));
        $this->assertSame($this->requestLogger->get('test'), 123);
        $this->assertInternalType('array', $this->requestLogger->extra());
        $this->assertSame($this->requestLogger->extra(), ['test' => 123]);
        $this->assertSame($this->requestLogger->all()['extra'], $this->requestLogger->extra());

        $this->requestLogger->forget('test');
        $this->assertSame($this->requestLogger->get('test'), null);
        $this->assertInternalType('array', $this->requestLogger->extra());
        $this->assertSame($this->requestLogger->extra(), []);

        $this->requestLogger->put('qwerty', 'some string');
        $this->requestLogger->put('string 1', 321);
        $this->requestLogger->put('std', $std = new stdClass());
        $this->assertInternalType('array', $this->requestLogger->extra());
        $this->assertSame(['qwerty' => 'some string', 'string 1' => 321, 'std' => $std], $this->requestLogger->extra());
        $this->assertInternalType('string', $this->requestLogger->get('qwerty'));
        $this->assertSame('some string', $this->requestLogger->get('qwerty'));
        $this->assertInternalType('integer', $this->requestLogger->get('string 1'));
        $this->assertSame(321, $this->requestLogger->get('string 1'));
        $this->assertInternalType('object', $this->requestLogger->get('std'));
        $this->assertSame($std, $this->requestLogger->get('std'));
        $this->assertSame($this->requestLogger->all()['extra'], $this->requestLogger->extra());

        $this->requestLogger->flush();
        $this->assertInternalType('array', $this->requestLogger->extra());
        $this->assertSame([], $this->requestLogger->extra());
        $this->assertSame($this->requestLogger->all()['extra'], $this->requestLogger->extra());
    }

    /**
     * @return void
     */
    public function testProcessStructure(): void
    {
        $this->requestLogger->process(
            $this->request,
            $this->response
        );

        $data = $this->requestLogger->all();

        $this->assertInternalType('array', $data);

        $this->assertArrayHasKey('user_id', $data);
        $this->assertNull($data['user_id']);

        $this->assertArrayHasKey('ip', $data);
        $this->assertInternalType('string', $data['ip']);

        $this->assertArrayHasKey('url', $data);
        $this->assertInternalType('string', $data['url']);

        $this->assertArrayHasKey('type', $data);
        $this->assertInternalType('string', $data['type']);

        $this->assertArrayHasKey('request', $data);
        $this->assertInternalType('array', $data['request']);

        $this->assertArrayHasKey('code', $data);
        $this->assertInternalType('integer', $data['code']);

        $this->assertArrayHasKey('response', $data);
        $this->assertInternalType('array', $data['response']);

        $this->assertArrayHasKey('duration', $data);
        $this->assertInternalType('float', $data['duration']);

        $this->assertArrayHasKey('id', $data);
        $this->assertNotEmpty($data['id']);

        $this->assertArrayHasKey('extra', $data);
        $this->assertInternalType('array', $data['extra']);
    }
}