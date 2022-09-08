### Using with AWS S3

#### Installation

Install dependency

```
composer require league/flysystem-aws-s3-v3
```

#### Configuration

Using with AWS S3 is very similar with local file system but needs some extra configuration.

```
$this->addBehavior(\FileUploader\Model\Behavior\UploadBehavior::class, [
    'photo' => [
        // ... all configuration from base configuration ...//

        // required, a fully configured S3 Client
        'client' => new \Aws\S3\S3Client([...configuration...]),

        // the bucket's name
        'container' => 'my-bucket',
    ]
]);
```

***client***

Required, default value: null.

The preconfigured AWS S3 client. In case of null the local file system will be used

***container***

Required, default value: 'files'.

the bucket's name where we upload the files
