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
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
