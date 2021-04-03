<?php

/**
 * Configurations for ExtDirect integration
 * Default values for all boolean configurations is "false" (this is easy to remember)
 *
 * @author J. Bruni
 * @fork   stanx
 */
class ExtDirect
{
    /**
     * @var bool     Set this to true to allow detailed information about exceptions in the output
     */
    public static $debug = false;

    /**
     * @var string   Available options are "json" (good for Ext Designer) and "javascript"
     */
    public static $default_api_output = 'javascript';

    /**
     * @var bool     Set this to true to pass all action method call results through utf8_encode function
     */
    public static $utf8_encode = false;

    /**
     * @var string   Ext.Direct API attribute "url"
     */
    public static $url;

    /**
     * @var string   Ext.Direct API attribute "namespace"
     */
    public static $namespace = 'Ext.php';

    /**
     * @var string   Ext.Direct API attribute "descriptor"
     */
    public static $descriptor = 'Ext.php.REMOTING_API';

    /**
     * @var string   Ext.Direct Provider attribute "id"
     */
    public static $id = '';

    /**
     * @var int      Ext.Direct Provider attribute "maxRetries"
     */
    public static $max_retries = 1;

    /**
     * @var int      Ext.Direct Provider attribute "timeout"
     */
    public static $timeout = 30000;

    /**
     * @var array    Names of the classes to be published to the Ext.Direct API.
     *               It can be string or key-value pair: key is api name, value is class.
     *               Example 1: 'MyClass'
     *               Example 2: 'AnotherClass' => 'MyNamespace\\AnotherClass'
     */
    public static $api_classes = [];

    /**
     * @var array    These directories will be scanned recursively and
     *               classes with @extdirect-api attribute in the DOC comment will be added to *$api_classes* option.
     *               Key is the path, value is the namespace.
     *               WARNING: This option can slow down the response if there are many files in discovery directories.
     *               You can get discovered classes from ExtDirect::discover_api_classes() and save them in cache.
     *               Example: [__DIR__ . '/MyNamespace/MyAPI' => 'MyNamespace\\MyAPI']
     */
    public static $api_classes_discovery_dirs = [];

    /**
     * @var array    Name of the methods to be flagged as "formHandler = true" (use "class::method" string format).
     */
    public static $form_handlers = [];

    /**
     * @var bool     Set this to true to count only the required parameters of a method for the API "len" attribute
     */
    public static $count_only_required_params = false;

    /**
     * @var bool     Set this to true to include static methods in the API declaration
     */
    public static $include_static_methods = false;

    /**
     * @var bool     Set this to true to include inherited methods in the API declaration
     */
    public static $include_inherited_methods = false;

    /**
     * @var bool     Set this to true to create an object instance of a class even if the method being called is static
     */
    public static $instantiate_static = false;

    /**
     * @var bool     Set this to true to call the action class constructor sending the action parameters to it
     */
    public static $constructor_send_params = false;

    /**
     * @var array    Parameters to be sent to the class constructor (use the class name as key)
     *               Example: ['ExtDirect\\MyClass' => ['param1','param2']]
     */
    public static $constructor_params = [];

    /**
     * @var callable Function to be called during the API generation, allowing a method to be declared or not
     *               callback(string $class, string $method) : bool
     */
    public static $declare_method_function = null;

    /**
     * @var callable Function to be called before the API action call, to perform authorization
     *               callback(ExtDirectAction $action) : bool
     */
    public static $authorization_function = null;

    /**
     * @var callable Function to be called in API action call, to instantiate object of class
     *               callback(ExtDirectAction $action) : object
     */
    public static $instantiate_function = null;

    /**
     * @var callable Function to be called after the API action call, to transform its result
     *               callback(ExtDirectAction $action, mixed $result) : mixed
     */
    public static $transform_result_function = null;

    /**
     * @var callable Function to be called after the API action call, to transform the response structure
     *               callback(ExtDirectAction $action, array $response) : array
     */
    public static $transform_response_function = null;


