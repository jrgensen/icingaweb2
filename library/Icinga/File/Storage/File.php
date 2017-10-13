<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Storage;

use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;

class File
{
    /**
     * Get this file's name
     *
     * @return  string
     */
    public function getName()
    {
    }

    /**
     * Load this file's data
     *
     * @return  string|null
     *
     * @throws  NotReadableError
     */
    public function readData()
    {
    }

    /**
     * Overwrite this file's data
     *
     * @param   string|null $data
     *
     * @return  $this
     *
     * @throws  NotWritableError
     */
    public function updateData($data)
    {
    }

    /**
     * Get the absolute path to this file in the local file system
     *
     * If the bucket's storage is not local, the file will be downloaded to a temporary local file first.
     *
     * @return  string
     *
     * @throws  NotReadableError
     * @throws  NotWritableError
     */
    public function getLocalPath()
    {
    }

    /**
     * Overwrite this file's data with the given file's content
     *
     * @param   string  $file
     *
     * @return  $this
     *
     * @throws  NotReadableError
     * @throws  NotWritableError
     */
    public function updateDataFromFile($file)
    {
    }
}
