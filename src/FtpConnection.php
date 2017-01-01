<?php
declare(strict_types = 1);

namespace mheinzerling\commons;


interface FtpConnection
{
    /**
     * @param string $dir
     * @param string|null $pregFilter
     * @param bool $basename
     * @return string[]
     */
    public function ls($dir = "", string $pregFilter = null, $basename = false): array;

    public function mkdir(string $dir): string;

    public function delete(string $file, $ignore): void;

    /**
     * @param $target
     * @param string|resource $source
     * @param int $mode
     * @param callable|\Closure $progressCallback with parameters $serverSize and $localSize
     * @throws \Exception
     */
    public function upload(string $target, $source, int $mode = FTP_ASCII, \Closure $progressCallback = null): void;

    public function get(string $target, $mode = FTP_ASCII): ?string;

}