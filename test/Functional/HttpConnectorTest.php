<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Functional;

use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpException;
use Amp\Http\Cookie\CookieAttributes;
use Amp\Http\Cookie\ResponseCookie;
use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Connector\DataSource;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Net\Http\HttpOptions;
use ScriptFUSION\Porter\Net\Http\HttpConnectionException;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSION\Porter\Net\Http\HttpServerException;
use ScriptFUSION\Porter\Net\Http\TlsOptions;
use ScriptFUSION\Retry\ExceptionHandler\ExponentialBackoffExceptionHandler;
use Symfony\Component\Process\Process;
use function ScriptFUSION\Retry\retry;

final class HttpConnectorTest extends TestCase
{
    private const IP = '[::1]';
    private const HOST = self::IP . ':12345';
    private const SSL_HOST = self::IP . ':6666';
    private const URI = 'feedback.php?baz=qux';
    private const DIR = __DIR__ . '/servers';

    private HttpConnector $connector;

    protected function setUp(): void
    {
        $this->connector = new HttpConnector();
    }

    public function testConnectionToLocalWebserver(): void
    {
        $server = $this->startServer();

        try {
            $response = $this->fetch(
                self::buildDataSource()
                    ->setMethod('POST')
                    ->addHeader($headerName = 'Foo', $headerValue = 'Bar')
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
        self::assertMatchesRegularExpression("[^$headerName: $headerValue$]m", $response->getBody());
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

        $this->connector = new HttpConnector;

        // Test cookies are sent.
        $this->connector->getCookieJar()->store(
            new ResponseCookie(
                $cookieName = uniqid(),
                $cookievalue = 'Alfa',
                CookieAttributes::default()->withDomain(preg_replace('[:[^:]*$]', '', self::HOST))
            )
        );

        try {
            $response = $this->fetch(
                self::buildDataSource()
                    ->setMethod('POST')
                    ->addHeader($headerName = 'Foo', $headerValue = 'Bar')
                    ->setBody($body = 'Baz')
            );
        } finally {
            $this->stopServer($server);
        }

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
     * Tests that when an error is returned by the sever, and we requested errors to be returned,
     * no exception is thrown.
     */
    public function testErrorResponseReturned(): void
    {
        $server = $this->startServer();
        $this->connector = new HttpConnector((new HttpOptions)->willReturnErrors());

        try {
            $response = $this->fetch(self::buildDataSource('404.php'));
        } finally {
            $this->stopServer($server);
        }

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * Tests that the response object is built correctly when a server redirects to another page.
     */
    public function testRedirect(): void
    {
        $server = $this->startServer();

        try {
            $response = $this->fetch(self::buildDataSource($source = 'redirect.php'));
        } finally {
            $this->stopServer($server);
        }

        self::assertSame(200, $response->getStatusCode());
        self::assertMatchesRegularExpression(
            '[^Referer: http://\\Q' . self::HOST . "/$source\\E$]m",
            $response->getBody()
        );

        self::assertNotNull($prev = $response->getPrevious());
        self::assertSame(302, $prev->getStatusCode());
    }

    /**
     * Tests that when the body length exceeds the default limit, an HTTP exception is thrown.
     */
    public function testDefaultBodyLengthTooLong(): void
    {
        $server = $this->startServer();

        $this->expectException(StreamException::class);

        try {
            $this->fetch(self::buildDataSource('big.php'))->getBody();
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

        $this->connector = new HttpConnector((new HttpOptions)->setMaxBodyLength(1));

        $this->expectException(StreamException::class);

        try {
            $this->fetch(self::buildDataSource())->getBody();
        } finally {
            $this->stopServer($server);
        }
    }

    /**
     * Tests that when the max body length is overriden on a per-request basis, an HTTP exception is thrown.
     */
    public function testCustomBodyLengthOverride(): void
    {
        $server = $this->startServer();

        $this->expectException(StreamException::class);

        try {
            $this->fetch(self::buildDataSource()->setMaxBodyLength(1))->getBody();
        } finally {
            $this->stopServer($server);
        }
    }

    public function testUserAgentOverride(): void
    {
        $server = $this->startServer();

        try {
            $response = $this->fetch(self::buildDataSource()->addHeader($h = 'user-agent', $v = 'Alfa'))->getBody();
        } finally {
            $this->stopServer($server);
        }

        self::assertMatchesRegularExpression("[^$h: $v$]m", $response);
    }

    private function startServer(): Process
    {
        $server = new Process([PHP_BINARY, '-S', self::HOST, '-t', self::DIR]);
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
        $ip = self::IP;
        $accept = str_replace($filter = ['[', ']'], '', self::SSL_HOST);
        $connect = str_replace($filter, '', self::HOST);
        $certificate = tempnam(sys_get_temp_dir(), '');

        // Create SSL tunnel process.
        Process::fromShellCommandline(
            // Generate self-signed SSL certificate in PEM format.
            "openssl req -new -x509 -nodes -subj /CN=$ip -keyout '$certificate' -out '$certificate'

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
        )->start();

        self::waitForHttpServer(fn () => $this->fetchViaSsl(self::createSslConnector($certificate)));

        return $certificate;
    }

    private static function stopSsl(): void
    {
        shell_exec('pkill stunnel');
    }

    private function fetch(DataSource $source): HttpResponse
    {
        return $this->connector->fetch($source);
    }

    private static function buildDataSource(string $url = self::URI): HttpDataSource
    {
        return new HttpDataSource('http://' . self::HOST . "/$url");
    }

    private function fetchViaSsl(Connector $connector): HttpResponse
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
            Import::DEFAULT_FETCH_ATTEMPTS,
            $serverInvoker,
            static function (\Exception $exception) {
                if (!$exception instanceof HttpConnectionException) {
                    return false;
                }

                static $handler;

                return ($handler ??= new ExponentialBackoffExceptionHandler)();
            }
        );
    }

    private static function createSslConnector(string $certificate): HttpConnector
    {
        return new HttpConnector(tlsOptions: (new TlsOptions)->setCertificateAuthorityFilePath($certificate));
    }
}
