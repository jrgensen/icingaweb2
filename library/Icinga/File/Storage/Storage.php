<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Storage;

use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;

abstract class Storage
{
    /**
     * Get all existing buckets by ID
     *
     * @return  Bucket[]
     *
     * @throws  NotReadableError
     */
    public function getBuckets()
    {
    }

    /**
     * Get or create a bucket by ID
     *
     * A fresh bucket will be persisted once one file's content has been updated.
     *
     * @param   string  $id
     *
     * @return  Bucket
     */
    public function getOrCreateBucket($id)
    {
    }

    /**
     * Delete a bucket by ID if it exists
     *
     * @param   string  $id
     *
     * @return  bool        Whether the bucket existed
     *
     * @throws  NotReadableError
     * @throws  NotWritableError
     */
    public function deleteBucketIfExists($id)
    {
    }
}
