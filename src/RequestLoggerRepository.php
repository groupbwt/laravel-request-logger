<?php
declare(strict_types=1);

namespace BwtTeam\LaravelRequestLogger;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\ResponseTrait;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use BwtTeam\LaravelRequestLogger\Stores\StoreInterface;
use Illuminate\Support\Arr;

class RequestLoggerRepository implements RepositoryInterface
{
    /**
     * The log's primary key.
     *
     * @var string
     */
    protected $key;

    /**
     * Indicates if the log record already exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * The request logger store implementation.
     *
     * @var \BwtTeam\LaravelRequestLogger\Stores\StoreInterface
     */
    protected $store;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The response instance.
     *
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * The array of collected extra data.
     *
     * @var array
     */
    protected $extra = [];

    /**
     * The array of collected data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Request start time
     *
     * @var float
     */
    protected $startTime;

    /**
     * RequestLoggerRepository constructor.
     *
     * @param \BwtTeam\LaravelRequestLogger\Stores\StoreInterface $store
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function __construct(StoreInterface $store, Dispatcher $events)
    {
        $this->key = Str::orderedUuid()->toString();
        $this->store = $store;
        $this->events = $events;

        if (defined('LARAVEL_START')) {
            $this->startTime = LARAVEL_START;
        } else {
            $this->startTime = array_get($_SERVER, 'REQUEST_TIME_FLOAT', microtime(true));
        }
    }

    /**
     * @inheritdoc
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    public function put(string $key, $value): void
    {
        Arr::set($this->extra, $key, $value);
    }

    /**
     * @inheritdoc
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->extra, $key, $default);
    }

    /**
     * @inheritdoc
     */
    public function extra(): array
    {
        return $this->extra;
    }

    /**
     * @inheritdoc
     */
    public function forget(string $key): void
    {
        Arr::forget($this->extra, $key);
    }

    /**
     * @inheritdoc
     */
    public function flush(): void
    {
        $this->extra = [];
    }

    /**
     * @inheritdoc
     */
    public function all(): array
    {
        return array_merge($this->data, [
            'id' => $this->getKey(),
            'extra' => $this->extra()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function process(Request $request, Response $response): RepositoryInterface
    {
        $this->data['user_id'] = optional($request->user())->getKey();
        $this->processRequest($request)
            ->processResponse($response);
        $this->data['duration'] = microtime(true) - $this->startTime;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function save(): bool
    {
        $this->events->dispatch(
            new Events\RequestLogSaving($this)
        );

        $callback = function () {
            if ($this->exists) {
                throw new \LogicException(sprintf('This log with primary key "%s" already has been saved.', $this->getKey()));
            }

            return tap($this->store->save($this->all()), function ($saved) {
                if ($saved) {
                    $this->exists = true;
                }
            });
        };

        return app('config')->get('app.debug') ? $callback() : rescue($callback, false);
    }

    /**
     * Collects data for the given Request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return RequestLoggerRepository
     */
    protected function processRequest(Request $request): RequestLoggerRepository
    {
        $except = config('request-logger.except', []);

        $this->data['ip'] = $request->getClientIp();
        $this->data['url'] = $request->fullUrl();
        $this->data['type'] = $request->getMethod();
        $this->data['request'] = [
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'params' => [
                'get' => $_GET,
                'post' => array_except($_POST, $except),
                'files' => $_FILES,
                'raw' => blank($except) ? $request->getContent() : transform($request->getContent(), function ($rawContent) use ($except) {
                    mb_parse_str($rawContent, $content);

                    return http_build_query(array_except($content, $except));
                })
            ],
            'cookies' => $request->cookie()
        ];

        return $this;
    }

    /**
     * Collects data for the given Response.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return RequestLoggerRepository
     */
    protected function processResponse(Response $response): RequestLoggerRepository
    {
        $this->data['code'] = $response->getStatusCode();
        $this->data['response'] = [
            'data' => transform($response, function (Response $response) {
                if ($response instanceof JsonResponse) {
                    return json_decode($response->getContent());
                } elseif ($response instanceof BinaryFileResponse) {
                    return $response->getFile();
                } elseif (in_array(ResponseTrait::class, class_uses_recursive($response))) {
                    /** @var ResponseTrait $response */
                    return $response->getOriginalContent();
                }

                return $response->getContent();
            }),
            'headers' => $response->headers->allPreserveCase()
        ];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        $config = config('request-logger');

        return $config['enabled'] instanceof \Closure ? app()->call($config['enabled']) : $config['enabled'];
    }
}