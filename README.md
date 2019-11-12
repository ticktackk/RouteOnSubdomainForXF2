# Route on Subdomain
This add-on allows setting routes to be used on specific subdomains.

# Installation
After installing the add-on like you would install any other add-on, you must add these lines to your `config.php`:
```php
$config['cookie']['domain'] = '.<example.com>'; // this is so cookies are available on every sub-domain
```
Replacing `<example.com>` with your own host. You'll also need to update nginx/apache/etc host configuration file to
support the routes that you have enabled.

Once the cookie domain is updated in the `config.php` file you can proceed to enable routes on subdomain via ACP > Setup > Navigation > Routes on subdomain.

# License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.