<?php
namespace Lock;

/**
 *
 * PHP version 5.5
 *
 * @author  Sergey V.Kuzin <sergey@kuzin.name>
 * @license MIT
 */
class Lock
{
    /** @var \Psr\Cache\CacheItemPoolInterface */
    protected $cache = null;

    /** @var string */
    protected $key = null;

    public function __construct(\Psr\Cache\CacheItemPoolInterface $cache, $key = null)
    {
        $this->cache = $cache;

        if (null === $key) {
            global $argv;
            $this->key = 'lock-' . basename($argv[0]);
        } else {
            $this->key = 'lock-' . $key;
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = 'lock=' . $key;
        return $this;
    }

    /**
     * @return int
     */
    protected function getPid()
    {
        return getmypid();
    }

    /**
     * @param $pid
     *
     * @return bool
     */
    protected function proccessExists($pid)
    {
        $exists = false;

        if ($pid) {
            $exists = file_exists('/proc/' . $pid);
        }
        return $exists;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        $locked = false;

        if ($this->cache instanceof \Cache\FilesystemCache) {
            clearstatcache();
        }

        $key = $this->getKey();
        $item = $this->cache->getItem($key);

        if ($item->exists()) {
            $pid = $item->get();
            $locked = $this->proccessExists($pid);

            if (!$locked) {
                $this->cache->deleteItems([$key]);
            }
        }
        return $locked;
    }

    protected function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return [$sec, substr($usec, 2)];
    }

    /**
     * @param int $wait
     *
     * @return bool
     * @throws \Exception
     */
    public function lock($waitSeconds = 0, $waitNanoseconds = 0)
    {
        if ($this->isLocked()) {
            $start = $this->getMicrotime();
            usleep(50000);
            $time = $this->getMicrotime();
            while ($time[0] - $start[0] <= $waitSeconds &&
                $time[1] - $start[1] <= $waitNanoseconds &&
                $this->isLocked()) {
                usleep(50000);
            }
            if ($this->isLocked()) {
                throw new \Exception('Resource busy');
            }
        }

        $this->cache->save(
            $this->cache->getItem($this->getKey())->set($this->getPid())
        );

        return true;
    }

    /**
     * @return $this
     */
    public function unlock()
    {
        if ($this->isLocked()) {
            $this->cache->deleteItems([$this->getKey()]);
        }
        return $this;
    }
}
