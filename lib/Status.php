<?php

namespace FriendsOfREDAXO;

use rex;
use rex_addon;
use rex_article;
use rex_i18n;
use rex_install_packages;
use rex_path;
use rex_request;
use rex_sql;
use rex_sql_exception;
use rex_url;
use rex_yform_rest;
use rex_yform_rest_route;
use rex_yrewrite;

use function count;
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
            $updateUrl = rex_url::backendPage('install/packages/update', [
                'addonkey' => $addonKey,
            ]);
            $title = $addon->getName() . ' [' . $addon->getVersion() . ']';
            $output[] = [
                'title' => "<a href=\"$updateUrl\">$title</a>",
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

    /**
     * Get all routes.
     */
    public function getYFormRoutes(): array
    {
        $output = [];

        if (rex_addon::get('yform')->isAvailable() && rex_addon::get('yform')->getPlugin('rest')->isAvailable()) {
            $routes = rex_yform_rest::getRoutes();
            $public = '<i class="rex-icon fa-unlock text-warning"></i>';
            $secured = '<i class="rex-icon fa-lock text-success"></i>';

            /** @var rex_yform_rest_route $route */
            foreach ($routes as $route) {
                $output[] = [
                    'title' => $route->getPath(),
                    'value' => !$route->hasAuth() ? "$secured Authentifizierung erforderlich" : "$public Keine Authentifizierung erforderlich",
                ];
            }
        }

        return $output;
    }

    /**
     * Get cronjobs.
     * @throws rex_sql_exception
     */
    public function getCronjobs(): array
    {
        $output = [];

        if (rex_addon::get('cronjob')->isAvailable()) {
            $sql = rex_sql::factory();
            $cronjobs = $sql->getArray('SELECT id, name, environment, status FROM ' . rex::getTable('cronjob') . ' ORDER BY status DESC');
            $active = '<i class="rex-icon fa-toggle-on text-success"></i>';
            $inactive = '<i class="rex-icon fa-toggle-off text-danger"></i>';

            foreach ($cronjobs as $cronjob) {
                $env = [];
                if (str_contains($cronjob['environment'], '|frontend|')) {
                    $env[] = rex_i18n::msg('cronjob_environment_frontend');
                }
                if (str_contains($cronjob['environment'], '|backend|')) {
                    $env[] = rex_i18n::msg('cronjob_environment_backend');
                }
                if (str_contains($cronjob['environment'], '|script|')) {
                    $env[] = rex_i18n::msg('cronjob_environment_script');
                }

                $url = rex_url::backendPage('cronjob/cronjobs', [
                    'func' => 'edit',
                    'oid' => (int) $cronjob['id'],
                ]);
                $title = $cronjob['name'];
                $value = $cronjob['status'] ? $active : $inactive;
                $value .= ' ' . implode(', ', $env);

                $output[] = [
                    'title' => "<a href=\"$url\">$title</a>",
                    'value' => $value,
                ];
            }
        }

        return $output;
    }

    /**
     * Get Error handling and debugging.
     */
    public function getErrorHandlingAndDebugging(): array
    {
        $errorLevels = [
            E_ALL => 'E_ALL',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            E_DEPRECATED => 'E_DEPRECATED',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_STRICT => 'E_STRICT',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_NOTICE => 'E_NOTICE',
            E_PARSE => 'E_PARSE',
            E_WARNING => 'E_WARNING',
            E_ERROR => 'E_ERROR'];

        return [
            [
                'title' => 'Error Reporting',
                'value' => $errorLevels[error_reporting()],
            ],
            [
                'title' => 'Debugging (Display Errors)',
                'value' => ini_get('display_errors'),
                'status' => (bool) !ini_get('display_errors'),
            ],
            [
                'title' => 'Debugging (Display Startup Errors)',
                'value' => ini_get('display_startup_errors'),
                'status' => (bool) !ini_get('display_startup_errors'),
            ],

        ];
    }

    /**
     * Get directory and database sizes.
     */
    public function getDirectoryAndDatabaseSizes(): array
    {
        $spinner = '<svg class="spinning spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>';

        $output = [
            [
                'title' => 'Media Ordnergröße',
                'value' => '<span class="dir-size" data-path="' . rex_path::media() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
            [
                'title' => 'Data Ordnergröße',
                'value' => '<span class="dir-size" data-path="' . rex_path::data() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
            [
                'title' => 'Src Ordnergröße',
                'value' => '<span class="dir-size" data-path="' . rex_path::src() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
            [
                'title' => 'Cache Ordnergröße',
                'value' => '<span class="dir-size" data-path="' . rex_path::cache() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
        ];

        return array_merge($output, $this->getDatabaseSize());
    }

    /**
     * Get Database size.
     */
    private function getDatabaseSize(): array
    {
        $sql = rex_sql::factory();
        $tableData = $sql->getArray('SHOW TABLE STATUS');
        $size = 0;

        if (0 === count($tableData)) {
            return [];
        }

        foreach ($tableData as $data) {
            $size += $data['Data_length'] + $data['Index_length'];
        }

        return [
            [
                'title' => 'Datenbankgröße',
                'value' => number_format($size / (1024 * 1024), 2) . ' MB',
            ],
        ];
    }
}
