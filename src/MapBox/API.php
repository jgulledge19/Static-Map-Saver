<?php

namespace Joshua19\StaticMapSaver\MapBox;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;

// /styles/v1/{username}/{style_id}/static/{overlay}/{lon},{lat},{zoom},{bearing},{pitch}|{auto}/{width}x{height}{@2x}

# Retrieve a map at -122.4241 longitude, 37.78 latitude,
# zoom 14.24, bearing 0, and pitch 60. The map
# will be 600 pixels wide and 600 pixels high
// curl "https://api.mapbox.com/styles/v1/mapbox/streets-v10/static/-122.4241,37.78,14.25,0,60/600x600?access_token=your-access-token"
class API
{
    /** @var string  */
    protected $base_uri = 'https://api.mapbox.com/styles/v1/';

    /** @var \GuzzleHttp\Client  */
    protected $client;

    /** @var array  */
    protected $client_headers = [];

    /** @var array  */
    protected $request_data = [];

    /** @var string The username of the account to which the style belongs. */
    protected $username;

    /** @var  string private API key */
    protected $access_token;

    /** @var string  The ID of the style from which to create a static map. */
    protected $style_id = '';

    /**
     * @var string One or more comma-separated features that can be applied on top of the map at request time.
     * The order of features in an overlay dictates their Z-order on the page. The last item in the list will have the
     * highest Z-order (will overlap the other features in the list), and the first item in the list will have the
     * lowest (will underlap the other features). Format can be a mix of geojson , marker , or path .
     * For more details on each option, see the Overlay options section .
     */
    protected $overlay = '';

    /** @var int Longitude for the center point of the static map; a number between -180 and 180 . */
    protected $lon = 100;

    /** @var int Latitude for the center point of the static map; a number between -90 and 90 . */
    protected $lat = 45;

    /** @var int Zoom level; a number between 0 and 20 . Fractional zoom levels will be rounded to two decimal places. */
    protected  $zoom = 1;

    /**
     * @var int
     * (optional) 	Bearing rotates the map around its center. A number between 0 and 360 , interpreted as decimal degrees.
     * 90 rotates the map 90° clockwise, while 180 flips the map. Defaults to 0 .
     */
    protected $bearing = 0;

    /**
     * @var int (optional) Pitch tilts the map, producing a perspective effect. A number between 0 and 60 , measured in degrees.
     * Defaults to 0 (looking straight down at the map).
     */
    protected $pitch = 0;

    /** @var string The viewport will fit the bounds of the overlay. If used, auto replaces lon , lat , zoom , bearing , and pitch . */
    protected $auto = '';

    /** @var int Width of the image; a number between 1 and 1280 pixels.  */
    protected $width = 600;

    /** @var int Height of the image; a number between 1 and 1280 pixels. */
    protected $height = 600;

    /** @var bool (optional) Render the static map at a @2x scale factor for high-density displays. */
    protected $high_density = false;


    /**
     * @var bool $verify_ssl ~ will load Client
     */
    protected $verify_ssl = true;

    /**
     * API constructor.
     *
     * @param string $username
     * @param string $access_token
     * @param string $base_uri ~ default: https://api.mapbox.com/styles/v1/
     */
    public function __construct($username, $access_token, $base_uri='https://api.mapbox.com/styles/v1/')
    {
        $this->username = $username;
        $this->access_token = $access_token;
        $this->base_uri = $base_uri;
        //$this->client_headers = ['Authorization' => 'Bearer '.$this->access_token];
    }

    /**
     * @param string $style_id
     * @return API
     */
    public function setStyleId(string $style_id): API
    {
        $this->style_id = $style_id;
        return $this;
    }

    /**
     * @param string $overlay
     * @return API
     */
    public function setOverlay(string $overlay): API
    {
        $this->overlay = $overlay;
        return $this;
    }

    /**
     * @param float $lon
     * @return API
     */
    public function setLon(float $lon): API
    {
        $this->lon = $lon;
        return $this;
    }

    /**
     * @param float $lat
     * @return API
     */
    public function setLat(float $lat): API
    {
        $this->lat = $lat;
        return $this;
    }

    /**
     * @param float $zoom
     * @return API
     */
    public function setZoom(float $zoom): API
    {
        $this->zoom = $zoom;
        return $this;
    }

    /**
     * @param int $bearing
     * @return API
     */
    public function setBearing(int $bearing): API
    {
        $this->bearing = $bearing;
        return $this;
    }

    /**
     * @param int $pitch
     * @return API
     */
    public function setPitch(int $pitch): API
    {
        $this->pitch = $pitch;
        return $this;
    }

    /**
     * @param string $auto
     * @return API
     */
    public function setAuto(string $auto): API
    {
        $this->auto = $auto;
        return $this;
    }

    /**
     * @param int $width
     * @return API
     */
    public function setWidth(int $width): API
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @param int $height
     * @return API
     */
    public function setHeight(int $height): API
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @param bool $high_density
     * @return API
     */
    public function setHighDensity(bool $high_density): API
    {
        $this->high_density = $high_density;
        return $this;
    }

    /**
     * @param string $file
     * @return bool|mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function savePNG($file)
    {
        $options = [
            'query' => [
                'access_token' => $this->access_token
            ],
            'sink' => $file
        ];

        $path = $this->buildImageURL();

        $this->request_data = [
            'method' => 'GET',
            'path' => $path,
            'options' => $options
        ];

        $response = false;
        try {
            if (!is_object($this->client) || !$this->client instanceof Client) {
                $this->loadClient();
            }

            $mapBoxResponse = $this->client->request('GET', $path, ['sink' => $file]);

        } catch (RequestException $exception) {
            // @TODO log error
            echo Psr7\str($exception->getRequest());
            if ($exception->hasResponse()) {
                echo Psr7\str($exception->getResponse());
            }
        }

        return $mapBoxResponse;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return $this->request_data;
    }


    /**
     * @param bool $verify_ssl
     * @return API
     */
    public function setVerifySsl(bool $verify_ssl): API
    {
        $this->verify_ssl = $verify_ssl;
        return $this;
    }

    public function buildImageURL()
    {
        // /{username}/{style_id}/static/{overlay}/{lon},{lat},{zoom},{bearing},{pitch}|{auto}/{width}x{height}{@2x}

        $path = $this->base_uri . $this->username . '/' . $this->style_id . '/static/';
        if (!empty($this->overlay)) {
            $path .= $this->overlay . '/';
        }

        if (!empty($this->auto)) {
            $path .= $this->auto . '/';
        } else {
            $path .= $this->lon . ',' . $this->lat . ',' . $this->zoom . ',' .
                $this->bearing . ',' . // Optional?
                $this->pitch . '/';
        }

        $path .= $this->width . 'x' . $this->height;

        if ($this->high_density) {
            $path .= '@2x';
        }

        $path .= '?access_token=' . $this->access_token;

        return $path;
    }

    /**
     *
     */
    protected function loadClient()
    {
        $this->client = new Client([
            'base_uri' => $this->base_uri, // Base URI is used with relative requests
            'timeout' => 120.0, // You can set any number of default request options.
            'http_errors' => false, // http://docs.guzzlephp.org/en/latest/request-options.html#http-errors
            'verify' => $this->verify_ssl, // local windows machines sometimes give issues here
            'headers' => $this->client_headers, // http://docs.guzzlephp.org/en/latest/request-options.html#headers
            'version' => 1.0 // http://docs.guzzlephp.org/en/latest/request-options.html#version
        ]);
    }
}