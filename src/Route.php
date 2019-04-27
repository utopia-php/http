<?php
/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Core
 *
 * @link https://github.com/utopia-php/framework
 * @author Eldad Fux <eldad@appwrite.io>
 * @version 2.0
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia;

class Route
{
    /**
     * HTTP Method
     *
     * @var string
     */
    protected $method = '';

    /**
     * URL
     *
     * @var string
     */
    protected $URL = '';

    /**
     * Description
     *
     * @var string
     */
    protected $desc = '';

    /**
     * Action Callback
     *
     * @var null|callback
     */
    protected $action = null;

    /**
     * @var int
     */
    public static $counter = 0;

    /**
     * Parameters
     *
     * List of route params names and validators
     *
     * @var array
     */
    protected $params = array();

    /**
     * Labels
     *
     * List of route label names
     *
     * @var array
     */
    protected $labels = array();

    /**
     * @var int
     */
    protected $order = null;

    /**
     * @param $method
     * @param $URL
     */
    public function __construct($method, $URL)
    {
        self::$counter++;

        $this->URL($URL);
        $this->method = $method;
        $this->order = self::$counter;
    }

    /**
     * Add URL
     *
     * @param string $URL
     * @return $this
     */
    public function URL($URL)
    {
        $this->URL = $URL;
        return $this;
    }

    /**
     * Add Description
     *
     * @param string $desc
     * @return $this
     */
    public function desc($desc)
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * Add Action
     *
     * @param $action
     * @return $this
     */
    public function action($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Add Param
     *
     * @param string $key
     * @param null $default
     * @param string $validator
     * @param string $description
     * @param bool $optional
     *
     * @return $this
     */
    public function param($key, $default, $validator, $description = '', $optional = false)
    {
        $this->params[$key] = array(
            'default'       => $default,
            'validator'     => $validator,
            'description'   => $description,
            'optional'      => $optional,
            'value'         => null,
        );

        return $this;
    }

    /**
     * Add Label
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function label($key, $value)
    {
        $this->labels[$key] = $value;

        return $this;
    }

    /**
     * Get HTTP Method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get URL
     *
     * @return string
     */
    public function getURL()
    {
        return $this->URL;
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * Get Action
     *
     * @return callable|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get Param Values
     *
     * @return array
     */
    public function getParamsValues()
    {
        $values = [];

        foreach ($this->params as $key => $param) {
            $values[$key] = $param['value'];
        }

        return $values;
    }

    /**
     * Set Param Value
     *
     * @param $key
     * @param $value
     * @return mixed
     * @throws Exception
     */
    public function setParamValue($key, $value)
    {
        if(!isset($this->params[$key])) {
            throw new Exception('Unknown key');
        }

        $this->params[$key]['value'] = $value;

        return $this;
    }

    /**
     * Get Param Value
     *
     * @param $key
     * @return mixed
     * @throws Exception
     */
    public function getParamValue($key)
    {
        if(!isset($this->params[$key])) {
            throw new Exception('Unknown key');
        }

        return $this->params[$key]['value'];
    }

    /**
     * Get Label
     *
     * Return given label value or default value if label doesn't exists
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getLabel($key, $default)
    {
        return (isset($this->labels[$key])) ? $this->labels[$key] : $default;
    }

    /**
     * Get Route Order ID
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }
}