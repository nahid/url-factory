<?php

declare(strict_types=1);

namespace Nahid\UrlFactory;

interface Enum
{
    /**
     * URL Schema related enums
     */

    public const URL_SCHEME = 'scheme';
    public const URL_HOST = 'host';
    public const URL_PORT = 'port';
    public const URL_PATH = 'path';
    public const URL_FRAGMENT = 'fragment';
    public const URL_QUERY = 'query';
    public const URL_QUERY_PARAMS = 'queryParams';
    public const URL_USERNAME = 'user';
    public const URL_PASSWORD = 'pass';
    public const URL_DOMAIN = 'domain';
    public const URL_SUB_DOMAIN = 'subDomain';
    public const URL_EXTENSION = 'extension';


    /**
     * Config related enums
     */

    public const CONFIG_KEY = 'key';
    public const CONFIG_SIGNER = 'signer';
    public const CONFIG_PSR_CACHE_INTERFACE = 'psrCacheInterface';
    public const CONFIG_PSR_CLIENT_INTERFACE = 'psrClientInterface';
    public const CONFIG_PSR_REQUEST_FACTORY_INTERFACE = 'psrRequestFactoryInterface';
}