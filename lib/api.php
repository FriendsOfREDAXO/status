<?php

class rex_api_status_dir_size extends rex_api_function
{
    public function execute()
    {
        $path = rex_get('path', 'string', '');

        if ('' === $path) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'Path is empty']);
            exit;
        }

        if (!file_exists($path) || !is_dir($path)) {
            rex_response::setStatus(rex_response::HTTP_NOT_FOUND);
            rex_response::sendJson(['error' => 'Path not found']);
            exit;
        }

        $bytes = $this->getDirSize($path);
        $kb = $bytes / 1024;
        $mb = $kb / 1024;

        rex_response::sendJson([
            'bytes' => $bytes,
            'kb' => $kb,
            'mb' => number_format($mb, 2) . ' MB',
        ]);

        exit;
    }

    /**
     * Get the size of a directory recursively.
     */
    public function getDirSize($path): int
    {
        $size = 0;

        if (is_dir($path)) {
            $files = scandir($path);

            foreach ($files as $file) {
                if ('.' !== $file && '..' !== $file) {
                    $filePath = $path . '/' . $file;

                    if (is_dir($filePath)) {
                        $size += $this->getDirSize($filePath);
                    } else {
                        $size += filesize($filePath);
                    }
                }
            }
        }

        return $size;
    }
}
