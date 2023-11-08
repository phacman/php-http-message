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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Trait implementing functionality common to requests and responses.
 *
 * @author Michael Dowling and contributors to guzzlehttp/psr7
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 * @author Pavel Vasin <phacman@yandex.ru>
 */
trait MessageTrait
{
    /** @var array Map of all registered headers, as original name => array of values */
    private array $headers = [];

    /** @var array Map of lowercase header name => original name at registration */
    private array $headerNames = [];

    /** @var string */
    private string $protocol = '1.1';

    /** @var StreamInterface|null */
    private ?StreamInterface $stream = null;

    /** {@inheritdoc} */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /** {@inheritdoc} */
    public function withProtocolVersion(string $version): MessageInterface
    {
        if ($this->protocol === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /** {@inheritdoc} */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** {@inheritdoc} */
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtr($name, StrTrEnum::FROM->value, StrTrEnum::TO->value)]);
    }

    /** {@inheritdoc} */
    public function getHeader(string $name): array
    {
        $header = strtr($name, StrTrEnum::FROM->value, StrTrEnum::TO->value);
        if (!isset($this->headerNames[$header])) {
            return [];
        }

        $header = $this->headerNames[$header];

        return $this->headers[$header];
    }

    /** {@inheritdoc} */
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /** {@inheritdoc} */
    public function withHeader(string $name, $value): MessageInterface
    {
        $value = $this->validateAndTrimHeader($name, $value);
        $normalized = strtr($name, StrTrEnum::FROM->value, StrTrEnum::TO->value);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }
        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = $value;

        return $new;
    }

    /** {@inheritdoc} */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        if ('' === $name) {
            throw new \InvalidArgumentException('Header name must be an RFC 7230 compatible string');
        }

        $new = clone $this;
        $new->setHeaders([$name => $value]);

        return $new;
    }

    /** {@inheritdoc} */
    public function withoutHeader(string $name): MessageInterface
    {
        $normalized = strtr($name, StrTrEnum::FROM->value, StrTrEnum::TO->value);
        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $header = $this->headerNames[$normalized];
        $new = clone $this;
        unset($new->headers[$header], $new->headerNames[$normalized]);

        return $new;
    }

    /** {@inheritdoc} */
    public function getBody(): StreamInterface
    {
        if (null === $this->stream) {
            $this->stream = Stream::create('');
        }

        return $this->stream;
    }

    /** {@inheritdoc} */
    public function withBody(StreamInterface $body): MessageInterface
    {
        if ($body === $this->stream) {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;

        return $new;
    }

    protected function setHeaders(array $headers): void
    {
        foreach ($headers as $header => $value) {
            if (\is_int($header)) {
                // If a header name was set to a numeric string, PHP will cast the key to an int.
                // We must cast it back to a string in order to comply with validation.
                $header = (string) $header;
            }
            $value = $this->validateAndTrimHeader($header, $value);
            $normalized = strtr($header, StrTrEnum::FROM->value, StrTrEnum::TO->value);
            if (isset($this->headerNames[$normalized])) {
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            } else {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }
    }

    /**
     * Make sure the header complies with RFC 7230.
     *
     * Header names must be a non-empty string consisting of token characters.
     *
     * Header values must be strings consisting of visible characters with all optional
     * leading and trailing whitespace stripped. This method will always strip such
     * optional whitespace. Note that the method does not allow folding whitespace within
     * the values as this was deprecated for almost all instances by the RFC.
     *
     * header-field = field-name ":" OWS field-value OWS
     * field-name   = 1*( "!" / "#" / "$" / "%" / "&" / "'" / "*" / "+" / "-" / "." / "^"
     *              / "_" / "`" / "|" / "~" / %x30-39 / ( %x41-5A / %x61-7A ) )
     * OWS          = *( SP / HTAB )
     * field-value  = *( ( %x21-7E / %x80-FF ) [ 1*( SP / HTAB ) ( %x21-7E / %x80-FF ) ] )
     *
     * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
     * @param mixed $header
     * @param mixed $values
     */
    protected function validateAndTrimHeader(mixed $header, mixed $values): array
    {
        if (!\is_string($header) || 1 !== preg_match("@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@D", $header)) {
            throw new \InvalidArgumentException('Header name must be an RFC 7230 compatible string');
        }

        if (!\is_array($values)) {
            // This is simple, just one value.
            if ((!is_numeric($values) && !\is_string($values)) || 1 !== preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string) $values)) {
                throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings');
            }

            return [trim((string) $values, " \t")];
        }

        if (empty($values)) {
            throw new \InvalidArgumentException('Header values must be a string or an array of strings, empty array given');
        }

        // Assert No empty array
        $returnValues = [];
        foreach ($values as $v) {
            if ((!is_numeric($v) && !\is_string($v)) || 1 !== preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@D", (string) $v)) {
                throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings');
            }

            $returnValues[] = trim((string) $v, " \t");
        }

        return $returnValues;
    }
}
