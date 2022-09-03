<?php
declare(strict_types=1);

namespace FileUploader\FilePathProcessor;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Psr\Http\Message\UploadedFileInterface;

/**
 * The FileProcessorInterface
 *
 * All method call must return an idempotent response
 * This implementation configures the root_dir,dir,filename,extension,url
 */
interface FilePathProcessorInterface
{
    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table  $table The instance managing the entity
     * @param \Cake\Datasource\EntityInterface $entity the entity to construct a path for.
     * @param \Psr\Http\Message\UploadedFileInterface $data the data being submitted for a save
     * @param string           $field the field for which data will be saved
     * @param array            $settings the settings for the current field
     */
    public function __construct(
        Table $table,
        EntityInterface $entity,
        UploadedFileInterface $data,
        string $field,
        array $settings
    );

    /**
     * The root directory where we place our directory structure and the file.
     *
     * For cloud filesystem MUST be the container's/bucket's name
     * For local filesystem the WWW_ROOT is a good default idea. Must contain the trailing slash.
     * This value will be saved in the database as `root_dir`
     *
     * @return string On local host returns a directory on cloud the container's name
     */
    public function getRootDirectory(): string;

    /**
     * The (relative) directory where we place the uploaded file. No trailing slash needed.
     * This value will be saved in the database as `dir`
     *
     * @return string The upload directory
     */
    public function getDirectory(): string;

    /**
     * Generates a filename for the uploaded file (without extension)
     * The filename must match for this regex: /^[A-Za-z0-9\-\_\.]+$/
     * This value will be saved in the database as `filename`
     *
     * @return string the generated filename
     */
    public function getFilename(): string;

    /**
     * Returns the file's extension
     * This value will be saved in the database as `ext`
     *
     * @return string the generated filename
     */
    public function getFileExtension(): string;

    /**
     * Returns the full URL from where the image is visible for the world
     * This value will be saved in the database as `url` value
     * The result can be null
     *
     * @return string|null the generated URL
     */
    public function getUrl(): ?string;
}
