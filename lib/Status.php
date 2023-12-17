<?php

namespace FriendsOfREDAXO;

use rex;
use rex_addon;
use rex_functional_exception;
use rex_i18n;
use rex_install_packages;
use rex_path;
use rex_request;
use rex_sql;
use rex_sql_exception;
use rex_url;
use rex_yform_rest;

use rex_yform_rest_route;

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
     * Addon.
     * @var rex_addon
     */
    private $addon;

    /**
     * Headers.
     * @var array
     */
    private $headers;

    public function __construct()
    {
        $this->url = rex::getServer();
        $this->addon = rex_addon::get('status');

        /**
         * Validate url.
         */
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            $protocol = rex_server('HTTPS') ? 'https' : 'http';
            $host = rex_server('HTTP_HOST');
            $this->url = "$protocol://$host";
        }

        $this->headers = get_headers($this->url);
    }

    /**
     * Fetches the available updates for the installed packages.
     *
     * This function retrieves the list of installed packages that have updates available.
     * For each package, it creates an array containing the title (which is a link to the update page)
     * and the version of the available update.
     *
     * @throws rex_functional_exception
     * @return array An array of associative arrays. Each associative array has two keys:
     *               'title' - a string that contains a link to the update page of the package,
     *               'value' - a string that represents the version of the available update.
     */
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
     *
     * This method returns an array of all registered addons that are currently inactive.
     * Each addon in the array is represented as an associative array with the following keys:
     * - 'title': The name of the addon.
     * - 'value': A string indicating that the addon is not activated.
     * - 'status': A boolean value indicating the activation status of the addon (false means inactive).
     *
     * @return array an array of associative arrays, each representing an inactive addon
     */
    public function getInactiveAddons(): array
    {
        $output = [];

        foreach (rex_addon::getRegisteredAddons() as $addon) {
            /** @var rex_addon $addon */
            if (!$addon->isAvailable()) {
                $output[] = [
                    'title' => $addon->getName(),
                    'value' => $this->i18n('not_activated'),
                    'status' => false,
                ];
            }
        }

        return $output;
    }

    /**
     * Get all security headers from the url.
     *
     * This method returns an array of all checked security headers.
     * Each header in the array is represented as an associative array with the following keys:
     * - 'title': The name of the security header.
     * - 'value': A string indicating the status of the header. 'OK' if the header is present, 'not_activated' otherwise.
     * - 'status': A boolean value indicating the presence of the header (true means present).
     *
     * @return array an array of associative arrays, each representing a security header and its status
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
                    'value' => $this->i18n('not_activated'),
                    'status' => false,
                ];
            }
        }

        return $output;
    }

    /**
     * Get all caching headers from the url.
     *
     * This method returns an array of all checked caching headers.
     * Each header in the array is represented as an associative array with the following keys:
     * - 'title': The name of the caching header.
     * - 'value': A string containing the values of the header if present, or a localized 'not_activated' message otherwise.
     * - 'status': A boolean value indicating the presence of the header (true means present).
     *
     * @return array an array of associative arrays, each representing a caching header and its status
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
                    'value' => $this->i18n('not_activated'),
                    'status' => false,
                ];
            }
        }

        return $output;
    }

    /**
     * Check if a specific header is present in the provided headers array.
     *
     * This method iterates over the provided headers array and checks if any of the headers start with the provided header name.
     * If a match is found, the method returns true. If no match is found after checking all headers, the method returns false.
     *
     * @param array $headers An array of headers to check. Each header is a string.
     * @param string $headerName the name of the header to look for
     * @return bool returns true if the header is found, false otherwise
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
     *
     * This method returns an array of server information. Each piece of information is represented as an associative array with the following keys:
     * - 'title': The name of the information.
     * - 'value': The value of the information.
     *
     * The information includes server architecture, server software, PHP version, PHP SAPI, max input vars, max execution time, memory limit, max input time, upload max filesize, post max size, cURL version, Imagick availability, Xdebug availability, current time, current UTC time, and current server time.
     *
     * @return array an array of associative arrays, each representing a piece of server information
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
            $curlVersion = $this->i18n('curl_not_available');
        }
        $isImagickAvailable = extension_loaded('imagick') ? $this->i18n('yes') : $this->i18n('no');
        $isXdebugAvailable = extension_loaded('xdebug') ? $this->i18n('yes') : $this->i18n('no');
        $currentTime = date('Y-m-d H:i:s');
        $currentUtcTime = gmdate('Y-m-d H:i:s');
        $currentServerTime = date_default_timezone_get();

        return [
            [
                'title' => $this->i18n('server_architecture'),
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
                'title' => $this->i18n('max_input_vars'),
                'value' => $maxInputVars,
            ],
            [
                'title' => $this->i18n('max_execution_time'),
                'value' => $this->i18n('x_seconds', $maxExecutionTime),
            ],
            [
                'title' => $this->i18n('memory_limit'),
                'value' => $memoryLimit,
            ],
            [
                'title' => $this->i18n('max_input_time'),
                'value' => $this->i18n('x_seconds', $maxInputTime),
            ],
            [
                'title' => $this->i18n('upload_max_filesize'),
                'value' => $uploadMaxFilesize,
            ],
            [
                'title' => $this->i18n('post_max_size'),
                'value' => $postMaxSize,
            ],
            [
                'title' => 'cURL-Version',
                'value' => $curlVersion,
            ],
            [
                'title' => $this->i18n('imagick_available'),
                'value' => $isImagickAvailable,
            ],
            [
                'title' => $this->i18n('xdebug_available'),
                'value' => $isXdebugAvailable,
            ],
            [
                'title' => 'Aktuelle Zeit',
                'value' => $currentTime,
            ],
            [
                'title' => $this->i18n('current_time'),
                'value' => $currentUtcTime,
            ],
            [
                'title' => $this->i18n('current_server_time'),
                'value' => $currentServerTime,
            ],
        ];
    }

    /**
     * Get all user-defined constants.
     *
     * This method returns an array of all user-defined constants. Each constant is represented as an associative array with the following keys:
     * - 'title': The name of the constant.
     * - 'value': The value of the constant.
     *
     * If no user-defined constants are found, the method returns an array with a single element, an associative array with 'title' set to a localized 'no_constants_defined' message and 'value' set to an empty string.
     *
     * @return array an array of associative arrays, each representing a user-defined constant
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
                'title' => $this->i18n('no_constants_defined'),
                'value' => '',
            ];
        }

        return $output;
    }

    /**
     * Get all YForm routes.
     *
     * This method returns an array of all YForm routes if the 'yform' addon and its 'rest' plugin are available.
     * Each route is represented as an associative array with the following keys:
     * - 'title': The path of the route.
     * - 'value': A string containing an icon and a localized message indicating whether authentication is required for the route.
     *
     * If the 'yform' addon or its 'rest' plugin are not available, the method returns an empty array.
     *
     * @return array an array of associative arrays, each representing a YForm route
     */
    public function getYFormRoutes(): array
    {
        $output = [];

        if (rex_addon::get('yform')->isAvailable() && rex_addon::get('yform')->getPlugin('rest')->isAvailable()) {
            $routes = rex_yform_rest::getRoutes();
            $public = '<i class="rex-icon fa-unlock text-warning"></i>';
            $secured = '<i class="rex-icon fa-lock text-success"></i>';
            $required = $this->i18n('authentication_required');
            $notRequired = $this->i18n('authentication_not_required');

            /** @var rex_yform_rest_route $route */
            foreach ($routes as $route) {
                $output[] = [
                    'title' => $route->getPath(),
                    'value' => !$route->hasAuth() ? "$secured $required" : "$public $notRequired",
                ];
            }
        }

        return $output;
    }

    /**
     * Fetches the list of cronjobs.
     *
     * This function retrieves the list of cronjobs from the database. For each cronjob, it creates an array containing the title (which is a link to the cronjob page)
     * and the status of the cronjob (active or inactive) along with the environment it runs in.
     *
     * @throws rex_sql_exception if there is an error executing the SQL query
     *
     * @return array An array of associative arrays. Each associative array has two keys:
     *               'title' - a string that contains a link to the cronjob page,
     *               'value' - a string that represents the status of the cronjob and the environment it runs in.
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
     * Get error handling and debugging information.
     *
     * This method retrieves information about the current error handling and debugging settings.
     * It checks the values of various PHP configuration options related to error reporting, display, and logging,
     * as well as the REDAXO debug mode status.
     *
     * The information is returned in an array of associative arrays. Each associative array represents a piece of information and has the following keys:
     * - 'title': The name of the information, e.g., 'Display Errors', 'Error Reporting', 'Error Log', 'REDAXO Debug Mode'.
     * - 'value': The value of the information, which can be a boolean (represented as 'On' or 'Off') or a string.
     *
     * @return array an array of associative arrays, each representing a piece of error handling or debugging information
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
     *
     * This method retrieves the sizes of the specified directories and the database.
     * For each directory, it calculates the total size of all files in the directory.
     * For the database, it sums the data length and index length of all tables.
     *
     * The sizes are returned in an array of associative arrays. Each associative array represents a directory or the database and has the following keys:
     * - 'title': The name of the directory or 'Database'.
     * - 'value': The size of the directory or database in megabytes (MB), formatted to two decimal places.
     *
     * If a specified directory does not exist or is not readable, its size is not included in the returned data.
     *
     * @throws rex_sql_exception
     * @return array an array of associative arrays, each representing a directory or the database and its size
     */
    public function getDirectoryAndDatabaseSizes(): array
    {
        $spinner = '<svg class="spinning spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>';

        $output = [
            [
                'title' => $this->i18n('media_dir_size'),
                'value' => '<span class="dir-size" data-path="' . rex_path::media() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
            [
                'title' => $this->i18n('data_dir_size'),
                'value' => '<span class="dir-size" data-path="' . rex_path::data() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
            [
                'title' => $this->i18n('src_dir_size'),
                'value' => '<span class="dir-size" data-path="' . rex_path::src() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
            [
                'title' => $this->i18n('cache_dir_size'),
                'value' => '<span class="dir-size" data-path="' . rex_path::cache() . '">' . $spinner . ' Wird noch berechnet...</span>',
            ],
        ];

        return array_merge($output, $this->getDatabaseSize());
    }

    /**
     * Get the size of the database.
     *
     * This method retrieves the size of the database by summing the data length and index length of all tables.
     * The size is returned in megabytes (MB) and is formatted to two decimal places.
     *
     * If there are no tables in the database, the method returns an empty array.
     *
     * @throws rex_sql_exception
     * @return array An array of associative arrays, each representing a piece of database information. Each associative array has the following keys:
     * - 'title': The name of the information, which is a localized 'db_size' message.
     * - 'value': The value of the information, which is the size of the database in MB.
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
                'title' => $this->i18n('db_size'),
                'value' => number_format($size / (1024 * 1024), 2) . ' MB',
            ],
        ];
    }

    /**
     * Get the translation for the given key.
     *
     * This method retrieves the translation for the provided key from REDAXO's translation system.
     * If replacements are provided, they will be inserted into the translation string at the corresponding placeholders.
     *
     * @param string $key the key for the translation string to retrieve
     * @param array|string|null $replacements The replacements to insert into the translation string. Can be an array, a string, or null.
     * @return string the translated string with any replacements inserted
     */
    private function i18n(string $key, array|string|null $replacements = null): string
    {
        return $this->addon->i18n($key, $replacements);
    }
}
