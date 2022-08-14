<?php

namespace Nahid\UrlFactory;

use Spatie\UrlSigner\Exceptions\InvalidExpiration;
use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use Spatie\UrlSigner\MD5UrlSigner;
use Spatie\UrlSigner\UrlSigner;

class Url
{
    protected string $url = '';

    protected array $meta = [];

    protected array $config = [];

    protected ?UrlSigner $urlSigner = null;


    public function __construct(string $url, array $config = [])
    {
        $this->config = $config;
        $this->extractUrl($url);
    }

    public function get(): string
    {
        $this->make();

        return $this->url;
    }

    public function getDomain(): string
    {
        return $this->meta['domain'];
    }

    public function getExtension(): string
    {
        return $this->meta['extension'] ?? '';
    }

    public function getSubdomain(): string
    {
        return $this->meta['subDomain'] ?? '';
    }

    public function addQueryParam(string $key, string|int|float $value): self
    {
        $this->meta['queryParams'][$key] = $value;
        $this->meta['query'] = http_build_query($this->meta['queryParams']);

        return $this;
    }

    public function concatUri(string $uri): self
    {
        $path = $this->meta['path'] ?? '';
        $path = rtrim($path, '/');
        $uri = '/' . ltrim($uri, '/');

        $this->meta['path'] = $path . $uri;

        return $this;
    }

    public function useSchemeHttps(): self
    {
        $this->meta['scheme'] = 'https';

        return $this;
    }

    public function useSchemeHttp(): self
    {
        $this->meta['scheme'] = 'http';

        return $this;
    }

    public function useDomain(string $domain): self
    {
        $this->extractDomain($domain);

        return $this;
    }

    public function useExtension(string $extension): self
    {
        $this->meta['extension'] = $extension;
        $domain = explode('.', $this->getDomain());
        $domain[count($domain) - 1] = $extension;
        $this->meta['domain'] = implode('.', $domain);

        return $this;
    }

    public function useHost(string $host): self
    {
        $this->extractDomain($host);

        return $this;
    }

    public function useSubdomain(string $subdomain): self
    {
        $this->meta['subDomain'] = $subdomain;

        return $this;
    }

    public function useFragment(string $fragment): self
    {
        $this->meta['fragment'] = $fragment;

        return $this;
    }

    public function getScheme(): string
    {
        return $this->meta['scheme'] ?? '';
    }

    public function getHost(): string
    {
        return $this->meta['host'] ?? '';
    }

    public function getPort(): int
    {
        return (int) $this->meta['port'] ?? 80;
    }

    public function getPath(): string
    {
        return $this->meta['path'] ?? '/';
    }

    public function getQuery(): string
    {
        return $this->meta['query'] ?? '';
    }

    public function getFragment(): string
    {
        return $this->meta['fragment'] ?? '';
    }


    public function getQueryParams(): array
    {
        return $this->meta['queryParams'] ?? [];
    }

    public function getQueryParam(string $key): string|int|float
    {
        return $this->meta['queryParams'][$key] ?? '';
    }

    public function getSegment(int $index): string
    {
        $segments = explode('/', ltrim($this->getPath(), '/'));
        return $segments[$index] ?? '';
    }

    public function useSegment(int $index, string $name): self
    {
        $segments = explode('/', ltrim($this->getPath(), '/'));

        if (!isset($segments[$index])) {
            throw new \Exception('No segment found at index ' . $index);
        }

        $segments[$index] = $name;
        $this->meta['path'] = '/' . implode('/', $segments);

        return $this;
    }

    public function isValid(): bool
    {
        $this->make();

        return filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }

    public function hasSubdomain(): bool
    {
        return !is_null($this->getSubdomain());
    }

    public function hasMultiLevelSubdomain(): bool
    {
        $subDomain = $this->getSubdomain();

        return str_contains($subDomain, '.');
    }

    /**
     * @throws InvalidSignatureKey
     * @throws InvalidExpiration
     */
    public function sign(\DateTime|int $expiration): self
    {
        $this->url = $this->getUrlSigner()->sign($this->get(), $expiration);
        $this->extractUrl($this->url);

        return $this;
    }

    public function validate(string $url): bool
    {
        return $this->getUrlSigner()->validate($url);
    }

    protected function make(): self
    {
        $url = $this->meta['scheme'] . '://';
        if ($this->hasSubdomain()) {
            $url .= $this->getSubdomain() . '.';
        }

        $url .= $this->getDomain();
        $url .= isset($this->meta['port']) ? ':' . $this->meta['port'] : '';
        $url .= $this->meta['path'];
        $url .= $this->meta['query'] ? '?' . $this->meta['query'] : '';
        $url .= $this->meta['fragment'] ? '#' . $this->meta['fragment'] : '';

        $this->url = $url;

        return $this;
    }

    protected function extractDomain(string $domain)
    {
        $host = explode('.', $domain);

        $subDomains = array_slice($host, 0, count($host) - 2);
        $domain = array_slice($host, count($host) - 2);

        $this->meta['extension'] = $domain[1];
        $this->meta['domain'] = implode('.', $domain);
        $host = '';

        if (count($subDomains) > 0) {
            $this->meta['subDomain'] = implode('.', $subDomains);
            $host = $this->meta['subDomain'] . '.';
        }

        $host = $this->meta['domain'];
        $this->meta['host'] = $host;
    }

    /**
     * @throws InvalidSignatureKey
     * @throws \Exception
     */
    protected function getUrlSigner(): MD5UrlSigner
    {
        $key = $this->config['key'] ?? null;
        if (!$key) {
            throw new \Exception('No key found in config');
        }

        if ($this->urlSigner) return $this->urlSigner;

        return new MD5UrlSigner($key);
    }

    protected function extractUrl(string $url): void
    {
        $this->url = $url;

        $this->meta = parse_url($this->url);
        $this->meta['subDomain'] = null;
        parse_str($this->meta['query'] ?? '', $queryParams);
        $this->meta['queryParams'] = $queryParams;

        $this->extractDomain($this->getHost());
    }

}