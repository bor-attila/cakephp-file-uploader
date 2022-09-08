<?php
declare(strict_types=1);

namespace FileUploader\Model\Behavior;

use Appwrite\ClamAV\Network;
use Appwrite\ClamAV\Pipe;
use ArrayObject;
use Aws\S3\S3Client;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use FileUploader\FilePathProcessor\CloudProcessor;
use FileUploader\FilePathProcessor\DefaultProcessor;
use FileUploader\Model\Table\UploadedFilesTable;
use Google\Cloud\Storage\StorageClient;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Upload behavior
 */
class UploadBehavior extends Behavior
{
    /**
     * The AWS S3 ID
     */
    public const S3 = 'aws_s3';

    /**
     * The MS Azure ID
     */
    public const MS_AZURE = 'ms_azure';

    /**
     * The Google Cloud ID
     */
    public const GOOGLE_CLOUD = 'google_cloud';

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [];

    /**
     * Initialize hook
     *
     * @param array $config The config for this behavior.
     * @return void
     */
    public function initialize(array $config): void
    {
        $configs = [];
        foreach ($config as $field => $settings) {
            if (is_int($field)) {
                $configs[$settings] = [];
            } else {
                $configs[$field] = $settings;
            }
        }
        $this->_config = $configs;

        $schema = $this->table()->getSchema();
        /** @var string $field */
        foreach (array_keys($this->getConfig()) as $field) {
            $schema->setColumnType($field, 'upload.file');
        }
        $this->table()->setSchema($schema);
    }

    /**
     * Modifies the data being marshalled to ensure invalid upload data is not inserted
     *
     * @param \Cake\Event\EventInterface $event an event instance
     * @param \ArrayObject $data data being marshalled
     * @param \ArrayObject $options options for the current event
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $validator = $this->table()->getValidator();
        $dataArray = $data->getArrayCopy();
        /** @var string $field */
        foreach (array_keys($this->getConfig(null, [])) as $field) {
            if (!$validator->isEmptyAllowed($field, false)) {
                continue;
            }
            if (!empty($dataArray[$field]) && $dataArray[$field]->getError() !== UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
    }

    /**
     * Modifies the entity before it is saved so that uploaded file data is persisted
     * in the database too.
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @param \ArrayObject $options the options passed to the save method
     * @return void|false
     * @throws \Exception
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        foreach ($this->getConfig(null, []) as $field => $settings) {
            if (is_int($field) || !$entity->isDirty($field)) {
                continue;
            }

            $data = $entity->get($field);
            if (!$data instanceof UploadedFileInterface || $data->getError() !== UPLOAD_ERR_OK) {
                $entity->set($field, $entity->getOriginal($field));
                $entity->setDirty($field, false);
                continue;
            }

            // before we even start to think about what we need to do
            // we need to perform a virus check if enabled
            if (Hash::get($settings, 'clamav.enabled', false)) {
                $socket = Hash::get($settings, 'clamav.socket');
                if ($socket) {
                    $clam = new Pipe($socket);
                } else {
                    $clam = new Network(
                        Hash::get($settings, 'clamav.host', 'localhost'),
                        Hash::get($settings, 'clamav.port', 3310)
                    );
                }

                if (!$clam->ping()) {
                    $entity->setError($field, [
                        'clamav-error' => __d('file_uploader', 'ClamAV in offline'),
                    ]);
                    continue;
                }

                if (!$clam->fileScan($data->getStream()->getMetadata('uri'))) {
                    $entity->setError($field, [
                        'clamav-error' => __d('file_uploader', 'The file is unreadable or dangerous/infected'),
                    ]);
                    continue;
                }
            }

            // The service or client instance
            $client = Hash::get($settings, 'client');

            // the file processor class
            $fileProcessor = Hash::get(
                $settings,
                'filePathProcessor',
                is_null($client) ? DefaultProcessor::class : CloudProcessor::class
            );

            /** @var \FileUploader\FilePathProcessor\FilePathProcessorInterface $processor */
            $processor = new $fileProcessor($this->table(), $entity, $data, $field, $settings);

            $image_data = [
                'filename' => $processor->getFilename(),
                'url' => $processor->getUrl(),
                'root_dir' => $processor->getRootDirectory(),
                'dir' => $processor->getDirectory(),
                'size' => $data->getSize(),
                'ext' => $processor->getFileExtension(),
                'original_filename' => $data->getClientFilename(),
                'type' => finfo_file(
                    finfo_open(FILEINFO_MIME_TYPE),
                    $data->getStream()->getMetadata('uri')
                ),
                'sha1_hash' => null,
                'origin' => $this->table()->getAlias(),
                'metadata' => [],
                'cloud_provider' => match (true) {
                    $client instanceof S3Client => self::S3,
                    $client instanceof BlobRestProxy => self::MS_AZURE,
                    $client instanceof StorageClient => self::GOOGLE_CLOUD,
                    default => null
                },
                '_file' => $data,
            ];

            if (Hash::get($settings, 'calculateHash', false)) {
                $image_data['sha1_hash'] = sha1_file($data->getStream()->getMetadata('uri'), true);
            }

            $metadataCallback = Hash::get($settings, 'metadataCallback');
            if (is_callable($metadataCallback)) {
                $image_data['metadata'] = $metadataCallback($this->table(), $entity, $data, $field, $settings);
            }

            /** @var \FileUploader\Model\Table\UploadedFilesTable $UploadedFileTable */
            $UploadedFileTable = TableRegistry::getTableLocator()->get(
                Hash::get($settings, 'validation.table', UploadedFilesTable::class)
            );

            $allowedExtensions = Hash::get($settings, 'validation.allowedExtensions', []);
            foreach ($allowedExtensions as $extension) {
                $UploadedFileTable->addAllowedExtension($extension);
            }

            $allowedMimeTypes = Hash::get($settings, 'validation.allowedMimeTypes', []);
            foreach ($allowedMimeTypes as $type) {
                $UploadedFileTable->addAllowedMimeType($type);
            }

            $UploadedFileTable->setMaxFileSize(Hash::get($settings, 'validation.fileSize.max'));

            $UploadedFileTable->setMinFileSize(Hash::get($settings, 'validation.fileSize.min'));

            $UploadedFileTable->setFilesystem($processor->getRootDirectory(), $processor->getDirectory(), $client);

            /** @var \FileUploader\Model\Entity\UploadedFile $fileEntity */
            $fileEntity = $UploadedFileTable->newEntity(
                $image_data,
                [
                    'accessibleFields' => ['_file' => true],
                    'validate' => Hash::get($settings, 'validation.method', 'default'),
                ]
            );

            try {
                if ($UploadedFileTable->save($fileEntity)) {
                    $entity->set($field, $fileEntity->get(Hash::get($settings, 'returnValue', 'id')));
                } else {
                    $entity->setError($field, $fileEntity->getErrors());
                    return false;
                }
            } catch (\Exception $exception) {
                $entity->setError($field, ['upload-error' => $exception->getMessage()]);
                return false;
            }
        }
    }
}
