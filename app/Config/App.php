<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = '';

    public function __construct()
    {
        parent::__construct();

        $envAllowedHostnames = $this->resolveAllowedHostnamesFromEnv();
        if ($envAllowedHostnames !== []) {
            $this->allowedHostnames = $envAllowedHostnames;
        }

        if ($this->baseURL === '') {
            $isHttps = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $scheme  = $isHttps ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

            $this->baseURL = $scheme . '://' . $host . '/';
        }
    }

    /**
     * @return list<string>
     */
    private function resolveAllowedHostnamesFromEnv(): array
    {
        $raw = trim((string) env('app.allowedHostnames', ''));
        if ($raw === '') {
            return [];
        }

        $items = [];
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        } else {
            $items = preg_split('/[\r\n,]+/', $raw) ?: [];
        }

        $hostnames = [];
        foreach ($items as $item) {
            $hostname = trim((string) $item);
            if ($hostname === '') {
                continue;
            }

            $hostnames[] = $hostname;
        }

        return array_values(array_unique($hostnames));
    }

    /**
     * @var list<string>
     */
    public array $allowedHostnames = [
        'localhost',
    ];

    public string $indexPage = '';
    public string $uriProtocol = 'REQUEST_URI';
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';
    public string $defaultLocale = 'en';
    public bool $negotiateLocale = false;

    /**
     * @var list<string>
     */
    public array $supportedLocales = ['en'];

    public string $appTimezone = 'Asia/Kolkata';
    public string $charset = 'UTF-8';
    public bool $forceGlobalSecureRequests = false;

    /**
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    public bool $CSPEnabled = false;
}