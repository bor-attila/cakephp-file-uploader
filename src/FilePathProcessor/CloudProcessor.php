<?php
declare(strict_types=1);

namespace FileUploader\FilePathProcessor;

use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Cloud file processor for saving file to cloud filesystem
 *
 * The `root_dir` set to <provided container name>
 * The `dir` set to {table_alias}/{year}/{month}
 * The `filename` set to time()
 * The `ext` set to <Automatically detects the file's extension from file name>
 * The `url` set to /{container_name}/{table_alias}/{year}/{month}/{filename}.{extension}
 */
class CloudProcessor implements FilePathProcessorInterface
{
    /**
     * @var \Cake\ORM\Table The instance managing the entity
     */
    protected Table $table;

    /**
     * @var \Cake\Datasource\EntityInterface the entity what was saved
     */
    protected EntityInterface $entity;

    /**
     * @var \Psr\Http\Message\UploadedFileInterface the data being submitted for a save
     */
    protected UploadedFileInterface $uploadedFile;

    /**
     * @var string the field for which data will be saved
     */
    protected string $field;

    /**
     * @var array the settings for the current field
     */
    protected array $settings;

    /**
     * @var string The filename
     */
    protected string $filename;

    /**
     * @var string The url
     */
    protected string $url;

    /**
     * @var string The directory
     */
    protected string $directory;

    /**
     * @var \Cake\I18n\FrozenTime The current time
     */
    private FrozenTime $currentTime;

    /**
     * @inheritDoc
     */
    public function __construct(
        Table $table,
        EntityInterface $entity,
        UploadedFileInterface $data,
        string $field,
        array $settings
    ) {
        $this->table = $table;
        $this->entity = $entity;
        $this->uploadedFile = $data;
        $this->field = $field;
        $this->settings = $settings;
        $this->currentTime = new FrozenTime(null, 'GMT');
    }

    /**
     * @inheritDoc
     */
    public function getRootDirectory(): string
    {
        return Hash::get($this->settings, 'container', 'files');
    }

    /**
     * @inheritDoc
     */
    public function getDirectory(): string
    {
        return strtolower(Text::insert(':table/:year/:month', [
            'table' => $this->table->getAlias(),
            'year' => $this->currentTime->year,
            'month' => $this->currentTime->month,
        ]));
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): string
    {
        return $this->currentTime->toUnixString();
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return strtolower(Text::insert('/:container/:table/:year/:month/:filename.:extension', [
            'container' => $this->getRootDirectory(),
            'table' => strtolower($this->table->getAlias()),
            'year' => $this->currentTime->year,
            'month' => $this->currentTime->month,
            'filename' => $this->getFilename(),
            'extension' => $this->getFileExtension(),
        ]));
    }

    /**
     * @inheritDoc
     */
    public function getFileExtension(): string
    {
        return strtolower(pathinfo($this->uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
    }
}