    /**
     * @return array Array containing the full API declaration
     */
    public static function get_api_array() : array
    {
        $api_array = [
            'id'         => self::$id,
            'url'        => (empty(self::$url) ? $_SERVER['PHP_SELF'] : self::$url),
            'type'       => 'remoting',
            'namespace'  => self::$namespace,
            'descriptor' => self::$descriptor,
            'maxRetries' => self::$max_retries,
            'timeout'    => self::$timeout
        ];

        if(empty($api_array['id']))
            unset($api_array['id']);

        $actions = [];

        foreach(self::$api_classes as $key => $value)
        {
            $apiClass = is_string($key) ? $key : $value;
            $class = $value;

            $methods = [];
            $reflection = new ReflectionClass($class);

            foreach($reflection->getMethods() as $method)
            {
                // Only public methods will be declared except methods marked with @extdirect-exclude

                if(!$method->isPublic() || (strpos($method->getDocComment(), '@extdirect-exclude') !== false))
                    continue;
                // Don't declare constructor, destructor or abstract methods
                if($method->isConstructor() || $method->isDestructor() || $method->isAbstract())
                    continue;

                // Only declare static methods according to "include_static_methods" configuration
                if(!self::$include_static_methods && $method->isStatic())
                    continue;

                // Do not declare inherited methods, according to "include_inherited_methods" configuration
                if(!self::$include_inherited_methods && ($method->getDeclaringClass()->name != $class))
                    continue;

                // If "declare_method_function" is set, we test if the method can be declared, according to its return result
                if(!empty(self::$declare_method_function) &&
                   !call_user_func(self::$declare_method_function, $class, $method->getName())
                )
                    continue;

                // Count only required parameters or count them all, according to "count_only_required_params" configuration
                if(self::$count_only_required_params)
                    $api_method = ['name' => $method->getName(), 'len' => $method->getNumberOfRequiredParameters()];
                else
                    $api_method = ['name' => $method->getName(), 'len' => $method->getNumberOfParameters()];

                // Check if method should be marked as "formHandler"
                if(in_array($class . '::' . $method->getName(), self::$form_handlers) ||
                   (strpos($method->getDocComment(), '@extdirect-formHandler') !== false)
                )
                    $api_method['formHandler'] = true;

                $methods[] = $api_method;
            }

            if(count($methods))
                $actions[$apiClass] = $methods;
        }

        $api_array['actions'] = $actions;

        return $api_array;
    }

