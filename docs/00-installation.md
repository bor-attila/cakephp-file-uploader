## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```
composer require bor-attila/cakephp-image-uploader
```

Enable the plugin in your Application.php:

```
$this->addPlugin('ImageUploader');
```

Run the migrations

```
bin/cake migrations migrate -p FileUploader
```

OR

You can create the database table manually.

```
CREATE TABLE `uploaded_files` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,

  -- the `root_dir` is the full path where we save the `dir` + `file` OR the container name in case of cloud
  `root_dir` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,

  -- the `dir` is the name of the directory where we save our file
  `dir` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,

  -- the generated filename
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,

  -- the file's extension
  `ext` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,

  -- the URL how the public can reach the file
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,

  -- the file's size in bytes
  `size` int NOT NULL,

  -- the file's mime type
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,

  -- the cloud providers name
  `cloud_provider` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,

  -- additional data
  `metadata` json NOT NULL,

  -- the upload datetime
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
