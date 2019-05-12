<?php

/**
 * gzip_browser class
 */
class gzip_browser {

	/**
	 *
	 */
	public $css_replacements = array();
	public $js_replacements = array();

	/**
	 *
	 */
	public $exit = false;
	public $method = 'get'; // or post or request
	public $param = 'uri';
	public $gz_folder = './';
	public $cache_days = 3600;
	public $document_root;
	public $document_realroot;
	public $last_modified = 0;
	public $mime_types = array(
		'htm' => 'text/html',
		'html' => 'text/html',
		'js' => 'text/javascript',
		'css' => 'text/css',
		'xml' => 'text/xml',
		'txt' => 'text/plain'
	);
	public $value = '';
	public $minify_js = true;
	public $minify_css = true;

	/**
	 *
	 */
	protected $src_uri = array();
	protected $gz_uri = '';
	protected $file_uri = '';
	protected $uri_root = '';
	protected $mime_type = '';
	protected $mime_ext = '';
	protected $browser_gzip = false;

	/**
	 *
	 * @param string $param
	 */
	public function __construct($param = '', $uri_root = '', $document_root = '.') {
		$this->param = $param;
		$this->uri_root = $uri_root;
		$this->document_root = $document_root;
	}

	/**
	 * 
	 */
	public static function css_wordwrap($data) {
		$str = $data[1];
		if (strpos($str, ',') !== FALSE) {
			$items = explode(', ', $str);
			$ret = '';
			$s = 0;
			foreach ($items as $item) {
				$n = strlen($item);
				if (empty($ret)) {
					$ret .= $item;
					$s = $n;
				}
				elseif ($s + $n > 38) {
					$ret .= ",\n" . $item;
					$s = $n;
				}
				else {
					$ret .= ", " . $item;
					$s += $n + 2;
				}
			}
			return $ret;
		}
		else {
			return $str;
		}
	}

	/**
	 *
	 * @param <type> $filename 
	 */
	protected function getJS($filename) {
		$js = file_get_contents($filename);
		$js = strtr($js, $this->js_replacements);
		if (class_exists('JSMin') && $this->minify_js) {
			$js = trim(JSMin::minify($js));
		}
		$js = "// " . basename($filename) . "\n" . $js . "\n\n";
		return $js;
	}

	/**
	 *
	 * @param <type> $filename
	 */
	protected function getCSS($filename) {
		$css = trim(file_get_contents($filename));
		$css = strtr($css, $this->css_replacements);
		if ($this->minify_css) {
			$css = preg_replace('!/\*.*?\*/!si', '', $css);
			$css = preg_replace('!\t|\r|\n!si', '', $css);
			$css = preg_replace('! *(;|,|:|\{|\}) *!si', '$1', $css);
			$css = str_replace('}', "}\n", $css);
		}
		$css = "/***[ " . basename($filename) . " ]***/\n" . $css . "\n\n\n";
		return $css;
	}

	/**
	 *
	 * @return bool
	 */
	protected function gz_compress() {
		$ret = false;
		$file = fopen($this->file_uri, 'wb');
		$filegz = gzopen($this->gz_uri, 'wb9');
		if ($filegz && $file) {
			if ($this->mime_ext == 'js') {
				foreach ($this->src_uri as $source) {
					$content = $this->getJS($source);
					fwrite($file, $content);
					gzwrite($filegz, $content);
				}
			}
			elseif ($this->mime_ext == 'css') {
				foreach ($this->src_uri as $source) {
					$content = $this->getCSS($source);
					fwrite($file, $content);
					gzwrite($filegz, $content);
				}
			}
			else {
				foreach ($this->src_uri as $source) {
					$content = file_get_contents($source) . "\n\n\n";
					fwrite($file, $content);
					gzwrite($filegz, $content);
				}
			}
			gzclose($filegz);
			fclose($file);
			$ret = true;
		}
		@chmod($this->file_uri, 0666);
		@chmod($this->gz_uri, 0666);
		return $ret;
	}

