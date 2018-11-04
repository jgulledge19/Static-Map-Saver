<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 11/3/18
 * Time: 11:37 AM
 */
namespace Joshua19\StaticMapSaver;

use function GuzzleHttp\Psr7\build_query;
use Joshua19\StaticMapSaver\AirTable\API;
use Joshua19\StaticMapSaver\Helpers\SimpleCache;
use Joshua19\StaticMapSaver\MapBox\API as MapBoxAPI;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

use GuzzleHttp\Exception\GuzzleException;

class MapSaver
{
    /** @var API */
    protected $airTable;

    /** @var bool  */
    protected $use_air_table_cache = false;

    /** @var MapBox\API */
    protected $mapBox;

    /** @var array  */
    protected $config = [];

    /** @var int  */
    protected $limit = 1000;

    public function __construct(string $env_dir)
    {
        try {
            /** @var Dotenv $dotenv */
            $dotenv = new Dotenv($env_dir);
            $dotenv->load();
            $dotenv->getEnvironmentVariableNames();
            $this->config = $_ENV;
            $this->use_air_table_cache = (bool) $this->config['AIRTABLE_API_CACHE_DATA'];

        } catch (InvalidPathException $e) {
            echo 'Invalid path to your .env file '.$e->getMessage().PHP_EOL;exit();
        }

        if (isset($this->config['DISPLAY_ERRORS']) && (bool)$this->config['DISPLAY_ERRORS']) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
    }

    /**
     * @param bool $use_air_table_cache
     * @return MapSaver
     */
    public function setUseAirTableCache(bool $use_air_table_cache): MapSaver
    {
        $this->use_air_table_cache = $use_air_table_cache;
        return $this;
    }

    /**
     * @return AirTable\API
     */
    public function getAirTable(): AirTable\API
    {
        if (empty($this->airTable)) {
            $this->airTable = new API($this->config['AIRTABLE_ORG_ID'], $this->config['AIRTABLE_API_KEY']);
            $this->airTable->setVerifySsl($this->config['VERIFY_SSL']);
        }

        return $this->airTable;
    }

    /**
     * @param string $table
     * @param int $offset
     * @param bool $get_all
     * @return bool|mixed|\Psr\Http\Message\StreamInterface|string
     * @throws GuzzleException
     */
    public function getAirTableList(string $table, $offset=0, $get_all=true)
    {
        // @TODO from cache:
        $limit = $this->limit;
        if ($limit > 100) {
            $limit = 100;
        }
        $cache_key = $this->getCacheKey('airTable', $table, ['l' => $limit, 'o'=>$offset]);

        $simpleCache = new SimpleCache();

        if ($this->use_air_table_cache) {
            $data = $simpleCache->get($cache_key);
            if (!$data) {
                // will need to get the data
            } else {
                return $data;
            }
        }

        $this->getAirTable();

        // Set the query params
        $this->airTable
            ->setQueryMaxRecords($limit)
            ->setQueryView($this->config['AIRTABLE_VIEW']) // ??
            ->setQueryCellFormat()
            ->setQueryPageSize()
            ->setOffset($offset);

        try {
            /**
             * @param int $channel_id
             * @return bool|\GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
             * @throws \GuzzleHttp\Exception\GuzzleException
             */

            $airTableResponse = $this->airTable->doRequest('GET', $table);

            $data = false;
            if (is_object($airTableResponse)) {
                if ($airTableResponse->getStatusCode() != 200) {
                    echo $airTableResponse->getReasonPhrase() . PHP_EOL;
                    echo $airTableResponse->getBody() . PHP_EOL;
                }

                $data = json_decode($airTableResponse->getBody(), true);
            }

            $simpleCache->set($cache_key, $data);

        } catch (GuzzleException $exception) {
            echo $exception->getMessage() . PHP_EOL;
            //exit();
        }

        if (isset($data['offset']) && $data['offset'] < $this->limit && $get_all) {
            $data2 = $this->getAirTableList($table, $data['offset'], $get_all);
            $data = array_merge($data, $data2);
        }

        return $data;
    }

