<?php
declare(strict_types=1);

namespace FileUploader\Model\Entity;

use Cake\ORM\Entity;

/**
 * UploadedFile Entity
 *
 * @property string $id
 * @property string $root_dir
 * @property string $dir
 * @property string $filename
 * @property string $ext
 * @property string $original_filename
 * @property string $url
 * @property int $size
 * @property string $type
 * @property string $sha1_hash
 * @property array $metadata
 * @property string|null $cloud_provider
 * @property \Cake\I18n\FrozenTime $created
 * @property string $full_path
 * @property bool $is_local_file
 * @property string $full_filename
 */
class UploadedFile extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'root_dir' => true,
        'dir' => true,
        'filename' => true,
        'ext' => true,
        'original_filename' => true,
        'url' => true,
        'size' => true,
        'type' => true,
        'sha1_hash' => true,
        'metadata' => true,
        'cloud_provider' => true,
        'created' => true,
    ];

    /**
     * @var string[] Hidden properties
     */
    protected $_hidden = [
        'root_dir',
        'dir',
        'filename',
        'ext',
        'original_filename',
        'size',
        'type',
        'sha1_hash',
        'metadata',
        'cloud_provider',
        'created',
    ];

    /**
     * Return's the file's full path
     *
     * @return string the file's path
     */
    protected function _getFullPath(): string
    {
        if ($this->is_local_file) {
            return $this->_fields['root_dir'] . $this->_fields['dir'] . DS . $this->full_filename;
        }

        return $this->_fields['root_dir'] . DS . $this->_fields['dir'] . DS . $this->full_filename;
    }

    /**
     * @return bool Returns true if the image was uploaded to local filesystem, false otherwise
     */
    protected function _getIsLocalFile(): bool
    {
        return is_null($this->_fields['cloud_provider']);
    }

    /**
     * @return string Returns the filename with extension
     */
    protected function _getFullFilename(): string
    {
        return $this->_fields['filename'] . '.' . $this->_fields['ext'];
    }
}
