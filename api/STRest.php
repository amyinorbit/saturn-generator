<?
/**
 * STRest.php - Basic REST API class
 * Saturn API - Simple REST interface for Saturn blogs
 * Created on 2014-10-12 by Cesar Parent <cesar@cesarparent.com>
 */

namespace Saturn;

require_once(__DIR__."/XMLEncode.php");

use \Exception as Exception;

abstract class RESTServer
{
	private static $allowed_methods = ["GET", "POST", "PUT", "DELETE"];
	protected $base_url;
	protected $method;
	protected $data;
	protected $route;
	protected $resource_id;
	protected $response_data;
	protected $response_status;
	protected $http_status;
	private $route_handlers;
	private $response_format;

	/**
	 * Build the REST Server. The request array should contain the requested
	 * resource's path. No default route handler is provided for /
	 *
	 * @param   array   $request_array the array containing the request
	 * @return \Saturn\RESTServer an instance of RESTServer
	 */
	public function __construct(array $request_array)
	{
		$this->route_handlers = [];
		$this->route = $this->parse_route($request_array["resource"]);
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->data = $this->get_data();
		$this->http_status = 200;
		$this->response_status = "success";

		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/".$this->response_format);
	}
	
    /**
     * Build the HMAC authentication string and sign it. If the signature is the one that
     * was sent with the request, and the timestamp of the request is less than 10 minutes old,
     * then the request is considered valid.
     * 
     * @return bool     True if the authentication data passed was valid, false otherwise.
     */
	private function authenticate()
	{
		$expiration = time() - (10*60);
		if(!isset($this->data["time"]) ||
			intval($this->data["time"]) < $expiration)
		{
			return false;
		}
		$this->data["time"] = intval($this->data["time"]);
		$sign_data = [];
		foreach($this->data as $key => $value)
		{
			if(in_array($key, ["title", "content", "tags", "time", "limit"]))
			{
				if(is_numeric($value))
				{
					$sign_data[$key] = intval($value);
				}
				else
				{
					$sign_data[$key] = $value;
				}
			}
		}
		ksort($sign_data);
		$json = json_encode($sign_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$to_sign = $this->method.$this->route.$json;
		$signature = hash_hmac("sha256",
			$to_sign,
			$this->saturn->blog["api-secret"]);
		if(!isset($_SERVER["HTTP_X_SATURN_AUTH"]) ||
			$_SERVER["HTTP_X_SATURN_AUTH"] !== $signature)
		{
			$output = "server: '".$to_sign."'\n\nsigned: '".$signature."'\nclient:'".$_SERVER["HTTP_X_SATURN_AUTH"]."'";
			file_put_contents("debug.txt", $output);
			return false;
		}
		return true;
	}

	/**
	 * Checks if a handler exists for the requested resource, and executes it
	 * In case none is provided, an error will be returned by the API.
	 * Once the handler has been ran, a JSON Response is echoed.
	 */
	public function handle_request()
	{
		if(!$this->authenticate())
		{
			$this->http_status = 403;
			$this->response_data = "Invalid request signature";
			$this->response_status = "error";
		}
		else
		{
			try
			{
				$handler = $this->match_route();
				if($handler !== null)
				{
					call_user_func($handler);
				}
				else
				{
					$this->http_status = 404;
					$this->response_status = "fail";
					$this->response_data["request"] = "Invalid method or endpoint";
				}
			}
			catch(Exception $e)
			{
				$this->http_status = 500;
				$this->response_status = "error";
				$this->response_data = $e->getMessage();
			}
		}
		echo $this->response();
	}

	/**
	 * Check if the route requested by the client is a valid one, and call the
     * appropriate handler method if it is.
	 *
	 * @param   String      $route      Route requested.
     * @param   String      $method     HTTP method the request was made through.
     * @param   callable    $handler    method to call if the route is valid.
	 */
	public function route($route, $method, callable $handler)
	{
		if(is_callable($handler))
		{
			if(!in_array($method, self::$allowed_methods))
			{
				throw new Exception("Invalid Method for route ".$handler);
			}
			$pattern = $this->create_pattern($route);
			$this->route_handlers[$method][$pattern] = $handler;
		}
	}

	/**
	 * Create a JSON or XML response based on the data held in the object.
     * Responses are compliant with the JSend <http://labs.omniti.com/labs/jsend>
     * format: a `status` field indicates wether the request was successful, and the
     * response payload is in the `data` field.
     *
     * @return  String  JSON or XML encoded response.
	 */
	protected function response()
	{
		$response = [];
		$response["status"] = $this->response_status;
		if($response["status"] === "success" || $response["status"] === "fail")
		{
			$response["data"] = $this->response_data;
		}
		else
		{
			$response["message"] = $this->response_data;
		}

		if($this->response_format === "xml")
		{
			$data = xml_encode($response);
		}
		else
		{
			$data = json_encode($response);
		}
		header("HTTP/1.1 ".$this->http_status." ".$this->status($this->http_status));
		header("Content-Length: ".strlen(utf8_decode($data)));
		return $data;
	}

	/**
	 * Create a RegEx pattern from the route to check it against registered
     * route handlers.
     *
     * @return  String  Request route corresponding pattern.
	 */
	private function create_pattern($route)
	{
		$pattern = str_replace("/", "\/", $route);
		$pattern = str_replace("<id>", "([a-zA-Z0-9-_\.\+]+)", $pattern);
		$pattern = "/^".$pattern."$/";
		return $pattern;
	}

	/*
	 *
	 *
	 *
	 */
	private function match_route()
	{
		foreach($this->route_handlers[$this->method] as $k => $v)
		{
			if(preg_match($k, $this->route, $matches))
			{
				$this->resource_id = (isset($matches[1]))? $matches[1] : null;
				return $v;
			}
		}
		return null;
	}

	/**
	 * Get the HTTP reason-phrase corresponding to its code. If the code does
     * not match one of the valid reson-phrases, 500 Server Error is returned.
     *
     * @param   int     $code   HTTP Status code.
     * @return  String          Reason phrase corresponding to $code.
	 */
	private function status($code)
	{
		$status = [
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
		];
		return ($status[$code])? $status[$code] : $status[500];
	}

	/**
     * Get the data from PHP superglobals depending on the request method.
     *
     * @return  array   Request data.
	 */
	private function get_data()
	{
		if($this->method === "POST")
		{
			$data = $this->clean_input($_POST);
		}
		else
		{
			$data = $this->clean_input($_GET);
		}
		return $data;
	}

	/**
	 * Cleanup the request route, and extract the response format (XML or JSON)
     * from the address. If nothing is specified, JSON is selected by default.
     *
     * @param   String  $route  Request's route.
     * @return  String          Clean route, without format extension and trailing slash.
	 */
	private function parse_route($route)
	{
		$clean_route = "/".trim($route, "/");
		if(preg_match("~\.(json|xml)$~", $clean_route, $matches))
		{
			$this->response_format = $matches[1];
			return str_replace($matches[0], "", $clean_route);
		}
		else
		{
			$this->response_format = "json";
			return $clean_route;
		}
	}

	/**
     * Recursively clean the request data (remove trailing whitespace, strip HTML tags).
     * @param   mixed   $input  Input to clean. (array or string).
     * @return  array           Cleaned data array.
	 */
	private function clean_input($input)
	{
		$cleaned = [];
		if(is_array($input))
		{
			foreach($input as $k => $v)
			{
				$cleaned[$k] = $this->clean_input($v);
			}
		}
		else
		{
			$cleaned = trim(strip_tags($input));
		}
		return $cleaned;
	}
}
?>
