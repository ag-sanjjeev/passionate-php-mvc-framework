<?php

namespace app\core;

/**
 * Class Route
 *
 * This is an routing class file. Which has information about all routes
 * given in this application
 *
 * @copyright 2023 ag-sanjjeev
 * @license url MIT
 * @version Release: @1.0@
 * @link url
 * @since Class available since Release 1.0
 */
class Route 
{
	/**
	 * The typed property value of Route class
	 * Potential value will be the object of the class
	 *
	 * @var Route $routeInstance
	 */
	public static Route $routeInstance;

	/**
	 * The typed property value of Request class
	 * Potential value will be the object of the class
	 *
	 * @var Request $request
	 */
	public Request $request;

	/**
	 * The typed property value of Response class
	 * Potential value will be the object of the class
	 *
	 * @var Response $response
	 */
	public Response $response;

	/**
	 * The value of path is when registering routes temporarily holds url path
	 * Potential value will be urlPath
	 *
	 * @var string $urlPath
	 */
	public static string $urlPath = "";

	/**
	 * The value of method is when registering routes temporarily holds request method
	 * Potential value will be ['get', 'post', 'any']
	 *
	 * @var string $method
	 */
	public static string $method = "";

	/**
	 * The value of routes is all route information of this application
	 * Potential value will be an array of all routes information
	 *
	 * @var array $routes
	 */
	public static array $routes = [];

	/**
	 * The value of middlewares is all middlewares information for respective routes
	 * Potential value will be an array of all middlewares information
	 *
	 * @var array $middlewares
	 */
	public static array $middlewares = [];

	/**
	 * Initiating routes entry point for this application 
	 *	 
	 * @author ag-sanjjeev <resulttext>	 
	 *
	 */
	function __construct()
	{
		self::$routeInstance 	= 	$this;
		$this->request 			=	new Request;
		$this->response 		= 	new Response;

		/*
			Assigning route directory for all route files.
			Which is routes inside the project main directory.
		*/
		$routesDirectory 		= 	dirname(__DIR__) . '/routes/';

		/*
			Getting all route files inside the routesDirectory.			
		*/
		$routeFiles 			= 	glob($routesDirectory . "*.php");

		/*
			Iterating through all route files in the routesDirectory to inject into this project
		*/
		foreach ($routeFiles as $file) {
			require_once $file;
		}
	}

	/**
	 * Registers all get request routes to this application
	 *
	 * @param urlPath $path This is a valid url for the request
	 * @param callback $callback This is a valid callback for the respective urlPath
	 * @author ag-sanjjeev <resulttext>
	 * @return $routeInstance
	 */
	public static function get($path, $callback)
	{
		self::$urlPath = $path;
		self::$method = 'get';
		self::$routes['get'][$path] = $callback;		
		return self::$routeInstance;
	}

	/**
	 * Registers all post request routes to this application
	 *
	 * @param urlPath $path This is a valid url for the request
	 * @param callback $callback This is a valid callback for the respective urlPath
	 * @author ag-sanjjeev <resulttext>
	 * @return $routeInstance
	 */
	public static function post($path, $callback)
	{
		self::$urlPath = $path;
		self::$method = 'post';
		self::$routes['post'][$path] = $callback;
		return self::$routeInstance;
	}

	/**
	 * Registers all any method request routes to this application
	 *
	 * @param urlPath $path This is a valid url for the request
	 * @param callback $callback This is a valid callback for the respective urlPath
	 * @author ag-sanjjeev <resulttext>
	 * @return $routeInstance
	 */
	public static function any($path, $callback)
	{
		self::$urlPath = $path;
		self::$method = 'any';
		self::$routes['any'][$path] = $callback;
		return self::$routeInstance;
	}

	/**
	 * Registers all middlewares for respective requests
	 *
	 * @param middlewareName $middleware This is a valid middleware class name
	 * @author ag-sanjjeev <resulttext>
	 * @return $routeInstance
	 */
	public static function middleware($middleware)
	{
		/*
			It Changes first letter of middleware name to be capitalized
		*/
		$middleware = ucfirst($middleware);

		/*
			Rewriting middleware for namespace
		*/
		$nsMiddleware = "app\\core\\middlewares\\$middleware";

		/*
			Checking for middlware exist inside the app\core\middlewares namespace
			If exists then registers it otherwise throws an exception
		*/
		if (class_exists($nsMiddleware)) {
			self::$middlewares[self::$method][self::$urlPath] = $nsMiddleware;
		} else {
			throw new Exception("$middleware is not exist in the $nsMiddleware", 1);
			exit();
		}

	}

	/**
	 * This method establishes routes for this application
	 *	 
	 * @author ag-sanjjeev <resulttext>
	 * @return response or callback
	 */
	public function establish()
	{
		/*
			Getting current url path from Request class
		*/
		$urlPath 			= 	self::$routeInstance->request::$urlPath;

		/*
			Getting current request method from Request class
		*/
		$method 			= 	self::$routeInstance->request::$method;

		/*
			Getting callback for current request if exist otherwise false
		*/
		$callback 			= 	self::$routes['any'][$urlPath] ?? self::$routes[$method][$urlPath] ?? false;

		/*
			Getting middleware for current request if exist otherwise false
		*/
		$middleware 		= 	self::$middlewares['any'][$urlPath] ?? self::$middlewares[$method][$urlPath] ?? false;

		/*
			If callback false then there is no valid callback which returns 404 response
		*/
		if ($callback === false) {
			/*
				Sets http response code to 404 page not found error
			*/
			$this->response->setStatusCode(404);
			
			echo $this->response->view("default/404_error");
		}

		/*
			Initiates middleware class if allocated for the current request
		*/
		if ($middleware !== false) {
			new $middleware;
		}

		/*
			This will directly point to the view page
		*/
		if (is_string($callback)) {			
			echo $this->response->view($callback);
		}

		/*
			If the callback is an array then it has Controller class and it's method.
			It creates controller instance and assigns to the Application property.
			And replaces the $callback array's first parameter with controller instance.
			The second parameter as it is method of that controller class.
		*/
		if (is_array($callback)) {
			Application::$app->controller = new $callback[0]();
			$callback[0] = Application::$app->controller;
		}

		/*
			This will executes either custom user function or controller method
		*/
		$responseView = call_user_func($callback, $this->request, $this->response);
		
		/*
			If it is an array then it will display as json content-type
		*/
		if (is_array($responseView)) {
			$this->response->setContentType('application/json');			
			print_r($responseView);
		}

		/*
			If it is a string then it will display as html content-type
		*/
		if (is_string($responseView)) {
			$this->response->setContentType('text/html');
			echo $responseView;
		}
		
	}
}