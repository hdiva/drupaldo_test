# drupaldo

1. Install dependencies with composer:
    ```
    $ composer install
    ```
2. Create `/web/sites/default/settings.local.php`, and add database settings.

3. Install the site from config with:

    ```
    $ drush site:install --existing-config
    ```

4. Create a test user, and login:

    ```
    $ drush user:create test
    $ drush user:login --name=test
    ```

## Demo
![Watch the video](docs/drupaldo-demo.gif)
