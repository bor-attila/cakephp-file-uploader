## Basic configuration

Let assume you have a users table. Add a new field to your table.
For example: photo

```
CREATE table users (
    id int(10) unsigned NOT NULL auto_increment,
    username varchar(20) NOT NULL,
    photo varchar(255)
);
```

In the table class:

```
<?php
declare(strict_types=1);

namespace App\Model\Table;
use Cake\ORM\Table;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('users');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');
        $this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, ['photo']);
    }
}
?>
```

In the form:

```
<?= $this->Form->create($user, ['type' => 'file']); ?>
    <?= $this->Form->control('username'); ?>
    <?= $this->Form->control('photo', ['type' => 'file']); ?>
<?= $this->Form->end(); ?>
```

And it's done.

The default configuration will upload the file to

```
WWW_ROOT/files/:table_alias/:year/:month/:timestamp.:extension
```

The full default configuration looks like this:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        'returnValue' => 'id',
        'metadataCallback' => function ($table, $entity, $data, $field, $settings) {
            // return a custom array with additional data
        },
        'filePathProcessor' => \FileUploader\FileProcessor\DefaultProcessor::class,
    ]
]);
```
***filePathProcessor***

Optional, default value: null

The __filePathProcessor__ it's an implementation of __\FileUploader\FilePathProcessor\FilePathProcessorInterface__.
This class decides how the directory structure should look like and also decides the filename.

The local filesystem always saves the file in the following format.

```
WWW_ROOT/files/:table_alias/:year/:month/:timestamp.:extension
```

If you want to change only the _files_ directory you can do it in _app.php_ by setting the _App.fileBaseDirectory_ value.
But ofc you can implement your own _filePathProcessor_ class.

***metadataCallback***

Optional, default value: null

A custom callback function what must return an array. You can save additional info here about the file

***returnValue***

Optional, default value: 'id'

This field is what we return and what we save into the 'external table'.
