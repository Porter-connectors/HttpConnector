<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Functional\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Net\Http\AsyncHttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpConnectionException;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpOptions;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSION\Porter\Net\Http\HttpServerException;
use ScriptFUSION\Porter\Specification\ImportSpecification;
use ScriptFUSION\Retry\ExceptionHandler\ExponentialBackoffExceptionHandler;
use ScriptFUSION\Retry\FailingTooHardException;
use ScriptFUSIONTest\FixtureFactory;
use Symfony\Component\Process\Process;

final class HttpConnectorTest extends \PHPUnit_Framework_TestCase
{
    private const HOST = '127.0.0.1:12345';
    private const SSL_HOST = '127.0.0.1:6666';
    private const URI = 'feedback.php?baz=qux';
    private const DIR = __DIR__ . '/servers';

    /** @var HttpConnector|AsyncHttpConnector */
    private $connector;

    protected function setUp()
    {
        $this->connector = new HttpConnector;
    }

    public function testConnectionToLocalWebserver(): void
    {
        $server = $this->startServer();

        $this->connector = new HttpConnector((new HttpOptions)->addHeader($header = 'Foo: Bar'));

        try {
            $response = $this->fetch();
        } finally {
            $this->stopServer($server);
        }

        self::assertSame(200, $response->getStatusCode());
        self::assertRegExp('[\AGET \Q' . self::HOST . '/' . self::URI . '\E HTTP/\d+\.\d+$]m', $response->getBody());
        self::assertRegExp("[^$header$]m", $response->getBody());
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
        self::assertRegExp(
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

        try {
            $response = $this->fetch();
        } finally {
            $this->stopServer($server);
        }

        self::assertInstanceOf(HttpResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertSame('1.1', $response->getProtocolVersion());
        self::assertTrue($response->hasHeader('x-powered-by'));
        self::assertRegExp('[\AGET \Q' . self::HOST . '/' . self::URI . '\E HTTP/\d+\.\d+$]m', $response->getBody());
    }

    public function testConnectionTimeout(): void
    {
        try {
            $this->fetch();

            self::fail('Expected FailingTooHardException exception.');
        } catch (FailingTooHardException $exception) {
            self::assertInstanceOf(HttpConnectionException::class, $exception->getPrevious());
        }
    }

    public function testErrorResponse(): void
    {
        $server = $this->startServer();

        try {
            $this->fetch('404.php');

            self::fail('Expected FailingTooHardException exception.');
        } catch (FailingTooHardException $exception) {
            /** @var HttpServerException $innerException */
            self::assertInstanceOf(HttpServerException::class, $innerException = $exception->getPrevious());

            self::assertSame(404, $innerException->getResponse()->getStatusCode());
            self::assertSame('foo', $innerException->getResponse()->getBody());
            self::assertStringEndsWith("\n\nfoo", $innerException->getMessage());
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
            $response = $this->fetch('redirect.php');
        } finally {
            $this->stopServer($server);
        }

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(HttpResponse::class, $prev = $response->getPrevious());
        self::assertSame(302, $prev->getStatusCode());
    }

    /**
     * @return Process Server.
     */
    private function startServer(): Process
    {
        $server = new Process(['php', '-S', self::HOST, '-t', self::DIR]);
        $server->start();

        self::waitForHttpServer(function () {
            $this->fetch();
        });

        return $server;
    }

    private function stopServer(Process $server): void
    {
        $server->stop();
    }

    private function startSsl(): string
    {
        $accept = str_replace($filter = ['[', ']'], null, self::SSL_HOST);
        $connect = str_replace($filter, null, self::HOST);
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

        self::waitForHttpServer(function () use ($certificate) {
            $this->fetchViaSsl(self::createSslConnector($certificate));
        });

        return $certificate;
    }

    private static function stopSsl(): void
    {
        `pkill stunnel`;
    }

    private function fetch(string $url = self::URI)
    {
        $context = FixtureFactory::createConnectionContext();
        $fullUrl = 'http://' . self::HOST . "/$url";

        if ($this->connector instanceof AsyncHttpConnector) {
            return \Amp\Promise\wait($this->connector->fetchAsync($context, $fullUrl));
        }

        return $this->connector->fetch($context, $fullUrl);
    }

    private function fetchViaSsl(Connector $connector)
    {
        return $connector->fetch(
            FixtureFactory::createConnectionContext(),
            'https://' . self::SSL_HOST . '/' . self::URI
        );
    }

    /**
     * Waits for the specified HTTP server invoker to stop throwing HttpConnectionExceptions.
     *
     * @param \Closure $serverInvoker HTTP server invoker.
     */
    private static function waitForHttpServer(\Closure $serverInvoker): void
    {
        \ScriptFUSION\Retry\retry(
            ImportSpecification::DEFAULT_FETCH_ATTEMPTS,
            $serverInvoker,
            function (\Exception $exception) {
                if (!$exception instanceof FailingTooHardException
                    || !$exception->getPrevious() instanceof HttpConnectionException
                ) {
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
