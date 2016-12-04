<?php

namespace SabreAWS\EFS;

use Sabre\DAV;
use Sabre\HTTP\URLUtil;

/**
 * Base node-class
 *
 * The node class implements the method used by both the File and the Directory classes
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Node implements DAV\INode {

    /**
     * The path to the current node
     *
     * @var string
     */
    protected $path;

    /**
     * Sets up the node, expects a full path name
     *
     * @param string $path
     */
    function __construct($path) {

        $this->path = $path;
        $key = $this->getName();
		$log = get_class($this) . "  : {$key}".PHP_EOL;
		file_put_contents('/tmp/dav.txt', $log, FILE_APPEND);
    }



    /**
     * Returns the name of the node
     *
     * @return string
     */
    function getName() {

        list(, $name)  = URLUtil::splitPath($this->path);
        return $name;

    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    function setName($name) {

        list($parentPath, ) = URLUtil::splitPath($this->path);
        list(, $newName) = URLUtil::splitPath($name);

        $newPath = $parentPath . '/' . $newName;
        rename($this->path, $newPath);

        $this->path = $newPath;

    }

    /**
     * Returns the last modification time, as a unix timestamp
     *
     * @return int
     */
    function getLastModified() {

        return filemtime($this->path);

    }

}
