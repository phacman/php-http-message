<?php

declare(strict_types=1);

/*
 * This file is part of HttpMessage package.
 *
 * (c) Pavel Vasin <phacman@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhacMan\HttpMessage;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @author Michael Dowling and contributors to guzzlehttp/psr7
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 * @author Pavel Vasin <phacman@yandex.ru>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class UploadedFile implements UploadedFileInterface
{
    /** @var string */
    private ?string $clientFilename;

    /** @var string */
    private ?string $clientMediaType;

    /** @var int */
    private int $error;

    /** @var string|null */
    private ?string $file = null;

    /** @var bool */
    private bool $moved = false;

    /** @var int */
    private int $size;

    /** @var StreamInterface|null */
    private ?StreamInterface $stream = null;

    /**
     * @param mixed       $streamOrFile
     * @param int|null    $size
     * @param int|null    $errorStatus
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     */
    public function __construct(mixed $streamOrFile, ?int $size, ?int $errorStatus, string $clientFilename = null, string $clientMediaType = null)
    {
        $this->error = $errorStatus;
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if (UPLOAD_ERR_OK === $this->error) {
            // Depending on the value set file or stream variable.
            if (\is_string($streamOrFile) && '' !== $streamOrFile) {
                $this->file = $streamOrFile;
            } elseif (\is_resource($streamOrFile)) {
                $this->stream = Stream::create($streamOrFile);
            } elseif ($streamOrFile instanceof StreamInterface) {
                $this->stream = $streamOrFile;
            } else {
                throw new \InvalidArgumentException('Invalid stream or file provided for UploadedFile');
            }
        }
    }

    /**
     * @throws \RuntimeException if is moved or not ok
     */
    private function validateActive(): void
    {
        if (UPLOAD_ERR_OK !== $this->error) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /** {@inheritdoc} */
    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        if (false === $resource = @fopen($this->file, 'r')) {
            throw new \RuntimeException(sprintf('The file "%s" cannot be opened: %s', $this->file, error_get_last()['message'] ?? ''));
        }

        return Stream::create($resource);
    }

    /** {@inheritdoc} */
    public function moveTo(string $targetPath): void
    {
        $this->validateActive();

        if ('' === $targetPath) {
            throw new \InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }

        if (null !== $this->file) {
            $this->moved = 'cli' === PHP_SAPI ? @rename($this->file, $targetPath) : @move_uploaded_file($this->file, $targetPath);

            if (false === $this->moved) {
                throw new \RuntimeException(sprintf('Uploaded file could not be moved to "%s": %s', $targetPath, error_get_last()['message'] ?? ''));
            }
        } else {
            $stream = $this->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            if (false === $resource = @fopen($targetPath, 'w')) {
                throw new \RuntimeException(sprintf('The file "%s" cannot be opened: %s', $targetPath, error_get_last()['message'] ?? ''));
            }

            $dest = Stream::create($resource);

            while (!$stream->eof()) {
                if (!$dest->write($stream->read(1048576))) {
                    break;
                }
            }

            $this->moved = true;
        }
    }

    /** {@inheritdoc} */
    public function getSize(): int
    {
        return $this->size;
    }

    /** {@inheritdoc} */
    public function getError(): int
    {
        return $this->error;
    }

    /** {@inheritdoc} */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /** {@inheritdoc} */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
