# ExtDirect.php for ExtJS
Extremely Easy Ext.Direct integration with PHP

Original author : J. Bruni  
Fork: stanx


## How to use:

1) PHP
```php
<?php
require 'ExtDirect.php';

class Server
{
    public function date($format)
    {
        return date($format);
    }
}

ExtDirect::provide('Server', true);
```
Here, "Server" is the PHP class we want to provide access from the JavaScript code. It could be any other class.

2) HTML:
```html
<script type="text/javascript" src="ext-direct.php"></script>
```
Here, "ext-direct.php" points to the PHP file shown on item 1. If you want to output API for Ext Designer you must add "?json" query string, because the default output is on javascript format. It may be changed by `$default_api_output`

3) JavaScript:
```javascript
Ext.php.Server.date( 'Y-m-d', function(result) {
    alert('Server date is ' + result);
});
```
Here, to call the "date" method from PHP "Server" class, we prepended the default namespace Ext.php. The first parameter is the $format parameter. The second parameter is the JavaScript callback function that will be executed after the AJAX call has been completed. Here, an alert box shows the result.



## Features

- API declaration with several classes (not limited to a single class)
- API `url`, `namespace` and `descriptor` settings ("ExtDirect" class assigns them automatically if you don't)
- Two types of API output format: `json` (for use with Ext Designer) and `javascript` (default: javascript)
- You choose if the "len" attribute of the actions will count only the required parameters of the PHP method, or all of them (default: all)
- You choose whether inherited methods will be declared in the API or not (default: no)
- You choose whether static methods will be declared in the API or not (default: no)
- You choose whether particular public method will be excluded from api (@extdirect-exclude)
- Instantiate an object if the called method is static? You choose! (default: no)
- Call the class constructor with the actions parameters? You choose! (default: no)
- "debug" option to enable server exceptions to be sent in the output of API action results (default: off)
- "utf8_encode" option to automatically apply UTF8 encoding in API action results (default: off)
- You choose if parameters will be converted in associative arrays (default: no)
- Handle forms
- Handle file uploads
  Configuration - How To
----------------------
Easy.
If the configuration option name is "configuration_name", and the configuration value is $value, use this syntax:
```php
ExtDirect::$configuration_name = $value;  
```
That's all.



## Configuration options

- name: `api_classes`  
  type: array of strings or key-value pairs  
  meaning: Name of the classes to be published to the Ext.Direct API. It can be string or key-value pair: key is api name, value is class.  
  default: `empty`  
  comments: This option is overridden if you provide a non-empty `api_classes` parameter for the `ExtDirect::provide` method. Choose one or another. If you want to declare a single class, you can set `api_classes` as a string, instead of an array  
  example:
```php
  ExtDirect::$api_classes = ['MyClass', 'AnotherClass'=>'MyNamespace\\AnotherClass'];
```
- name: `api_classes_discovery_dirs`  
  type: array
  meaning: These directories will be scanned recursively and classes with `@extdirect-api` attribute in the DOC comment will be added to `api_classes` option.<br>Key is the path, value is the namespace.  
  default: `empty`  
  comments: **WARNING**: This option can slow down the response if there are many files in discovery directories. You can get discovered classes from `ExtDirect::discover_api_classes()` and save them in cache  
  example:
```php
  ExtDirect::$api_classes_discovery_dirs = [__DIR__ . '/MyNamespace/MyAPI' => 'MyNamespace\\MyAPI'];
```
- name: `url`  
  type: string  
  meaning: Ext.Direct API attribute "url"  
  default: `$_SERVER['PHP_SELF']`  
  comments: Sometimes, PHP_SELF is not what we want. So, it is possible to specify the API URL manually  
  example:
```php
  ExtDirect::$url = '/path/to/my_php_script.php';  
```
- name: `namespace`  
  type: string  
  meaning: Ext.Direct API attribute "namespace"  
  default: `Ext.php`  
  comments: Feel free to choose your own namespace, according to ExtJS rules for it.  
  example:
```php
  ExtDirect::$namespace = 'Ext.php'; 
```
- name: `descriptor`  
  type: string  
  meaning: Ext.Direct API attribute "descriptor"  
  default: `Ext.php.REMOTING_API`  
  comments: Feel free to choose your own descriptor, according to ExtJS rules for it, and to the chosen namespace.  
  example:
```php
  ExtDirect::$descriptor = 'Ext.php.REMOTING_API';  
```
- name: `id`  
  type: string  
  meaning: Ext.Direct Provider attribute "id"  
  default: `empty`  
  example:
```php
  ExtDirect::$id = 'MyProvider';  
```
- name: `max_retries`  
  type: int  
  meaning: Number of times to re-attempt delivery on failure of a call  
  default: `1`  
  example:
```php
  ExtDirect::$max_retries = 1;  
```
- name: `timeout`  
  type: int  
  meaning: The number of milliseconds to use as the timeout for every Method invocation in this Remoting API  
  default: `30000`  
  example:
```php
  ExtDirect::$timeout = 30000;  
```
- name: `count_only_required_params`  
  type: boolean  
  meaning: Set this to true to count only the required parameters of a method for the API "len" attribute  
  default: `false`  
  example:
```php
  ExtDirect::$count_only_required_params = true;  
```
- name: `include_static_methods`  
  type: boolean  
  meaning: Set this to true to include static methods in the API declaration  
  default: `false`  
  example:
```php
  ExtDirect::$include_static_methods = true;  
```
- name: `include_inherited_methods`  
  type: boolean  
  meaning: Set this to true to include inherited methods in the API declaration  
  default: `false`  
  example:
```php
  ExtDirect::$include_inherited_methods = true;
```
- name: `instantiate_static`  
  type: boolean  
  meaning: Set this to true to create an object instance of a class even if the method being called is static  
  default: `false`  
  example:
```php
  ExtDirect::$instantiate_static = true;
```
- name: `constructor_send_params`  
  type: boolean  
  meaning: Set this to true to call the action class constructor sending the action parameters to it  
  default: `false`  
  example:
```php
  ExtDirect::$constructor_send_params = true;
```
- name: `constructor_params`  
  type: array  
  meaning: parameters to be sent to the class constructor (use the class key as name)  
  default: `[]`  
  example:
```php
  ExtDirect::$constructor_params = ['MyNamespace\\MyClass' => ['param1', 'param2']];
```
- name: `debug`  
  type: boolean  
  meaning: Set this to true to allow exception detailed information in the output  
  default: `false`  
  example:
```php
  ExtDirect::$debug = true;
```
- name: `utf8_encode`  
  type: boolean  
  meaning: Set this to true to pass all action method call results through utf8_encode function  
  default: `false`  
  example:
```php
  ExtDirect::$utf8_encode = true;  
```
- name: `params_enforce_associative`  
  type: boolean  
  meaning: Set this to true to pass all action parameters through json_decode(json_encode($params), true)  
  default: `false`  
  example:
```php
  ExtDirect::$params_enforce_associative = true;  
```
- name: `default_api_output`  
  type: string
  meaning: API output format - available options are "json" (good for Ext Designer) and "javascript"  
  default: `javascript`  
  comments: Another way to enforce "json" output is to append the "?json" query string in the end of your PHP script URL; do this in the HTML `<script>` tag that refers to your API  
  example:
```php
  ExtDirect::$default_api_output = 'javascript';  
```


### formHandler

There are two different ways to flag a method as a `formHandler`.


Method 1: use the new `ExtDirect::$form_handlers` configuration option.

- name: `form_handlers`
  type: array of strings  
  meaning: Name of the class/methods to be flagged as formHandler in the Ext.Direct API  
  default: `[]`  
  comments: The string format for each method must be "className::methodName"  
  example:
```php
  ExtDirect::$form_handlers = ['someClass::someMethod', 'MyNamespace\\Server::date'];  
```
Method 2: include `@extdirect-formHandler` in the DOC comment of the method.

Example:
```php
class FTP_Manager
{
    /**
    * Sets FTP password for a specific account
    * 
    * @extdirect-formHandler
    * @param string $account   Name of the account
    * @param string $password   New password
    * @param string $password_confirm   New password confirmation
    * @return string
    */
    public function set_ftp_password($account, $password, $password_confirm)
    {
        // do stuff
        return 'result';
    }
}  
```
In the example above, due to the `@extdirect-formHandler` string inside the method's DOC comment, it will be flagged as a `formHandler` method.

It has the same effect as this:
```php
ExtDirect::$form_handlers[] = 'FTP_Manager::set_ftp_password';  
```


### Receiving parameters

The parameters sent by forms are adapted to be received by the class method.

Pay attention now, because this is not usual.

I will use the "set_ftp_password" method above as the example.

First, note that we don't want that all `formHandler` methods have the same not-friendly signature, like this:
```php
function set_ftp_password($data){};
function do_something($data){};
function do_something_completely_different($data){};  
```
`$data` is the user input (usually `$_POST`)

So, to be able to keep normal method signatures, like this...
```php
function set_ftp_password($account, $password, $password_confirm){}
```
...I have implemented the following solution:

When the method/action function is a `formHandler`, its parameter values are taken from the input names that matches the parameter's names.

So... `$_POST['account']` will automatically become the `$account` parameter...

`$_POST['password']` value will be the `$password` parameter value...

...and from where the `$password_confirm` parameter value will come from? Yes! From `$_POST['password_confirm']`

That's it: the method's parameters' names matches the `$_POST` array keys.

Advantages:

- Don't need to worry with parameter order
- Can use meaningful and clean method/function signature
- Don't need to sniff with `$_POST` array - the ExtDirect controller does this for us (forget "isset" checkings... if a certain parameter value is not set in the `$_POST` array, the default value - if available - or null is passed to the method/function)

Disadvantages:

- The input names must match the method/function parameter names (IMHO, an advantage!)
- This approach may just not be the best for you (in this case, just ignore all this stuff and go for the `$_POST` / `$_GET` / `$_REQUEST` arrays!)

Of course, all validation / filtering / sanitization of input data, as always, must be carefully considered.


### Additional note about file upload:

If your file input name is `userFile`, it will be available in your method/function parameter named `$userFile`

In other words: your `$userFile` parameter will receive the value from `$_FILES['userFile']`



## Intercept functions

- `declare_method_function`
- `authorization_function`
- `instantiate_function`
- `transform_result_function`
- `transform_response_function`

With the `declare_method_function`, you can determine if method declaration is allowed or not.

Now, you are able to specify an `authorization_function`, where you can check the user permissions and return true or false accordingly, allowing the API call or not.

`instantiate_function` will be called in API action call to instantiate object of class. This is useful if you are using a DI container to instantiate classes.

With the `transform_result_function`, you can modify the result of the API method call after its execution, but before it is sent to the client-side.

Finally, with the `transform_response_function`, you can modify the response structure. This allows firing server-side events, and to send extra server side data together with the RPC result.

```php
// New configuration option
ExtDirect::$id = 'my_api';

// All "function" configurations accept parameters of callback type
ExtDirect::$declare_method_function     = 'declare_method';
ExtDirect::$authorization_function      = 'authorize';
ExtDirect::$instantiate_function        = 'instantiate';
ExtDirect::$transform_result_function   = 'transform_result';
ExtDirect::$transform_response_function = 'transform_response';

function declare_method(string $class, string $method) : bool
{
    $key = $class . '::' . $method;
    
    // return boolean - declare the method in the API or not
    return in_array($key, MyFramework::$user->permissions);
}

function authorize(ExtDirectAction $action) : bool
{
    // return boolean - authorize the action call or not
    return declare_method($action->action, $action->method);
}

function instantiate(ExtDirectAction $action) : object
{
    // return instance of class
    return DI::get($action->class);
}

function transform_result(ExtDirectAction $action, mixed $result) : mixed
{
    if ($action->form_handler)
        $result = ['success' => $result];

    // return modified result
    return $result;
}

function transform_response(ExtDirectAction $action, array $response) : array
{
    $response['error_msg']   = MyFramework::$errors;
    $response['success_msg'] = MyFramework::$success;

    // return modified response
    return $response;
}
```
