<?php

namespace Tests\E2E;

use Exception;

class Client
{
    public const METHOD_GET = 'GET';

    public const METHOD_POST = 'POST';

    public const METHOD_PUT = 'PUT';

    public const METHOD_PATCH = 'PATCH';

    public const METHOD_DELETE = 'DELETE';

    public const METHOD_HEAD = 'HEAD';

    public const METHOD_OPTIONS = 'OPTIONS';

    public const METHOD_CONNECT = 'CONNECT';

    public const METHOD_TRACE = 'TRACE';

    /**
     * Service host name
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * SDK constructor.
     */
    public function __construct(string $baseUrl = 'http://fpm')
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Call
     *
     * Make an API call
     *
     * @param  string  $method
     * @param  string  $path
     * @param  array  $params
     * @param  array  $headers
     * @return array|string
     *
     * @throws Exception
     */
    public function call(string $method, string $path = '', array $headers = [], array $params = [])
    {
        usleep(50000);
        $url = $this->baseUrl.$path.(($method == self::METHOD_GET && !empty($params)) ? '?'.http_build_query($params) : '');
        $ch = curl_init($this->baseUrl.$path.(($method == self::METHOD_GET && !empty($params)) ? '?'.http_build_query($params) : ''));
        $responseHeaders = [];
        $responseStatus = -1;
        $responseType = '';
        $responseBody = '';

        if ($method == self::METHOD_HEAD) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $cookies = [];

        $query = match ($headers['content-type'] ?? '') {
            'application/json' => \json_encode($params),
            'text/plain' => $params,
            default => \http_build_query($params),
        };

        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'accept-encoding') {
                curl_setopt($ch, CURLOPT_ENCODING, $value);
                continue;
            } else {
                $formattedHeaders[] = $key . ': ' . $value;
            }
        }

        curl_setopt($ch, CURLOPT_PATH_AS_IS, 1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders, &$cookies) {
            $len = strlen($header);
            $header = explode(':', $header, 2);

            if (count($header) < 2) { // ignore invalid headers
                return $len;
            }

            if (strtolower(trim($header[0])) == 'set-cookie') {
                $parsed = $this->parseCookie((string)trim($header[1]));
                $name = array_key_first($parsed);
                $cookies[$name] = $parsed[$name];
            }

            $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);

            return $len;
        });

        if ($method !== self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        $responseBody = curl_exec($ch);
        $responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ((curl_errno($ch)/* || 200 != $responseStatus*/)) {
            throw new Exception(curl_error($ch).' with status code '.$responseStatus, $responseStatus);
        }

        curl_close($ch);

        $responseHeaders['status-code'] = $responseStatus;

        if ($responseStatus === 500) {
            echo 'Server error('.$method.': '.$path.'. Params: '.json_encode($params).'): '.json_encode($responseBody)."\n";
        }

        return [
            'headers' => $responseHeaders,
            'body' => $responseBody,
            'cookies' => $cookies,
        ];
    }

    /**
     * Parse Cookie String
     *
     * @param string $cookie
     * @return array
     */
    public function parseCookie(string $cookie): array
    {
        $cookies = [];

        parse_str(strtr($cookie, ['&' => '%26', '+' => '%2B', ';' => '&']), $cookies);

        return $cookies;
    }
}
