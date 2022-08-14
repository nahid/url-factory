<?php

namespace Nahid\UrlFactory;

class Parser
{
    protected string $url = '';

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function parse()
    {
        $url = parse_url($this->url);

        return $url;
    }
}