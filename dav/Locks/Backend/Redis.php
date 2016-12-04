<?php

namespace SabreAWS\Locks\Backend;

use Sabre\DAV\Locks\LockInfo;
use Sabre\DAV\Locks\Backend\AbstractBackend;
use Predis\Client;
use Predis\Collection\Iterator;
/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in a database. You must pass a PDO
 * connection object in the constructor.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Redis extends AbstractBackend {

    /**
     * The PDO tablename this backend uses.
     *
     * @var string
     */
    public $tableName = 'locks';

    /**
     * The PDO connection object
     *
     * @var pdo
     */
    protected $redis;

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Returns a list of Sabre\DAV\Locks\LockInfo objects
     *
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * If returnChildLocks is set to true, this method should also look for
     * any locks in the subtree of the uri for locks.
     *
     * @param string $uri
     * @param bool $returnChildLocks
     * @return array
     */
    function getLocks($uri, $returnChildLocks)
    {
        $keys = [];
        $path = trim(str_replace('/',':',$uri),':');
        $pattern = "DAV:Lock:{$path}:*";

        foreach (new Iterator\Keyspace($this->redis, $path) as $key)
        {
            $keys[$key] = $key;
        }

        // NOTE: the following 10 lines or so could be easily replaced by
        // pure sql. MySQL's non-standard string concatenation prevents us
        // from doing this though.
        $query = 'SELECT owner, token, timeout, created, scope, depth, uri FROM ' . $this->tableName . ' WHERE (created > (? - timeout)) AND ((uri = ?)';
        $params = [time(),$uri];

        // We need to check locks for every part in the uri.
        $uriParts = explode('/', $uri);

        // We already covered the last part of the uri
        array_pop($uriParts);

        $currentPath = '';

        foreach ($uriParts as $part) {

            if ($currentPath) $currentPath .= '/';
            $currentPath .= $part;

            $query .= ' OR (depth!=0 AND uri = ?)';
            $params[] = $currentPath;

        }

        if ($returnChildLocks) {

            $query .= ' OR (uri LIKE ?)';
            $params[] = $uri . '/%';

        }
        $query .= ')';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        $lockList = [];
        foreach ($result as $row) {

            $lockInfo = new LockInfo();
            $lockInfo->owner = $row['owner'];
            $lockInfo->token = $row['token'];
            $lockInfo->timeout = $row['timeout'];
            $lockInfo->created = $row['created'];
            $lockInfo->scope = $row['scope'];
            $lockInfo->depth = $row['depth'];
            $lockInfo->uri   = $row['uri'];
            $lockList[] = $lockInfo;

        }

        return $lockList;

    }

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param LockInfo $lockInfo
     * @return bool
     */
    function lock($uri, LockInfo $lockInfo)
    {
        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 30 * 60;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $key = $this->makeKey($uri,$lockInfo);
        $this->redis->hmset($this->makeKey($uri,$lockInfo),[
            'owner' => $lockInfo->owner,
            'timeout' => $lockInfo->timeout,
            'scope' => $lockInfo->scope,
            'depth' => $lockInfo->depth,
            'uri' => $uri,
            'created' => $lockInfo->created,
            'token' => $lockInfo->token
        ]);
        $this-redis->expire($key,$lockInfo->timeout);
        return true;
    }

    function makeKey($uri, LockInfo $lockInfo)
    {
        $path = str_replace('/',':',$uri);
        $path = trim($path,':');
        return "DAV:Lock:{$path}:{$lockInfo->token}";
    }

    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param LockInfo $lockInfo
     * @return bool
     */
    function unlock($uri, LockInfo $lockInfo)
    {
        $path = str_replace('/',':',$uri);
        $path = trim($path,':');
        return $this->redis->del($this->makeKey($uri,$lockInfo)) === 1;
    }

}
