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
use Cake\Utility\Text;
use Cake\Validation\Validation;
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
     * @var array Field names used by the framework
     */
    protected array $_invalidFieldNames = [
        'priority',
    ];

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
        $validator = $this->table()->getValidator();

        /** @var string $field */
        foreach (array_keys($this->getConfig()) as $field) {
            $schema->setColumnType($field, 'upload.file');
            $this->table()->setSchema($schema);

            // set validation rules
            $validator->allowEmptyFile(
                $field,
                __d('file_uploader', 'This field is required'),
                $this->getConfig($field . '.validation.allowEmptyFile', true)
            );

            $allowedExtensions = $this->getConfig($field . '.validation.allowedExtensions');
            if (is_array($allowedExtensions) && !empty($allowedExtensions)) {
                $validator->add(
                    $field,
                    'extension',
                    [
                        'rule' => ['extension', $allowedExtensions],
                        'message' => __d(
                            'file_uploader',
                            'Invalid file extension. Allowed file types are: {0}',
                            implode(', ', $allowedExtensions)
                        )
                    ]
                );
            }

            $allowedMimeTypes = $this->getConfig($field . '.validation.allowedMimeTypes');
            if (is_array($allowedExtensions) && !empty($allowedMimeTypes)) {
                $validator->add(
                    $field,
                    'mimeType',
                    [
                        'rule' => ['mimeType', $allowedMimeTypes],
                        'message' => __d(
                            'file_uploader',
                            'Invalid file type. Allowed file types are: {0}',
                            implode(', ', $allowedMimeTypes)
                        )
                    ]
                );
            }

            $minSize = $this->getConfig($field . '.validation.fileSize.min');
            if (is_string($minSize)) {
                $validator->add(
                    $field,
                    'fizeSizeMin',
                    [
                        'rule' => ['fileSize', Validation::COMPARE_GREATER_OR_EQUAL, Text::parseFileSize($minSize)],
                        'message' => __d('file_uploader', 'The file must be at least {0}', $minSize)
                    ]
                );
            }

            $maxSize = $this->getConfig($field . '.validation.fileSize.max');
            if (is_string($maxSize)) {
                $validator->add(
                    $field,
                    'fizeSizeMax',
                    [
                        'rule' => ['fileSize', Validation::COMPARE_LESS_OR_EQUAL, Text::parseFileSize($maxSize)],
                        'message' => __d('file_uploader', 'The file must not exceed {0}', $maxSize)
                    ]
                );
            }
        }
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
            if (!$validator->isEmptyAllowed($field, false) || in_array($field, $this->_invalidFieldNames)) {
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
     * @return void
     * @throws \Exception
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        foreach ($this->getConfig(null, []) as $field => $settings) {
            if (is_int($field) || in_array($field, $this->_invalidFieldNames) || !$entity->isDirty($field)) {
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

            /** @var \FileUploader\Model\Table\UploadedFilesTable $FilesTable */
            $FilesTable = TableRegistry::getTableLocator()->get(
                Hash::get($settings, 'table', UploadedFilesTable::class)
            );

            $allowedExtensions = Hash::get($settings, 'validation.allowedExtensions', []);
            foreach ($allowedExtensions as $extension) {
                $FilesTable->addAllowedExtension($extension);
            }

            $allowedMimeTypes = Hash::get($settings, 'validation.allowedMimeTypes', []);
            foreach ($allowedMimeTypes as $type) {
                $FilesTable->addAllowedMimeType($type);
            }

            $FilesTable->setMaxFileSize(Hash::get($settings, 'validation.fileSize.max'));

            $FilesTable->setMinFileSize(Hash::get($settings, 'validation.fileSize.min'));

            $FilesTable->setFilesystem($processor->getRootDirectory(), $processor->getDirectory(), $client);

            /** @var \FileUploader\Model\Entity\UploadedFile $fileEntity */
            $fileEntity = $FilesTable->newEntity(
                $image_data,
                [
                    'accessibleFields' => ['_file' => true],
                    'validate' => Hash::get($settings, 'validation.method', 'default'),
                ]
            );

            $success = true;
            try {
                if ($FilesTable->save($fileEntity)) {
                    $entity->set($field, $fileEntity->get(Hash::get($settings, 'returnValue', 'id')));
                } else {
                    $entity->setError($field, $fileEntity->getErrors());
                    $success = false;
                }
            } catch (\Exception $exception) {
                $entity->setError($field, ['upload-error' => $exception->getMessage()]);
                $success = false;
            }

            if (!$success) {
                $event->stopPropagation();
                $event->setResult(false);
                break;
            }
        }
    }

    /**
     * The Model.beforeDelete event is fired before an entity is deleted. By stopping this event you will abort the
     * delete operation. When the event is stopped the result of the event will be returned.
     *
     * @param \Cake\Event\EventInterface $event The beforeDelete event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be deleted
     * @param \ArrayObject $options Additional options
     * @return void
     * @throws \Exception
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        foreach ($this->getConfig(null, []) as $field => $settings) {
            if (is_int($field) || in_array($field, $this->_invalidFieldNames)) {
                continue;
            }

            $deleteCallback = Hash::get($settings, 'deleteCallback');
            if (!is_callable($deleteCallback)) {
                continue;
            }

            /** @var \FileUploader\Model\Table\UploadedFilesTable $FilesTable */
            $FilesTable = TableRegistry::getTableLocator()->get(
                Hash::get($settings, 'table', UploadedFilesTable::class)
            );

            $fileEntity = $deleteCallback($entity, $FilesTable);
            if (!$fileEntity instanceof \FileUploader\Model\Entity\UploadedFile) {
                continue;
            }

            $FilesTable->setFilesystem($fileEntity->root_dir, $fileEntity->dir, Hash::get($settings, 'client'));

            try {
                $success = $FilesTable->delete($fileEntity);
            } catch (\Exception $exception) {
                $success = false;
            }

            if (!$success) {
                $event->stopPropagation();
                $event->setResult(false);
                break;
            }
        }
    }
}
