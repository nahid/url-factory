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


    /**
     * @param string $url
     * @param array $config
     */
    public function __construct(string $url, array $config = [])
    {
        $this->config = $config;
        $this->extractUrl($url);
    }

    /**
     * @return string
     */
    public function get(): string
    {
        $this->make();

        return $this->url;
    }

    public function encode(): string
    {
        return urlencode($this->get());
    }

    public function decode(string $url): self
    {
        $this->url = urldecode($url);
        $this->extractUrl($this->url);

        return $this;
    }

    public function base64Encode(): string
    {
        return base64_encode($this->get());
    }

    public function base64Decode(): self
    {
        $this->url = base64_decode($this->url);
        $this->extractUrl($this->url);

        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->meta[Enum::URL_DOMAIN];
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->meta[Enum::URL_EXTENSION] ?? '';
    }

    /**
     * @return string
     */
    public function getSubdomain(): string
    {
        return $this->meta[Enum::URL_SUB_DOMAIN] ?? '';
    }

    /**
     * @param string $key
     * @param string|int|float $value
     * @return $this
     */
    public function addQueryParam(string $key, string|int|float $value): self
    {
        $this->meta[Enum::URL_QUERY_PARAMS][$key] = $value;
        $this->meta[Enum::URL_QUERY] = http_build_query($this->meta[Enum::URL_QUERY_PARAMS]);

        return $this;
    }

    /**
     * @param string $uri
     * @return $this
     */
    public function concatUri(string $uri): self
    {
        if (!$this->pregCheck($uri, '/^[a-z0-9\-_\/]+$/i')) {
            throw new \Exception('Invalid URI');
        }

        $path = $this->meta[Enum::URL_PATH] ?? '';
        $path = rtrim($path, '/');
        $uri = '/' . ltrim($uri, '/');

        $this->meta[Enum::URL_PATH] = $path . $uri;

        return $this;
    }

    /**
     * @return $this
     */
    public function useSchemeHttps(): self
    {
        $this->meta[Enum::URL_SCHEME] = 'https';

        return $this;
    }

    /**
     * @return $this
     */
    public function useSchemeHttp(): self
    {
        $this->meta[Enum::URL_SCHEME] = 'http';

        return $this;
    }

    /**
     * @param string $domain
     * @return $this
     */
    public function useDomain(string $domain): self
    {
        if (!$this->pregCheck($domain, '/^(?:([a-z.]+))[a-z0-9\-]+.[a-z]{2,8}$/i')) {
            throw new \Exception('Invalid domain');
        }

        $this->extractDomain($domain);

        return $this;
    }

    /**
     * @param string $extension
     * @return $this
     */
    public function useExtension(string $extension): self
    {
        if (!$this->pregCheck($extension, '/^[a-z]{2,8}$/i')) {
            throw new \InvalidArgumentException('Invalid extension');
        }

        $this->meta[Enum::URL_EXTENSION] = $extension;
        $domain = explode('.', $this->getDomain());
        $domain[count($domain) - 1] = $extension;
        $this->meta[Enum::URL_DOMAIN] = implode('.', $domain);

        return $this;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function useHost(string $host): self
    {
        if (!$this->pregCheck($host, '/^(?:([a-z.]+))[a-z0-9\-]+.[a-z]{2,8}$')) {
            throw new \Exception('Invalid host');
        }

        $this->extractDomain($host);

        return $this;
    }

    /**
     * @param string $subdomain
     * @return $this
     */
    public function useSubdomain(string $subdomain): self
    {
        if (!$this->pregCheck($subdomain, '/^[a-z0-9.]+$/i')) {
            throw new \InvalidArgumentException('Subdomain must be alphanumeric');
        }
        $this->meta[Enum::URL_SUB_DOMAIN] = $subdomain;

        return $this;
    }

    /**
     * @param string $fragment
     * @return $this
     */
    public function useFragment(string $fragment): self
    {
        $this->meta[Enum::URL_FRAGMENT] = $fragment;

        return $this;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->meta[Enum::URL_SCHEME] ?? '';
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->meta[Enum::URL_HOST] ?? '';
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return (int) $this->meta[Enum::URL_PORT] ?? 80;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->meta[Enum::URL_PATH] ?? '/';
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->meta[Enum::URL_QUERY] ?? '';
    }

    /**
     * @return string
     */
    public function getFragment(): string
    {
        return $this->meta[Enum::URL_FRAGMENT] ?? '';
    }


    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->meta[Enum::URL_QUERY_PARAMS] ?? [];
    }

    /**
     * @param string $key
     * @return string|int|float
     */
    public function getQueryParam(string $key): string|int|float
    {
        return $this->meta[Enum::URL_QUERY_PARAMS][$key] ?? '';
    }

    /**
     * @param int $index
     * @return string
     */
    public function getSegment(int $index): string
    {
        $segments = explode('/', ltrim($this->getPath(), '/'));
        return $segments[$index] ?? '';
    }

    /**
     * @param int $index
     * @param string $name
     * @return $this
     * @throws \Exception
     */
    public function useSegment(int $index, string $name): self
    {
        $segments = explode('/', ltrim($this->getPath(), '/'));

        if (!isset($segments[$index])) {
            throw new \Exception('No segment found at index ' . $index);
        }

        $segments[$index] = $name;
        $this->meta[Enum::URL_PATH] = '/' . implode('/', $segments);

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        $this->make();

        return filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @return bool
     */
    public function hasSubdomain(): bool
    {
        return !is_null($this->getSubdomain());
    }

    /**
     * @return bool
     */
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

    /**
     * @param string $url
     * @return bool
     * @throws InvalidSignatureKey
     */
    public function validate(string $url): bool
    {
        return $this->getUrlSigner()->validate($url);
    }

    /**
     * @return $this
     */
    protected function make(): self
    {
        $url = $this->meta[Enum::URL_SCHEME] . '://';
        if ($this->hasSubdomain()) {
            $url .= $this->getSubdomain() . '.';
        }

        $url .= $this->getDomain();
        $url .= isset($this->meta[Enum::URL_PORT]) ? ':' . $this->meta[Enum::URL_PORT] : '';
        $url .= $this->meta[Enum::URL_PATH];
        $url .= $this->meta[Enum::URL_QUERY] ? '?' . $this->meta[Enum::URL_QUERY] : '';
        $url .= $this->meta[Enum::URL_FRAGMENT] ? '#' . $this->meta[Enum::URL_FRAGMENT] : '';

        $this->url = $url;

        return $this;
    }

    /**
     * @param string $domain
     * @return void
     * @throws \Exception
     */
    protected function extractDomain(string $domain)
    {
        $host = explode('.', $domain);

        $subDomains = array_slice($host, 0, count($host) - 2);
        $domain = array_slice($host, count($host) - 2);

        $this->meta[Enum::URL_EXTENSION] = $domain[1];
        $this->meta[Enum::URL_DOMAIN] = implode('.', $domain);
        $host = '';

        if (count($subDomains) > 0) {
            $this->meta[Enum::URL_SUB_DOMAIN] = implode('.', $subDomains);
            $host .= $this->meta[Enum::URL_SUB_DOMAIN] . '.';
        }

        $host .= $this->meta[Enum::URL_DOMAIN];
        $this->meta[Enum::URL_HOST] = $host;
    }

    /**
     * @throws InvalidSignatureKey
     * @throws \Exception
     */
    protected function getUrlSigner(): UrlSigner
    {
        $key = $this->config[Enum::CONFIG_KEY] ?? null;
        if (!$key) {
            throw new \Exception('No key found in config');
        }

        if ($this->urlSigner) return $this->urlSigner;

        $signerClass = $this->config[Enum::CONFIG_SIGNER] ?? MD5UrlSigner::class;
        $signer = new $signerClass($key);

        if (!$signer instanceof UrlSigner) {
            throw new InvalidSignatureKey('Signer must be an instance of UrlSigner');
        }

        return $signer;
    }

    /**
     * @param string $url
     * @return void
     * @throws \Exception
     */
    protected function extractUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid url');
        }

        $this->url = $url;

        $this->meta = parse_url($this->url);
        $this->meta[Enum::URL_SUB_DOMAIN] = null;
        parse_str($this->meta[Enum::URL_QUERY] ?? '', $queryParams);
        $this->meta[Enum::URL_QUERY_PARAMS] = $queryParams;

        $this->extractDomain($this->getHost());
    }

    protected function pregCheck(string $string, string $pattern): bool
    {
        return preg_match($pattern, $string) === 1;
    }

}