<?php

namespace Nahid\UrlFactory;

use Pdp\Storage\PsrStorageFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
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

    protected Domain $domainClass;


    /**
     * @param string|null $url
     * @param array $config
     * @param Domain|null $domain
     * @throws \Exception
     */
    public function __construct(?string $url = null, array $config = [], ?Domain $domain = null)
    {
        $this->config = $config;
        if (is_null($domain)) {
            $domain = new Domain(storage: $this->makePsrStorageFactory($config));
        }

        $this->domainClass = $domain;

        if (!is_null($url)) {
            $this->extractUrl($url);
        }

    }

    public function domain(?callable $fn = null): Domain|self
    {
        if (is_null($fn)) {
            return $this->domainClass;
        }

        $fn($this->domainClass);

        return $this;
    }

    public function useDomain(string $domain): self
    {
        $this->domainClass->parse($domain);
        return $this;
    }

    /**
     * @return string
     */
    public function get(): string
    {
        $this->make();

        return $this->url;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        return urlencode($this->get());
    }

    /**
     * @param string $url
     * @return $this
     * @throws \Exception
     */
    public function decode(string $url): self
    {
        $this->url = urldecode($url);
        $this->extractUrl($this->url);

        return $this;
    }

    /**
     * @return string
     */
    public function base64Encode(): string
    {
        return base64_encode($this->get());
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function base64Decode(): self
    {
        $this->url = base64_decode($this->url);
        $this->extractUrl($this->url);

        return $this;
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
     * @param array $keys
     * @return $this
     */
    public function removeParams(array $keys): self
    {
        $currentParams = $this->getQueryParams();
        $newParams = array_diff_key($currentParams, array_flip($keys));

        return $this->useQueryParams($newParams);

    }

    /**
     * @param array $params
     * @return $this
     */
    public function useQueryParams(array $params): self
    {
        $this->meta[Enum::URL_QUERY_PARAMS] = $params;
        $this->meta[Enum::URL_QUERY] = http_build_query($params);

        return $this;

    }

    /**
     * @param string $uri
     * @return $this
     */
    public function appendPath(string $uri): self
    {
        if (!$this->pregCheck($uri, '/^[a-z0-9.\-_\/]+$/i')) {
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
     * @param string $path
     * @return $this
     */
    public function usePath(string $path): self
    {
        $this->meta[Enum::URL_PATH] = '/' . ltrim($path, '/');
        return $this;
    }


    /**
     * @param int $port
     * @return $this
     */
    public function usePort(int $port): self
    {
        $this->meta[Enum::URL_PORT] = $port;

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
     * @return int
     */
    public function getPort(): int
    {
        return  (int) ($this->meta[Enum::URL_PORT] ?? 80);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->meta[Enum::URL_PATH] ?? '';
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

    public function isActiveHost(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $resp = @get_headers($this->get());

        return $resp !== false;
    }

    public function isValidUrl()
    {
        if (!$this->isValid()) {
            return false;
        }

        $resp = @get_headers($this->get(), true);

        return !($resp == false || $resp[0] != 'HTTP/1.1 200 OK');
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
        $url = '';
        $scheme = $this->getScheme();
        if (!empty($scheme)) {
            $url .= $scheme . '://';
        }

        $url .= $this->domain()->get();

        $port = $this->getPort();
        if ($port != 80 && $port != 443) {
            $url .= ':' . $port;
        }

        $path= $this->getPath();
        if (!empty($path)) {
            $url .= $path;
        }

        $query = $this->getQuery();
        if (!empty($query)) {
            $url .= '?' . $query;
        }

        $fragment = $this->getFragment();
        if (!empty($fragment)) {
            $url .= '#' . $fragment;
        }

        $this->url = $url;

        return $this;
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
        $this->domain()->parse($this->meta[Enum::URL_HOST]);

        parse_str($this->meta[Enum::URL_QUERY] ?? '', $queryParams);
        $this->meta[Enum::URL_QUERY_PARAMS] = $queryParams;

    }

    protected function pregCheck(string $string, string $pattern): bool
    {
        return preg_match($pattern, $string) === 1;
    }

    protected function mergeConfig(array $config): array
    {
        $defaultConfig = [
            Enum::CONFIG_KEY => 'secret-key',
            Enum::CONFIG_SIGNER => MD5UrlSigner::class,
            Enum::CONFIG_PSR_CACHE_INTERFACE => null,
            Enum::CONFIG_PSR_CLIENT_INTERFACE => null,
            Enum::CONFIG_PSR_REQUEST_FACTORY_INTERFACE => null,
        ];

        return array_merge($defaultConfig, $config);
    }

    protected function makePsrStorageFactory(?array $config = null): ?PsrStorageFactory
    {
        if (is_null($config)) {
            $config = $this->config;
        }
        $cacheClass = $config[Enum::CONFIG_PSR_CACHE_INTERFACE] ?? null;
        $clientClass = $config[Enum::CONFIG_PSR_CLIENT_INTERFACE] ?? null;
        $requestFactoryClass = $config[Enum::CONFIG_PSR_REQUEST_FACTORY_INTERFACE] ?? null;

        if (is_null($cacheClass)) {
            return null;
        }

        if (is_null($clientClass)) {
            return null;
        }

        if (is_null($requestFactoryClass)) {
            return null;
        }

        if (is_string($cacheClass)) {
            $cacheClass = new $cacheClass();
        }

        if (is_string($clientClass)) {
            $clientClass = new $clientClass();
        }

        if (is_string($requestFactoryClass)) {
            $requestFactoryClass = new $requestFactoryClass();
        }

        if (!$cacheClass instanceof CacheInterface) {
            return null;
        }

        if (!$clientClass instanceof ClientInterface) {
            return null;
        }

        if (!$requestFactoryClass instanceof RequestFactoryInterface) {
            return null;
        }

        return new PsrStorageFactory($cacheClass, $clientClass, $requestFactoryClass);

    }

}