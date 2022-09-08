# CakePHP File Upload Plugin

___NOTE: Still under development! If you want a well tested plugin then go with [cakephp-upload](https://github.com/FriendsOfCake/cakephp-upload).___

The File Upload plugin is an attempt to easily handle file uploads with CakePHP.

This is an alternate for [cakephp-upload](https://github.com/FriendsOfCake/cakephp-upload) plugin.

### What is the difference between this and the other [cakephp-upload](https://github.com/FriendsOfCake/cakephp-upload) plugin ?

* [+] Saves all data about the file into the database with a unique id
* [-] Supports only predefined filesystems. Supports: local filesystem, AWS S3, Microsoft Azure, Google Cloud.
* [+] Optionally: Can perform basic validations (extension, mime type, file size)
* [+] Optionally: Uses ClamAV to check for viruses
* [+] Optionally: Can calculate SHA1 hash of the file

### Documentation

* [Installation](docs/00-installation.md)
* [Using with local filesystem and basic configuration](docs/01-basic-configuration.md)
* [Using with AWS S3](docs/02-using-aws-s3.md)
* [Using with Microsoft Azure](docs/03-using-ms-azure.md)
* [Using with Google cloud](docs/04-using-google-cloud.md)
* [Validation](docs/05-validation.md)
* [ClamAV configuration](docs/06-clamav.md)
* [Deleting files](docs/07-delete.md)
* [Basic examples](docs/90-basic-example.md)