<?php

/*
 * This file is part of HttpMessage package.
 *
 * (c) Pavel Vasin <phacman@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhacMan\HttpMessage\Tests;

use PhacMan\HttpMessage\Stream;
use PhacMan\HttpMessage\UploadedFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \PhacMan\HttpMessage\UploadedFile
 */
class UploadedFileTest extends TestCase
{
    /**
     * @var array<bool|string>
     */
    protected array $cleanup;

    protected function setUp(): void
    {
        $this->cleanup = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $file) {
            if (\is_scalar($file) && file_exists((string) $file)) {
                unlink((string) $file);
            }
        }
    }

    /**
     * @return array<string,array>
     */
    public static function invalidStreams(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'array' => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    /**
     * @dataProvider invalidStreams
     * @param mixed $streamOrFile
     */
    public function testRaisesExceptionOnInvalidStreamOrFile(mixed $streamOrFile): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream or file provided for UploadedFile');

        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    public function testGetStreamReturnsOriginalStreamObject(): void
    {
        $stream = Stream::create('');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->assertSame($stream, $upload->getStream());
    }

    public function testGetStreamReturnsWrappedPhpStream(): void
    {
        $stream = fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();

        $this->assertSame($stream, $uploadStream);
    }

    public function testGetStream(): void
    {
        $upload = new UploadedFile(__DIR__.'/Resources/foo.txt', 0, UPLOAD_ERR_OK);
        $stream = $upload->getStream();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals('Foobar'.PHP_EOL, $stream->__toString());
    }

    public function testSuccessful(): void
    {
        $stream = Stream::create('Foo bar!');
        $upload = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->assertEquals($stream->getSize(), $upload->getSize());
        $this->assertEquals('filename.txt', $upload->getClientFilename());
        $this->assertEquals('text/plain', $upload->getClientMediaType());

        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'successful');
        $upload->moveTo((string) $to);
        $this->assertFileExists((string) $to);
        $this->assertEquals($stream->__toString(), file_get_contents((string) $to));
    }

    public function testMoveCannotBeCalledMoreThanOnce(): void
    {
        $stream = (new \PhacMan\HttpMessage\HttpFactory())->createStream('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $to = (string) tempnam(sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertTrue(file_exists($to));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('moved');
        $upload->moveTo($to);
    }

    public function testCannotRetrieveStreamAfterMove(): void
    {
        $stream = (new \PhacMan\HttpMessage\HttpFactory())->createStream('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $to = (string) tempnam(sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertFileExists($to);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('moved');
        $upload->getStream();
    }

    /**
     * @return array<string, array<int, int>>
     */
    public static function nonOkErrorStatus(): array
    {
        return [
            'UPLOAD_ERR_INI_SIZE' => [UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE' => [UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL' => [UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE' => [UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION' => [UPLOAD_ERR_EXTENSION],
        ];
    }

    /**
     * @dataProvider nonOkErrorStatus
     * @param int $status
     */
    public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent(int $status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->assertSame($status, $uploadedFile->getError());
    }

    /**
     * @dataProvider nonOkErrorStatus
     * @param int $status
     */
    public function testMoveToRaisesExceptionWhenErrorStatusPresent(int $status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('upload error');
        $uploadedFile->moveTo(__DIR__.'/'.uniqid());
    }

    /**
     * @dataProvider nonOkErrorStatus
     * @param int $status
     */
    public function testGetStreamRaisesExceptionWhenErrorStatusPresent(int $status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('upload error');
        $uploadedFile->getStream();
    }

    public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided(): void
    {
        $this->cleanup[] = $from = (string) tempnam(sys_get_temp_dir(), 'copy_from');
        $this->cleanup[] = $to = (string) tempnam(sys_get_temp_dir(), 'copy_to');

        copy(__FILE__, $from);

        $uploadedFile = new UploadedFile($from, 100, UPLOAD_ERR_OK, basename($from), 'text/plain');
        $uploadedFile->moveTo($to);

        $this->assertFileEquals(__FILE__, $to);
    }
}
