<?php
declare(strict_types=1);

use BwtTeam\LaravelRequestLogger\Providers\RequestLoggerServiceProvider;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Schema\Blueprint;

class DatabaseTest extends \Orchestra\Testbench\TestCase
{
    /** @var BwtTeam\LaravelRequestLogger\Stores\Database */
    protected $store;
    /** @var string */
    protected $table;

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

        $this->table = config('request-logger.stores.database.table');
        $this->store = $this->app->make('request_logger')->driver('database');

        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->ipAddress('ip');
            $table->string('type', 8);
            $table->decimal('duration', 8, 4)->index();
            $table->bigInteger('user_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * @return void
     */
    public function testSave(): void
    {
        $this->store->save([
            'ip' => '127.0.0.1',
            'type' => 'GET',
            'duration' => 1.234567,
            'user_id' => 1,
            'request' => ['test' => 'some_text'],
            'response' => null,
        ]);

        $this->assertDatabaseHas($this->table, [
            'ip' => '127.0.0.1',
            'type' => 'GET',
            'duration' => "1.234567",
            'user_id' => 1,
            'request' => json_encode(['test' => 'some_text']),
            'response' => null
        ]);
    }

    /**
     * @return void
     */
    public function testHtmlWithoutCompression(): void
    {
        config()->set('request-logger.casts.compress_html', false);
        $this->store->save([
            'ip' => '127.0.0.1',
            'type' => 'GET',
            'duration' => 1.234567,
            'user_id' => 1,
            'request' => ['test' => 'some_text'],
            'response' => $view = new class() implements Renderable
            {
                public function render()
                {
                    return "<html>\r\n\t<body>\r\n\t\t<h1>Title</h1>\r\n\t<script>\r\n\t\talert(' iqeugfo wef we');\r\n\t</script>\r\n\t</body>\r\n</html>\r\n";
                }

                public function __toString()
                {
                    return $this->render();
                }
            },
        ]);

        $this->assertDatabaseHas($this->table, [
            'ip' => '127.0.0.1',
            'type' => 'GET',
            'duration' => 1.234567,
            'user_id' => 1,
            'request' => json_encode(['test' => 'some_text']),
            'response' => "<html>\r\n\t<body>\r\n\t\t<h1>Title</h1>\r\n\t<script>\r\n\t\talert(' iqeugfo wef we');\r\n\t</script>\r\n\t</body>\r\n</html>\r\n"
        ]);
    }

    /**
     * @return void
     */
    public function testHtmlWithCompression(): void
    {
        config()->set('request-logger.casts.compress_html', true);
        $this->store->save([
            'ip' => '127.0.0.1',
            'type' => 'GET',
            'duration' => 1.234567,
            'user_id' => 1,
            'request' => ['test' => 'some_text'],
            'response' => $view = new class() implements Renderable
            {
                public function render()
                {
                    return "<html>\r\n\t<body>\r\n\t\t<h1>Title</h1>\r\n\t<script>\r\n\t\talert(' iqeugfo wef we');\r\n\t</script>\r\n\t</body>\r\n</html>\r\n";
                }

                public function __toString()
                {
                    return $this->render();
                }
            },
        ]);

        $this->assertDatabaseHas($this->table, [
            'ip' => '127.0.0.1',
            'type' => 'GET',
            'duration' => 1.234567,
            'user_id' => 1,
            'request' => json_encode(['test' => 'some_text']),
        ]);

        $response = $this->store->query()->latest()->value('response');
        $this->assertLessThan(
            mb_strlen("<html>\r\n\t<body>\r\n\t\t<h1>Title</h1>\r\n\t<script>\r\n\t\talert(' iqeugfo wef we');\r\n\t</script>\r\n\t</body>\r\n</html>\r\n"),
            mb_strlen($response)
        );
    }
}