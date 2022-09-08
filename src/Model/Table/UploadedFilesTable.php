<?php
declare(strict_types=1);

namespace FileUploader\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use FileUploader\Model\Behavior\UploadBehavior;

/**
 * UploadedFiles Model
 *
 * @method \FileUploader\Model\Entity\UploadedFile newEmptyEntity()
 * @method \FileUploader\Model\Entity\UploadedFile newEntity(array $data, array $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile[] newEntities(array $data, array $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile get($primaryKey, $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \FileUploader\Model\Entity\UploadedFile[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UploadedFilesTable extends Table
{
    /**
     * @var array Contains all available cloud providers
     */
    private array $_cloudProviders = [];

    /**
     * @var array Contains all allowed Mime types with extension
     * @see https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Image_types#common_image_file_types
     */
    private array $_allowedMimeTypes = [];

    /**
     * @var array Contains all allowed file extensions
     * @see https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Image_types#common_image_file_types
     */
    private array $_allowedExtensions = [];

    /**
     * @var array Contains the allowed media size (in bytes)
     */
    private array $_size_limit = [
        'min' => null,
        'max' => null,
    ];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('uploaded_files');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->addCloudProvider(UploadBehavior::S3);
        $this->addCloudProvider(UploadBehavior::MS_AZURE);
        $this->addCloudProvider(UploadBehavior::GOOGLE_CLOUD);
    }

    /**
     * Adds an allowed extension to the whitelist
     *
     * @param string $extension The extension
     * @return void
     */
    public function addAllowedExtension(string $extension): void
    {
        $this->_allowedExtensions[] = strtolower($extension);
    }

    /**
     * Adds an allowed mime type to the whitelist
     *
     * @param string $type The mime type
     * @return void
     */
    public function addAllowedMimeType(string $type): void
    {
        $this->_allowedMimeTypes[] = strtolower($type);
    }

    /**
     * Sets the file max size for validation
     *
     * @param int|null $size The file's max size in bytes
     * @return void
     */
    public function setMaxFileSize(?int $size): void
    {
        $this->_size_limit['max'] = $size;
    }

    /**
     * Sets the file min size for validation
     *
     * @param int|null $size The file's min size in bytes
     * @return void
     */
    public function setMinFileSize(?int $size): void
    {
        $this->_size_limit['min'] = $size;
    }

    /**
     * Adds an allowed mime type to the whitelist
     *
     * @param string $provider The mime type
     * @return void
     */
    protected function addCloudProvider(string $provider): void
    {
        $this->_cloudProviders[] = strtolower($provider);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('root_dir', __d('file_uploader', 'Invalid root directory/container value.'))
            ->requirePresence('root_dir', 'create', __d('file_uploader', 'The root directory/container is not defined'))
            ->maxLength('root_dir', 255, __d('file_uploader', 'The root directory/container value is too long'))
            ->notEmptyString('root_dir', __d('file_uploader', 'The root directory/container is required.'));

        $validator
            ->scalar('dir', __d('file_uploader', 'Invalid upload directory.'))
            ->maxLength('dir', 255, __d('file_uploader', 'The upload directory\'s name is too long'))
            ->requirePresence('dir', 'create', __d('file_uploader', 'The upload directory is not defined'))
            ->notEmptyString('dir', __d('file_uploader', 'The upload directory is required'));

        $validator
            ->requirePresence('filename', 'create', __d('file_uploader', 'The file\'s name is not defined'))
            ->scalar('filename', __d('file_uploader', 'Invalid filename value.'))
            ->maxLength('filename', 255, __d('file_uploader', 'The file\'s name value is too long'))
            ->regex('filename', '/^[A-Za-z0-9\-\_\.]+$/', __d('file_uploader', 'Invalid filename'));

        $validator
            ->scalar('ext', __d('file_uploader', 'Invalid file extension'))
            ->maxLength('ext', 32, __d('file_uploader', 'The file\'s extension is too long, max {0} chars', [32]))
            ->requirePresence('ext', 'create', __d('file_uploader', 'The file\'s extension is not defined'))
            ->notEmptyString('ext', __d('file_uploader', 'The file extension is required'));

        $validator
            ->scalar('original_filename', __d('file_uploader', 'Invalid original filename extension'))
            ->maxLength(
                'original_filename',
                255,
                __d('file_uploader', 'The file\'s original filename is too long, max {0} chars', [255])
            )
            ->requirePresence('ext', 'create', __d('file_uploader', 'the file\'s original filename is not defined'))
            ->allowEmptyString('original_filename');

        $validator
            ->requirePresence('url', 'create', __d('file_uploader', 'The file\'s url is not defined'))
            ->allowEmptyString('url');

        $validator
            ->nonNegativeInteger('size', __d('file_uploader', 'Invalid file size'))
            ->requirePresence('size', 'create', __d('file_uploader', 'The file\'s size is not defined'))
            ->notEmptyString('size', __d('file_uploader', 'The file\'s size is not defined'));

        $validator
            ->scalar('type', __d('file_uploader', 'Invalid file mime type'))
            ->maxLength('type', 32, __d('file_uploader', 'The mime type is too long'))
            ->requirePresence('type', 'create', __d('file_uploader', 'The file mime type is not defined'))
            ->notEmptyString('type', __d('file_uploader', 'The file mime type is required'));

        $validator
            ->maxLengthBytes('sha1_hash', 20, __d('file_uploader', 'Invalid SHA1 hash'))
            ->minLengthBytes('sha1_hash', 20, __d('file_uploader', 'Invalid SHA1 hash'))
            ->allowEmptyString('sha1_hash');

        $validator
            ->requirePresence('metadata', 'create', __d('file_uploader', 'The file metadata\'s is not defined'))
            ->allowEmptyArray('metadata');

        $validator
            ->requirePresence('cloud_provider', 'create', __d('file_uploader', 'The cloud provider\'s is not defined'))
            ->inList('cloud_provider', $this->_cloudProviders, __d('file_uploader', 'Invalid cloud provider'))
            ->allowEmptyString('cloud_provider');

        if (!empty($this->_allowedMimeTypes)) {
            $validator
                ->inList('type', $this->_allowedMimeTypes, __d('file_uploader', 'Invalid file mime type'));
        }

        if (!empty($this->_allowedExtensions)) {
            $validator
                ->inList('ext', $this->_allowedExtensions, __d('file_uploader', 'Invalid file extension'));
        }

        if (is_int($this->_size_limit['min'])) {
            $validator
                ->greaterThanOrEqual(
                    'size',
                    $this->_size_limit['min'],
                    __d('file_uploader', 'The file must be at least {0} bytes', $this->_size_limit['min'])
                );
        }

        if (is_int($this->_size_limit['max'])) {
            $validator
                ->lessThanOrEqual(
                    'size',
                    $this->_size_limit['max'],
                    __d('file_uploader', 'The file must not exceed {0} bytes', $this->_size_limit['max'])
                );
        }

        return $validator;
    }
}
