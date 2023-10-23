<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateUploadedFiles extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('uploaded_files', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', ['default' => null, 'null' => false])
            ->addColumn('root_dir', 'string', ['limit' => 255])
            ->addColumn('dir', 'string', ['limit' => 255])
            ->addColumn('filename', 'string', ['limit' => 255])
            ->addColumn('ext', 'string', ['limit' => 32])
            ->addColumn('original_filename', 'string', ['limit' => 255])
            ->addColumn('url', 'string', ['limit' => 255, 'default' => null, 'null' => true])
            ->addColumn('size', 'integer')
            ->addColumn('type', 'string', ['limit' => 32])
            ->addColumn('sha1_hash', 'binary', ['limit' => 20, 'default' => null, 'null' => true])
            ->addColumn('cloud_provider', 'string', ['limit' => 32, 'default' => null, 'null' => true])
            ->addColumn('origin', 'string', ['limit' => 255])
            ->addColumn('metadata', 'json')
            ->addColumn('created', 'datetime')
            ->create();
    }
}
