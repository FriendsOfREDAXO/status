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
     *
     * This function calculates the total size of all files within a specified directory and its subdirectories.
     * If the specified path is not a directory, it returns 0.
     *
     * @param string $path the path of the directory
     *
     * @throws UnexpectedValueException if the path cannot be opened
     * @throws FilesystemIterator::SKIP_DOTS if there is an error skipping dot files
     *
     * @return int the total size of all files within the directory and its subdirectories in bytes
     */
    public function getDirSize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD,
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
