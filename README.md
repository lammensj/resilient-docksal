<p align="center"><img width=50% src="https://raw.githubusercontent.com/lammensj/resilient-docksal/master/assets/images/logo.jpg"></p>

# [Project name]

[Project description]

## Deployment
### Drupal
shared folder:
- 'files'-directory
- master.salt.txt
- master.settings.private.php contains: db credentials, config directory sync (`$config_directories['sync'] = '../config/sync';`) and path to hash salt (`$settings['hash_salt'] = file_get_contents(DRUPAL_ROOT . '/../salt.txt');`)

## Authors

* **Jasper Lammens** - *Initial work* - [lammensj](https://github.com/lammensj)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
