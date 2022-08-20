<?php

namespace Nahid\UrlFactory;

use Pdp\ResolvedDomain;
use Pdp\Rules;
use Pdp\Domain as PdpDomain;
use Pdp\Storage\PsrStorageFactory;

class Domain
{
    protected ?Rules $tlds = null;
    protected ?PdpDomain $domain = null;
    protected ?ResolvedDomain $resolvedDomain = null;

    public function __construct(?string $domain = null, ?PsrStorageFactory $storage = null)
    {
        $path = '';
        if (is_null($storage)) {
            $path = __DIR__ . '/../storage/public_suffix_list.dat';
            $this->tlds = Rules::fromPath($path);
        }

        if ($storage) {
            $cache = $storage->createPublicSuffixListStorage('url-factory', 3600);
            $this->tlds = Rules::fromString($cache->get(PsrStorageFactory::PUBLIC_SUFFIX_LIST_URI));
        }

        $this->tlds = Rules::fromPath($path);
        if (!is_null($domain)) {
            $this->parse($domain);
        }
    }

    public function parse(string $domain): void
    {
        $this->domain = PdpDomain::fromIDNA2008($domain);
        $this->resolvedDomain = $this->tlds->resolve($this->domain);
    }

    public function instance(): ?ResolvedDomain
    {
        return $this->resolvedDomain;
    }

    public function get(): string
    {
        return $this->instance()->domain()->toString();
    }

    public function getRegistrableName(): string
    {
        return $this->instance()->registrableDomain()->toString();
    }

    public function hasSubdomain(): bool
    {
        return $this->instance()->subDomain()->count() > 0;
    }

    public function getSubdomain(): string
    {
        return $this->instance()->subDomain()->toString();
    }

    public function getBaseName(): string
    {
        return $this->instance()->secondLevelDomain()->toString();
    }

    /**
     * @return Rules|null
     */
    public function getSuffix(): string
    {
        return $this->instance()->suffix()->toString();
    }

    public function getTld(): string
    {
        return $this->instance()->suffix()->domain()->label(1);
    }

    public function get2ndLevelTld(): string
    {
        return $this->instance()->suffix()->domain()->label(0);
    }

    public function useSuffix(string $suffix): self
    {
        $this->resolvedDomain = $this->instance()->withSuffix(\Pdp\Domain::fromIDNA2008($suffix));

        return $this;
    }

    public function useSubdomain(string $subdomain): self
    {
        $this->resolvedDomain = $this->instance()->withSubdomain(\Pdp\Domain::fromIDNA2008($subdomain));
        return $this;
    }

    public function useBaseName(string $name): self
    {
        $this->resolvedDomain = $this->instance()->withSecondLevelDomain(\Pdp\Domain::fromIDNA2008($name));
        return $this;

    }



}