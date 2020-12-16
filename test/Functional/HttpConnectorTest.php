<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Functional\Porter\Net\Http;

use Amp\Http\Client\Body\StringBody;
use Amp\Http\Client\HttpException;
use Amp\Http\Cookie\CookieAttributes;
use Amp\Http\Cookie\ResponseCookie;
use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Connector\AsyncDataSource;
use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Connector\DataSource;
use ScriptFUSION\Porter\Net\Http\AsyncHttpConnector;
use ScriptFUSION\Porter\Net\Http\AsyncHttpDataSource;
use ScriptFUSION\Porter\Net\Http\AsyncHttpOptions;
use ScriptFUSION\Porter\Net\Http\HttpConnectionException;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Net\Http\HttpOptions;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSION\Porter\Net\Http\HttpServerException;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Retry\ExceptionHandler\ExponentialBackoffExceptionHandler;
use Symfony\Component\Process\Process;
use function Amp\Promise\wait;
use function ScriptFUSION\Retry\retry;

final class HttpConnectorTest extends TestCase
{
    private const HOST = '127.0.0.1:12345';
    private const SSL_HOST = '127.0.0.1:6666';
    private const URI = 'feedback.php?baz=qux';
    private const DIR = __DIR__ . '/servers';

    /** @var HttpConnector|AsyncHttpConnector */
    private $connector;

    protected function setUp(): void
    {
        $this->connector = new HttpConnector;
    }

    public function testConnectionToLocalWebserver(): void
    {
        $server = $this->startServer();

        try {
            $response = $this->fetch(
                self::buildDataSource()
                    ->setMethod('POST')
                    ->addHeader($header = 'Foo: Bar')
                    ->setBody($body = 'Baz')
            );
        } finally {
            $this->stopServer($server);
        }

        self::assertSame(200, $response->getStatusCode());
        self::assertMatchesRegularExpression(
            '[\APOST \Q' . self::HOST . '/' . self::URI . '\E HTTP/\d+\.\d+$]m',
            $response->getBody()
        );
        self::assertMatchesRegularExpression("[^$header$]m", $response->getBody());
        self::assertStringEndsWith("\n\n$body", $response->getBody());
    }

    /**
     * @requires OS Linux
     */
    public function testSslConnectionToLocalWebserver(): void
    {
        $server = $this->startServer();

        try {
            $certificate = $this->startSsl();
            $response = $this->fetchViaSsl(self::createSslConnector($certificate));
        } finally {
            self::stopSsl();
            $this->stopServer($server);
        }

        self::assertSame(200, $response->getStatusCode());
        self::assertMatchesRegularExpression(
            '[\AGET \Q' . self::SSL_HOST . '/' . self::URI . '\E HTTP/\d+\.\d+$]m',
            $response->getBody()
        );
    }

