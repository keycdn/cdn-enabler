<?php

/**
* CDN_Enabler_Rewriter
*
* @since 0.0.1
*/

class CDN_Enabler_Rewriter
{
	var $blog_url = null; // origin URL
	var $cdn_url = null; // CDN URL

	var $dirs = null; // included directories
	var $excludes = array(); // excludes
	var $relative = false; // use CDN on relative paths
	var $https = false; // use CDN on HTTPS

    /**
	* constructor
	*
	* @since   0.0.1
	* @change  1.0.3
	*/

	function __construct($blog_url, $cdn_url, $dirs, array $excludes, $relative, $https) {
		$this->blog_url = $blog_url;
		$this->cdn_url = $cdn_url;
		$this->dirs	= $dirs;
		$this->excludes = $excludes;
		$this->relative	= $relative;
		$this->https = $https;
	}


    /**
    * exclude assets that should not be rewritten
    *
    * @since   0.0.1
    * @change  1.0.3
    *
    * @param   string  $asset  current asset
    * @return  boolean  true if need to be excluded
    */

	protected function exclude_asset(&$asset) {
		// excludes
		foreach ($this->excludes as $exclude) {
			if (!!$exclude && stristr($asset, $exclude) != false) {
				return true;
			}
		}
		return false;
	}


    /**
    * rewrite url
    *
    * @since   0.0.1
    * @change  0.0.1
    *
    * @param   string  $asset  current asset
    * @return  string  updated url if not excluded
    */

    protected function rewrite_url($asset) {
		if ($this->exclude_asset($asset[0])) {
			return $asset[0];
		}
		$blog_url = $this->blog_url;

        // check if not a relative path
		if (!$this->relative || strstr($asset[0], $blog_url)) {
			return str_replace($blog_url, $this->cdn_url, $asset[0]);
		}

		return $this->cdn_url . $asset[0];
	}


    /**
    * get directory scope
    *
    * @since   0.0.1
    * @change  0.0.1
    *
    * @return  string  directory scope
    */

	protected function get_dir_scope() {
		$input = explode(',', $this->dirs);

        // default
		if ($this->dirs == '' || count($input) < 1) {
			return 'wp\-content|wp\-includes';
		}

		return implode('|', array_map('quotemeta', array_map('trim', $input)));
	}


    /**
    * rewrite url
    *
    * @since   0.0.1
    * @change  1.0.1
    *
    * @param   string  $html  current raw HTML doc
    * @return  string  updated HTML doc with CDN links
    */

	public function rewrite($html) {
        // check if HTTPS and use CDN over HTTPS enabled
		if (!$this->https && isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') {
			return $html;
		}

        // get dir scope in regex format
		$dirs = $this->get_dir_scope();
        $blog_url = quotemeta($this->blog_url);

		// regex rule start
		$regex_rule = '#(?<=[(\"\'])';

        // check if relative paths
        if ($this->relative) {
            $regex_rule .= '(?:'.$blog_url.')?';
        } else {
			$regex_rule .= $blog_url;
		}

        // regex rule end
		$regex_rule .= '/(?:((?:'.$dirs.')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

        // call the cdn rewriter callback
		$cdn_html = preg_replace_callback($regex_rule, array(&$this, 'rewrite_url'), $html);

		return $cdn_html;
	}
}
