<?php
declare(strict_types=1);

namespace FileUploader\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;

/**
 * Custom file type
 */
class FileType extends BaseType
{
    /**
     * @inheritDoc
     */
    public function marshal(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        if (is_numeric($value)) {
            return (string)$value;
        }

        return $value;
    }
}
