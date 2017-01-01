<?php
declare(strict_types = 1);

namespace mheinzerling\commons;


class ExtensionFtpConnection implements FtpConnection
{
    /**
     * @var resource
     */
    private $connection_id;

    public function __construct(string $server, string $user, string $password, bool $passive = true)
    {
        $this->connection_id = ftp_connect($server);
        if ($this->connection_id === false) throw new FtpException("Connection to " . $server . "failed");
        if (!(ftp_login($this->connection_id, $user, $password))) throw new FtpException("Invalid login $user@$server");
        if ($passive && !ftp_pasv($this->connection_id, true)) throw new FtpException("Enable passive mode failed");
    }

    public function __destruct()
    {
        ftp_quit($this->connection_id);
    }

    /**
     * @param string $dir
     * @param string|null $pregFilter
     * @param bool $basename
     * @return string[]
     */
    public function ls($dir = "", string $pregFilter = null, $basename = false): array
    {
        $entries = ftp_nlist($this->connection_id, $dir); //enable passive mode if this call is hanging
        if ($entries === false) return [];
        if ($pregFilter === null && $basename === false) return $entries;
        $result = [];
        foreach ($entries as $entry) {
            if ($basename) $entry = basename($entry);
            if ($pregFilter === null || preg_match($pregFilter, $entry)) $result[] = $entry;
        }
        return $result;
    }

    public function mkdir(string $dir): string
    {
        $dir = ftp_mkdir($this->connection_id, $dir);
        if ($dir === false) throw new FtpException("Couldn't create directory >$dir<");
        return $dir;
    }

    public function delete(string $file, $ignore): void
    {
        $deleted = ftp_delete($this->connection_id, $file);
        if ($deleted === false) throw new FtpException("Couldn't delete file >$file<");
    }

    /**
     * @param $target
     * @param string|resource $source
     * @param int $mode
     * @param callable|\Closure $progressCallback with parameters $serverSize and $localSize
     * @throws \Exception
     */
    public function upload(string $target, $source, int $mode = FTP_ASCII, \Closure $progressCallback = null): void
    {
        if (is_resource($source)) {
            $fh = $source;
            $stats = fstat($fh);
            $localSize = $stats['size'];
        } else {
            $localSize = filesize($source);
            $fh = fopen($source, "r");
        }
        $state = ftp_nb_fput($this->connection_id, $target, $fh, $mode);
        while ($state == FTP_MOREDATA) {
            if ($progressCallback != null) {
                $serverSize = ftell($fh);
                $progressCallback($serverSize, $localSize);
            }
            $state = ftp_nb_continue($this->connection_id);
        }

        if ($state != FTP_FINISHED) {
            throw new FtpException("Upload didn't finished and is in state $state");
        }
        if ($progressCallback != null) {
            $progressCallback($localSize, $localSize);
        }
    }

    public function get(string $target, $mode = FTP_ASCII): ?string
    {
        $temp = fopen('php://memory', 'r+');
        if (ftp_fget($this->connection_id, $temp, $target, $mode, 0)) {
            rewind($temp);
            return stream_get_contents($temp);
        } else {
            throw new FtpException("Error downloading file $target");
        }
    }
}