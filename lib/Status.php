<?php

namespace FriendsOfREDAXO;

use rex;
use rex_addon;
use rex_article;
use rex_install_packages;
use rex_request;
use rex_yrewrite;

use function extension_loaded;
use function function_exists;
use function ini_get;

class Status
{
    /**
     * Url to check.
     * @var string
     */
    private $url;

    /**
     * Headers.
     * @var array
     */
    private $headers;

    public function __construct()
    {
        $this->url = rex::getServer();

        /**
         * Check if yrewrite is available.
         */
        if (rex_addon::get('yrewrite')->isAvailable()) {
            $this->url = rex_yrewrite::getFullUrlByArticleId(rex_article::getSiteStartArticleId());
        }

        $this->headers = get_headers($this->url);
    }

    public function getAvailableUpdates(): array
    {
        $output = [];

        $availableUpdates = rex_install_packages::getUpdatePackages();

        foreach ($availableUpdates as $addonKey => $package) {
            $addon = rex_addon::get($addonKey);

            $output[] = [
                'title' => $package['name'] . ' [' . $addon->getVersion() . ']',
                'value' => end($package['files'])['version'],
            ];
        }

        return $output;
    }

    /**
     * Get inactive addons.
     */
    public function getInactiveAddons(): array
    {
        $output = [];

        foreach (rex_addon::getRegisteredAddons() as $addon) {
            /** @var rex_addon $addon */
            if (!$addon->isAvailable()) {
                $output[] = [
                    'title' => $addon->getName(),
                    'value' => 'Nicht aktiviert',
                    'status' => false,
                ];
            }
        }

        return $output;
    }

    /**
     * Get all security headers from the url.
     */
    public function getSecurityHeaders(): array
    {
        /**
         * Security headers to check.
         */
        $securityHeaders = [
            'Strict-Transport-Security',
            'Content-Security-Policy',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
        ];

        $output = [];

        foreach ($securityHeaders as $header) {
            if ($this->hasHeader($this->headers, $header)) {
                $output[] = [
                    'title' => $header,
                    'value' => 'OK',
                    'status' => true,
                ];
            } else {
                $output[] = [
                    'title' => $header,
                    'value' => 'Nicht vorhanden',
                    'status' => false,
                ];
            }
        }

        return $output;
    }

    /**
     * Get all caching headers from the url.
     */
    public function getCachingHeaders(): array
    {
        /**
         * Caching-Header to check.
         */
        $cachingHeaders = [
            'Cache-Control',
            'Expires',
            'Age',
            'Last-Modified',
            'ETag',
            'X-Cache-Enabled',
            'X-Cache-Disabled',
            'X-Srcache-Store-Status',
            'X-Srcache-Fetch-Status',
        ];

        $output = [];

        foreach ($cachingHeaders as $header) {
            if ($this->hasHeader($this->headers, $header)) {
                $values = [];

                foreach ($this->headers as $h) {
                    if (str_starts_with($h, $header)) {
                        $values[] = $h;
                    }
                }

                $output[] = [
                    'title' => $header,
                    'value' => implode('<br>', $values),
                    'status' => true,
                ];
            } else {
                $output[] = [
                    'title' => $header,
                    'value' => 'Nicht vorhanden',
                    'status' => false,
                ];
            }
        }

        return $output;
    }

    /**
     * Check if a header is present.
     */
    private function hasHeader(array $headers, string $headerName): bool
    {
        foreach ($headers as $header) {
            if (str_starts_with($header, $headerName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get server information.
     */
    public function getServerArchitecture(): array
    {
        $serverArchitecture = php_uname('m');
        $serverSoftware = rex_request::server('SERVER_SOFTWARE', 'string');
        $phpVersion = PHP_VERSION;
        $phpSAPI = PHP_SAPI;
        $maxInputVars = ini_get('max_input_vars');
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');
        $maxInputTime = ini_get('max_input_time');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        if (function_exists('curl_version')) {
            $curlVersion = curl_version();
            $curlVersion = $curlVersion['version'];
        } else {
            $curlVersion = 'cURL ist nicht verfügbar.';
        }
        $isImagickAvailable = extension_loaded('imagick') ? 'Ja' : 'Nein';
        $currentTime = date('Y-m-d H:i:s');
        $currentUtcTime = gmdate('Y-m-d H:i:s');
        $currentServerTime = date_default_timezone_get();

        return [
            [
                'title' => 'Server-Architektur',
                'value' => $serverArchitecture,
            ],
            [
                'title' => 'Webserver',
                'value' => $serverSoftware,
            ],
            [
                'title' => 'PHP-Version',
                'value' => $phpVersion,
            ],
            [
                'title' => 'PHP-SAPI',
                'value' => $phpSAPI,
            ],
            [
                'title' => 'Maximale PHP-Eingabe-Variablen (max_input_vars)',
                'value' => $maxInputVars,
            ],
            [
                'title' => 'Maximale PHP-Ausführungszeit (max_execution_time)',
                'value' => $maxExecutionTime . 'Sekunden',
            ],
            [
                'title' => 'PHP-Speicher-Limit (memory_limit)',
                'value' => $memoryLimit,
            ],
            [
                'title' => 'Maximale Eingabe-Zeit (max_input_time)',
                'value' => $maxInputTime . 'Sekunden',
            ],
            [
                'title' => 'Maximale Dateigröße beim Upload (upload_max_filesize)',
                'value' => $uploadMaxFilesize,
            ],
            [
                'title' => 'Maximale Größe der PHP-Post-Daten (post_max_size)',
                'value' => $postMaxSize,
            ],
            [
                'title' => 'cURL-Version',
                'value' => $curlVersion,
            ],
            [
                'title' => 'Ist die Imagick-Bibliothek verfügbar?',
                'value' => $isImagickAvailable,
            ],
            [
                'title' => 'Aktuelle Zeit',
                'value' => $currentTime,
            ],
            [
                'title' => 'Aktuelle UTC-Zeit',
                'value' => $currentUtcTime,
            ],
            [
                'title' => 'Aktuelle Serverzeit',
                'value' => $currentServerTime,
            ],
        ];
    }

    /**
     * Get all constants.
     */
    public function getConstants(): array
    {
        $constants = get_defined_constants(true);

        $output = [];

        if (isset($constants['user'])) {
            foreach ($constants['user'] as $constantName => $constantValue) {
                $output[] = [
                    'title' => $constantName,
                    'value' => $constantValue,
                ];
            }
        } else {
            $output[] = [
                'title' => 'Keine Konstanten definiert',
                'value' => '',
            ];
        }

        return $output;
    }
}
