### Using with AWS S3

If you want to use AWS S3, please install

```
composer require league/flysystem-aws-s3-v3
```

Using with AWS S3 is very similar with local file system but needs some extra configuration.

Minimal:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        'client' => new \Aws\S3\S3Client([...configuration...]),
        'container' => 'my-bucket',
    ]
]);
```

Advanced:

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        // required
        'client' => new \Aws\S3\S3Client([...configuration...]),
        'container' => 'my-bucket',
        // optinal, but different from the local filesystem. you can skip
        'filePathProcessor' => \FileUploader\FileProcessor\CloudProcessor::class,
        // optional configurations, you can skip
        'returnValue' => 'id',
        'metadataCallback' => function ($table, $entity, $data, $field, $settings) {
            // return a custom array with additional data
        },
    ]
]);
```

***client***

Required, default value: null.

The preconfigured AWS S3 client. In case of null the local file system will be used

***container***

Required, default value: 'files'.

the bucket's name where we upload the files

***filePathProcessor***

Optional, default value: null
See the detailed description at [basic configuration](docs/01-basic-configuration.md)
NOTE: This uses __CloudProcessor__ and not __DefaultProcessor__

***metadataCallback***

Optional, default value: null
See the detailed description at [basic configuration](docs/01-basic-configuration.md)

***returnValue***

Optional, default value: 'id'
See the detailed description at [basic configuration](docs/01-basic-configuration.md)
