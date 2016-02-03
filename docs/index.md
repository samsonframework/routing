# SamsonFramework Router
# Why this is the fastest router implementation ever?
After gathering all routes in `RouteCollection` we perform router logic generation. In the end we have a generated 
PHP function code which is used to find correct route identifier. All routes should have unique identifier, which 
can be passed through constructor or would be generated automatically. In the end we get something similar 
to this:
```php
function __router5894($path, $method)
{
    $matches = array();
    $parameters = array();
    if ($method === "GET") {
        if ($path === 'userlist') {
            return array('test-two-similar-fixed', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
        } elseif (substr($path, 0, 8) === 'userlist') {
            $path17135 = substr($path, 9);
            if ($path17135 === 'friends') {
                return array('test-two-similar-fixed2', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
            }
            if (preg_match('/^(?<group>[^\/]+)\/(?<action>[^\/]+)$/i', $path17135, $matches)) {
                $parameters['group'] = $matches['group'];$parameters['action'] = $matches['action'];
                return array('test-two-params-at-end', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
            }
        } elseif ($path === 'user') {
            return array('user-home-without-slash', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
        } elseif (substr($path, 0, 4) === 'user') {
            $path18209 = substr($path, 5);
            if ($path18209 === 'winners') {
                return array('user-winners-slash', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
            }
            if (preg_match('/^(?<gender>male|female)/i', $path18209, $matches)) {
                $parameters['gender'] = $matches['gender'];
                $path38751 = substr($path18209, strlen($matches[0]) + 1);
                if (preg_match('/^(?<age>[0-9]+)$/i', $path38751, $matches)) {
                    $parameters['age'] = $matches['age'];
                    return array('user-by-gender-age-filtered', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                }
                if (preg_match('/^(?<age>[^\/]+)$/i', $path38751, $matches)) {
                    $parameters['age'] = $matches['age'];
                    return array('user-by-gender-age', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                }
            }
            if (preg_match('/^(?<id>[^\/]+)/i', $path18209, $matches)) {
                $parameters['id'] = $matches['id'];
                $path87524 = substr($path18209, strlen($matches[0]) + 1);
                if ($path87524 === false) {
                    return array('user-by-id', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                } elseif ($path87524 === 'friends') {
                    return array('user-by-id-friends', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                }
                if (preg_match('/^friends\/(?<groupid>[^\/]+)$/i', $path87524, $matches)) {
                    $parameters['groupid'] = $matches['groupid'];
                    return array('user-by-id-friends-with-id', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                } elseif ($path87524 === 'n"$ode') {
                    return array('user-by-id-node', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                }
                if (preg_match('/^n"\$ode\/(?<param>[^\/]+)$/i', $path87524, $matches)) {
                    $parameters['param'] = $matches['param'];
                    return array('user-by-id-node-with-id', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                } elseif ($path87524 === 'form') {
                    return array('user-by-id-form', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                }
            }
        } elseif ($path === '') {
            return array('main-page', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
        }
        if (preg_match('/^(?<entity>[^\/]+)\/(?<id>[^\/]+)\/form$/i', $path, $matches)) {
            $parameters['entity'] = $matches['entity'];$parameters['id'] = $matches['id'];
            return array('entity-by-id-form', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
        }
        if (preg_match('/^(?<id>[^\/]+)\/test\/(?<page>\d+)$/i', $path, $matches)) {
            $parameters['id'] = $matches['id'];$parameters['page'] = $matches['page'];
            return array('entity-by-id-form-test', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
        }
        if (preg_match('/^(?<num>[^\/]+)\/(?<page>\d+)$/i', $path, $matches)) {
            $parameters['num'] = $matches['num'];$parameters['page'] = $matches['page'];
            return array('two-params', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
        }
        if (preg_match('/^(?<page>[^\/]+)$/i', $path, $matches)) {
            $parameters['page'] = $matches['page'];
            return array('inner-page', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
        }
        return null;
    } elseif ($method === "POST") {
        if (preg_match('/^user\/(?<id>[^\/]+)/i', $path, $matches)) {
            $parameters['id'] = $matches['id'];
            $path96365 = substr($path, strlen($matches[0]) + 1);
            if ($path96365 === 'save') {
                return array('user-post-by-id', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
            }
            if (preg_match('/^save\/(?<name>[^\/]+)/i', $path96365, $matches)) {
                $parameters['name'] = $matches['name'];
                $path10778 = substr($path96365, strlen($matches[0]) + 1);
                if ($path10778 === false) {
                    return array('user-post-by-id-param', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                }
                if (preg_match('/^(?<group>[^\/]+)$/i', $path10778, $matches)) {
                    $parameters['group'] = $matches['group'];
                    return array('user-post-by-id-param2', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
                }
            }
        } elseif (substr($path, 0, 8) === 'cms/gift') {
            $path65430 = substr($path, 9);
            if (preg_match('/^form\/(?<id>[^\/]+)$/i', $path65430, $matches)) {
                $parameters['id'] = $matches['id'];
                return array('user-post-by-id-param3', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
            }
            if (preg_match('/^(?<id>[^\/]+)\/(?<search>[^\/]+)$/i', $path65430, $matches)) {
                $parameters['id'] = $matches['id'];$parameters['search'] = $matches['search'];
                return array('user-post-by-id-param4', $parameters, 'samsonframework\routing\tests\GeneratorTest#baseCallback');
            }
        }
        return null;
    }
    return null;
}
```
 
 
## Route creation
### Route variables
Route pattern can have variable placeholder which is intend to be used as parameters for controller actions. All variable placeholder logic is very similar to awesome [FastRoute](https://github.com/nikic/FastRoute) package.
```php
new Route('/user/{id}', 'my_callback_function');
```
Variable placeholder should be surrounded by braces ```{variable_name}``` symbols and its identifier inside this template. All route variables should have unique identifiers.

### Filtered route variables
Variable placeholder can also have [PHP PRCE](http://php.net/manual/ru/reference.pcre.pattern.syntax.php) filter,
it should be specified after route variable name followed by ```:```, ```{variable_name:filter}```:
```php
new Route('/user/{id:\d+}', 'my_callback_function');
```
In the example above we have added a `\d+` pattern for filtering variable, this means that route placeholder variable should have at least one digit(0-9) symbol to match this filter.
So `/user/vitaly` would not match this pattern, but `/user/123` will. Filters are interpreted as regular regular expression for route variable.



