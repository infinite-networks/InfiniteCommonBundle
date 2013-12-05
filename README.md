Infinite Common Bundle
----------------------

Provides common functionality used in most Infinite Networks Symfony2 applications.

* To use the twig variable `site`, AppKernel must be modified to add an additional
  parameter to the container:

```php
    protected function getKernelParameters()
    {
        $parameters = parent::getKernelParameters();
        $parameters['site.version'] = trim(shell_exec('git describe --tags --always'));

        return $parameters;
    }
```