	/**
	 *
	 */
	public function send() {
		if (!$this->security_checks()) {
			return false;
		}
		$this->cache_headers();

		if (!file_exists(dirname($this->gz_uri))) {
			$this->mkdir(dirname($this->gz_uri));
		}

		if (file_exists($this->gz_uri)) {
			$src_last_modified = $this->last_modified;
			$dst_last_modified = filemtime($this->gz_uri);
			// The gzip version of the file exists, but it is older
			// than the source file. We need to recreate it...
			if ($src_last_modified > $dst_last_modified) {
				@unlink($this->gz_uri);
				@unlink($this->file_uri);
			}
		}

		if (!file_exists($this->gz_uri)) {
			if (!$this->gz_compress()) {
				if (!$this->exit) {
					return false;
				}
				header('HTTP/1.1 404 Not Found');
				echo('<html><body><h1>HTTP 404 - Not Found (4)</h1></body></html>');
				exit;
			}
		}

		// check browser gzip support
		$this->browser_gzip = true;
		// Let's compress only text files...
		$this->browser_gzip = $this->browser_gzip && (strpos($this->mime_type, 'text') !== false );
		// Finally, see if the client sent us the correct Accept-encoding: header value...
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			$this->browser_gzip = $this->browser_gzip && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false );
		}
		else {
			$this->browser_gzip = false;
		}

		if ($this->browser_gzip) {
			header('Content-Encoding: gzip');
			header("Content-Type: " . $this->mime_type);
			header("Content-Length: " . filesize($this->gz_uri));
			$this->readfile($this->gz_uri);
		}
		else {
			header("Content-Type: " . $this->mime_type);
			header("Content-Length: " . filesize($this->file_uri));
			$this->readfile($this->file_uri);
		}
		exit;
	}

	/**
	 *
	 * @param string $filename
	 */
	protected function readfile($filename) {
		if (file_exists($filename)) {
			$fh = fopen($filename, 'rb');
			while (!feof($fh)) {
				echo fread($fh, 65536);
			}
			fclose($fh);
		}
	}

	/**
	 *
	 * @param string $dir_name
	 */
	protected function mkdir($dir_name) {
		$dirs = explode('/', $dir_name);
		$dir = '';
		foreach ($dirs as $part) {
			$dir .= $part . '/';
			if (!is_dir($dir) && strlen($dir) > 0) {
				mkdir($dir);
				chmod($dir, 0755);
			}
		}
	}

	/**
	 *
	 */
	protected function cache_headers() {
		$max_age = $this->cache_days * 86400;

		$this->last_modified = 0;
		foreach ($this->src_uri as $uri) {
			$t = filemtime($uri);
			if ($t > $this->last_modified) {
				$this->last_modified = $t;
			}
		}
		header('Last-Modified: ' . date('r', $this->last_modified));

		$expires = $this->last_modified + $max_age;
		header('Expires: ' . date('r', $expires));

		$etag = dechex($this->last_modified);
		header('ETag: ' . $etag);

		$cache_control = 'must-revalidate, proxy-revalidate, max-age=86400, s-maxage=86400';
		header('Cache-Control: ' . $cache_control);

		// Check if the client should use the cached version. Return HTTP 304 if needed.
		if (function_exists('http_match_etag') && function_exists('http_match_modified')) {
			if (http_match_etag($etag) || http_match_modified($this->last_modified)) {
				header('HTTP/1.1 304 Not Modified');
				exit;
			}
		}
		else {
			error_log('The HTTP extensions to PHP does not seem to be installed...');
		}
	}

	/**
	 *
	 */
	protected function get_mime_type($filename, & $mime, & $ext) {
		$filename = basename($filename);
		$ext = '';
		$i = strrpos($filename, '.');
		if ($i !== false) {
			$ext = strtolower(substr($filename, $i + 1, strlen($filename) - $i - 1));
		}
		if (isset($this->mime_types[$ext])) {
			$mime = $this->mime_types[$ext];
			$ext = $ext;
			return TRUE;
		}
		else {
			$mime = '';
			$ext = '';
			return FALSE;
		}
	}

	/**
	 *
	 */
	protected function security_checks() {

		if (!empty($this->param)) {
			$VARS = null;
			switch (strtoupper($this->method)) {
				case 'GET' : $VARS = & $_GET;
					break;
				case 'POST' : $VARS = & $_POST;
					break;
				case 'REQUEST' : $VARS = & $_REQUEST;
					break;
			}
			$this->gz_folder = preg_replace('!/+$!', '/', $this->gz_folder);

			// the parameter is not set
			if (!isset($VARS[$this->param])) {
				header('HTTP/1.1 400 Bad Request');
				echo('<html><body><h1>HTTP 400 - Bad Request</h1></body></html>');
				exit;
			}
			$uris = explode(',', $VARS[$this->param]);
		}
		else {
			if (empty($this->value)) {
				header('HTTP/1.1 400 Bad Request');
				echo('<html><body><h1>HTTP 400 - Bad Request</h1></body></html>');
				exit;
			}
			$uris = explode(',', $this->value);
		}

		// check uris and mime types
		$current_folder = '';
		foreach ($uris as $uri) {
			$folder = dirname($uri);
			if (empty($folder) || $folder == '.') {
				$uri = $current_folder . $uri;
			}
			else {
				$current_folder = $folder . '/';
			}
			$uri = preg_replace('!//+!si', '/', $this->uri_root . $uri);
			if ($this->get_mime_type($uri, $mime, $ext)) {
				if (empty($this->mime_type)) {
					$this->mime_type = $mime;
					$this->mime_ext = $ext;
				}
				elseif ($this->mime_type != $mime) {
					if (!$this->exit) {
						return false;
					}
					header('HTTP/1.1 404 Not Found');
					echo('<html><body><h1>HTTP 404 - Not Found (1)</h1></body></html>');
					exit;
				}
			}
			$this->src_uri[] = $uri;
		}

		if (empty($this->mime_type)) {
			if (!$this->exit) {
				return false;
			}
			header('HTTP/1.1 404 Not Found');
			echo('<html><body><h1>HTTP 404 - Not Found (2)</h1></body></html>');
			exit;
		}

		// extensions uri
		$this->document_realroot = realpath($this->document_root);
		$uris = $this->src_uri;
		$this->src_uri = array();
		$folder = '';
		$list = '';
		$md5 = false;
		foreach ($uris as $uri) {

			// check if exists
			$uri = $this->document_realroot . '/' . preg_replace('/\.' . $this->mime_ext . '$/si', '', $uri) . '.' . $this->mime_ext;
			if (!file_exists($uri)) {
				if (!$this->exit) {
					return false;
				}
				header('HTTP/1.1 404 Not Found');
				echo('<html><body><h1>HTTP 404 - Not Found (3)</h1></body></html>');
				exit;
			}
			$this->src_uri[] = $uri;
			$list .= $uri . "\n";

			// the file is not in the web site folders
			$real_uri = realpath($uri);
			//echo "$real_uri<br />$real_root";
			if (substr($real_uri, 0, strlen($this->document_realroot)) !== $this->document_realroot) {
				if (!$this->exit) {
					return false;
				}
				header('HTTP/1.1 403 Forbidden');
				echo('<html><body><h1>HTTP 403 - Forbidden</h1></body></html>');
				exit;
			}
		}
		//die(print_r($this->src_uri, true));
		// gz_uri
		$this->file_uri = $this->gz_folder . $this->mime_ext . '.' . md5($list);
		$this->gz_uri = $this->file_uri . '.gz';

		return true;
	}

}