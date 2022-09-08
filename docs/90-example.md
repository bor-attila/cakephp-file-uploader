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
