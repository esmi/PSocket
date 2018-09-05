<?php
namespace Esmi\socket;

class socket {
    protected $host;
    protected $post;
    protected $sock;
    protected $connection;
    protected $size;
    protected $errmsg;
    protected $sockstat;
    protected $time_limit;
    private $callback;

    function __construct($a) {

		//echo 'construct() running.....';
        $this->host = $a['HOST'];
        $this->port = $a['PORT'];
        $this->size = $a['SIZE'];
        $this->time_limit = $a['TIME_LIMIT'];
        //$this->size = 5 * 1000 * 1000;

		$this->sockstat = true;
		$this->errmsg = "";
		$this->callback = new UnregisterableCallback(array($this, "check_for_fatal"));

		set_time_limit($this->time_limit);
		register_shutdown_function(array($this->callback, "call"));
		set_error_handler(array($this, 'errhandler'));
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$this->connection = socket_connect($this->sock,$this->host, $this->port);
        restore_error_handler();

    }

	function __destruct() {
		$this->callback->unregister();
	}

	function check_for_fatal()
	{
		$error = error_get_last();
		if ( $error["type"] == E_ERROR )
			error_log ( $error["type"], $error["message"], $error["file"], $error["line"] );
	}

    function errhandler($errno, $errstr, $errfile, $errline)
	{
	   if (!(error_reporting() & $errno)) {
			// Ce code d'erreur n'est pas inclus dans error_reporting()
			echo "not error_reporting......";
			return;
		}

		switch ($errno) {
		case E_WARNING:
			//echo $errstr . "xx";
			if ( $errstr == 'socket_connect(): ') {
				$this->errmsg = "報表服務程式未開啟! 請開啟報表服務程式.";
				$this->sockstat = false;

			}
			// 無法讀取的原因可能是: 機器修眠重啟後, report.exe 無法讀取資料庫資料.
			if ($errstr == 'socket_read(): ') {
				$this->errmsg = "無法讀取報表服務的資料! 請重新開啟報表服務程式.";
				$this->sockstat = false;

			}
			//echo $errstr;
			//Fatal error: Maximum execution time of 30 seconds exceeded in 
			//C:\xampp\htdocs\southtainan\manage\pages\socket.php 
			//on line 104
			break;
		case E_USER_ERROR:
			echo "<b>Mon ERREUR</b> [$errno] $errstr<br />\n";
			echo "  Erreur fatale sur la ligne $errline dans le fichier $errfile";
			echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
			echo "Arrêt...<br />\n";
			exit(1);
			break;

		case E_USER_WARNING:
			echo "<b>Mon ALERTE</b> [$errno] $errstr<br />\n";
			break;

		case E_USER_NOTICE:
			echo "<b>Mon AVERTISSEMENT</b> [$errno] $errstr<br />\n";
			break;

		default:
			echo "Type d'erreur inconnu : [$errno] $errstr<br />\n";
			break;
		}

		/* Ne pas exécuter le gestionnaire interne de PHP */
		return true;
	}
    function status() {
    	return $this->sockstat;
    }
    function error() {
    	$res = array();
        $res['status'] = "error";
        $res['message'] = $this->strerror();
        return ($res);
    }
    function strerror() {
    	return $this->errmsg;
    }
    function write($input) {
    	//echo "write input: " . $input . "\r\n";
		set_error_handler(array($this, 'errhandler'));
        $res = socket_write($this->sock, $input); 
        restore_error_handler();
        //var_dump($res);
        return $res;
    }
    function read() {
    	//echo "reading socket: " . "\r\n";
		set_error_handler(array($this, 'errhandler'));
        $res = socket_read($this->sock, $this->size, PHP_NORMAL_READ);
        //var_dump( $res);
        restore_error_handler();
        //var_dump($res);
        return $res;
    }
    function setsize($i) {
        $this->size = $i;
    }
}

class UnregisterableCallback{

    // Store the Callback for Later
    private $callback;

    // Check if the argument is callable, if so store it
    public function __construct($callback)
    {
        if(is_callable($callback))
        {
            $this->callback = $callback;
        }
        else
        {
            throw new InvalidArgumentException("Not a Callback");
        }
    }

    // Check if the argument has been unregistered, if not call it
    public function call()
    {
        if($this->callback == false)
            return false;

        $callback = $this->callback;
        $callback(); // weird PHP bug
    }

    // Unregister the callback
    public function unregister()
    {
        $this->callback = false;
    }
}