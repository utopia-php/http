<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/eldadfux/Utopia-PHP-Framework
 * @author Eldad Fux <eldad@fuxie.co.il>
 * @version 2.0
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia;

use Exception;

class View
{
    /**
     * @var self
     */
    protected $parent = null;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var bool
     */
    protected $rendered = false;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * Constructor
     *
     * You can optionally initialize the View object with a template path, although this can also be set later using the $this->setPath($path) method
     *
     * @param string $path
     * @throws Exception
     */
    public function __construct($path = '')
    {
        $this->setPath($path);
    }

    /**
     * Set param
     *
     * Assign a parameter by key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     * @throws Exception
     */
    public function setParam($key, $value)
    {
        if(strpos($key, '.') !== false) {
            throw new Exception('$key can\'t contain a dot "." character');
        }

        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Set parent View object conatining this object
     *
     * @param View $view
     * @return View
     */
    public function setParent(self $view)
    {
        $this->parent = $view;
        return $this;
    }

    /**
     * Return a View instance of the parent view containing this view
     *
     * @return View|null
     */
    public function getParent()
    {
        if(!empty($this->parent)) {
            return $this->parent;
        }

        return null;
    }

    /**
     * Get param
     *
     * Returns an assigned parameter by its key or $default if param key doesn't exists
     *
     * @param string $path
     * @param mixed $default (optional)
     * @return mixed
     */
    public function getParam($path, $default = null)
    {
        $path   = explode('.', $path);
        $temp   = $this->params;

        foreach ($path as $key) {
            $temp = (isset($temp[$key])) ? $temp[$key] : null;

            if (null !== $temp) {
                $value = $temp;
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set path
     *
     * Set object template path that will be used to render view output
     *
     * @param  string    $path
     * @throws Exception
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set rendered
     *
     * By enabling rendered state to true, the object will not render its template and will return an empty string instead
     *
     * @param bool $state
     * @return $this
     */
    public function setRendered($state = true)
    {
        $this->rendered = $state;

        return $this;
    }

    /**
     * Is rendered
     *
     * Return whether current View rendering state is set to true or false
     *
     * @return bool
     */
    public function isRendered()
    {
        return (bool) $this->rendered;
    }

    /**
     * Render
     *
     * Render view .phtml template file if template has not been set as rendered yet using $this->setRendered(true).
     * In case path is not readable throws Exception.
     *
     * @return string
     * @throws Exception
     */
    public function render()
    {
        if ($this->rendered) { // Don't render any template

            return '';
        }

        ob_start(); //Start of build

        if (is_readable($this->path)) {
            include $this->path; // Include template file
        } else {
            ob_end_clean();
            throw new Exception('"' . $this->path . '" view template is not readable');
        }

        $html = ob_get_contents();

        ob_end_clean(); //End of build

        // Searching textarea and pre
        preg_match_all('#\<textarea.*\>.*\<\/textarea\>#Uis', $html, $foundTxt);
        preg_match_all('#\<pre.*\>.*\<\/pre\>#Uis', $html, $foundPre);

        // replacing both with <textarea>$index</textarea> / <pre>$index</pre>
        $html = str_replace($foundTxt[0], array_map(function($el){ return '<textarea>'.$el.'</textarea>'; }, array_keys($foundTxt[0])), $html);
        $html = str_replace($foundPre[0], array_map(function($el){ return '<pre>'.$el.'</pre>'; }, array_keys($foundPre[0])), $html);

        // your stuff
        $search = array(
            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
            '/(\s)+/s'       // shorten multiple whitespace sequences
        );

        $replace = array(
            '>',
            '<',
            '\\1'
        );

        $html = preg_replace($search, $replace, $html);

        // Replacing back with content
        $html = str_replace(array_map(function($el){ return '<textarea>'.$el.'</textarea>'; }, array_keys($foundTxt[0])), $foundTxt[0], $html);
        $html = str_replace(array_map(function($el){ return '<pre>'.$el.'</pre>'; }, array_keys($foundPre[0])), $foundPre[0], $html);


        return $html;
    }

    /* View Helpers */

    /**
     * Exec
     *
     * Exec child View components
     *
     * @param array|self $view
     * @return string
     * @throws Exception
     */
    public function exec($view)
    {
        $output = '';

        if(is_array($view)) {
            foreach($view as $node) { /* @var $node self */
                if($node instanceof self) {
                    $node->setParent($this);
                    $output .= $node->render();
                }
            }
        }
        else if ($view instanceof self) {
            $view->setParent($this);
            $output = $view->render();
        }

        return $output;
    }

    /**
     * Escape
     *
     * Convert all applicable characters to HTML entities
     *
     * @param  string $str
     * @return string
     */
    public function escape($str)
    {
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * nl2p
     *
     * Convert new line breaks text to HTML paragraphs
     *
     * @note This function will remove any single line-breaks.
     * @see http://stackoverflow.com/a/14467470
     *
     * @param string $string
     * @return string
     */
    public function nl2p($string)
    {
        $paragraphs = '';

        foreach (explode("\n\n", $string) as $line) {
            if (trim($line)) {
                $paragraphs .= '<p>' . $line . '</p>';
            }
        }

        $paragraphs = str_replace("\n", '<br />', $paragraphs);

        return $paragraphs;
    }
}