    /**
     * @return MapBoxAPI
     */
    public function getMapBox(): MapBoxAPI
    {
        if (empty($this->mapBox)) {
            $this->mapBox = new MapBoxAPI($this->config['MAPBOX_USERNAME'], $this->config['MAPBOX_ACCESS_TOKEN']);
            $this->mapBox->setVerifySsl($this->config['VERIFY_SSL']);
        }

        return $this->mapBox;
    }

    /**
     * @param $place
     * @param $lat
     * @param $long
     * @param string $size
     * @return string
     */
    public function getStaticMapImages($place, $lat, $long, $size='wide')
    {
        $this->getMapBox();

        $this->mapBox
            ->setLat($lat)
            ->setLon($long)
            ->setStyleId($this->config['MAPBOX_STYLE_ID'])
            ->setHeight($this->config['MAPBOX_HEIGHT'])
            ->setWidth($this->config['MAPBOX_WIDTH'])
            ->setHighDensity((bool)$this->config['MAPBOX_@2X']);

        if ($size == 'wide') {
            $this->mapBox->setZoom($this->config['MAPBOX_ZOOM_WIDE']);
        } else {
            $this->mapBox->setZoom($this->config['MAPBOX_ZOOM_DETAIL']);
        }

        // echo 'URL: '.$this->mapBox->buildImageURL() . PHP_EOL;

        $local_path = $this->config['MAP_SAVER_IMAGE_DIR'] . $place . '-'.$size . '.png';

        // echo 'Local: '.$local_path . PHP_EOL;

        $this->mapBox->savePNG($local_path);

        return $local_path;
    }

    /**
     * @param string $table
     * @param int $offset
     * @return bool|mixed|\Psr\Http\Message\StreamInterface|string
     * @throws GuzzleException
     */
    public function saveStaticMapImages(string $table='Places', $offset=0)
    {
        $air_table_data = $this->getAirTableList($table, $offset);

        foreach ($air_table_data['records'] as $record) {
            echo 'ID: ' .$record['id'];

            if (isset($record['fields'])) {
                $fields = $record['fields'];
                echo ' '.$fields['Place Lookup'];

                $latitude = '';
                $longitude = '';
                if (isset($fields['Lat'])) {
                    $latitude = $fields['Lat'];
                }

                if (isset($fields['Lon'])) {
                    $longitude = $fields['Lon'];
                }

                // VERIFIED Map info:
                if (isset($fields['Recogito Status']) && strtoupper($fields['Recogito Status']) == 'VERIFIED') {
                    if (isset($fields['Recogito Lat'])) {
                        $latitude = $fields['Recogito Lat'];
                    }
                    if (isset($fields['Recogito Lon'])) {
                        $longitude = $fields['Recogito Lon'];
                    }
                }

                if (!empty($latitude) && !empty($longitude)) {
                    // files:
                    echo PHP_EOL . 'New image: ' . $this->getStaticMapImages($fields['Place Lookup'], $latitude, $longitude, 'wide');
                    echo PHP_EOL . 'New image: ' . $this->getStaticMapImages($fields['Place Lookup'], $latitude, $longitude, 'detail');

                } else {
                    echo ' FAILED to provide valid latitude and longitude';
                }
            } else {
                echo ' FAILED fields not found';
            }

            echo PHP_EOL;
        }

        if (isset($air_table_data['offset'])) {

        }
        if (isset($air_table_data['offset']) && $air_table_data['offset'] < $this->limit) {
            $data2 = $this->saveStaticMapImages($table, $air_table_data['offset']);
        }
    }

    /**
     * @param int $limit
     * @return MapSaver
     */
    public function setLimit(int $limit): MapSaver
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param string $type
     * @param string $name
     * @param array $options
     * @return string
     */
    protected function getCacheKey(string $type, string $name, $options=[])
    {
        return $this->cleanString($type).'_'.$this->cleanString($name) . '_query_'. $this->cleanString(build_query($options));
    }

    /**
     * @param string $string
     * @return null|string|string[]
     */
    protected function cleanString(string $string)
    {
        return preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(['/', ' '], '_', $string));
    }
}