    /**
     * Tests that an async connection can be established to the local echo server and responds as expected.
     */
    public function testAsyncConnectionToLocalWebserver(): void
    {
        $server = $this->startServer();

        $this->connector = new AsyncHttpConnector;

        // Test cookies are sent.
        $this->connector->getCookieJar()->store(
            new ResponseCookie(
                $cookieName = uniqid(),
                $cookievalue = 'Alfa',
                CookieAttributes::default()->withDomain(explode(':', self::HOST)[0])
            )
        );

        try {
            $response = $this->fetchAsync(
                self::buildAsyncDataSource()
                    ->setMethod('POST')
                    ->addHeader($headerName = 'Foo', $headerValue = 'Bar')
                    ->setBody(new StringBody($body = 'Baz'))
            );
        } finally {
            $this->stopServer($server);
        }

        self::assertInstanceOf(HttpResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertSame('1.1', $response->getProtocolVersion());
        self::assertTrue($response->hasHeader('x-powered-by'));
        self::assertMatchesRegularExpression(
            '[\APOST \Q' . self::HOST . '/' . self::URI . '\E HTTP/\d+\.\d+$]m',
            $response->getBody()
        );
        self::assertMatchesRegularExpression(
            "[^$headerName: $headerValue$]m",
            $response->getBody(),
            'Headers sent.'
        );
        self::assertMatchesRegularExpression(
            "[^Cookie: \Q$cookieName=$cookievalue\E$]m",
            $response->getBody(),
            'Cookies sent.'
        );
        self::assertStringEndsWith("\n\n$body", $response->getBody(), 'Body sent.');
    }

    public function testConnectionTimeout(): void
    {
        $this->expectException(HttpConnectionException::class);

        $this->fetch(self::buildDataSource());
    }

    public function testErrorResponse(): void
    {
        $server = $this->startServer();

        $this->expectException(HttpServerException::class);

        try {
            $this->fetch(self::buildDataSource('404.php'));
        } catch (HttpServerException $exception) {
            self::assertStringEndsWith('foo', $exception->getMessage());
            self::assertSame('foo', $exception->getResponse()->getBody());

            throw $exception;
        } finally {
            $this->stopServer($server);
        }
    }

    /**
     * Tests that the response object is built correctly when a server redirects to another page.
     */
    public function testRedirect(): void
    {
        $server = $this->startServer();

        try {
            $response = $this->fetch(self::buildDataSource('redirect.php'));
        } finally {
            $this->stopServer($server);
        }

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(HttpResponse::class, $prev = $response->getPrevious());
        self::assertSame(302, $prev->getStatusCode());
    }

    /**
     * Tests that when the body length exceeds the default limit, an HTTP exception is thrown.
     */
    public function testDefaultBodyLengthTooLong(): void
    {
        $server = $this->startServer();

        $this->connector = new AsyncHttpConnector();

        $this->expectException(HttpException::class);

        try {
            $this->fetchAsync(self::buildAsyncDataSource('big.php'));
        } finally {
            $this->stopServer($server);
        }
    }

    /**
     * Tests that when the body length exceeds a small custom limit, an HTTP exception is thrown.
     */
    public function testCustomBodyLengthTooLong(): void
    {
        $server = $this->startServer();

        $this->connector = new AsyncHttpConnector((new AsyncHttpOptions)->setMaxBodyLength(1));

        // N.B. Actual type is Amp\Artax\ParseException.
        $this->expectException(HttpException::class);

        try {
            $this->fetchAsync(self::buildAsyncDataSource());
        } finally {
            $this->stopServer($server);
        }
    }

    /**
     * @return Process Server.
     */
    private function startServer(): Process
    {
        $server = new Process(['php', '-S', self::HOST, '-t', self::DIR]);
        $server->start();

        self::waitForHttpServer(function () {
            $this->fetch(self::buildDataSource());
        });

        return $server;
    }

    private function stopServer(Process $server): void
    {
        $server->stop();
    }

    private function startSsl(): string
    {
        $accept = str_replace($filter = ['[', ']'], '', self::SSL_HOST);
        $connect = str_replace($filter, '', self::HOST);
        $certificate = tempnam(sys_get_temp_dir(), '');

        // Create SSL tunnel process.
        (new Process(
            // Generate self-signed SSL certificate in PEM format.
            "openssl req -new -x509 -nodes -subj /CN=127.0.0.1 -keyout '$certificate' -out '$certificate'

            { stunnel4 -fd 0 || stunnel -fd 0; } <<.
                # Disable PID to run as non-root user.
                pid=
                # Must run as foreground process on Travis, for some reason.
                foreground=yes

                []
                cert=$certificate
                accept=$accept
                connect=$connect
."
        ))->start();

        self::waitForHttpServer(function () use ($certificate): void {
            $this->fetchViaSsl(self::createSslConnector($certificate));
        });

        return $certificate;
    }

    private static function stopSsl(): void
    {
        shell_exec('pkill stunnel');
    }

    private function fetch(DataSource $source)
    {
        return $this->connector->fetch($source);
    }

    private function fetchAsync(AsyncDataSource $source)
    {
        return wait($this->connector->fetchAsync($source));
    }

    private static function buildDataSource(string $url = self::URI): HttpDataSource
    {
        return new HttpDataSource('http://' . self::HOST . "/$url");
    }

    private static function buildAsyncDataSource(string $url = self::URI): AsyncHttpDataSource
    {
        return new AsyncHttpDataSource('http://' . self::HOST . "/$url");
    }

    private function fetchViaSsl(Connector $connector)
    {
        return $connector->fetch(new HttpDataSource('https://' . self::SSL_HOST . '/' . self::URI));
    }

    /**
     * Waits for the specified HTTP server invoker to stop throwing HttpConnectionExceptions.
     *
     * @param \Closure $serverInvoker HTTP server invoker.
     */
    private static function waitForHttpServer(\Closure $serverInvoker): void
    {
        retry(
            ImportSpecification::DEFAULT_FETCH_ATTEMPTS,
            $serverInvoker,
            static function (\Exception $exception) {
                if (!$exception instanceof HttpConnectionException) {
                    return false;
                }

                static $handler;
                $handler = $handler ?: new ExponentialBackoffExceptionHandler;

                return $handler();
            }
        );
    }

    private static function createSslConnector(string $certificate): HttpConnector
    {
        $connector = new HttpConnector($options = new HttpOptions);
        $options->getSslOptions()->setCertificateAuthorityFilePath($certificate);

        return $connector;
    }
}