    /**
     * @return string JSON encoded array containing the full API declaration
     */
    public static function get_api_json() : string
    {
        return json_encode(self::get_api_array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string JavaScript code containing the full API declaration
     */
    public static function get_api_javascript() : string
    {
        $template = <<<JAVASCRIPT

if(Ext.syncRequire)
    Ext.syncRequire('Ext.direct.Manager');

Ext.ns('[%namespace%]');
[%descriptor%] = [%actions%];
Ext.direct.Manager.addProvider([%descriptor%]);

JAVASCRIPT;

        $elements = [
            '[%actions%]'    => self::get_api_json(),
            '[%namespace%]'  => ExtDirect::$namespace,
            '[%descriptor%]' => ExtDirect::$descriptor
        ];

        return strtr($template, $elements);
    }

    /**
     * Automatic discovering api classes with "@extdirect-api" attribute in the DOC comment of the class.
     *
     * @return array Classes detected as @extdirect-api
     */
    public static function discover_api_classes() : array
    {
        $api_classes = [];

        if(empty(self::$api_classes_discovery_dirs))
            return $api_classes;

        foreach(self::$api_classes_discovery_dirs as $dir => $namespace)
        {
            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

            /** @var SplFileInfo $file */
            foreach($rii as $file)
            {
                if($file->isDir())
                    continue;

                try
                {
                    $class = $namespace
                             . str_replace([$dir, '/'], ['', '\\'], $file->getPath())
                             . '\\' . $file->getBasename('.' . $file->getExtension());

                    $refClass = new ReflectionClass($class);

                    if(strpos($refClass->getDocComment(), '@extdirect-api') !== false)
                    {
                        // Remove from api name $namespace and replace slashes with dots
                        $apiName = str_replace([$namespace . '\\', '\\'], ['', '.'], $refClass->getName());

                        // Add class
                        $api_classes[$apiName] = $refClass->getName();
                    }
                }
                catch(ReflectionException $e)
                {
                    // Class does not exist. Do nothing.
                }
            }
        }
        return $api_classes;
    }

    /**
     * Provide access via Ext.Direct to the specified class or classes
     * This method does one of the following two things, depending on the HTTP request.
     * 1) Outputs the API declaration in the chosen format (JSON or JavaScript)
     * 2) Process the action(s) and return its result(s) (JSON)
     * @param string|array|null $api_classes Class name(s) or key-value pair(s) to publish in the API declaration
     * @param bool              $echo        If true, automatically send headers and content to php://output
     * @return ExtDirectResponse
     */
    public static function provide($api_classes = null, bool $echo = false) : ExtDirectResponse
    {
        return (new ExtDirectController($api_classes, true, $echo))->response;
    }
}

/**
 * Process Ext.Direct HTTP requests
 *
 * @author J. Bruni
 * @fork   stanx
 */
class ExtDirectRequest
{
    /**
     * @var array Actions to be executed in this request
     */
    public $actions = [];

    /**
     * @var bool True if there is a file upload; false otherwise
     */
    public $upload = false;

    /**
     * Call the correct actions processing method according to $_POST['extAction'] availability
     */
    public function __construct()
    {
        if(isset($_POST['extAction']))
            $this->get_form_action();
        else
            $this->get_request_actions();
    }

    /**
     * Instantiate actions to be executed in this request using "extAction" (form)
     */
    protected function get_form_action()
    {
        $extParameters = $_POST;

        /**
         * @var string      $extType
         * @var string      $extAction
         * @var string      $extMethod
         * @var int         $extTID
         * @var bool|string $extUpload
         */

        foreach(['extAction', 'extMethod', 'extTID', 'extUpload', 'extType'] as $variable)
        {
            if(!isset($extParameters[$variable]))
            {
                $$variable = '';
            }
            else
            {
                $$variable = $extParameters[$variable];
                unset($extParameters[$variable]);
            }
        }

        if($extType == 'rpc')
        {
            $this->upload = ($extUpload == 'true');

            $this->actions[] =
                new ExtDirectAction($extAction, $extMethod, $extParameters, $extTID, $this->upload, true);
        }
    }

    /**
     * Instantiate actions to be executed in this request (without "extAction")
     */
    protected function get_request_actions()
    {
        $input = file_get_contents('php://input');

        $request = json_decode($input);

        if(!is_array($request))
            $request = [$request];

        foreach($request as $rpc)
        {
            /**
             * @var string     $type
             * @var string     $action
             * @var string     $method
             * @var array|null $data
             * @var int|string $tid
             */

            foreach(['type', 'action', 'method', 'data', 'tid'] as $variable)
            {
                $$variable = (isset($rpc->$variable) ? $rpc->$variable : '');
            }

            if($type == 'rpc')
            {
                $data = empty($data) ? [] : $data;

                $this->actions[] = new ExtDirectAction($action, $method, $data, intval($tid), false, false);
            }
        }
    }
}

/**
 * Store HTTP response contents for output
 *
 * @author J. Bruni
 * @fork   stanx
 */
class ExtDirectResponse
{
    /**
     * @var array HTTP headers to be sent in the response
     */
    public $headers = [];

    /**
     * @var string HTTP body to be sent in the response
     */
    public $contents = '';
}

/**
 * Call Ext.Direct API class method and format the results
 *
 * @author J. Bruni
 * @fork   stanx
 */
class ExtDirectAction
{
    /**
     * @var string API class name
     */
    public $action;

    /**
     * @var string Real class name with namespace
     */
    public $class;

    /**
     * @var string Method name
     */
    public $method;

    /**
     * @var array Method parameters
     */
    public $parameters;

    /**
     * @var int Unique identifier for the transaction
     */
    public $transaction_id;

    /**
     * @var bool True if there is a file upload; false otherwise
     */
    public $upload = false;

    /**
     * @var bool True if this action is handling a form; false otherwise
     */
    public $form_handler = false;

    /**
     * @var bool False only when "authorization_function" (if configured) returns a non-true value
     */
    public $authorized = true;

    /**
     * @var Exception Exception object, instantiated if an exception occurs while executing the action
     */
    public $exception;

    /**
     * @param string $action         API class name
     * @param string $method         Method name
     * @param array  $parameters     Method parameters
     * @param int    $transaction_id Unique identifier for the transaction
     * @param bool   $upload         True if there is a file upload; false otherwise
     * @param bool   $form_handler   True if the action is a form handler; false otherwise
     */
    public function __construct(string $action, string $method, array $parameters,
                                int $transaction_id, bool $upload = false, bool $form_handler = false)
    {
        $this->action = $action;
        $this->method = $method;
        $this->parameters = $parameters;
        $this->transaction_id = $transaction_id;
        $this->upload = $upload;
        $this->form_handler = $form_handler;
    }

    /**
     * @return array Result of the action execution
     */
    public function run() : array
    {
        $response = [
            'type'   => 'rpc',
            'tid'    => $this->transaction_id,
            'action' => $this->action,
            'method' => $this->method
        ];

        try
        {
            $result = $this->call_action();
            $response['result'] = $result;
        }

        catch(Exception $e)
        {
            $response['type'] = 'exception';
            $response['message'] = 'Exception';

            if(ExtDirect::$debug)
            {
                $response['message'] = $e->getMessage();
                $response['where'] = $e->getTraceAsString();
            }

            $this->exception = $e;
        }

        if(is_callable(ExtDirect::$transform_response_function))
            $response = call_user_func(ExtDirect::$transform_response_function, $this, $response);

        if(ExtDirect::$utf8_encode)
        {
            array_walk_recursive($response, function(&$value, $key)
            {
                if(is_string($value))
                    $value = utf8_encode($value);
                return $value;
            });
        }

        return $response;
    }


    /**
     * @return mixed Result of the action
     * @throws Exception
     */
    protected function call_action()
    {
        $apiClass = null;
        $class = null;

        foreach(ExtDirect::$api_classes as $key => $value)
        {
            $name = is_string($key) ? $key : $value;
            if($this->action == $name)
            {
                $apiClass = $name;
                $class = $value;
                break;
            }
        }
        $this->class = $class;

        // Accept only calls to classes defined at "api_classes" configuration
        if(!isset($apiClass))
            throw new Exception('Call to undefined or not allowed class ' . $class,
                E_USER_ERROR);

        // Do not allow calls to magic methods; only allow calls to methods returned by "get_class_methods" function
        if((substr($this->method, 0, 2) == '__') || !in_array($this->method, get_class_methods($class)))
            throw new Exception('Call to undefined or not allowed method ' . $class . '::' . $this->method,
                E_USER_ERROR);

        // Do not allow calls to methods that do not pass the declare_method_function (if configured)
        if(!empty(ExtDirect::$declare_method_function) &&
           !call_user_func(ExtDirect::$declare_method_function, $class, $this->method)
        )
            throw new Exception('Call to undefined or not allowed method ' . $class . '::' . $this->method,
                E_USER_ERROR);

        // Do not allow calls to methods that do not pass the authorization_function (if configured)
        if(!empty(ExtDirect::$authorization_function) &&
           !call_user_func(ExtDirect::$authorization_function, $this))
        {
            $this->authorized = false;
            throw new Exception('Not authorized to call ' . $class . '::' . $this->method,
                E_USER_ERROR);
        }

        $ref_method = new ReflectionMethod($class, $this->method);

        // Do not allow calls to methods that marked with @extdirect-exclude
        if(strpos($ref_method->getDocComment(), '@extdirect-exclude') !== false)
            throw new Exception('Call to undefined or not allowed method ' . $class . '::' .
                                $this->method, E_USER_ERROR);

        // Get number of parameters for the method
        if(ExtDirect::$count_only_required_params)
            $params = $ref_method->getNumberOfRequiredParameters();
        else
            $params = $ref_method->getNumberOfParameters();

        if($this->upload)
            $params -= count($_FILES);

        if(count($this->parameters) < $params)
            throw new Exception('Call to ' . $class . ' method ' . $this->method . ' needs at least ' . $params .
                                ' parameters', E_USER_ERROR);

        // Check inheritance
        if(!ExtDirect::$include_inherited_methods && ($ref_method->getDeclaringClass()->name != $class))
            throw new Exception('Call to ' . $class . ' inherited method ' . $this->method .
                                ' not allowed', E_USER_ERROR);

        // Confirm if the method is a formHandler
        $this->form_handler = $this->form_handler &&
                              (in_array($class . '::' . $this->method, ExtDirect::$form_handlers) ||
                               (strpos($ref_method->getDocComment(), '@extdirect-formHandler') !== false));

        if(!$this->form_handler)
        {
            $parameters = $this->parameters;
        }
        else
        {
            $parameters = [];

            // We treat formHandler's parameters in a special way
            foreach($ref_method->getParameters() as $ref_parameter)
            {
                $param_name = $ref_parameter->getName();

                if(isset($this->parameters[$param_name]))
                {
                    $value = $this->parameters[$param_name];

                    // Decode array|object value from json string
                    if(is_string($value) &&
                       ($ref_parameter_type = $ref_parameter->getType()) &&
                       $ref_parameter_type instanceof ReflectionNamedType &&
                       in_array($ref_parameter_type->getName(), ['array', 'object', 'stdClass']))
                    {
                        $value = json_decode($value, $ref_parameter_type->getName() === 'array');
                    }
                }
                else if($this->upload && isset($_FILES[$param_name]))
                {
                    $value = $_FILES[$param_name];
                }
                else if($ref_parameter->isDefaultValueAvailable())
                {
                    $value = $ref_parameter->getDefaultValue();
                }
                else
                {
                    $value = null;
                }

                $parameters[] = $value;
            }
        }

        if($ref_method->isStatic())
        {
            if(!ExtDirect::$include_static_methods)
                throw new Exception('Call to static method ' . $class . '::' . $this->method . ' not allowed',
                    E_USER_ERROR);

            // If the method is static, we usually don't need to create an instance
            if(!ExtDirect::$instantiate_static)
                return $this->call_action_func_array([$class, $this->method], $parameters);
        }

        // By default, we do not send parameters to constructor,
        // but "constructor_send_params" and "constructor_params" configurations allow this
        if(is_callable(ExtDirect::$instantiate_function))
        {
            $instance = call_user_func(ExtDirect::$instantiate_function, $this);
        }
        else if(!ExtDirect::$constructor_send_params && empty(ExtDirect::$constructor_params[$class]))
        {
            $instance = new $class();
        }
        else
        {
            if(empty(ExtDirect::$constructor_params[$class]))
                $constructor_params = $this->parameters;
            else
                $constructor_params = ExtDirect::$constructor_params[$class];

            $ref_class = new ReflectionClass($class);
            $instance = $ref_class->newInstanceArgs($constructor_params);
        }

        return $this->call_action_func_array([$instance, $this->method], $parameters);
    }

    /**
     * Calls the action method,
     * transform the result (if "transform_result_function" is configured),
     * and then return the result
     *
     * @param callable $callback   Action method to be called
     * @param array    $parameters Parameters to pass to the action method
     *
     * @return mixed Result of the action method
     */
    protected function call_action_func_array(callable $callback, array $parameters)
    {
        $result = call_user_func_array($callback, $parameters);

        if(is_callable(ExtDirect::$transform_result_function))
            $result = call_user_func(ExtDirect::$transform_result_function, $this, $result);

        return $result;
    }
}

/**
 * Ext.Direct API controller
 *
 * @author J. Bruni
 * @fork   stanx
 */
class ExtDirectController
{
    /**
     * @var ExtDirectRequest Object to process HTTP request
     */
    public $request;

    /**
     * @var ExtDirectResponse Object to store HTTP response
     */
    public $response;

    /**
     * @param string|array|null $api_classes Name of the class or classes to be published to the Ext.Direct API
     * @param bool              $autorun     If true, automatically run the controller
     * @param bool              $echo        If true, automatically send headers and content to php://output
     */
    public function __construct($api_classes = null, bool $autorun = true, bool $echo = false)
    {
        if(is_array($api_classes))
            ExtDirect::$api_classes = $api_classes;
        else if(is_string($api_classes))
            ExtDirect::$api_classes = [$api_classes];

        // Merge initial api classes with discovered ones
        ExtDirect::$api_classes = array_merge(ExtDirect::$api_classes, ExtDirect::discover_api_classes());

        $this->request = new ExtDirectRequest();
        $this->response = new ExtDirectResponse();

        if($autorun)
        {
            $this->run();

            if($echo)
            {
                $this->output();
                exit();
            }
        }
    }

    /**
     * @return string JSON or JavaScript API declaration for the classes on "api_classes" configuration array
     */
    public function get_api() : string
    {
        if(isset($_GET['json']) || (ExtDirect::$default_api_output == 'json'))
            return ExtDirect::get_api_json();
        else
            return ExtDirect::get_api_javascript();
    }

    /**
     * Process the request, execute the actions, and generate the response
     */
    public function run()
    {
        if(empty($this->request->actions))
        {
            $this->response->contents = $this->get_api();

            $this->response->headers[] = 'Content-Type: application/javascript';
        }
        else
        {
            $response = [];
            foreach($this->request->actions as $action)
            {
                $response[] = $action->run();
            }

            if(count($response) > 1)
                $this->response->contents = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            else
                $this->response->contents = json_encode($response[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if($this->request->upload)
            {
                $this->response->contents = '<html><body><textarea>' .
                                            preg_replace('/&quot;/', '\\&quot;', $this->response->contents) .
                                            '</textarea></body></html>';

                $this->response->headers[] = 'Content-Type: text/html';
            }
            else
            {
                $this->response->headers[] = 'Content-Type: application/json';
            }
        }
    }

    /**
     * Output response contents
     */
    public function output()
    {
        foreach($this->response->headers as $header)
        {
            header($header);
        }

        echo $this->response->contents;
    }
}
